# Student journey test report (updated 2026-10-31)

**Data:** synthetic only: accounts `journey-…@example.test`, the seeded local demo accounts, and the Stripe stand-in. **Where:** this branch, served locally (`php -S`) on a local SQLite database. Nothing touched production, Stripe or any real person.

**Re-run 2026-10-31** on the current branch, with the payment gate and the watcher changes. All six browser journeys passed with `problems: []`:
- `public-journey`;
- `journey` (student);
- `staff-journey`;
- `payment-journey`, phone and desktop;
- `stripe-e2e` for T1, T2 and T3.

The payment gate is off outside production, so these journeys exercise the full payment path. `CommercialLaunchGateTest` covers the gated production behaviour.

## 1. Results by stage

| # | Stage | How tested | Result | Evidence |
|---|---|---|---|---|
| 1 | New visitor | `public-journey.cjs`, phone: home (Google referrer) → Medicine → Requirements → Medical schools → Fees → Eligibility → Apply Online → Registration, by visible links only | Pass, `problems: []`; no service fee on any public page | log `qa-final/public-journey.cjs.0.log` |
| 2 | Enquiry | `journey.cjs`: eligibility checker → route map "2028 entry" → lead created; admin alerted | Pass | log; `LeadFollowUpTest` |
| 3 | Account creation | register (email prefilled from the enquiry) → email verification → portal | Pass | log: `/email/verify` → `/portal` |
| 4 | Application, save and resume | start → application number SMUKN-2028-000010 → autosave "Saved · just now" | Pass; no fee shown at start (`startPageShowsFee: false`) | log; `ApplicationWorkflowTest` (autosave, resume) |
| 5 | Document upload and handling | passport upload → "received — under review" | Pass; stored on the private disk, encrypted, content-checked | log; `DocumentSecurityTest`, `FinalSecurityAuditTest` |
| 6 | Staff review | `staff-journey.cjs`: admin two-step sign-in → document accepted → profile approved for services (T1 £125, T2 £695, T3 £1,295 shown only now) | Pass | log `flashes` |
| 7 | Status updates | proposal → approval requested → student approves the exact package → submitted with reference UCAS-QA-001 | Pass, `SUBMITTED … auth:yes` | log `status` |
| 8 | Payment | `payment-journey.cjs` phone and desktop (T2 incl. cancel and retry); `stripe-e2e.cjs` T1–T3 (T1 declined card first); paid only by the signed webhook | Pass; the page amount equals the amount charged | `STRIPE-TEST-AND-LIVE-REHEARSAL.md` §3 |
| 9 | Confirmation and service delivery | "Payment received" email; services, messages and submissions pages 200; data export 200 (4,267 bytes JSON) | Pass. **Gap:** the payment email is not the reg. 16 contract confirmation (`LEGAL-REVIEW-PACK.md` C8) | log |
| 10 | Refund or cancellation | The student asks by portal message or email; staff refund in the Stripe Dashboard; `charge.refunded` → REFUNDED_FULL/PARTIAL; paid access ends | Pass in tests (`StripeWebhookTest::…refund…`, `ServicePaymentsTest::test_refunds_are_recorded_once…`, `FinalSecurityAuditTest::test_a_partially_refunded_fee_is_not_charged_again`). Not run live (§6 of the Stripe doc) | tests |
| 11 | Complaints | No complaints procedure is published yet (draft in `LEGAL-REVIEW-PACK.md` §5). Today a complaint arrives as a portal message or email and is handled in Admin → Messages | **Awaiting owner and legal review** | — |

**Status transitions are recorded.** `application_events` for the journey application, in order, each with actor and time:
1. `application.started`
2. `documents.generated`
3. `document.uploaded`
4. `document.accepted`
5. `services.approved`
6. `service.chosen`
7. `payment.initiated`
8. `payment.succeeded`
9. `approval.requested`
10. `student.approved`
11. `submission.sent`

There are also 4 `submission_events` and 2 `admin_actions`. Document views and downloads go to `document_access_log`; this journey had none, because staff accepted without opening the file.

## 2. Privacy and access checks

