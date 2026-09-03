#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
ingest_kb.py — RAG ingestion for the `kb_search` tool ("عمر" WhatsApp agent).

WHAT IT DOES
------------
1. Reads the knowledge base (knowledge-base.md) and, optionally, the deep
   336-lesson curriculum (_generated-curriculum.md).
2. Splits each file into chunks by markdown heading, keeping the FULL
   parent-heading trail with every chunk (so a lone "و2 — Linked List" still
   carries "هياكل البيانات م١" context). It also understands the curriculum's
   "**الوحدة: ...**" bold-line convention as a sub-heading.
3. Computes an embedding per chunk via a provider chosen by env var
   (default: Gemini text-embedding-004). No keys are hardcoded — all secrets
   come from the environment.
4. Upserts chunks idempotently into the pgvector table omar.kb_chunks
   (keyed on (source, content_hash)). Unchanged chunks are skipped; removed
   chunks are pruned. Re-running after a content edit is safe and cheap.

THIS SCRIPT IS AUTHORED, NOT RUN. It does not touch prod on import. Running it
requires explicit approval (see omar-build-plan.md §Deploy and rag/README.md).

USAGE
-----
    python ingest_kb.py                 # full ingest (needs DB + embedding env)
    python ingest_kb.py --dry-run       # parse + chunk + report; NO db, NO embeds
    python ingest_kb.py --with-curriculum   # also ingest _generated-curriculum.md
    python ingest_kb.py --prune-only    # only delete rows whose chunks no longer exist
    python ingest_kb.py --verbose

ENV (read from environment / .env — NEVER hardcode):
    # --- Database ---
    OMAR_DB_DSN            postgres DSN, e.g. postgresql://omar_agent:<pw>@127.0.0.1:5432/omar_agent
    # --- Embedding provider (swappable) ---
    EMBED_PROVIDER         'gemini' (default) | 'openai' | 'fake' (dry-run/testing)
    EMBED_MODEL            default 'text-embedding-004' (Gemini) / 'text-embedding-3-small' (OpenAI)
    EMBED_DIM              default 768 — MUST match schema-kb.sql vector(N)
    GEMINI_API_KEY         required when EMBED_PROVIDER=gemini (NOT logged)
    OPENAI_API_KEY         required when EMBED_PROVIDER=openai (NOT logged)
    # --- Source files (optional overrides; sensible defaults below) ---
    KB_FILE                path to knowledge-base.md
    CURRICULUM_FILE        path to _generated-curriculum.md

DEPENDENCIES (install in the ingestion venv, not on prod blindly):
    psycopg[binary]        # Postgres driver (psycopg 3)
    google-generativeai    # only if EMBED_PROVIDER=gemini
    openai                 # only if EMBED_PROVIDER=openai
