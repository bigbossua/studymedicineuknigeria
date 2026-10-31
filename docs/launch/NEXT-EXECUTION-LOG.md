# Next execution log (2026-10-31)

Branch `claude/new-session-p6gdm6`, starting from `92e4c7f`.

**Not done** (owner rules): no deploy, no production data change, no live Stripe change or transaction, no refund, and no email to a student, lead or university.

## What was found

| # | Finding | Evidence |
|---|---|---|
| F1 | **Payment exposure is real in configuration but nil in practice.** Production has a live Stripe key, so any student staff approved for service selection could pay before the legal and commercial blockers are closed. Production has **0 students and 0 applications** today | *Operations summary* run 41167257013 (`"students": 0`, `"applications_by_stage": []`) |
| F2 | **The 30 October backup failed.** Cause: SSH from the runner timed out (`connect to host … port 65002: Connection timed out`; `ssh-keyscan` also failed). It was a network or firewall drop, not a backup or script fault | run 41030369264, step "Create encrypted backup on the server" |
| F3 | **The source watcher never sees three of the nine indexed schools.** The server's nightly `smukn:sources-check` gets HTTP 403 from Cambridge, Cardiff and Queen's Belfast (and from Leicester, Hertfordshire, Ulster, City St George's and the GMC). Their pages have never been fingerprinted, so their facts were never re-checked automatically | new watcher block in *Operations summary* run 41168271747 (`status 403 … changed -`) |
| F4 | **Cardiff withdrew its 2026/27 overseas fee.** The 2026-entry page forwards to the 2027-entry page: "Fees for overseas status ⏎ We are currently awaiting confirmation on tuition fees for the 2027/28 academic year." The overseas fees page names no Medicine fee | page-excerpts runs 41167881085, 41168636613 (sha256 475ec49ced3c6535) |
| F5 | **A redirect could hide a change from the watcher.** Values were re-checked only when the fingerprint changed, so a page that already redirected when first fingerprinted would never be re-checked | code reading of `CheckSources` |
| F6 | The runner-side watch over the refused hosts (F3) finds **18 recorded quotes no longer matched**:<br>• **Cardiff:** 4, of which 3 are F4;<br>• **Leicester:** 9, but every Leicester page answered "Just a moment…" (HTTP 403 bot check), so they are unreadable, not changed;<br>• **Hertfordshire:** 3;<br>• **City St George's:** 2.<br>None of Leicester, Hertfordshire or City St George's has a page among the indexed URLs | *Blocked sources watch* run 41168890758 |
| F7 | Semrush: still `no_api_units` (30 October); no units bought | — |

## What was changed

| Change | Files | Tests |
|---|---|---|
| **Commercial-launch payment gate** (F1). In production no new payment starts, by card or bank transfer, until `SITE_COMMERCIAL_LAUNCH_APPROVED` records the owner's approval date. Unaffected:<br>• payments already made;<br>• checkouts already started (their signed webhooks still complete them);<br>• refunds, disputes and paid access.<br>The admin checklist shows the gate. *Update server settings* carries the date and then tells waiting students once. *Live verification* expects payments closed while the gate is closed, and keeps its old expectation for an older release | `StripeService::launchApproved`, `config/site.php`, `PaymentController`, admin dashboard and application view, `ops/update-env.sh`, `update-env-hostinger.yml`, `live-verify.yml` | `CommercialLaunchGateTest` (4 tests; mutation-checked) |
| **Watcher follows redirects** (F5): a source that now forwards elsewhere is checked where it lands on every run, first visit included | `CheckSources` | `SourceWatcherTest` (+2) |
| **Watcher health in *Operations summary*** (F3): what the watcher last saw for the indexed schools' sources, and errors by host | `ops-summary.yml` | run 41168271747 |
| **Blocked sources watch** (F3, F6): reads the refused hosts' pages in Chrome on a runner twice a week and fails when a recorded quote is gone. A refused or bot-checked page is reported as unreachable | `ops/verification/blocked-watch.py`, `blocked-sources-watch.yml` | runs 41168655324, 41168890758 (the first passed wrongly, because a pipe hid the exit code; fixed) |
| **Cardiff corrected** (F4). The fee, clinical-years and international-acceptance facts are recorded as **source changed**: hidden, with nothing inferred. Register D08 is DRAFT. Cardiff moved from the publication list to a dated "withdrawn" entry; it stays published and noindex | `decisions-2026-10-31-cardiff.csv`, `reviews/2026-10-31-cardiff.json`, `build-register.py` → register and knowledge graph, `publication.json`, `docs/seo/PAGE-AUDIT.md` | mirror: 38 URLs after the deploy, Cardiff `noindex, nofollow` |
| **Lead reply drafts without exposure** (Workstream 3). One draft per lead marked "new" is built on the server from its own answers, encrypted there with the backup passphrase, and uploaded as an artifact. The log shows only `drafts=3`. The draft omits the account invitation while registration is closed | `ops/lead-drafts.php`, `ops/lead-drafts.sh`, `lead-drafts.yml`, `LeadReplyDraft` | `LeadFollowUpTest` (+3 assertions); run 41168273841 (artifact 13029637886, expires 2026-11-07) |
| **School-level Semrush route** (Workstream 5): 36 Nigeria-database themes, three per school, each tied to the school's register row; the import keeps its own record file | `data/semrush/school-lookup-sheet.csv`, `school-paste-list.txt`, `README.md`, `ImportSemrushLookups` | `SemrushImportTest` (+2) |

**Operations.** The failed backup job was re-run once after SSH worked again: success, with its restore test (run 41030369264, attempt 2). *Operations summary* was run twice (41167257013, 41168271747).

## Commits (all on `claude/new-session-p6gdm6`)

| Commit | What |
|---|---|
| `f52be93` | payment gate; watcher redirects; watcher health; encrypted lead drafts |
| `144c687` | blocked sources watch |
| `fd309aa` | school Semrush sheet; the watch fails properly |
| `5ce3b62` | Cardiff decision file and page audit (**CI red: register row still PUBLISHED**; fixed in the next commit) |
| `8abff80` | Cardiff register row DRAFT and publication list "withdrawn" |
| final | this log and the updated launch documents (see the final report for its SHA and CI run) |

## Verification

See `FINAL-READINESS-REPORT.md` §3 for exact results.
