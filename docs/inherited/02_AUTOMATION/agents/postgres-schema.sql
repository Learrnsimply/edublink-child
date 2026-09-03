-- ============================================================================
-- "عمر" — WhatsApp AI Agent — Postgres Schema (hybrid memory + escalation log)
-- ============================================================================
-- Target  : Postgres 16 on the VPS (187.124.9.249). The same server that runs
--           n8n's Postgres, but a SEPARATE database/schema to keep agent data
--           isolated from n8n's internal tables.
-- Memory   : Hybrid pattern (RS-brand port). Long-lived profile in omar.contacts
--           + append-only message log in omar.messages (rolling window read by
--           the LLM node, full log kept for daily QA review).
-- Handoff  : omar.escalations tracks every human-handoff so nothing is dropped.
-- Idempotent: safe to re-run. Nothing here is destructive.
-- Deploy   : DO NOT run on prod until Omar approves. See omar-build-plan.md §Deploy.
--
-- Recommended provisioning (run once as the Postgres superuser on the VPS):
--   CREATE DATABASE omar_agent;
--   CREATE USER omar_agent WITH PASSWORD '<from .env / Bitwarden>';
--   GRANT ALL PRIVILEGES ON DATABASE omar_agent TO omar_agent;
-- Then connect to omar_agent and run everything below.
-- ============================================================================

CREATE SCHEMA IF NOT EXISTS omar;

-- ---------------------------------------------------------------------------
-- Enums — constrain the agent's structured fields so bad values can't sneak in
-- ---------------------------------------------------------------------------
DO $$ BEGIN
  CREATE TYPE omar.intent_box AS ENUM (
    'sales',        -- A 🛒
    'support',      -- B 🛠️
    'payment_intl', -- C 🌍
    'for_ahmed',    -- D 🎓
    'code',         -- E 📚
    'sensitive'     -- F 🚨
  );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE TYPE omar.lead_status AS ENUM (
    'new', 'engaged', 'hot', 'purchased', 'cold', 'unknown'
  );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE TYPE omar.escalation_type AS ENUM (
    'access',        -- B-F2: paid but no course
    'certificate',   -- B-cert
    'payment',       -- B-payfail (domestic)
    'payment_intl',  -- C: foreign card declined
    'site_access',   -- F1: site/lessons won't open
    'refund',        -- F5a
    'angry',         -- F5b
    'for_ahmed'      -- D: private/collab/ad/course request
  );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE TYPE omar.priority AS ENUM ('normal', 'high', 'urgent');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE TYPE omar.escalation_status AS ENUM ('open', 'in_progress', 'resolved');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE TYPE omar.msg_role AS ENUM ('customer', 'omar', 'system');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

-- ---------------------------------------------------------------------------
-- updated_at trigger helper
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION omar.touch_updated_at() RETURNS trigger AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ---------------------------------------------------------------------------
-- 1) contacts — long-lived customer profile (the agent's memory of WHO this is)
--    Keyed on WhatsApp phone (E.164, digits only, no '+'). One row per person.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS omar.contacts (
  phone            TEXT PRIMARY KEY,                 -- e.g. '201011516829'
  name             TEXT,
  email            TEXT,
  wc_customer_id   BIGINT,                           -- WooCommerce customer id if matched
  mautic_contact_id BIGINT,                          -- link to Mautic (mautic_upsert)
  course_interest  TEXT[] NOT NULL DEFAULT '{}',     -- e.g. {'java','data-structure'}
  last_intent      omar.intent_box,
  lead_status      omar.lead_status NOT NULL DEFAULT 'new',
  country          TEXT,                             -- helps the C (intl payment) flow
  total_messages   INTEGER NOT NULL DEFAULT 0,
  notes            TEXT,                             -- free-form context the agent learned
  first_seen       TIMESTAMPTZ NOT NULL DEFAULT now(),
  last_seen        TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_contacts_email       ON omar.contacts (lower(email));
CREATE INDEX IF NOT EXISTS idx_contacts_lead_status ON omar.contacts (lead_status);
CREATE INDEX IF NOT EXISTS idx_contacts_last_seen   ON omar.contacts (last_seen DESC);

DROP TRIGGER IF EXISTS trg_contacts_touch ON omar.contacts;
CREATE TRIGGER trg_contacts_touch BEFORE UPDATE ON omar.contacts
  FOR EACH ROW EXECUTE FUNCTION omar.touch_updated_at();

-- ---------------------------------------------------------------------------
-- 2) messages — append-only conversation log
--    Serves two purposes: (a) the rolling window the LLM reads for context,
--    (b) the full audit trail for the daily QA-review pattern (RS port P2).
--    n8n's native "Postgres Chat Memory" can ALSO manage its own window table;
--    keep this log regardless so QA + analytics survive memory trimming.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS omar.messages (
  id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  phone        TEXT NOT NULL REFERENCES omar.contacts(phone) ON DELETE CASCADE,
  role         omar.msg_role NOT NULL,
  body         TEXT NOT NULL,
  intent       omar.intent_box,                      -- triage label (on customer msgs)
  media_url    TEXT,                                 -- e.g. screenshot for F1
  wa_message_id TEXT,                                 -- Evolution API message id (idempotency)
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_messages_phone_time ON omar.messages (phone, created_at DESC);
CREATE UNIQUE INDEX IF NOT EXISTS uq_messages_wa_id ON omar.messages (wa_message_id)
  WHERE wa_message_id IS NOT NULL;                   -- dedup repeated webhook deliveries

-- ---------------------------------------------------------------------------
-- 3) escalations — every human-handoff, tracked open -> resolved
--    This is the safety net: if omar escalated it, it MUST be visible until
--    a human closes it. Powers the alert email/WhatsApp to Ahmed + Omar.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS omar.escalations (
  id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  phone         TEXT NOT NULL REFERENCES omar.contacts(phone) ON DELETE CASCADE,
  type          omar.escalation_type NOT NULL,
  priority      omar.priority NOT NULL DEFAULT 'normal',
  status        omar.escalation_status NOT NULL DEFAULT 'open',
  summary       TEXT NOT NULL,                       -- omar's summary of the issue
  customer_email TEXT,
  order_id      TEXT,
  screenshot_url TEXT,
  context_snapshot JSONB,                            -- last few messages at escalation time
  alerted_at    TIMESTAMPTZ,                         -- when the email/WhatsApp alert fired
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  resolved_at   TIMESTAMPTZ,
  resolved_by   TEXT,                                -- 'ahmed' | 'omar'
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_escalations_open     ON omar.escalations (status) WHERE status <> 'resolved';
CREATE INDEX IF NOT EXISTS idx_escalations_phone    ON omar.escalations (phone);
CREATE INDEX IF NOT EXISTS idx_escalations_priority ON omar.escalations (priority, created_at DESC);

DROP TRIGGER IF EXISTS trg_escalations_touch ON omar.escalations;
CREATE TRIGGER trg_escalations_touch BEFORE UPDATE ON omar.escalations
  FOR EACH ROW EXECUTE FUNCTION omar.touch_updated_at();

-- ---------------------------------------------------------------------------
-- Convenience view — open escalations the team still owes a human reply
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW omar.open_escalations AS
SELECT e.id, e.priority, e.type, e.summary, c.name, e.phone,
       e.customer_email, e.created_at,
       now() - e.created_at AS age
FROM omar.escalations e
JOIN omar.contacts c USING (phone)
WHERE e.status <> 'resolved'
ORDER BY
  CASE e.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 ELSE 2 END,
  e.created_at;

-- ============================================================================
-- END
-- ============================================================================
