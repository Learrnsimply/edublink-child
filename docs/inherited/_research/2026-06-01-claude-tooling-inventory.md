I have enough data. No project-local `.claude` config exists for Learn Simply. Now producing the three tables.

---

# Learn Simply — Claude Code Tooling Inventory

**Context:** WordPress course site (learrnsimply.com) + Mautic + n8n + AI customer agent + Egyptian Arabic marketing. Omar = solo GTM Engineer. No project-local `.claude/` exists yet — everything inherits from user-global `~/.claude/`.

---

## Table A — INSTALLED + USEFUL for Learn Simply

| Name | Type | Purpose for Learn Simply | Priority |
|---|---|---|---|
| `seo` | skill | Egyptian Arabic SEO for course pages, Ahmed YouTube → SERP capture | P0 |
| `seo-specialist` | agent | On-page + technical SEO audit for learrnsimply.com | P0 |
| `content-engine` | skill | Repurpose Ahmed YouTube into Telegram/FB/TikTok native posts | P0 |
| `brand-voice` | skill | Build "Ahmed Adel" voice profile from his real YouTube transcripts | P0 |
| `article-writing` | skill | Course landing copy + blog posts in Ahmed's voice | P0 |
| `crosspost` | skill | Distribute one course launch across YouTube/Telegram/FB/TikTok | P0 |
| `email-ops` | skill | Mautic-adjacent triage; verify "From-address" deliverability fixes | P0 |
| `security-review` | skill | Already in use — WP `.htaccess`, xmlrpc, uploads hardening | P0 |
| `security-reviewer` | agent | Pre-PR review on `Learrnsimply/edublink-child` PRs | P0 |
| `security-scan` | skill | Scan WP plugins (61 active) for known CVEs | P0 |
| `audit` | skill | A11y/perf/responsive audit on course pages (mobile-first, RTL) | P0 |
| `deep-research` | skill | Research Kashier vs alternatives, payment failure root causes | P0 |
| `exa-search` | skill | Find WP-side patterns, Mautic recipes, course-platform comparables | P1 |
| `n8n-workflow-patterns` | skill | n8n workflows for cart recovery, Mautic sync, AI agent routing | P0 |
| `n8n-code-javascript` | skill | Custom n8n Code nodes for the cart-recovery + AI customer agent | P0 |
| `n8n-code-python` | skill | Python nodes where JS is awkward (e.g., WC API pagination) | P1 |
| `n8n-expression-syntax` | skill | Templating WC order data, Mautic contact fields | P1 |
| `n8n-mcp-tools-expert` | skill | n8n MCP integration patterns | P1 |
| `n8n-node-configuration` | skill | Correct configuration for WC, Mautic, Telegram nodes | P1 |
| `n8n-validation-expert` | skill | Validate workflows before pushing to VPS n8n | P1 |
| `customer-billing-ops` | skill | Diagnose Kashier 909 failed orders, refund/recovery flows | P0 |
| `finance-billing-ops` | skill | Revenue snapshot, churn triage, Stripe-vs-Kashier comparisons | P0 |
| `lead-intelligence` | skill | Score the 13,140 dormant email subscribers before Mautic blast | P0 |
| `market-research` | skill | Competitor map (Almentor, EdRaak, Coursera Arabic) | P1 |
| `dashboard-builder` | skill | Build the Sprint 2 KPI dashboard (orders, cart abandon, MAPI ROAS) | P1 |
| `unified-notifications-ops` | skill | Reuse `_agency/notify.sh` Telegram bot for Mautic/n8n alerts | P1 |
| `frontend-design` | skill | Redesign course detail / checkout pages (RTL-first) | P1 |
| `frontend-patterns` | skill | Block patterns for course catalog templates | P1 |
| `harden` | skill | Production-readiness on edublink-child (error states, i18n) | P1 |
| `clarify` | skill | Improve Arabic UX copy on checkout + error pages | P1 |
| `extract` | skill | Pull repeated patterns from theme into reusable blocks | P2 |
| `deployment-patterns` | skill | Hostinger SFTP + git workflow for edublink-child | P1 |
| `database-migrations` | skill | wp_options autoload cleanup playbook (already started) | P1 |
| `postgres-patterns` | skill | If Mautic later moves to managed PG | P2 |
| `code-reviewer` | agent | Auto-review on theme PRs before Ahmed merges | P0 |
| `code-architect` | agent | Architectural decisions (HPOS migration, plugin reduction) | P1 |
| `planner` | agent | Sprint 2-4 planning sessions | P0 |
| `tdd-guide` | agent | Tests for any new PHP/JS in edublink-child | P2 |
| `performance-optimizer` | agent | LiteSpeed vs WP-Optimize decision, autoload analysis | P0 |
| `database-reviewer` | agent | Validate the 40+ orphan tables cleanup plan | P0 |
| `a11y-architect` | agent | RTL + Arabic screen-reader audit on checkout | P1 |
| `silent-failure-hunter` | agent | Hunt silent JS/PHP errors hiding 909 Kashier failures | P0 |
| `refactor-cleaner` | agent | Dead code in cart, footer about-me, view-all link cleanup | P1 |
| `doc-updater` | agent | Keep `bugs-report.md` + sprint READMEs current | P1 |
| `/code-review` | command | Before each PR to Ahmed | P0 |
| `/security-review` | command | Before pushing any WP-facing change | P0 |
| `/verify` | command | Confirm a fix actually works on staging | P0 |
| `/review-pr` | command | Review Ahmed's responses to the open Sprint 1 PR | P0 |
| `/run` | command | Spin up local edublink-child if we need to repro | P1 |
| `/plan` | command | Sprint 2/3/4 planning | P0 |
| `/feature-dev` | command | Cart recovery + Mautic AI agent build cycle | P0 |
| `/save-session` + `/resume-session` | commands | Cross-device handoff (HANDOFF.md replacement) | P0 |
| `/checkpoint` | command | Mid-task snapshots during long sprints | P1 |
| `/devfleet` + `claude-devfleet` skill | command+skill | Parallel sub-agents for Sprint 2 (Kashier + WC + Mautic in parallel) | P1 |
| `/orchestrate` + `/multi-execute` | commands | Run audit + security + perf agents in parallel | P1 |
| `/eval` + `eval-harness` | command+skill | Eval the AI customer agent's answers before going live | P0 |
| `/plannotator-annotate` | command | Per CLAUDE.md rule — all plans through plannotator | P0 |
| `/loop-start` + `loop-operator` agent | command+agent | Watch cart-recovery KPIs over a sprint | P2 |
| `/santa-loop` + `continuous-agent-loop` | command+skill | Cron-style AI agent QA loop | P2 |
| `/schedule` (claude.ai routine) | deferred | Daily Mautic/Kashier KPI digest to Telegram | P1 |
| `/loop` | deferred | Poll Kashier/WC for failed-payment alerts | P2 |
| `mcp__github__*` (Plugin GitHub) | MCP | Manage Sprint 1 PR + future PRs on edublink-child + learn-simply-backups | P0 |
| `mcp__playwright__*` | MCP | UI audit (already used in `_tools/ui-audit/`); checkout flow E2E | P0 |
| `mcp__claude_ai_Notion__*` | MCP | Status sync to GrowthMora Notion DB | P0 |
| `mcp__claude_ai_Gmail__*` | MCP | Ahmed correspondence + delivery validation | P1 |
| `mcp__claude_ai_Google_Drive__*` | MCP | Client-facing report sharing | P1 |
| `mcp__claude_ai_Google_Calendar__*` | MCP | Sprint planning + Ahmed sync calls | P2 |
| `mcp__memory__*` | MCP | Persist Ahmed/ICP/Kashier learnings | P1 |
| `mcp__sequential-thinking__*` | MCP | Multi-step decisions (LiteSpeed-vs-WP-Optimize, HPOS migration) | P1 |
| `mcp__context7__*` | MCP | WooCommerce/Mautic/n8n/LiteSpeed current docs | P0 |
| Hooks: `pre-bash-dispatcher` + GateGuard | hook | Already active globally — protects against destructive WP commands | P0 |

