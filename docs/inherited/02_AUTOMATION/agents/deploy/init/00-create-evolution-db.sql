-- Runs once on first pgvector boot (as omar_agent, connected to omar_agent DB).
-- Creates the separate database Evolution API uses for its own Prisma tables.
-- The omar_agent schema (postgres-schema.sql) + RAG (schema-kb.sql) are mounted
-- as 01-*.sql and 02-*.sql and run into omar_agent right after this.
CREATE DATABASE evolution;
