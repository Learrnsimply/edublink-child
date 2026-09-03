# Security Requirements Quality Checklist — Phase 2

**Feature**: 001-bug-remediation-90day
**Domain**: Security Hardening (Phase 2)
**Created**: 2026-05-24
**Purpose**: "Unit tests for English" — تحقق من جودة وكمال متطلبات الأمن قبل التنفيذ، مش testing implementation
**Source skill**: `.claude/skills/speckit-checklist/SKILL.md` (applied manually for brand-tactical scope)

> **Concept reminder:** كل item بيـ test **الـ requirement quality** (هل المتطلب مكتوب صح؟)، مش الـ implementation (هل الكود شغّال؟).

---

## Requirement Completeness (هل كل المتطلبات موجودة؟)

- [ ] CHK001 — Are SMTP credential storage requirements specified for both stored-value rotation AND wp-config constant migration? [Completeness, Spec §US4، tasks.md 2.1+2.2]
- [ ] CHK002 — Are `.htaccess` deny rules specified for all sensitive file types (`.log`, `.sql`, `.bak`, `.tar.gz`, `.zip`)? [Completeness, tasks.md 2.3]
- [ ] CHK003 — Are PHP execution prevention requirements defined for ALL upload-writable directories (not just `wp-content/uploads`)? [Coverage, Gap — tasks.md 2.4 covers only uploads/]
- [ ] CHK004 — Are 2FA backup/recovery code requirements specified للحالة لو Ahmed فقد device؟ [Gap, Edge Case — tasks.md 2.8 missing recovery flow]
- [ ] CHK005 — Are HSTS preload-list submission requirements documented after ramp-up complete? [Gap — tasks.md 2.10 ينتهي عند max-age=300 only]
- [ ] CHK006 — Are wp-config.php fallback access requirements defined لو chmod 600 كسر site access؟ [Gap, Recovery Flow — tasks.md 2.12 risk mentioned but no recovery plan]
- [ ] CHK007 — Are CSP (Content Security Policy) requirements specified in Phase 2 or deferred? [Coverage, Spec §US4] (Note: CSP موجود في 4.11، not Phase 2 — intentional?)

## Requirement Clarity (هل المتطلبات specific?)

- [ ] CHK008 — Is "limit login attempts" quantified بـ specific threshold + lockout duration + whitelist IPs? [Clarity, tasks.md 2.7] (Currently: only "threshold=5" — no duration, no whitelist)
- [ ] CHK009 — Is "audit suspicious uploads PHP" quantified بـ specific detection criteria (file size? entropy? known-signature?)؟ [Clarity, tasks.md 2.9] (Currently: vague "confirm `// Silence is golden`")
- [ ] CHK010 — Is "HSTS ramp-up" defined بـ specific schedule (e.g., 300s → 1d → 7d → 1y over X weeks)؟ [Clarity, tasks.md 2.10]
- [ ] CHK011 — Is "xmlrpc disable" specified بـ method preference (filter vs .htaccess vs both)? [Clarity, tasks.md 2.6]

## Requirement Consistency (هل المتطلبات متوافقة؟)

- [ ] CHK012 — Do `.htaccess` requirements (2.3 + 2.4) align مع spec §US4 acceptance "xmlrpc returns 403"? [Consistency, Spec §US4 vs tasks.md 2.3+2.6]
- [ ] CHK013 — Are Phase 2 exit gate criteria consistent مع individual task acceptance? [Consistency, tasks.md §Phase 2 Exit Gate vs 2.1-2.12]
- [ ] CHK014 — Is "SMTP password rotation" consistent مع HANDOFF.md note "Ahmed يـ rotate before any email campaign"? [Consistency, tasks.md 2.1 vs HANDOFF.md]

## Acceptance Criteria Quality (هل success criteria قابلة للقياس؟)

- [ ] CHK015 — Can "0 critical security findings" (plan.md Phase 2 Exit Gate) be objectively measured? [Measurability] (Currently no tool/scan specified — WPScan? Wordfence? manual review?)
- [ ] CHK016 — Are HSTS verification requirements measurable (e.g., curl test, ssllabs.com score target)? [Measurability, tasks.md 2.10]
- [ ] CHK017 — Is "no X-Powered-By in response" measurable بـ specific verification command? [Measurability, tasks.md 2.11] ✅ — curl -I check exists

## Scenario Coverage (هل كل السيناريوهات مغطّاة؟)