---

## Table B — INSTALLED but NOT useful for Learn Simply

| Name | Type | Why not | Priority |
|---|---|---|---|
| All `cpp-*`, `rust-*`, `golang-*`, `swift-*`, `kotlin-*`, `java-*`, `dotnet-*`, `csharp-*`, `dart-flutter-*`, `compose-multiplatform-*`, `android-clean-architecture`, `foundation-models-on-device`, `swiftui-patterns`, `jpa-patterns` | skills+agents+cmds | Stack is PHP/WP + JS/n8n + Mautic. No native/mobile/Rust/Go. | skip |
| `django-*`, `laravel-*`, `springboot-*`, `nestjs-*`, `perl-*` | skills | Wrong framework. WP only. | skip |
| `clickhouse-io` | skill | No analytics warehouse yet; GA4 + WC reports suffice | skip |
| `defi-amm-security`, `evm-token-decimals`, `nodejs-keccak256`, `llm-trading-agent-security` | skills | Crypto — irrelevant | skip |
| `healthcare-phi-compliance`, `hipaa-compliance`, `healthcare-reviewer` | skills+agent | Not healthcare | skip |
| `customs-trade-compliance`, `carrier-relationship-management`, `logistics-exception-management`, `returns-reverse-logistics`, `inventory-demand-planning`, `production-scheduling`, `quality-nonconformance`, `energy-procurement` | skills | Industrial/logistics — wrong vertical | skip |
| `visa-doc-translate` | skill | Wrong domain | skip |
| `investor-materials`, `investor-outreach` | skills | Ahmed is a creator, not raising | skip |
| `manim-video`, `remotion-video-creation`, `videodb`, `video-editing` | skills | Ahmed handles video himself (~250K-369K subs); no leverage here yet | skip |
| `fal-ai-media` (image/video gen) | skill | Maybe later for ad creatives — not Phase 1/2 | defer |
| `frontend-slides`, `liquid-glass-design`, `ui-demo`, `nanoclaw-repl`, `typeset` | skills | No pitch decks / no design playground need now | skip |
| `defi-*`, `mcp__claude_ai_Higgsfield__*` (video gen) | skills+MCP | Premium video gen — not in Phase 1 budget | defer |
| `mcp__claude_ai_GoDaddy__*` | MCP | Domain is on Hostinger, not GoDaddy | skip |
| `mcp__claude_ai_Vercel__*`, `mcp__claude_ai_Supabase__*` | MCP | WP/Hostinger stack, not Vercel/Supabase | skip |
| `mcp__claude_ai_Cloudflare_Developer_Platform__*` | MCP | Not on Cloudflare Workers | skip |
| `mcp__plugin_figma_figma__*` | MCP | No Figma source — Ahmed handles design via Elementor | skip |
| `mcp__linkedin-scraper__*` | MCP | Audience is YouTube/Telegram, not LinkedIn | skip |
| `mcp__token-optimizer__*` (entire family, ~80 tools) | MCP | Heavyweight LLM ops tooling; not needed for solo brand | skip |
| `cpp-build-resolver`, `pytorch-build-resolver`, `dart-build-resolver`, `go-build-resolver`, `java-build-resolver`, `kotlin-build-resolver`, `rust-build-resolver` | agents | Wrong stack | skip |
| `opensource-forker`, `opensource-packager`, `opensource-sanitizer` | agents | Not publishing OSS | skip |
| `gan-evaluator`, `gan-generator`, `gan-planner` + `/gan-*` cmds | agents+cmds | GAN design loop not relevant to a course site | skip |
| `dmux-workflows` | skill | tmux-based parallel agents — Windows host, low value | skip |
| `claude-api` | skill | Building Anthropic SDK apps; not what Learn Simply needs (we consume Claude, not build on it) | defer |
| `mcp-server-patterns` | skill | Authoring MCP servers — not Phase 1 | defer |
| `data-scraper-agent` | skill | Could become useful later for Ahmed's competitor monitor | defer |