"""

from __future__ import annotations

import argparse
import hashlib
import os
import re
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Callable, Optional

# Arabic + emoji in the report must print on any console (Windows cp1252 included).
for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8")  # type: ignore[attr-defined]
    except Exception:
        pass

# ---------------------------------------------------------------------------
# Paths — resolved relative to this file so the script is location-independent.
#   rag/ -> agents/ -> 02_AUTOMATION/ -> learn-simply/  (then into 03_KNOWLEDGE)
# ---------------------------------------------------------------------------
HERE = Path(__file__).resolve().parent                       # .../02_AUTOMATION/agents/rag
BRAND_ROOT = HERE.parent.parent.parent                       # .../brands/learn-simply
DEFAULT_KB_FILE = BRAND_ROOT / "03_KNOWLEDGE" / "knowledge-base.md"
DEFAULT_CURRICULUM_FILE = (
    BRAND_ROOT / "03_KNOWLEDGE" / "data" / "website-extract" / "_generated-curriculum.md"
)

# Embedding defaults — keep EMBED_DIM in lockstep with schema-kb.sql vector(N).
DEFAULT_EMBED_DIM = 768
DEFAULT_GEMINI_MODEL = "gemini-embedding-001"  # text-embedding-004 retired by Google (2026); 768-dim via output_dimensionality
DEFAULT_OPENAI_MODEL = "text-embedding-3-small"

# Chunking guardrails. The KB is already chunk-friendly (one section/course/unit
# per heading), so these are safety bounds, not aggressive resizers.
MAX_CHUNK_CHARS = 4000      # split a huge heading body into ~MAX_CHUNK_CHARS pieces
MIN_CHUNK_CHARS = 1         # keep even tiny chunks (a heading + one fact is valid)
EMBED_BATCH_SLEEP = 0.0     # seconds between embed calls (raise if you hit rate limits)


# ===========================================================================
# Chunk model
# ===========================================================================
@dataclass
class Chunk:
    source: str
    heading_path: str          # 'A > B > C' parent trail (deepest last)
    content: str               # heading line + its prose (the embeddable body)
    chunk_index: int
    token_count: int = 0
    content_hash: str = ""

    def finalize(self) -> "Chunk":
        self.token_count = approx_token_count(self.content)
        self.content_hash = sha256_of(self.source, self.heading_path, self.content)
        return self


def sha256_of(*parts: str) -> str:
    h = hashlib.sha256()
    h.update("\x1f".join(parts).encode("utf-8"))   # unit-separator joins parts unambiguously
    return h.hexdigest()


def approx_token_count(text: str) -> int:
    """Cheap, dependency-free token estimate.

    Mixed Arabic/English: ~3.5 chars/token is a reasonable middle estimate for
    budgeting LLM context. This is intentionally an APPROXIMATION used only for
    sizing — not for billing.
    """
    return max(1, round(len(text) / 3.5))


# ===========================================================================
# Markdown chunker — heading-aware, parent-context-preserving
# ===========================================================================
ATX_HEADING_RE = re.compile(r"^(#{1,6})\s+(.*\S)\s*#*\s*$")
# Curriculum convention: a whole line that is just "**الوحدة: ...**" (or "**Unit ...**")
# acts as a sub-heading even though it is not a markdown '#' heading.
BOLD_UNIT_LINE_RE = re.compile(r"^\*\*\s*(?:الوحدة|Unit|الوحده)\s*[:：].*\*\*\s*$")


def _clean_heading(text: str) -> str:
    # Strip surrounding ** bold markers and collapse whitespace for the trail.
    text = text.strip()
    if text.startswith("**") and text.endswith("**"):
        text = text[2:-2].strip()
    return re.sub(r"\s+", " ", text)


def chunk_markdown(text: str, source: str) -> list[Chunk]:
    """Split markdown into one chunk per heading, parent trail preserved.

    A "heading" is either an ATX heading (``# .. ######``) OR a standalone
    ``**الوحدة: ...**`` bold line (curriculum unit convention). Each chunk's
    ``content`` is the heading line plus all body lines until the next heading
    at the same-or-shallower level. ``heading_path`` is the trail of all
    ancestor headings joined by " > " so context is never lost.

    Preamble before the first heading (e.g. the KB's top blockquote) is emitted
    as a single chunk under a synthetic "(preamble)" path so nothing is dropped.
    """
    lines = text.splitlines()
    chunks: list[Chunk] = []
    idx = 0

    # heading stack: list of (level, is_atx, title). A bold-unit line nests under
    # the nearest ATX heading (NOT under a previous bold unit), so all units in
    # a course are SIBLINGS at the same level — never a runaway chain.
    stack: list[tuple[int, bool, str]] = []
    cur_heading_line: Optional[str] = None
    cur_body: list[str] = []
    cur_level: Optional[int] = None

    def heading_path_for(level: int, title: str) -> str:
        # ancestors strictly shallower than `level`, then this title
        trail = [t for (lvl, _atx, t) in stack if lvl < level]
        trail.append(title)
        return " > ".join(trail)

    def flush(heading_line: Optional[str], level: Optional[int]):
        nonlocal idx
        if heading_line is None and not cur_body:
            return
        if heading_line is None:
            # preamble before any heading
            body = "\n".join(cur_body).strip()
            if len(body) >= MIN_CHUNK_CHARS:
                for piece in _split_oversized(body):
                    chunks.append(
                        Chunk(source, "(preamble)", piece, idx).finalize()
                    )
                    idx += 1
            return
        title = _clean_heading(_heading_title(heading_line))
        path = heading_path_for(level or 1, title)
        body_text = "\n".join(cur_body).strip()
        # Embed the heading WITH its body so the vector carries the topic label.
        full = f"{title}\n{body_text}".strip()
        # Tables → also as sentences (see flatten_md_tables docstring).
        tbl = flatten_md_tables(body_text)
        if tbl:
            full += "\n\nملخص نصي للجدول (نفس البيانات كجُمل):\n- " + "\n- ".join(tbl)
        for piece_no, piece in enumerate(_split_oversized(full)):
            # On a split, re-prepend the heading so every piece stays self-describing.
            piece_content = piece if piece_no == 0 else f"{title} (تابع)\n{piece}"
            if len(piece_content.strip()) >= MIN_CHUNK_CHARS:
                chunks.append(Chunk(source, path, piece_content, idx).finalize())
                idx += 1

    def push_heading(level: int, is_atx: bool, title: str):
        # pop siblings/deeper, then push
        while stack and stack[-1][0] >= level:
            stack.pop()
        stack.append((level, is_atx, title))

    def nearest_atx_level() -> int:
        for lvl, is_atx, _t in reversed(stack):
            if is_atx:
                return lvl
        return 2  # default course depth if no ATX ancestor yet

    for raw in lines:
        atx = ATX_HEADING_RE.match(raw)
        is_bold_unit = bool(BOLD_UNIT_LINE_RE.match(raw.strip()))

        if atx or is_bold_unit:
            # close the chunk we were building
            flush(cur_heading_line, cur_level)
            cur_body = []
            if atx:
                level = len(atx.group(1))
                title = _clean_heading(atx.group(2))
                push_heading(level, True, title)
            else:
                # A bold unit nests one level deeper than the nearest ATX heading.
                # Using the ATX depth (not the previous unit's depth) keeps all
                # units in a course as siblings rather than a deepening chain.
                level = nearest_atx_level() + 1
                title = _clean_heading(raw.strip())
                push_heading(level, False, title)
            cur_heading_line = raw
            cur_level = level
        else:
            cur_body.append(raw)

    # final flush
    flush(cur_heading_line, cur_level)
    return chunks


def _heading_title(heading_line: str) -> str:
    m = ATX_HEADING_RE.match(heading_line)
    if m:
        return m.group(2)
    return heading_line.strip()


# ---------------------------------------------------------------------------
# Table flattening — markdown tables embed poorly (proven: bundle chunk ranked
# sim ~0.6, outside top-5, for "باقة جافا"). For every table in a chunk body we
# append a sentence per row ("header: cell؛ header: cell.") so both the vector
# AND the lexical (pg_trgm) side of kb_search_hybrid can match row facts.
# ---------------------------------------------------------------------------
TABLE_SEP_RE = re.compile(r"^\s*\|(\s*:?-{2,}:?\s*\|)+\s*$")


def _table_cell(c: str) -> str:
    return re.sub(r"\*\*", "", c).strip()


def flatten_md_tables(text: str) -> list[str]:
    """Return one descriptive sentence per data row of every markdown table."""
    lines = text.splitlines()
    out: list[str] = []
    i = 0
    while i < len(lines):
        if "|" in lines[i] and i + 1 < len(lines) and TABLE_SEP_RE.match(lines[i + 1]):
            header = [_table_cell(c) for c in lines[i].strip().strip("|").split("|")]
            i += 2
            while i < len(lines) and "|" in lines[i] and lines[i].strip():
                cells = [_table_cell(c) for c in lines[i].strip().strip("|").split("|")]
                pairs = [
                    (f"{h}: {c}" if h else c)
                    for h, c in zip(header, cells)
                    if c and c not in {"—", "-"}
                ]
                if pairs:
                    out.append("؛ ".join(pairs) + ".")
                i += 1
        else:
            i += 1
    return out


def _split_oversized(text: str) -> list[str]:
    """Split a too-long body on paragraph/list boundaries, never mid-line."""
    if len(text) <= MAX_CHUNK_CHARS:
        return [text] if text.strip() else []
    pieces: list[str] = []
    buf: list[str] = []
    size = 0
    for line in text.splitlines(keepends=True):
        if size + len(line) > MAX_CHUNK_CHARS and buf:
            pieces.append("".join(buf).strip())
            buf, size = [], 0
        buf.append(line)
        size += len(line)
    if buf:
        pieces.append("".join(buf).strip())
    return [p for p in pieces if p.strip()]


# ===========================================================================
# Embeddings — provider behind an env var, swappable, no hardcoded keys
# ===========================================================================
def get_embedder(provider: str, model: str, dim: int, verbose: bool) -> Callable[[str], list[float]]:
    """Return embed(text) -> list[float]. Provider chosen by env. Keys from env."""
    provider = (provider or "gemini").lower()

    if provider == "fake":
        # Deterministic pseudo-embedding for --dry-run / offline tests. NOT for prod.
        def embed_fake(text: str) -> list[float]:
            seed = int(hashlib.sha256(text.encode("utf-8")).hexdigest(), 16)
            vals = []
            for i in range(dim):
                seed = (1103515245 * seed + 12345) & 0x7FFFFFFF
                vals.append((seed % 2000) / 1000.0 - 1.0)
            return vals
        return embed_fake

    if provider == "gemini":
        api_key = os.environ.get("GEMINI_API_KEY")
        if not api_key:
            raise SystemExit("ERROR: GEMINI_API_KEY not set (EMBED_PROVIDER=gemini).")
        try:
            import google.generativeai as genai  # type: ignore
        except ImportError as e:
            raise SystemExit("ERROR: pip install google-generativeai") from e
        genai.configure(api_key=api_key)
        model_id = model if model.startswith("models/") else f"models/{model}"

        def embed_gemini(text: str) -> list[float]:
            # gemini-embedding-001 defaults to 3072 dims; truncate to schema dim.
            # Cosine ops are scale-invariant, so the non-renormalized truncation is safe
            # as long as query-side (W3c Embed Query) uses the same model + dims.
            resp = genai.embed_content(
                model=model_id,
                content=text,
                task_type="retrieval_document",
                output_dimensionality=dim,
            )
            vec = resp["embedding"]
            if len(vec) != dim:
                raise SystemExit(
                    f"ERROR: embedding dim {len(vec)} != EMBED_DIM {dim}. "
                    f"Update EMBED_DIM and schema-kb.sql vector(N) to match."
                )
            return vec
        return embed_gemini

    if provider == "openai":
        api_key = os.environ.get("OPENAI_API_KEY")
        if not api_key:
            raise SystemExit("ERROR: OPENAI_API_KEY not set (EMBED_PROVIDER=openai).")
        try:
            from openai import OpenAI  # type: ignore
        except ImportError as e:
            raise SystemExit("ERROR: pip install openai") from e
        client = OpenAI(api_key=api_key)

        def embed_openai(text: str) -> list[float]:
            resp = client.embeddings.create(model=model, input=text, dimensions=dim)
            vec = resp.data[0].embedding
            if len(vec) != dim:
                raise SystemExit(
                    f"ERROR: embedding dim {len(vec)} != EMBED_DIM {dim}."
                )
            return vec
        return embed_openai

    raise SystemExit(f"ERROR: unknown EMBED_PROVIDER '{provider}' (use gemini|openai|fake).")


def vec_to_pg_literal(vec: list[float]) -> str:
    """pgvector text input format: '[0.1,0.2,...]'."""
    return "[" + ",".join(f"{x:.7f}" for x in vec) + "]"


# ===========================================================================
# Database upsert — idempotent on (source, content_hash); prunes stale rows
# ===========================================================================
def upsert_chunks(
    dsn: str,
    chunks: list[Chunk],
    embed: Callable[[str], list[float]],
    sources: set[str],
    verbose: bool,
) -> dict[str, int]:
    try:
        import psycopg  # type: ignore
    except ImportError as e:
        raise SystemExit("ERROR: pip install 'psycopg[binary]'") from e

    stats = {"inserted": 0, "skipped": 0, "pruned": 0}
    with psycopg.connect(dsn) as conn:
        with conn.cursor() as cur:
            # Which (source, content_hash) already exist? Skip re-embedding those.
            cur.execute(
                "SELECT source, content_hash FROM omar.kb_chunks WHERE source = ANY(%s)",
                (list(sources),),
            )
            existing = {(r[0], r[1]) for r in cur.fetchall()}
            fresh_hashes: dict[str, set[str]] = {s: set() for s in sources}

            for ch in chunks:
                fresh_hashes[ch.source].add(ch.content_hash)
                if (ch.source, ch.content_hash) in existing:
                    stats["skipped"] += 1
                    continue
                vec = embed(ch.content)
                if EMBED_BATCH_SLEEP:
                    time.sleep(EMBED_BATCH_SLEEP)
                cur.execute(
                    """
                    INSERT INTO omar.kb_chunks
                        (source, heading_path, content, content_hash,
                         token_count, embedding, chunk_index)
                    VALUES (%s, %s, %s, %s, %s, %s::vector, %s)
                    ON CONFLICT (source, content_hash) DO UPDATE
                       SET heading_path = EXCLUDED.heading_path,
                           content      = EXCLUDED.content,
                           token_count  = EXCLUDED.token_count,
                           embedding    = EXCLUDED.embedding,
                           chunk_index  = EXCLUDED.chunk_index
                    """,
                    (
                        ch.source, ch.heading_path, ch.content, ch.content_hash,
                        ch.token_count, vec_to_pg_literal(vec), ch.chunk_index,
                    ),
                )
                stats["inserted"] += 1
                if verbose:
                    print(f"  + [{ch.source}] {ch.heading_path}", file=sys.stderr)

            # Prune rows for these sources whose chunk no longer exists.
            for src in sources:
                keep = fresh_hashes[src]
                if keep:
                    cur.execute(
                        "DELETE FROM omar.kb_chunks WHERE source = %s AND NOT (content_hash = ANY(%s))",
                        (src, list(keep)),
                    )
                else:
                    cur.execute("DELETE FROM omar.kb_chunks WHERE source = %s", (src,))
                stats["pruned"] += cur.rowcount or 0
        conn.commit()
    return stats


# ===========================================================================
# Source loading
# ===========================================================================
def load_sources(with_curriculum: bool, kb_file: Path, curriculum_file: Path) -> list[Chunk]:
    all_chunks: list[Chunk] = []

    if not kb_file.exists():
        raise SystemExit(f"ERROR: KB file not found: {kb_file}")
    kb_text = kb_file.read_text(encoding="utf-8")
    all_chunks.extend(chunk_markdown(kb_text, kb_file.name))

    if with_curriculum:
        if not curriculum_file.exists():
            print(
                f"WARN: --with-curriculum set but file missing: {curriculum_file} (skipping)",
                file=sys.stderr,
            )
        else:
            cur_text = curriculum_file.read_text(encoding="utf-8")
            all_chunks.extend(chunk_markdown(cur_text, curriculum_file.name))

    return all_chunks


# ===========================================================================
# CLI
# ===========================================================================
def parse_args(argv: list[str]) -> argparse.Namespace:
    p = argparse.ArgumentParser(description="RAG ingestion for the kb_search tool (omar agent).")
    p.add_argument("--dry-run", action="store_true",
                   help="Parse + chunk + report only. No DB writes, no embedding calls.")
    p.add_argument("--with-curriculum", action="store_true",
                   help="Also ingest 03_KNOWLEDGE/.../_generated-curriculum.md (336-lesson detail).")
    p.add_argument("--prune-only", action="store_true",
                   help="Only delete DB rows whose chunks no longer exist; no inserts.")
    p.add_argument("--verbose", action="store_true", help="Per-chunk logging to stderr.")
    return p.parse_args(argv)


def main(argv: Optional[list[str]] = None) -> int:
    args = parse_args(argv if argv is not None else sys.argv[1:])

    kb_file = Path(os.environ.get("KB_FILE", DEFAULT_KB_FILE))
    curriculum_file = Path(os.environ.get("CURRICULUM_FILE", DEFAULT_CURRICULUM_FILE))
    dim = int(os.environ.get("EMBED_DIM", DEFAULT_EMBED_DIM))
    provider = os.environ.get("EMBED_PROVIDER", "gemini")
    default_model = DEFAULT_OPENAI_MODEL if provider.lower() == "openai" else DEFAULT_GEMINI_MODEL
    model = os.environ.get("EMBED_MODEL", default_model)

    chunks = load_sources(args.with_curriculum, kb_file, curriculum_file)
    sources = {c.source for c in chunks}

    # ---- report (always) ----
    total_tokens = sum(c.token_count for c in chunks)
    print(f"Parsed {len(chunks)} chunks from {len(sources)} source(s): {', '.join(sorted(sources))}")
    print(f"Approx total tokens: {total_tokens}")
    if args.verbose or args.dry_run:
        for c in chunks:
            print(f"  [{c.token_count:>4}t] {c.source} :: {c.heading_path}")

    if args.dry_run:
        print("\n--dry-run: no embeddings computed, no DB writes. Done.")
        return 0

    dsn = os.environ.get("OMAR_DB_DSN")
    if not dsn:
        raise SystemExit("ERROR: OMAR_DB_DSN not set (needed for a real ingest). Use --dry-run to parse only.")

    if args.prune_only:
        embed = get_embedder("fake", model, dim, args.verbose)  # never called on prune-only path
        stats = upsert_chunks(dsn, [], embed, sources, args.verbose)
        print(f"prune-only: pruned {stats['pruned']} stale row(s).")
        return 0

    embed = get_embedder(provider, model, dim, args.verbose)
    print(f"Embedding via provider={provider} model={model} dim={dim} ...")
    stats = upsert_chunks(dsn, chunks, embed, sources, args.verbose)
    print(
        f"Done. inserted/updated={stats['inserted']} "
        f"skipped(unchanged)={stats['skipped']} pruned={stats['pruned']}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