| Check | Result | Evidence |
|---|---|---|
| Student data visible only to that student and authorised staff | Pass | `ApplicationWorkflowTest::test_another_student_cannot_read_or_write_the_application`; `ServicePaymentsTest::test_another_student_cannot_see_pay_change_or_read_a_return_page`; `PricingVisibilityTest::test_one_students_approval_never_shows_fees_to_another_student` |
| Staff areas need the staff role and two-step verification | Pass | `TwoFactorTest`; `EnsureStaff`, `EnsureTwoFactor` (unchanged) |
| Documents never at public URLs | Pass: private disk, served only through an authorised controller, every access logged | `FinalSecurityAuditTest::test_documents_live_only_on_the_private_disk…`, `::test_uploads_and_downloads_are_written_to_the_audit_trail`; `DocumentSecurityTest` |
| Private pages not indexed or cached | Pass | `PublicSeoTest::test_private_routes_carry_noindex_and_no_store_headers` |
| Emails disclose no unnecessary personal data | Pass. They contain the first name, the application number, document titles and university names; message content is never emailed ("You have a new message in your portal") | `ApplicationNotification`; `NotificationRenderTest` |
| Stripe data minimised | Pass: the stored webhook keeps ids, amounts and statuses, not name, email, address or card | `StripeWebhookController::minimal` |
| Funnel events hold no personal data | Pass | `App\Support\Funnel`; privacy tests |
| Overdue enquiries visible to staff | Pass: dashboard and Admin → Leads highlight leads still "new" after 2 working days | `LeadFollowUpTest` |

## 3. The three unanswered enquiries (Workstream 3; updated 2026-10-31)

**Identified.** *Operations summary* (counts only) shows 3 leads, all still "new" and past the 2-working-day promise. Production has 0 student accounts.

**Individual drafts, produced without exposing anyone.** The repository and its GitHub Actions logs are public, so a lead's name, email or answers must never be printed.

The workflow **Lead reply drafts (encrypted, nothing sent)** (`lead-drafts.yml`, run 41168273841, 31 October):
1. builds one draft per lead **on the server**, from that lead's own answers and the assessment the checker showed them (`App\Support\LeadReplyDraft`, the same code as Admin → Leads after the deploy);
2. encrypts the drafts **on the server** with the backup passphrase (AES-256-CBC, PBKDF2 200k, as the backups are);
3. downloads only the ciphertext, verifies its checksum, deletes it from the server and uploads it as a 7-day artifact.

The log shows only `drafts=3` and the encrypted file's path. The artifact is `lead-drafts-encrypted-41168273841` (id 13029637886, expires 7 November 2026).

**What each draft contains:**
- the address it should go to and the date of the enquiry;
- an apology for the delay;
- the lead's answers;
- the routes the checker showed, with their published-requirement explanations;
- links to the requirement pages;
- the statement that only universities decide.

Because student registration is still closed on production, the drafts do **not** invite the lead to create an account (no dead link). They invite a reply instead. Nothing was sent.

**Owner action (`OWNER-ACTION-REGISTER.md` O17):**
1. GitHub → Actions → the run → Artifacts → download.
2. Decrypt: `openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in lead-drafts-*.tar.enc | tar -xf -`
3. Read and edit each `drafts/lead-N.txt`, send it from the business mailbox, then set the lead to "contacted" in Admin → Leads.

**Tested.** `LeadFollowUpTest` covers:
- the draft's content;
- the registration-closed variant;
- that nothing is sent.

The generator was also simulated end to end locally: the plaintext was removed, the ciphertext did not contain the address, and it decrypted with the passphrase.

## 4. Notifications and delivery handling (checked 2026-10-31)

- **Wording:** every student email is in `ApplicationNotification`.
  - Content minimised: first name, application number, document titles, university names.
  - Message content is never emailed.
  - The service-approval email says "nothing is charged until you confirm and pay", which stays true under the payment gate.
- **Delivery:**
  - every student and staff notification is queued;
  - the queue worker runs every minute with 3 tries;
  - failed jobs are kept 30 days;
  - *Live verification* fails if the scheduler stops or mail waits more than 10 minutes.
- **Tests:** `NotificationRenderTest` renders every notification type.
