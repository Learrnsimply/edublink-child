-- ============================================================================
-- migration-hybrid.sql — hybrid (vector + lexical) retrieval for kb_search
-- ============================================================================
-- WHY: baseline eval (2026-06-10, eval_rag.py) showed facts-questions hit@5 =
--      71% with pure vector search. The 4 misses were all exact-keyword
--      questions ("فودافون كاش", "باقة جافا", "مواعيد الدعم", "الدفع من برة").
--      Lexical matching fixes exactly this class; RRF fuses both rankings.
-- WHAT: pg_trgm extension + omar.kb_search_hybrid(q, qtext, k).
--       ADDITIVE ONLY — the old omar.kb_search() stays untouched (rollback =
--       point W3c back at the old function).
-- HOW:  ssh learnsimply-vps "docker exec -i omar-pgvector psql -U omar_agent \
--           -d omar_agent" < migration-hybrid.sql
-- ============================================================================
\set ON_ERROR_STOP on

-- pg_trgm is a TRUSTED extension (PG13+): db owner can create it.
CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- ---------------------------------------------------------------------------
-- kb_search_hybrid — Reciprocal Rank Fusion of:
--   vec: top-20 by cosine similarity (same as the old kb_search)
--   lex: top-20 by word_similarity(qtext, heading+content) — how well the
--        query's words appear inside the chunk (trigram-based, Arabic-safe)
-- RRF constant 60 = standard. Corpus is tiny (~80-200 rows) so the lexical
-- seq-scan is negligible; no extra index needed yet.
-- Returns the SAME columns the W3c "KB Query" node selects (+ score).
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION omar.kb_search_hybrid(
  q     vector(768),
  qtext TEXT,
  k     INTEGER DEFAULT 5
)
RETURNS TABLE (
  id            BIGINT,
  source        TEXT,
  heading_path  TEXT,
  content       TEXT,
  token_count   INTEGER,
  similarity    REAL,
  score         REAL
) AS $$
  WITH vec AS (
    SELECT c.id,
           row_number() OVER (ORDER BY c.embedding <=> q) AS r
    FROM omar.kb_chunks c
    WHERE c.embedding IS NOT NULL
    ORDER BY c.embedding <=> q
    LIMIT 20
  ),
  lex AS (
    SELECT s.id, row_number() OVER (ORDER BY s.lsim DESC) AS r
    FROM (
      SELECT c.id,
             word_similarity(qtext, c.heading_path || E'\n' || c.content) AS lsim
      FROM omar.kb_chunks c
    ) s
    WHERE s.lsim > 0.05            -- drop pure noise
    ORDER BY s.lsim DESC
    LIMIT 20
  ),
  fused AS (
    SELECT COALESCE(v.id, l.id) AS id,
           (COALESCE(1.0 / (60 + v.r), 0) + COALESCE(1.0 / (60 + l.r), 0))::REAL AS rrf
    FROM vec v
    FULL OUTER JOIN lex l USING (id)
  )
  SELECT c.id, c.source, c.heading_path, c.content, c.token_count,
         COALESCE((1 - (c.embedding <=> q)), 0)::REAL AS similarity,
         f.rrf AS score
  FROM fused f
  JOIN omar.kb_chunks c ON c.id = f.id
  ORDER BY f.rrf DESC
  LIMIT k;
$$ LANGUAGE sql STABLE;

-- sanity ping
SELECT 'migration-hybrid OK' AS status, count(*) AS chunks FROM omar.kb_chunks;