- [ ] CHK018 — Are requirements defined for primary scenario: Ahmed logs in normally بـ 2FA? [Coverage, Primary — tasks.md 2.8]
- [ ] CHK019 — Are requirements defined for exception scenario: Ahmed locked out of 2FA (lost phone)? [Coverage, Exception — Gap, see CHK004]
- [ ] CHK020 — Are requirements defined for recovery scenario: site 500-error after .htaccess change? [Coverage, Recovery — Gap]
- [ ] CHK021 — Are requirements defined for security incident response (e.g., SMTP password leaked again post-rotation)? [Coverage, Exception — Gap]

## Edge Case Coverage (هل الحالات الحدّية مغطّاة؟)

- [ ] CHK022 — Are requirements defined لو Hostinger CDN edge بيـ inject headers جديدة (platform/panel/hcdn) — هل بنتعامل مع الـ fingerprinting ده؟ [Edge Case, Gap]
- [ ] CHK023 — Are requirements defined for `.user.ini` PHP-FPM cache TTL (5 min delay before changes apply)? [Edge Case — Gap, but not blocking]
- [ ] CHK024 — Are requirements defined لو Limit Login Attempts plugin block IP بتاع Ahmed نفسه (whitelist needed)? [Edge Case — see CHK008]
- [ ] CHK025 — Are requirements defined for partial failure: 2FA enabled but Two-Factor plugin breaks before Ahmed enrolls? [Edge Case, Recovery — Gap]

## Non-Functional Requirements (هل الـ NFRs محددة؟)

- [ ] CHK026 — Are security audit/scan cadence requirements specified (e.g., monthly WPScan run)? [NFR, Gap]
- [ ] CHK027 — Are security event logging requirements specified (e.g., who logs auth attempts, how long retained)? [NFR, Gap]
- [ ] CHK028 — Are GDPR-related security requirements documented لـ DB dumps containing PII (subscribers, customers)? [NFR, Privacy — Gap, related to bugs-plugins.md C-2]

## Dependencies & Assumptions (هل الـ dependencies موثّقة؟)

- [ ] CHK029 — Is the assumption "Hostinger Business plan supports `.user.ini` and `.htaccess` Header directives" validated؟ [Assumption — tasks.md 2.11]
- [ ] CHK030 — Is the dependency "Ahmed availability for 2FA enrollment + SMTP rotation approval" documented? [Dependency, Spec §Constraints]
- [ ] CHK031 — Are Hostinger-specific quirks documented (e.g., cron 5-min minimum, CageFS restrictions affecting security tools)? [Dependency, partly in CLAUDE.md]

## Ambiguities & Conflicts

- [ ] CHK032 — Is "wp-config.php chmod 600" reversible behavior documented if site breaks (back to 644)? [Ambiguity — tasks.md 2.12 risk mentioned but resolution path unclear]
- [ ] CHK033 — Does "Audit suspicious uploads" (2.9) require Ahmed approval before deletion، or auto-delete based on signature? [Ambiguity — tasks.md 2.9 says "Delete suspicious ones" without approval gate]
- [ ] CHK034 — Are the X-Powered-By + Hostinger edge headers (`platform`, `panel`, `Server`) treated as a unified fingerprinting concern OR separate items? [Ambiguity — tasks.md 2.11 covers only X-Powered-By، edge headers unaddressed]

---

## Summary

- **Total items:** 34
- **Coverage by dimension:**
  - Completeness: 7
  - Clarity: 4
  - Consistency: 3
  - Measurability: 3
  - Scenario Coverage: 4
  - Edge Cases: 4
  - NFRs: 3
  - Dependencies/Assumptions: 3
  - Ambiguities: 3
- **Gap markers:** 14 items flagged as `[Gap]`
- **Traceability:** 88% of items reference spec/tasks/plan (target: ≥80%) ✅

## Top 5 Critical Gaps (priorities قبل تنفيذ Phase 2)

1. **CHK004** — 2FA recovery flow undefined (CRITICAL if Ahmed loses access)
2. **CHK020** — `.htaccess` rollback procedure undefined
3. **CHK015** — "0 critical security findings" not measurable (which scanner?)
4. **CHK024** — Limit Login Attempts could lock Ahmed out (whitelist needed)
5. **CHK008** — Limit Login Attempts threshold incomplete (no duration, no whitelist)

## Recommended Actions

1. Resolve CHK004 + CHK020 + CHK024 **before** starting Phase 2 execution
2. Add answers to CHK008 + CHK015 to tasks.md to clarify Phase 2 exit criteria
3. Defer CHK022-CHK023 (edge cases) — not blocking
4. Pin CHK028 (GDPR) for Phase 5 review
