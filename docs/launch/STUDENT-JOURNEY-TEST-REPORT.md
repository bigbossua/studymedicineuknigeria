# Student journey test report (2026-10-30)

**Data:** synthetic only: accounts `journey-…@example.test`, the seeded local demo accounts, and the Stripe stand-in. **Where:** this branch, served locally (`php -S`) on a local SQLite database. Nothing touched production, Stripe or any real person.

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
| Emails disclose no unnecessary personal data | Pass. They contain the first name, the application number, document titles and university names; message content is never emailed ("You have a new message in your portal") | `ApplicationNotification`; `NotificationRenderingTest` |
| Stripe data minimised | Pass: the stored webhook keeps ids, amounts and statuses, not name, email, address or card | `StripeWebhookController::minimal` |
| Funnel events hold no personal data | Pass | `App\Support\Funnel`; privacy tests |
| Overdue enquiries visible to staff | Pass: dashboard and Admin → Leads highlight leads still "new" after 2 working days | `LeadFollowUpTest` |

## 3. Workstream 5: the three unanswered enquiries

**Identified.** The 29 October audit and the *Operations summary* (counts only) show three eligibility-checker leads still marked new, past the 2-working-day promise on the Contact page.

**Why the drafts are not in this report.** The repository and its GitHub Actions logs are public. A lead's name, email and answers are personal data, and reading them out of production into a log or this file would publish them. So no individual draft is written here.

**What was built instead (in this branch, deployed with the next release).**
- Admin → Leads now shows, under each lead, a **Draft reply (review and send yourself)**, with an "Open in your email program" link.
- It is built only from that lead's own answers: qualification, sciences, English, UCAT, intended start, and the date of the check. It uses the route assessment the checker already showed them, so it adds no requirement, date or promise.
- For a lead past 2 working days it apologises for the delay. It says the assessment is not an admissions decision, links the pages that hold each school's published requirements, and invites a reply or registration, without any guarantee.
- **Nothing is sent by the site.** Test: `LeadFollowUpTest::test_an_overdue_lead_gets_a_reply_draft_from_its_own_answers_and_nothing_is_sent`.

**Example (synthetic lead "Ada Example", WAEC only, 2028 entry), as the test produces it:**

```
Dear Ada,

Thank you for using the eligibility checker on Study Medicine UK Nigeria on 30 October 2026. I am sorry this reply has taken longer than the two working days we promise.

You told us:
- Your qualifications: WAEC or NECO (WASSCE) only
- Sciences: Chemistry and Biology
- English evidence: WAEC or NECO English
- UCAT: not planned yet
- Intended start: 2028

Based on the requirements universities publish (this is not an admissions decision; only universities decide):
- Standard-entry Medicine (A100) directly on WASSCE/NECO (not open): None of the UK medical schools we reviewed publishes direct entry on WASSCE or NECO alone; …
- Foundation year leading to Medicine (possible): …
- A-levels or IB first, then standard entry (possible): …
- English language evidence (depends on conditions): …
- UCAT (depends on conditions): …

These pages set out each school's published requirements:
- https://studymedicineuknigeria.com/requirements/waec …

If you would like us to look at your situation in more detail, reply to this email with your results so far, or create an account at … to start an application profile. We cannot guarantee an offer from any university.

Kind regards,
[your name]
Study Medicine UK Nigeria
```

**Owner action** (`OWNER-ACTION-REGISTER.md` O9). The three replies can be sent today, without waiting for the deploy:
1. Open Admin → Leads → Show only overdue leads.
2. For each lead, write from the "Eligibility result" column using the structure above; after the deploy, the draft appears there ready to edit.
3. Send it from the business mailbox, then set the status to "contacted".
