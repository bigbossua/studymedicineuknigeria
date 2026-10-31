# Final readiness report (updated 2026-10-31)

**Scope:** StudyMedicineUKNigeria.com, branch `claude/new-session-p6gdm6`. **Production:** release `2026-10-28T11-12-26` (commit `5d76858`), unchanged.

**Not done** (owner rules): no deploy, no production data change, no live Stripe change or transaction, no refund, and no email to a student, lead or university. This cycle's work is listed in `NEXT-EXECUTION-LOG.md`.

## 1. Verdicts (two separate decisions)

| Decision | Verdict | Why |
|---|---|---|
| **Technical deployment** of the branch head | **Ready, awaiting the owner's explicit approval** | Tests, rehearsal, journeys, SEO and security checks pass (§3). After the deploy: 38 sitemap URLs; Cardiff noindex because its fee was withdrawn; new payments closed by the gate. Rollback undoes fact decisions; the deploy only reads Stripe; no migrations |
| **Commercial launch** (accepting student payments) | **NOT READY** | These are unresolved (`OWNER-ACTION-REGISTER.md`, items marked L):<br>• legal identity and ICO;<br>• legal review, cancellation form, complaints procedure and ADR position;<br>• the contract-confirmation email;<br>• Stripe details and a real test-mode run;<br>• the £125 rehearsal.<br>After the deploy, the code itself keeps payments closed until the owner sets `SITE_COMMERCIAL_LAUNCH_APPROVED` |

## 2. What changed since `92e4c7f`

| Area | Change | Evidence |
|---|---|---|
| **Payment safety** | Production starts no new payment, by card or bank transfer, until the owner records the commercial-launch approval. Payments made, checkouts in flight, refunds and paid access are preserved. Exposure today is nil in practice (0 students, 0 applications on production) | `CommercialLaunchGateTest` (4); `STRIPE-TEST-AND-LIVE-REHEARSAL.md` §2a (opening, recovery, rollback) |
| **Source watcher** | Follows redirects and checks values where a page lands. *Operations summary* reports the watcher's view of the indexed schools' sources. That report showed the server is refused (HTTP 403) by Cambridge, Cardiff, Queen's Belfast and five more hosts, which have never been watched. A new runner-side *Blocked sources watch* covers them twice a week | `SourceWatcherTest` (+2); runs 41168271747 and 41168890758 |
| **Cardiff** | The 2026/27 overseas fee is withdrawn; 2027/28 is "awaiting confirmation". Three facts are recorded as source changed (nothing inferred). Register D08 is DRAFT; Cardiff moved to "withdrawn" in the publication list. The page stays published and noindex | `decisions-2026-10-31-cardiff.csv`; `reviews/2026-10-31-cardiff.json`; mirror |
| **Leads** | Three individual drafts were generated on the server and encrypted with the backup passphrase. The log shows only `drafts=3`; nothing was sent | run 41168273841; artifact 13029637886 (expires 7 Nov) |
| **Backups** | The 30 October failure was an SSH timeout, not a backup fault. Re-run: success, with its restore test | run 41030369264, attempt 2 |
| **Semrush** | The manual export/import route is extended to school-level evidence: 36 themes tied to register rows. No API units used or bought | `data/semrush/README.md`; `SemrushImportTest` (+2) |

## 3. Verification results (2026-10-31, exact)

| Check | Result |
|---|---|
| Full test suite (`phpunit`) | **296 passed**, 19,789 assertions, 0 failed |
| Formatting (`pint --test`) | passed |
| Dependency audits | `composer audit`: no advisories; `npm audit --omit=dev`: 0 vulnerabilities |
| Production rehearsal | "rehearsal: all production checks passed" (44 ok, 0 fail) |
| Browser journeys (stand-in Stripe) | `public-journey`, `journey`, `staff-journey`, `payment-journey` phone and desktop: `problems: []`. `stripe-e2e`: T1 12500, T2 69500 and T3 129500 all SUCCEEDED, test mode |
| Google readiness (`google-audit.py`, production mode, post-deploy mirror) | **85 passed, 0 failed**; 38 sitemap URLs, 85 internal URLs |
| Sitemap and indexation (mirror) | 38 URLs; `docs/seo/PAGE-AUDIT.md` regenerated (38 pages, 0 flagged). Indexed school pages: Aberdeen, Brighton and Sussex, Buckingham, Edinburgh, Manchester, plus the three owner exceptions. Cardiff: `noindex, nofollow` |
| Owner exceptions | Cambridge (D14), King's College London (D27) and Queen's Belfast (D34) still indexed by exception; `SchoolRegisterGateTest` pins exactly these three; no demand evidence claimed |
| Sitemap-probe self-test | 3/3 |
| Live operations | Uptime success (30 Oct 23:43); *Live verification* success (30 Oct); sitemap probe success (30 Oct); backup re-run success with restore test |
| *Operations summary* | runs 41167257013 and 41168271747: no new flags since 30 October; 0 students; 3 leads |
| *Blocked sources watch* | run 41168890758 **fails, as designed**, on 18 quotes:<br>• **Cardiff:** 4 (3 corrected now; the IB wording is open);<br>• **Hertfordshire:** 3;<br>• **City St George's:** 2;<br>• **Leicester:** 9, all bot-checked ("Just a moment…"), now reported as unreachable.<br>None of these is on an indexed page except Cardiff, which is corrected |
| CI | the intermediate push `5ce3b62` was red (register row not yet updated) and was fixed in `8abff80` (green). The final head's run is in the final report |

## 4. Failures, limits and open items, stated plainly

- **Payments were not tested against real Stripe.** There is no `STRIPE_TEST_SECRET`; the stand-in only.
- **No Semrush keyword data exists.** `no_api_units`. The owner's web-interface export is the route.
- **Watcher blind spots:**
  - Leicester refuses both the server and GitHub runners, so its facts can only be read by hand;
  - the other refused hosts are now covered twice a week from runners, not nightly.
- **Changed wording not yet reviewed:**
  - Hertfordshire (3 quotes);
  - City St George's (2);
  - Cardiff's IB wording.

  None of these is on an indexed page.
- **The 2027/28 overseas fee for Cardiff is not published yet.** Cardiff stays noindex until it is.
- **Pending legal review:** every legal-wording item in the legal review pack (`LEGAL-REVIEW-PACK.md`).
- **Rollback caveat.** Rolling the code back removes the payment gate, and live payments would reopen. In that case remove the live key instead (`STRIPE-TEST-AND-LIVE-REHEARSAL.md` §2a).

## 5. Recommendation

1. **Today, no deploy needed:** send the three lead replies. Decrypt the drafts (`STUDENT-JOURNEY-TEST-REPORT.md` §3), review them and send from the business mailbox.
2. **Deploy:** approve the deploy of the branch head named in the final report, after a fresh *Operations summary*. It closes new payments by code, hides Cardiff's withdrawn fee and is reversible.
3. **Commercial launch:** supply O1–O4 (identity, ICO) first, which unlocks the legal review (O8–O12) and the Stripe details (O13). Set `SITE_COMMERCIAL_LAUNCH_APPROVED` only after every B row in `FINAL-LAUNCH-CHECKLIST.md` is evidenced.
