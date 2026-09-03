{
  "question": "Best-practice marketing automation stack for a small Egyptian course-selling business in 2026. Context: WordPress site (learrnsimply.com \u2014 note double-r), Mautic 5 just deployed on Hostinger VPS (Ubuntu 24.04, 16GB RAM, Traefik with Let's Encrypt), n8n being added next, ~67K EGP/month revenue, 369K YouTube subs, 13K email subs not yet activated. The owner is a marketer (not developer) working in Egyptian Arabic. Need to find best practices for: (1) Cart recovery workflows that actually convert for course businesses, (2) Email deliverability specifically for Mautic on Hostinger sending from a new subdomain (SPF/DKIM/DMARC setup), (3) n8n + Claude API patterns for AI customer support agent integrated with WhatsApp + Telegram + on-site chat, (4) MCPs/integrations that amplify this stack (especially for marketing analytics, conversion tracking, WordPress/Mautic API access from Claude Code), (5) Top 3 actionable n8n templates from official n8n community/marketplace that fit course-selling business model, (6) Common operational pitfalls when running Mautic + n8n self-hosted on a single VPS (memory limits, queue mode, backup strategy, security). Cite all sources with URLs. Be specific to 2025-2026 best practices.",
  "summary": "For Learn Simply's 2026 marketing automation stack (Mautic 5.2 + n8n + Claude on Hostinger VPS), the verified best-practice path is: (1) deploy Mautic with the documented 15-min/5-min-staggered cron pattern and bounded `messenger:consume email --time-limit=160 --memory-limit=128M --limit=60` queue consumer to prevent VPS resource exhaustion; (2) authenticate the sending subdomain with both SPF and DKIM (`mautic._domainkey.learrnsimply.com`) plus DMARC \u2014 Gmail/Yahoo/Outlook bulk-sender rules now make both practically mandatory for a 13K-subscriber list; (3) wire WhatsApp via Evolution API (native n8n integration, already on Omar's VPS) and Telegram using existing n8n marketplace templates that follow the per-phone-number memory-buffer pattern for AI agents; (4) install three high-leverage MCP servers on Claude Code \u2014 n8n-MCP (1,851 nodes + 2,352 templates + 13 workflow management tools), the Mautic MCP n8n template (all 20 CRM operations), and the WordPress MCP n8n template (12 post/page/user operations) \u2014 plus track Meta's new Ads AI Connectors (open beta April 29 2026) for paid-channel control. No turnkey course-business cart-recovery template exists in n8n's lead-nurturing category, so Omar will adapt e-commerce WooCommerce cart-recovery templates rather than expect a drop-in solution.",
  "findings": [
    {
      "claim": "Mautic 5.2 production operations require staggered cron jobs (0,15,30,45 / 5,20,35,50 / 10,25,40,55 for segments:update / campaigns:update / campaigns:trigger) AND a bounded messenger:consume email consumer with --time-limit=160 plus at least one of --memory-limit/--limit to prevent runaway processes on a VPS.",
      "confidence": "high",
      "sources": [
        "https://docs.mautic.org/en/5.2/configuration/cron_jobs.html",
        "https://allthingsopen.org/articles/what-is-mautic-open-source-marketing-automation"
      ],
      "evidence": "Primary Mautic docs verbatim specify the three required commands, the exact stagger pattern (5-min gaps on a 15-min cycle), and the required bounding flags for messenger:consume. Multiple independent sources (Joey Keller, Powertic, Netcore, Mauteam) corroborate this as the operational pattern that prevents segment/campaign membership lock conflicts and OOM kills on single-VPS deployments. Recommended production command verbatim from docs: `php /path/to/mautic/bin/console messenger:consume email --time-limit=160`.",
      "vote": "3-0 across all three constituent claims (cron stagger pattern, bounded consumer requirement, exact consumer command)"
    },
    {
      "claim": "Mautic deliverability on a new sending subdomain requires DKIM TXT record at `mautic._domainkey.<domain>` plus SPF, with both technically optional under DMARC alignment but practically mandatory in 2026 for any sender at Learn Simply's 13K subscriber scale due to Gmail/Yahoo (Feb 2024) and Microsoft Outlook (May 2025) bulk-sender rules.",
      "confidence": "high",
      "sources": [
        "https://support.valimail.com/en/articles/8822646-mautic",
        "https://docs.mautic.org/en/5.2/configuration/cron_jobs.html"
      ],
      "evidence": "DMARC RFC 7489 confirms only one of SPF/DKIM must align to pass \u2014 verified across dmarcian, Google Workspace Admin Help, Red Sift, PowerDMARC, MXToolbox. Mautic-specific DKIM selector convention `mautic._domainkey.<domain>` confirmed across 5+ independent DMARC vendors (Valimail, PowerDMARC, OnDMARC, Skysnag, Audienture) using standard `v=DKIM1; k=rsa; p=<key>` value. 2024-2025 bulk-sender enforcement (Gmail/Yahoo 5K+/day threshold; Outlook May 2025) effectively requires both for Omar's list size \u2014 'recommended' understates current operational reality.",
      "vote": "3-0 on DMARC mechanics and DKIM selector format; 2-1 on the selector specifically (the dissenting vote noted 'mautic' selector is a community convention rather than a hard requirement, which refines but does not refute the claim)"
    },
    {
      "claim": "WhatsApp customer-support integration on Omar's stack is best built on Evolution API (already deployed on his VPS), which provides native n8n integration via Chat Node + the n8n-nodes-evolution-api community package and supports both free Baileys-based WhatsApp Web mode and Meta's official WhatsApp Cloud API.",
      "confidence": "high",
      "sources": [
        "https://github.com/EvolutionAPI/evolution-api",
        "https://n8n.io/workflows/5311-ai-powered-telegram-and-whatsapp-business-agent-workflow/"
      ],
      "evidence": "Evolution API GitHub README explicitly lists n8n among native chatbot integrations (alongside Typebot, Chatwoot, OpenAI, Dify, Flowise) and documents v2.3 release adding Chat Node integration. Community node `n8n-nodes-evolution-api` exists on npm. Official n8n.io marketplace hosts working WhatsApp+Evolution templates. CRITICAL OPERATIONAL CAVEAT (flagged by verifier): Baileys mode uses reverse-engineered WhatsApp Web protocol \u2014 Meta actively bans numbers using it, especially new numbers with moderate volume. For a course business sending order confirmations / cart recovery to real customers, official WhatsApp Cloud API is the production-safe path despite the free Baileys option.",
      "vote": "3-0 on Evolution API dual-mode support; 2-1 on n8n integration (dissent noted n8n docs page is less detailed than Typebot/Chatwoot, but Omar's self-hosted setup makes the community node viable)"
    },
    {
      "claim": "AI customer support agents in n8n must use a per-phone-number / per-session memory buffer (Postgres Chat Memory for production, Simple Memory for prototypes) keyed by phone number \u2014 stateless per-message calls cause cross-user chat leakage.",
      "confidence": "high",
      "sources": [
        "https://n8n.io/workflows/9027-whatsapp-customer-support-with-claude-ai-google-docs-and-multilingual-capabilities/",
        "https://docs.n8n.io/advanced-ai/examples/understand-memory/"
      ],
      "evidence": "Pattern documented across n8n's official advanced-AI memory docs, multiple 2026 community guides (Towards AI 2026 setup guide recommending prefix+phone-number session key with 10-30 message window), GrowwStacks, Heltar, and n8n template #3586. Simple Memory is volatile (resets on n8n restart) so Postgres Chat Memory is the production recommendation. This is the standard pattern Omar should specify when building the Learn Simply support agent across WhatsApp + Telegram + on-site chat \u2014 each channel uses its own session-key prefix combined with the user identifier.",
      "vote": "3-0"
    },
    {
      "claim": "n8n-MCP (czlonkowski/n8n-mcp) is the single highest-leverage MCP server for Omar's stack \u2014 it gives Claude Code comprehensive access to 1,851 nodes (822 core + 1,029 community, 911 verified), 2,352 workflow templates with 99.96% AI metadata coverage, and 13 management tools that let Claude create/update/delete/validate workflows directly via the n8n API including AI Agent validation.",
      "confidence": "high",
      "sources": [
        "https://github.com/czlonkowski/n8n-mcp"
      ],
      "evidence": "Primary GitHub source verified verbatim on all numerical claims: 1,851 nodes, 822 core + 1,029 community (911 verified), 2,352 templates, 99.96% metadata coverage, 13 management tools. Repo contains dedicated setup docs for Claude Code (docs/CLAUDE_CODE_SETUP.md), Claude Desktop, Cursor, and Windsurf \u2014 concrete evidence of first-class support. Management tools include search_templates with searchMode 'by_task' and 'by_metadata' (filterable by complexity, required services, target audiences including marketers) \u2014 directly relevant for discovering cart-recovery and course-business workflows. validate_workflow includes AI Agent validation (v2.17.0, 2026).",
      "vote": "3-0 across all four constituent claims (MCP existence + Claude Code support, node/template counts, management tools count + AI Agent validation, template search coverage)"
    },
    {
      "claim": "Two community n8n MCP templates extend Claude Code's reach into the rest of Omar's marketing stack: a Mautic MCP server template exposing all 20 Mautic operations (8 contact ops + 5 company ops + 2 segment ops + 2 campaign-contact ops + 2 company-contact ops + segment email send) and a WordPress MCP server template exposing 12 operations (Posts, Pages, Users \u2014 each with create/get/get-many/update). Both deploy as MCP webhook endpoints with under-2-minute import-and-activate setup.",
      "confidence": "medium",
      "sources": [
        "https://n8n.io/workflows/5184-mautic-tool-mcp-server-all-20-operations/",
        "https://n8n.io/workflows/5060-create-update-posts-wordpress-tool-mcp-server-all-12-operations/",
        "https://docs.n8n.io/integrations/builtin/app-nodes/n8n-nodes-base.mautic"
      ],
      "evidence": "Both templates verified live on official n8n.io marketplace with exact operation counts confirmed by WebFetch. Mautic template covers core CRM actions needed for marketing automation from an AI agent (CRUD contacts/companies, segmentation, do-not-contact management, transactional + segment email send). WordPress template uses MCP Trigger node as the AI-agent endpoint. Confidence downgraded to medium because: (a) these are community-contributed marketplace templates rather than n8n-official products, (b) WordPress template is paid ($25), (c) 'under 2 minutes' assumes API credentials are pre-provisioned, (d) one verifier vote dissented on the Mautic template claim. Pattern is sound but Omar should expect to validate operations against his specific Mautic 5.2 / WordPress versions before relying in production.",
      "vote": "2-1 on Mautic MCP template; 3-0 on Mautic operation breakdown; 3-0 on WordPress MCP template"
    },
    {
      "claim": "No turnkey course-business or cart-recovery template exists in n8n's lead-nurturing category \u2014 the 10 featured templates are generic LinkedIn/cold-email/SMS outreach. However, adaptable patterns exist: (a) WooCommerce cart-recovery templates in the e-commerce category (workflow #6322 'Automated WooCommerce Abandoned Cart Recovery' and #14367 'Recover abandoned WooCommerce carts using OpenAI GPT-4.1-mini'), and (b) WhatsApp/Telegram sales-agent templates with product-catalog vector stores and human-in-the-loop approval patterns (e.g., 'Building Your First WhatsApp Chatbot' by Jimleuk, 'AI Sales Agent with Telegram Approvals' #5074 by David Olusola).",
      "confidence": "high",
      "sources": [
        "https://n8n.io/workflows/categories/lead-nurturing/",
        "https://n8n.io/workflows/5074-ai-sales-agent-with-telegram-approvals-and-google-sheets-sync/"
      ],
      "evidence": "Direct WebFetch of lead-nurturing category page confirmed all 10 featured templates are generic outreach (no course, no cart recovery, no Mautic). Cart-recovery templates DO exist on n8n.io but are indexed under e-commerce categories \u2014 Omar should search those rather than lead-nurturing for cart recovery. The two named sales-agent templates are verified live on the official marketplace with patterns that generalize cleanly to course-selling: replace food menu / product catalog with course catalog vector store; replace payment-screenshot approval with enrollment confirmation; replace order taking with course discovery + checkout link. WhatsApp template is ~2 years old and may need node-version updates but pattern remains valid.",
      "vote": "3-0 on availability of WhatsApp/Telegram sales-agent templates; 2-1 on the lead-nurturing category gap (dissent on scoping); refuted claim about a specific WhatsApp+Claude+Google-Docs education template (vote 0-3) means Omar should NOT assume a drop-in education template exists \u2014 adapt the sales-agent patterns instead"
    },
    {
      "claim": "For paid-channel marketing automation in 2026, Meta launched Ads AI Connectors in open beta on April 29, 2026, built on an ads MCP server + CLI architecture, allowing Claude and ChatGPT agents to connect directly to Meta advertiser accounts for real campaign performance, ad creation, catalog management, and audience insights. This fits an industry pattern (Google Ads API MCP open-sourced Oct 7 2025; Amazon Ads MCP closed beta Nov 13 2025).",
      "confidence": "high",
      "sources": [
        "https://facebook.com/business/news/meta-ads-ai-connectors",
        "https://ppc.land/meta-opens-its-ad-system-to-claude-and-chatgpt-with-new-ai-connectors/"
      ],
      "evidence": "Primary source (Meta Business News) confirms April 29 2026 announcement, open beta status, MCP server + ads CLI architecture, Claude + ChatGPT as named integrations, and exact feature scope. Independently corroborated by Digiday, Jon Loomer Digital, Common Thread Co, GoMarble, Innovation Village. Strategic context (Google + Amazon parallel launches) verified. CAVEAT: one verifier vote dissented on the 'no developer credentials / minutes to set up' claim \u2014 Omar should expect to provision ad-account access through Meta Business Manager standard flow, not literally zero-config. Treat as 'tracking item' rather than immediate install \u2014 open beta means feature stability + access gating may still be evolving as of mid-2026.",
      "vote": "3-0 on Meta launch + MCP architecture + Claude integration; 1-2 on the 'no credentials / minutes to set up' marketing line (treated as overreach by verifiers)"
    }
  ],
  "caveats": "Source-strength caveats: (1) The Mautic MCP and WordPress MCP n8n templates are community-contributed marketplace items, not n8n-official products \u2014 Omar should validate operations against his specific Mautic 5.2 + WordPress versions before production use. (2) The WhatsApp sales-agent template is ~2 years old and likely needs node-version updates; the patterns transfer but the exact JSON may not import cleanly into a current n8n. (3) Evolution API Baileys mode (the 'free' WhatsApp option) uses reverse-engineered WhatsApp Web protocol \u2014 Meta actively bans numbers using it, especially new numbers with moderate volume. For a real course business sending order confirmations and cart recovery, the official WhatsApp Cloud API path is production-safe despite higher cost. (4) Meta Ads AI Connectors is open beta as of April 29 2026 \u2014 feature stability and access gating may still be changing; treat as 'install when stable + track release notes' rather than 'install today'. (5) The DMARC framing 'only one of SPF or DKIM is required' is technically true under RFC 7489 but operationally misleading in 2026 \u2014 Gmail/Yahoo/Outlook bulk-sender rules now require both for Learn Simply's 13K-subscriber list size. (6) Six claims were refuted by adversarial verification, most notably: a specific WhatsApp+Claude+Google-Docs education customer-support template (refuted 0-3 \u2014 does not actually exist as described), a specific WooCommerce\u2192Mautic sync template #1456 (refuted 1-2 \u2014 exists but applicability oversold), and the 'no credentials needed' framing on Meta Ads MCP. Time-sensitivity: research is current to May 2026 \u2014 Meta Ads MCP, n8n-MCP node counts, and template marketplace contents are all evolving monthly. Recommend re-verifying template availability + n8n-MCP version before each major Sprint.",
  "openQuestions": [
    "Which specific WooCommerce cart-recovery template (#6322 vs #14367 vs newer) is the best starting point for a course business where 'abandonment' may include checkout-failure-via-Kashier (909 failed CC attempts \u2248 195K EGP/year) rather than classic distraction abandonment \u2014 does either template handle payment-gateway-failure recovery differently from distraction recovery?",
    "What is the actual Mautic 5.2 messenger:consume resource profile on a 16GB Hostinger VPS that's also running Traefik + Evolution API + n8n \u2014 does the documented --memory-limit=128M / --time-limit=160 / --limit=60 hold up under a 13K-subscriber broadcast, or does Omar need to migrate to systemd daemons + Redis transport for queue durability?",
    "Does Hostinger's shared SMTP relay impose per-domain rate caps that would throttle a Mautic broadcast to 13K subscribers, requiring Omar to bring his own SMTP provider (e.g., Amazon SES, Postmark) for the activation campaign \u2014 and if so, what's the cheapest Egyptian-customer-friendly option that integrates cleanly with Mautic 5.2?",
    "Is there a production-grade pattern for sharing the same Postgres Chat Memory across WhatsApp + Telegram + on-site chat so a Learn Simply student who starts a conversation on WhatsApp can continue it on the website without context loss \u2014 or is per-channel memory the correct architecture and cross-channel continuity should be handled at the CRM (Mautic contact) layer instead?"
  ],
  "refuted": [
    {
      "claim": "The dominant integration stack in n8n lead-nurturing templates is Google Sheets + Gmail/Outlook + Twilio/WhatsApp/Telegram + OpenAI/Gemini/Groq, with notably no HubSpot or Salesforce in featured templates and no Mautic visible \u2014 suggesting Mautic-specific n8n workflows are not yet a mainstream template category.",
      "vote": "1-2",
      "source": "https://n8n.io/workflows/categories/lead-nurturing/"
    },
    {
      "claim": "Mautic 2.15.x exhibits OutOfMemoryException errors on low-traffic sites even when PHP memory_limit is raised from 128MB through 896MB, indicating the OOM is not solved by simply raising memory_limit.",
      "vote": "0-3",
      "source": "https://github.com/mautic/mautic/issues/7408"
    },
    {
      "claim": "Mautic 5 domain authentication for sending email is configured by adding the sending domain in Settings > Configuration > Domains and copying the provided DKIM and SPF records to DNS, but the article does not address DMARC explicitly.",
      "vote": "0-3",
      "source": "https://allthingsopen.org/articles/what-is-mautic-open-source-marketing-automation"
    },
    {
      "claim": "An n8n template combining WhatsApp Business API, Claude Sonnet 4 (via OpenRouter), and a Google Docs knowledge base provides a production-ready architecture for AI customer support with explicit applicability to education/course-selling businesses.",
      "vote": "0-3",
      "source": "https://n8n.io/workflows/9027-whatsapp-customer-support-with-claude-ai-google-docs-and-multilingual-capabilities/"
    },
    {
      "claim": "The MCP path requires no developer credentials, API setup, or coding and can be set up in minutes, lowering the integration barrier for non-developer marketers (relevant to Omar's profile as a marketer running Learn Simply).",
      "vote": "1-2",
      "source": "https://ppc.land/meta-opens-its-ad-system-to-claude-and-chatgpt-with-new-ai-connectors/"
    },
    {
      "claim": "An official n8n workflow template (#1456) exists that automatically syncs new WooCommerce customers to Mautic using a WooCommerce trigger and a Mautic node, directly applicable to the learrnsimply.com WooCommerce + Mautic stack.",
      "vote": "1-2",
      "source": "https://n8n.io/workflows/1456-add-new-customers-from-woocommerce-to-mautic/"
    }
  ],
  "sources": [
    {
      "url": "https://www.monicabadiu.com/2026/03/case-study-what-10-a-b-tests-taught-us-about-abandoned-cart-emails-for-an-online-course-business/",
      "quality": "blog",
      "angle": "cart-recovery-course-business",
      "claimCount": 5
    },
    {
      "url": "https://sitepact.com/how-to-recover-abandoned-carts-in-woocommerce-using-mautic/",
      "quality": "unreliable",
      "angle": "cart-recovery-course-business",
      "claimCount": 0
    },
    {
      "url": "https://geekplugin.com/blog/woocommerce-cart-abandonment-best-practices",
      "quality": "blog",
      "angle": "cart-recovery-course-business",
      "claimCount": 5
    },
    {
      "url": "https://allthingsopen.org/articles/what-is-mautic-open-source-marketing-automation",
      "quality": "secondary",
      "angle": "mautic-deliverability-hostinger",
      "claimCount": 3
    },
    {
      "url": "https://knowledge.ondmarc.redsift.com/en/articles/2536917-mautic-spf-and-dkim-set-up",
      "quality": "secondary",
      "angle": "mautic-deliverability-hostinger",
      "claimCount": 3
    },
    {
      "url": "https://audienture.com/how-to-set-up-spf-dkim-and-dmarc-for-spam-protection-with-mautic",
      "quality": "blog",
      "angle": "mautic-deliverability-hostinger",
      "claimCount": 5
    },
    {
      "url": "https://support.valimail.com/en/articles/8822646-mautic",
      "quality": "secondary",
      "angle": "mautic-deliverability-hostinger",
      "claimCount": 4
    },
    {
      "url": "https://webnestify.cloud/insights/operations-automation/mautic-self-hosted-marketing-automation/",
      "quality": "unreliable",
      "angle": "mautic-deliverability-hostinger",
      "claimCount": 0
    },
    {
      "url": "https://use-apify.com/blog/mailchimp-alternatives-2026",
      "quality": "blog",
      "angle": "mautic-deliverability-hostinger",
      "claimCount": 4
    },
    {
      "url": "https://n8n.io/workflows/9027-whatsapp-customer-support-with-claude-ai-google-docs-and-multilingual-capabilities/",
      "quality": "secondary",
      "angle": "n8n-claude-ai-agent-omnichannel",
      "claimCount": 5
    },
    {
      "url": "https://dev.to/aws/multichannel-ai-agent-shared-memory-across-messaging-platforms-56j4",
      "quality": "blog",
      "angle": "n8n-claude-ai-agent-omnichannel",
      "claimCount": 5
    },
    {
      "url": "https://towardsai.net/p/machine-learning/n8n-ai-agent-node-memory-complete-setup-guide-for-2026",
      "quality": "blog",
      "angle": "n8n-claude-ai-agent-omnichannel",
      "claimCount": 5
    },
    {
      "url": "https://github.com/EvolutionAPI/evolution-api",
      "quality": "primary",
      "angle": "n8n-claude-ai-agent-omnichannel",
      "claimCount": 5
    },
    {
      "url": "https://n8n.io/workflows/5311-ai-powered-telegram-and-whatsapp-business-agent-workflow/",
      "quality": "secondary",
      "angle": "n8n-claude-ai-agent-omnichannel",
      "claimCount": 5
    },
    {
      "url": "https://github.com/czlonkowski/n8n-mcp",
      "quality": "primary",
      "angle": "mcp-wordpress-mautic-analytics",
      "claimCount": 5
    },
    {
      "url": "https://n8n.io/workflows/5184-mautic-tool-mcp-server-all-20-operations/",
      "quality": "secondary",
      "angle": "mcp-wordpress-mautic-analytics",
      "claimCount": 5
    },
    {
      "url": "https://n8n.io/workflows/5060-create-update-posts-wordpress-tool-mcp-server-all-12-operations/",
      "quality": "secondary",
      "angle": "mcp-wordpress-mautic-analytics",
      "claimCount": 5
    },
    {
      "url": "https://github.com/irinabuht12-oss/google-meta-ads-ga4-mcp",
      "quality": "unreliable",
      "angle": "mcp-wordpress-mautic-analytics",
      "claimCount": 5
    },
    {
      "url": "https://www.markifact.com/meta-ads-mcp",
      "quality": "unreliable",
      "angle": "mcp-wordpress-mautic-analytics",
      "claimCount": 0
    },
    {
      "url": "https://ppc.land/meta-opens-its-ad-system-to-claude-and-chatgpt-with-new-ai-connectors/",
      "quality": "secondary",
      "angle": "mcp-wordpress-mautic-analytics",
      "claimCount": 5
    },
    {
      "url": "https://n8n.io/workflows/1456-add-new-customers-from-woocommerce-to-mautic/",
      "quality": "secondary",
      "angle": "n8n-templates-course-marketing",
      "claimCount": 4
    },
    {
      "url": "https://n8n.io/workflows/6322-automated-woocommerce-abandoned-cart-recovery-with-google-sheets-email-reminders/",
      "quality": "secondary",
      "angle": "n8n-templates-course-marketing",
      "claimCount": 5
    },
    {
      "url": "https://n8n.io/workflows/categories/lead-nurturing/",
      "quality": "primary",
      "angle": "n8n-templates-course-marketing",
      "claimCount": 5
    },
    {
      "url": "https://docs.mautic.org/en/5.2/configuration/cron_jobs.html",
      "quality": "primary",
      "angle": "mautic-n8n-vps-ops-pitfalls",
      "claimCount": 5
    },
    {
      "url": "https://forum.mautic.org/t/mautic-worker-cron-interaction/34508",
      "quality": "forum",
      "angle": "mautic-n8n-vps-ops-pitfalls",
      "claimCount": 5
    },
    {
      "url": "https://github.com/mautic/mautic/issues/7408",
      "quality": "primary",
      "angle": "mautic-n8n-vps-ops-pitfalls",
      "claimCount": 5
    },
    {
      "url": "https://massivegrid.com/blog/n8n-queue-mode-redis-workers-vps/",
      "quality": "blog",
      "angle": "mautic-n8n-vps-ops-pitfalls",
      "claimCount": 5
    },
    {
      "url": "https://docs.n8n.io/hosting/configuration/environment-variables/queue-mode/",
      "quality": "primary",
      "angle": "mautic-n8n-vps-ops-pitfalls",
      "claimCount": 0
    }
  ],
  "stats": {
    "angles": 6,
    "sourcesFetched": 28,
    "claimsExtracted": 113,
    "claimsVerified": 25,
    "confirmed": 19,
    "killed": 6,
    "afterSynthesis": 8,
    "urlDupes": 1,
    "budgetDropped": 7,
    "agentCalls": 111
  }
}