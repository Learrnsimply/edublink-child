#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
eval_rag.py — golden-set retrieval eval for the `kb_search` RAG endpoint.

WHY THIS EXISTS
---------------
Omar's architecture question ("can we move ALL facts into RAG and keep the
prompt instructions-only?") is answerable only with measurements. This script
holds a fixed golden set of questions in two groups:

  - facts      : prices / bundles / links / payment / policies / hours / Dart
                 (the data currently inline in the system prompt)
  - curriculum : deep 336-lesson curriculum questions (RAG's original job)

It POSTs each question to the live kb-search webhook (same endpoint the agent
uses), checks whether any expected marker appears in the top-k results, and
reports hit@1 / hit@k / MRR per group. Run BEFORE and AFTER every retrieval
change and compare JSON outputs.

USAGE
-----
    python eval_rag.py                          # run against live endpoint
    python eval_rag.py --out baseline.json      # also save per-question results
    python eval_rag.py --k 5                    # top-k (default 5)
    KB_SEARCH_URL=... python eval_rag.py        # override endpoint

READ-ONLY: this script only performs search queries. No DB writes.
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import time
import urllib.request

for _s in (sys.stdout, sys.stderr):
    try:
        _s.reconfigure(encoding="utf-8")  # type: ignore[attr-defined]
    except Exception:
        pass

DEFAULT_URL = "https://n8n.learrnsimply.com/webhook/kb-search-4061a4f2df54"
REQUEST_DELAY_S = 0.4   # be polite to the live endpoint
TIMEOUT_S = 45

# ---------------------------------------------------------------------------
# Golden set. A question is a HIT at rank r if ANY marker (case-insensitive
# substring) appears in heading_path+content of the r-th result. Markers were
# verified to exist in the ingested sources (knowledge-base.md / curriculum).
# ---------------------------------------------------------------------------
QUESTIONS = [
    # --- group: facts (the prompt-inline data Omar wants to move to RAG) ---
    {"id": "F01", "group": "facts", "q": "كورس جافا للمبتدئين بكام؟", "markers": ["450"]},
    {"id": "F02", "group": "facts", "q": "باقة جافا الكاملة فيها إيه وبكام؟", "markers": ["849"]},
    {"id": "F03", "group": "facts", "q": "عايز لينك باقة جافا", "markers": ["java-basics-oop-bundle"]},
    {"id": "F04", "group": "facts", "q": "باقة هياكل البيانات الكاملة بكام؟", "markers": ["900"]},
    {"id": "F05", "group": "facts", "q": "إزاي أدفع من برة مصر؟", "markers": ["العملة", "Kashier", "01030127228"]},
    {"id": "F06", "group": "facts", "q": "رقم فودافون كاش للتحويل اليدوي إيه؟", "markers": ["01030127228"]},
    {"id": "F07", "group": "facts", "q": "رقم الإنستاباي إيه؟", "markers": ["01102681074"]},
    {"id": "F08", "group": "facts", "q": "سياسة الاسترجاع إيه؟", "markers": ["7 أيام", "20%"]},
    {"id": "F09", "group": "facts", "q": "مواعيد رد الدعم إيه؟", "markers": ["السبت", "الخميس"]},
    {"id": "F10", "group": "facts", "q": "كورس Dart هينزل إمتى وبكام؟", "markers": ["DART50", "15 يونيو", "700"]},
    {"id": "F11", "group": "facts", "q": "فين جروب التليجرام بتاع الطلاب؟", "markers": ["t.me/Et3lambBsata"]},
    {"id": "F12", "group": "facts", "q": "فين إنستجرام أحمد وكل لينكاته؟", "markers": ["linktr.ee"]},
    {"id": "F13", "group": "facts", "q": "الشهادة معتمدة ولا لأ؟", "markers": ["إتمام", "completion", "معتمدة"]},
    {"id": "F14", "group": "facts", "q": "كورس هياكل البيانات المستوى الأول بكام؟", "markers": ["550"]},
    # --- group: curriculum (deep-curriculum retrieval — RAG's original job) ---
    {"id": "C01", "group": "curriculum", "q": "Linked List موجودة في أنهي وحدة؟", "markers": ["Linked List"]},
    {"id": "C02", "group": "curriculum", "q": "بتشرحوا الـ Stack في أنهي وحدة؟", "markers": ["Stack"]},
    {"id": "C03", "group": "curriculum", "q": "فيه شرح للـ Queue؟", "markers": ["Queue"]},
    {"id": "C04", "group": "curriculum", "q": "كورس OOP فيه inheritance؟", "markers": ["inheritance", "الوراثة"]},
    {"id": "C05", "group": "curriculum", "q": "فيه شرح Polymorphism؟", "markers": ["Polymorphism"]},
    {"id": "C06", "group": "curriculum", "q": "المتغيرات في جافا بتتشرح فين؟", "markers": ["المتغيرات"]},
    {"id": "C07", "group": "curriculum", "q": "شرح الـ Loops في أنهي وحدة في كورس جافا؟", "markers": ["Loops"]},
    {"id": "C08", "group": "curriculum", "q": "فيه وحدة عن الـ if والـ switch؟", "markers": ["switch"]},
    {"id": "C09", "group": "curriculum", "q": "الـ Double Linked List متغطية في الكورس؟", "markers": ["Double Linked List"]},
    {"id": "C10", "group": "curriculum", "q": "وحدة الـ Functions في كورس جافا فيها إيه؟", "markers": ["Functions"]},
]


def search(url: str, query: str, k: int) -> dict:
    payload = json.dumps({"query": query, "k": k}).encode("utf-8")
    req = urllib.request.Request(
        url, data=payload, headers={"Content-Type": "application/json"}, method="POST"
    )
    with urllib.request.urlopen(req, timeout=TIMEOUT_S) as resp:
        return json.loads(resp.read().decode("utf-8"))


def first_hit_rank(results: list[dict], markers: list[str]) -> int:
    """1-based rank of the first result containing any marker; 0 = miss."""
    for i, r in enumerate(results, start=1):
        haystack = ((r.get("heading_path") or "") + "\n" + (r.get("content") or "")).lower()
        if any(m.lower() in haystack for m in markers):
            return i
    return 0


def main(argv: list[str] | None = None) -> int:
    ap = argparse.ArgumentParser(description="Golden-set eval for kb_search retrieval quality.")
    ap.add_argument("--k", type=int, default=5)
    ap.add_argument("--out", help="save per-question results JSON for before/after diffing")
    ap.add_argument("--url", default=os.environ.get("KB_SEARCH_URL", DEFAULT_URL))
    args = ap.parse_args(argv if argv is not None else sys.argv[1:])

    rows = []
    for q in QUESTIONS:
        try:
            data = search(args.url, q["q"], args.k)
            results = data.get("results") or []
            note = data.get("note") or ""
        except Exception as e:  # endpoint/network failure = recorded miss, eval continues
            results, note = [], f"request failed: {e}"
        rank = first_hit_rank(results, q["markers"])
        top_sim = results[0].get("similarity") if results else None
        rows.append({
            "id": q["id"], "group": q["group"], "q": q["q"], "rank": rank,
            "top_similarity": top_sim, "n_results": len(results), "note": note,
        })
        status = f"hit@{rank}" if rank else "MISS"
        sim = f" top_sim={top_sim:.2f}" if isinstance(top_sim, (int, float)) else ""
        print(f"[{q['id']}] {status:<6}{sim}  {q['q']}")
        time.sleep(REQUEST_DELAY_S)

    def summarize(group: str | None) -> dict:
        sel = [r for r in rows if group is None or r["group"] == group]
        n = len(sel)
        hits5 = sum(1 for r in sel if r["rank"])
        hits1 = sum(1 for r in sel if r["rank"] == 1)
        mrr = sum(1.0 / r["rank"] for r in sel if r["rank"]) / n if n else 0.0
        return {"n": n, "hit@1": hits1 / n, f"hit@{args.k}": hits5 / n, "mrr": round(mrr, 3)}

    summary = {
        "facts": summarize("facts"),
        "curriculum": summarize("curriculum"),
        "overall": summarize(None),
    }
    print("\n=== SUMMARY ===")
    for name, s in summary.items():
        print(f"{name:<11} n={s['n']:>2}  hit@1={s['hit@1']:.0%}  hit@{args.k}={s[f'hit@{args.k}']:.0%}  MRR={s['mrr']}")

    if args.out:
        with open(args.out, "w", encoding="utf-8") as fh:
            json.dump({"summary": summary, "rows": rows, "k": args.k}, fh, ensure_ascii=False, indent=2)
        print(f"\nsaved: {args.out}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