---

## Table C — MISSING / Should be installed

| Name | Type | Purpose for Learn Simply | Install command / Priority |
|---|---|---|---|
| **WordPress patterns skill** | skill | WP hooks, filters, child-theme conventions, WC + Elementor patterns | Create `~/.claude/skills/wordpress-patterns/` (custom) — **P0** |
| **WooCommerce ops skill** | skill | WC order states, HPOS, payment-gateway debug playbook, REST API recipes | Custom skill at `~/.claude/skills/woocommerce-ops/` — **P0** |
| **Mautic patterns skill** | skill | Segments, campaigns, lead scoring, contact sync, deliverability | Custom skill — **P0** |
| **Egyptian-Arabic marketing skill** | skill | Voice rules, Egyptian colloquial vs MSA, RTL copy patterns, conversion phrases | Custom — extends `brand-voice` — **P0** |
| **Kashier / Paymob integration skill** | skill | EG payment-gateway specifics, 3DS flow, common failure codes | Custom skill — **P0** for Sprint 2 |
| **Cart-recovery playbook skill** | skill | Sequences, copy, timing, channel choice (Telegram > email for EG) | Custom — **P0** |
| **GA4 + Meta Pixel + TikTok MAPI skill** | skill | Server-side events, debug HTML for pixel verification (Memory: "verify in browser, not admin") | Custom — **P0** |
| **GTM (Google Tag Manager) skill** | skill | Tied to your 2026-05-24 decision: GTM container as analytics hub | Custom — **P0** |
| **`wp-cli` patterns skill** | skill | Safe wp-cli over SSH (CageFS-aware), `wp db query`, `wp option get` cookbook | Custom — **P0** |
| **Hostinger / cPanel ops skill** | skill | SFTP, .htaccess, PHP versions, MySQL via phpMyAdmin, backup rotation | Custom — **P1** |
| **`wp-reviewer` agent** | agent | Spots common WP security/perf anti-patterns in theme PHP + theme JS | Custom in `~/.claude/agents/` — **P0** |
| **`mautic-reviewer` agent** | agent | Reviews Mautic campaign JSON exports + segment logic | Custom — **P1** |
| **`n8n-workflow-reviewer` agent** | agent | Lint n8n workflows before pushing to VPS | Custom — **P1** |
| **`/ls-status` command** | command | Pulls WC revenue, cart count, Kashier failure rate, GA4 pixel-fire status into one snapshot | Custom — **P0** |
| **`/ls-sprint-update` command** | command | Updates `01_WEB/specs/001-bug-remediation-90day/README.md` + Notion DB row in one shot | Custom — **P1** |
| **`/ls-deploy-theme` command** | command | Codified deploy: PR merged → SFTP `edublink-child` → wp-cli cache flush → verify on URL | Custom — **P0** |
| **PreToolUse hook: SSH guard** | hook | Block `rm -rf`, `crontab -e`, `wp db drop`, `chmod 777` on `ssh learnsimply` per CLAUDE.md rules | Add to `~/.claude/hooks/hooks.json` — **P0** |
| **PostToolUse hook: bugs-report sync** | hook | When any `bugs-*.md` edited → auto-roll-up to `bugs-report.md` totals | Custom — **P1** |
| **Project `.claude/settings.json`** | config | Pin allowedTools (gh, wp-cli over ssh, mysqldump), set `additionalDirectories`, brand-scoped permissions | Create `brands/learn-simply/.claude/settings.json` — **P0** |
| **Project `.claude/skills/learn-simply-platform.md`** | skill | Single source of truth: SSH alias, paths, plugin list, DB creds vault ref, deploy URLs | Create per-project skill — **P0** |
| **`mcp__claude_ai_WordPress__*` or equivalent** | MCP | Direct WP REST access from Claude (alternative: use SSH+wp-cli via Bash) | No official; **defer** — wp-cli over SSH already works |
| **Stripe MCP (already partial)** | MCP | Not relevant (Kashier is the gateway) | skip |
| **Telegram Bot MCP** | MCP | Direct Telegram channel posting + Mautic ↔ Telegram bridge | `npx telegram-mcp` (community) — **P1** |
| **`hookify-rules` skill** | already installed | Use to write the SSH-guard + bugs-report hooks above | Already installed — wire it up — **P0** |
| **`continuous-learning-v2` skill** | already installed | Capture lessons per session into per-project instincts (avoids polluting other brands' lessons) | Already installed — enable project scope — **P1** |

---

## Recommended immediate setup actions (next 30 min)

1. **Create `brands/learn-simply/.claude/settings.json`** with: scoped Bash allowlist for `wp` (over `ssh learnsimply`), `gh`, `mysqldump`; deny `rm -rf`, `wp db drop`, `chmod 777`.
2. **Create per-project skill `learn-simply-platform`** holding the SSH alias, WP path, current plugin list, deploy URL, vault refs — so every session loads it via SKILL.md.
3. **Add SSH-guard PreToolUse hook** (using already-installed `hookify-rules`) enforcing the CLAUDE.md no-destructive-without-confirm rule.
4. **Spin up `wordpress-patterns` + `woocommerce-ops` + `mautic-patterns` custom skills** — even thin stubs collecting today's lessons unblock all future Sprint 2/3 work.
5. **Wire `/ls-status` slash command** to surface KPIs (revenue, cart count, Kashier failures, pixel-fire status) — solves the recurring "where are we?" overhead.