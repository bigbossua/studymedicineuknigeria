# Conversion / funnel strategy

Status: DECISION DRAFT (brief 58 item 17; sections 20, 21, 56, 75, 104, 105, 112, 121).

## 1. The funnel we measure

```
Google impression (NG) → organic click → landing page → [Answer] → contextual CTA click
→ Eligibility check started → Eligibility check completed (LEAD)
→ Account created → Tier selected → Payment succeeded
→ Application started → Application complete → Documents complete
→ Approval requested → Student approved → Submitted → University response
```
Every arrow is a GA4 event (12.9) and a server-side event. The headline KPI is **qualified applications** (= STUDENT_APPROVED), not traffic.

## 2. Page pattern: Search → Answer → Evidence → Decide → Act (brief 104)

1. **Answer in the first screen**: the H1 restates the query; the first paragraph answers it directly (e.g. "Most UK medical schools do not accept WASSCE alone for entry to Medicine; they treat it as GCSE-equivalent and require A-levels, IB or an approved foundation year. Here is what each school publishes.").
2. **Evidence**: tables with Official source + Last verified; per-university statements; no generalisations.
3. **Decide**: a "What this means for you" block with 2–3 scenarios (WAEC only / WAEC + A-levels / Nigerian degree) linking to the relevant next pages.
4. **Act**: one contextual CTA band (19.5 copy patterns) → Apply Online or Check Eligibility. At most one CTA band per ~2 screens; one floating CTA.

## 3. The eligibility check (lead capture that is genuinely useful)

5–7 questions, no account needed, ~90 seconds: highest qualification (WAEC/NECO only · A-levels/IB · Nigerian degree · foundation), subjects/grades band, English evidence, UCAT taken?, intake year, email + WhatsApp (optional phone). Output page (instant, rule-based, cautious): which **route categories** appear open based on published requirements (standard entry / graduate entry / foundation first / none without further qualifications), the pages to read, and the **suggested tier** — wording "suggested based on your answers; a qualified reviewer confirms eligibility". Creates a `lead`; offers account creation to save the result. No fabricated eligibility (brief 66).

## 4. CTA placement rules

- Primary CTA text is always **APPLY ONLINE** (brief 20); on requirements pages the band may use **CHECK YOUR ELIGIBILITY** as the primary with Apply Online in the nav.
- Floating CTA on public pages only; hidden within forms, near footer, and in portal.
- No pop-ups, no exit-intent modals, no countdown timers (trust first).
- WhatsApp as secondary, deep-linked with page context; staff log the enquiry to the lead.

## 5. Friction reduction specific to Nigerian users

Mobile-first forms; autosave; magic-link login; WhatsApp number field with country code default +234; file upload via camera; payment fallback for card declines (15.2); light pages for data cost; clear GBP pricing with approximate NGN; no requirement to upload anything before seeing the checklist.

## 6. Trust elements on every commercial page

Company identity and UK contact; what we are (independent application-support service) and are not (not an agent of any university unless stated); transparent prices and refund terms; verification dates; privacy; no testimonials or numbers until genuine ones exist (brief 114).

## 7. Experiments (later, once traffic exists)

A/B only on CTA copy and band position; never on factual content. Minimum 200 conversions per arm before decisions.

## 8. Reporting

Monthly funnel table (impressions NG, clicks, landing sessions, eligibility starts/completes, accounts, payments by tier, applications complete, approvals, submissions) in admin; GSC query review feeds the asset register (brief 54, 99).
