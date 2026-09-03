-- ============================================================================
-- "عمر" — WhatsApp AI Agent — RAG / pgvector schema (kb_search backing store)
-- ============================================================================
-- Target  : Postgres 16 on the VPS (187.124.9.249). SAME database as the agent
--           memory (omar_agent), SAME schema (omar). This table is the vector
--           store the `kb_search` tool reads from. It is the deep-curriculum
--           knowledge ONLY — prices/catalog/policies live inline in the system
--           prompt for absolute accuracy and are NOT served from here.
-- Extension: pgvector (https://github.com/pgvector/pgvector) MUST be installed
--           and the extension enabled in this database. The official n8n /
--           Postgres-on-VPS image does NOT bundle it — see note at the bottom.
-- Embeddings: Gemini text-embedding-004 → 768 dimensions (vector(768)). If you
--           swap the embedding model, the dimension MUST match (see ingest_kb.py
--           EMBED_DIM env). Re-ingest from scratch after any dimension change.
-- Idempotent: safe to re-run. Nothing here is destructive.
-- Deploy   : DO NOT run on prod until Omar approves. Mirrors postgres-schema.sql
--           deploy policy (§Deploy in omar-build-plan.md).
--
-- Run order (once provisioning omar_agent exists — see postgres-schema.sql):
--   1) CREATE EXTENSION IF NOT EXISTS vector;   -- needs pgvector installed + superuser
--   2) psql ... -f schema-kb.sql                 -- this file
--   3) python ingest_kb.py                       -- populates omar.kb_chunks
-- ============================================================================

-- pgvector must be enabled BEFORE this table can use the `vector` type.
-- Requires the pgvector binaries installed in the Postgres image, and a role
-- with privileges to create the extension (usually the superuser, run once).
CREATE EXTENSION IF NOT EXISTS vector;

CREATE SCHEMA IF NOT EXISTS omar;

-- ---------------------------------------------------------------------------
-- kb_chunks — one row per markdown chunk of the knowledge base.
--   The `kb_search` tool runs a similarity query against `embedding` and
--   returns the top-k `content` rows (with `heading_path` for context).
--   `content_hash` makes re-ingestion idempotent: a chunk whose text didn't
--   change keeps its row + embedding (no needless re-embed / churn).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS omar.kb_chunks (
  id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  source        TEXT NOT NULL,                 -- e.g. 'knowledge-base.md' | '_generated-curriculum.md'
  heading_path  TEXT NOT NULL,                 -- full parent-heading trail, e.g. 'الكورسات > هياكل البيانات م١ > و2 — Linked List'
  content       TEXT NOT NULL,                 -- the chunk body (heading line + its text)
  content_hash  TEXT NOT NULL,                 -- sha256 of (source|heading_path|content) — dedup / change-detect key
  token_count   INTEGER NOT NULL DEFAULT 0,    -- approx tokens (for budgeting the LLM context)
  embedding     vector(768),                   -- Gemini text-embedding-004 = 768 dims. NULL only on --dry-run / failed embed.
  chunk_index   INTEGER NOT NULL DEFAULT 0,    -- order within the source file (stable read order)
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- One row per (source, content_hash): the upsert key. Same text, same source
-- => one row, re-runnable without duplicates.
CREATE UNIQUE INDEX IF NOT EXISTS uq_kb_chunks_source_hash
  ON omar.kb_chunks (source, content_hash);

-- Fast lookup / housekeeping by source file.
CREATE INDEX IF NOT EXISTS idx_kb_chunks_source ON omar.kb_chunks (source);

-- ---------------------------------------------------------------------------
-- Vector similarity index.
--   Default below = HNSW (pgvector >= 0.5.0): best recall/latency for a small,
--   rarely-changing corpus like this KB (a few hundred chunks). Cosine ops
--   (`vector_cosine_ops`) match Gemini embeddings, which are meant to be
--   compared by cosine similarity. Build the index AFTER the first ingest so
--   it has data to learn from (re-running is cheap on a small table).
--
--   If your pgvector build predates HNSW, comment the HNSW block and use the
--   IVFFlat fallback instead (lists≈sqrt(rows); re-tune as the corpus grows).
-- ---------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_kb_chunks_embedding_hnsw
  ON omar.kb_chunks USING hnsw (embedding vector_cosine_ops)
  WITH (m = 16, ef_construction = 64);

-- --- IVFFlat fallback (use ONLY if HNSW unavailable; comment out the HNSW index above) ---
-- CREATE INDEX IF NOT EXISTS idx_kb_chunks_embedding_ivf
--   ON omar.kb_chunks USING ivfflat (embedding vector_cosine_ops)
--   WITH (lists = 16);
-- After building an IVFFlat index, set `SET ivfflat.probes = 4;` per session for recall.

-- ---------------------------------------------------------------------------
-- updated_at trigger — reuse omar.touch_updated_at() from postgres-schema.sql.
--   Defined defensively here so this file can run standalone if needed.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION omar.touch_updated_at() RETURNS trigger AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_kb_chunks_touch ON omar.kb_chunks;
CREATE TRIGGER trg_kb_chunks_touch BEFORE UPDATE ON omar.kb_chunks
  FOR EACH ROW EXECUTE FUNCTION omar.touch_updated_at();

-- ---------------------------------------------------------------------------
-- kb_search helper — top-k cosine-nearest chunks for a query embedding.
--   The n8n `kb_search` tool can call this, or run the SELECT inline.
--   Input  : query embedding (vector(768)) + k (default 5).
--   Output : id, source, heading_path, content, token_count, similarity (0..1).
--   `1 - (embedding <=> q)` converts cosine DISTANCE (<=>) to cosine SIMILARITY.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION omar.kb_search(q vector(768), k INTEGER DEFAULT 5)
RETURNS TABLE (
  id            BIGINT,
  source        TEXT,
  heading_path  TEXT,
  content       TEXT,
  token_count   INTEGER,
  similarity    REAL
) AS $$
  SELECT c.id, c.source, c.heading_path, c.content, c.token_count,
         (1 - (c.embedding <=> q))::REAL AS similarity
  FROM omar.kb_chunks c
  WHERE c.embedding IS NOT NULL
  ORDER BY c.embedding <=> q          -- cosine distance ASC = most similar first
  LIMIT k;
$$ LANGUAGE sql STABLE;

-- ============================================================================
-- Enabling pgvector on the VPS (operational note — DO NOT run blindly):
--   The stock postgres:16 image does NOT include pgvector. Options:
--     a) Use the `pgvector/pgvector:pg16` image (drop-in superset of postgres:16).
--     b) Or `apt-get install postgresql-16-pgvector` inside the container/host,
--        then `CREATE EXTENSION vector;` as superuser.
--   n8n's own Postgres stays on a SEPARATE DB — adding pgvector here does not
--   touch n8n. Coordinate the image swap with Omar before changing prod.
-- ============================================================================
-- END
-- ============================================================================
