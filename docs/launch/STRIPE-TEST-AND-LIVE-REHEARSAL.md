# Stripe: test-mode workflow and controlled live rehearsal (updated 2026-10-31)

**Nothing here was run against live Stripe.** No live payment, refund, catalogue change or Dashboard change was made. The live rehearsal in §5 needs the owner's separate approval.

## 1. The three services (inspected in code and the local database)

| Code | Service | Price | Payment gate | Terms | Stripe catalogue |
|---|---|---|---|---|---|
| T1 | Eligibility & Course Assessment | £125.00 (12500 GBP minor units) | at start | v1 | product `smukn_t1`, price lookup key `smukn_t1_gbp` |
| T2 | Medical Application Preparation | £695.00 (69500) | at start | v1 | `smukn_t2`, `smukn_t2_gbp` |
| T3 | Full Medical Application Support | £1,295.00 (129500) | at start | v1 | `smukn_t3`, `smukn_t3_gbp` |

**Who sees prices.** Prices are never on a public page, in JSON-LD or JSON; `PricingVisibilityTest` crawls the sitemap for leaks. Only staff, and a student whose profile staff approved for service selection (`User::canSeeServicePrices`), see them. The deliverables and exclusions shown before payment come from the same service record. The live catalogue was last checked read-only by *Live Verification*. The deploy only reads it (`smukn:stripe-sync --check`; see `PRE-DEPLOYMENT-CHECKLIST.md` §6).

## 2. How a payment works, and the safeguards (code references)

| Step | Behaviour | Where |
|---|---|---|
| Choose and confirm | Checkbox required (terms version, early-start request and acknowledgement); a payment row records the time and terms version | `Portal\PaymentController::checkout` |
| Duplicate charge | A second checkout for a service already paid, under review, partly refunded or disputed is refused; earlier open Checkout sessions are expired first | `PaymentController` lines 63 and 110; `StripeService` |
| Price charged | A catalogue Price chosen by the server (never an amount from the browser); no Payment Links | `StripeCatalog::ensure` |
| Confirmation | Only the signed webhook marks a payment paid. The return page never does; it waits for the webhook | `Webhooks\StripeWebhookController` (`Webhook::constructEvent`; an invalid signature gets 400) |
| Duplicate events | Each Stripe event id is stored once (`stripe_events`, unique). A processed event is answered "Already processed"; an already paid payment is not paid twice | controller and `StripeService::applyEvent` |
| Wrong mode | An event whose `livemode` differs from the environment is ignored | `StripeService` line 132 |
| Wrong amount or currency | Not marked paid. Status `MANUAL_REVIEW`, with staff notified | `StripeService` lines 157–160 |
| Second payment for the same service | Not counted; staff notified to refund it in the Dashboard | `StripeService` lines 148–152 |
| Expired, failed | `checkout.session.expired` → EXPIRED; `payment_intent.payment_failed` → FAILED | `StripeService` |
| Refund | Issued by staff in the Stripe Dashboard (the site has no refund button). `charge.refunded` sets `REFUNDED_PARTIAL` or `REFUNDED_FULL` with the refunded amount | `StripeService` line 186 |
| Dispute | `charge.dispute.created` → DISPUTED | `StripeService` line 195 |
| Paid-service access | Only a `SUCCEEDED` payment counts (`Application::hasSucceededPayment`), so a refund or a dispute ends paid access | `Application.php` line 105 |
| **Commercial-launch gate** (2026-10-31) | In production no new payment starts, by card or bank transfer, until `SITE_COMMERCIAL_LAUNCH_APPROVED` is set; see §2a | `StripeService::launchApproved` |
| Receipt | Stripe sends its receipt (needs the Dashboard support email, §4). The site emails "Payment received — £X" | `ApplicationNotification` `payment.succeeded` |
| Stored data | The stored webhook keeps ids, amounts, currency and status only, never name, email, address or card | `StripeWebhookController::minimal` |
| Bank transfer | Off by default (`SITE_BANK_TRANSFER`). A transfer is `MANUAL_REVIEW` until an admin confirms it | `manualTransfer`; admin confirm |

## 2a. The commercial-launch gate (prepared 2026-10-31, live after the deploy)

**[Fact] Exposure today (before the deploy).** Production has a live key, so `payments_open` is true. A student whom staff approve for service selection could pay before the legal and commercial blockers are closed. Production has **0 students and 0 applications** (*Operations summary* 41167257013), so no one can reach a payment today without the admin's own action.

**[Fact] After the deploy.**

| Behaviour | |
|---|---|
| Refused while the gate is closed | **new** Stripe Checkout sessions and bank-transfer requests. The payment page shows "Payment is not open yet", and the dashboard's next action says the same. Waiting students are not emailed |
| Unaffected | webhooks (a checkout started before the deploy still completes), refunds, disputes, paid access and `MANUAL_REVIEW` confirmation by an admin |
| Shown to staff | the Admin checklist item "Commercial launch approved (new payments accepted)" |

Tests: `CommercialLaunchGateTest` (4 tests; mutation-checked).

**Opening payments (launch).**
1. After every B item of `FINAL-LAUNCH-CHECKLIST.md`, set the production variable `SITE_COMMERCIAL_LAUNCH_APPROVED` to the approval date (`YYYY-MM-DD`).
2. Run *Update server settings*. It rebuilds the configuration and tells waiting students once (`smukn:payments-open-notify`).
3. Run *Live verification*; it now expects payments open.

**Closing again (recovery).** Delete the `SITE_COMMERCIAL_LAUNCH_APPROVED` line from `~/apps/smukn-production/shared/.env` (hPanel File Manager), then run `php artisan config:cache` in `current/` (hPanel terminal). *Update server settings* only writes non-empty values, so it cannot remove a setting. Payments already made stay valid.

**Rolling back the code** (*Roll back Hostinger release*) removes the gate itself, and payments open as before. To keep payments closed in that case, remove the live key instead.

**Gap carried to the legal pack.** The site's payment email is not yet the "confirmation of the contract on a durable medium" that CCR 2013 regulation 16 requires (`LEGAL-REVIEW-PACK.md` C8, R2). It blocks commercial launch, not deployment.

## 3. Test results (2026-10-30, local, Stripe stand-in `ops/qa/fake-stripe.php`)

| Test | Result |
|---|---|
| `stripe-e2e.cjs` (stand-in), T1 | declined test card shown, then paid: SUCCEEDED, 12500 GBP, `payment_status` paid, `livemode` false |
| `stripe-e2e.cjs`, T2 | SUCCEEDED, 69500 GBP, paid, test mode |
| `stripe-e2e.cjs`, T3 | SUCCEEDED, 129500 GBP, paid, test mode; `problems: []`, `ok: true` |
| `payment-journey.cjs` phone and desktop | T2: cancel at checkout → retry → pay → signed webhook → SUCCEEDED 69500 GBP; the amount on the page equals the amount charged; no problems (30 and 31 October) |
| `CommercialLaunchGateTest` | gated production: no Checkout session, no bank transfer, no payment row, nobody notified; earlier payments, an in-flight checkout's webhook and a refund still processed; the approval date reopens payment |
| PHPUnit `Pricing\|Stripe\|Payment\|Checkout\|Webhook\|DeploymentSafety` | included in the full run (`FINAL-READINESS-REPORT.md` §3) |

**Real Stripe test mode is BLOCKED.** There is no `STRIPE_TEST_SECRET` repository secret.

## 4. Settings still missing (owner, Stripe Dashboard; Claude has no access)

1. **Repository secret `STRIPE_TEST_SECRET`** (`sk_test_…` or `rk_test_…`), and optionally `STRIPE_TEST_KEY` (`pk_test_…`). Unblocks §3 against real Stripe.
2. **Settings → Public details → Support email.** *Live Verification* last reported `support_email_set: false`. Stripe prints it on receipts and Checkout.
3. **Statement descriptor:** recognisable as the business.
4. **Business name and address in Stripe:** must match `SITE_LEGAL_NAME` and `SITE_ADDRESS` once supplied.

## 5. How to run the test-mode journey (after item 4.1)

1. GitHub → Settings → Secrets and variables → Actions → New repository secret: `STRIPE_TEST_SECRET`.
2. Actions → **Stripe test-mode journey (real Stripe)** → Run workflow (branch `claude/new-session-p6gdm6`).
3. On the runner it:
   - syncs a **test-mode** catalogue;
   - forwards Stripe-signed webhooks with `stripe listen`;
   - takes three new students through staff approval → choice → Stripe Checkout with test cards (T1 also a declined card) → webhook → paid;
   - reads each charge back from Stripe;
   - runs the price-leak tests.
4. **Pass** = the step summary shows T1, T2 and T3 `paid`, `livemode false`, with amounts 12500, 69500 and 129500.

To also test a refund in test mode, refund one test charge in the Stripe Dashboard (test mode). That is not automated, because the runner's app stops when the job ends; it needs the hosted test site that does not exist. Refund handling is covered by the unit tests of `charge.refunded`.

## 6. Controlled £125 live rehearsal (FOR SEPARATE OWNER APPROVAL; not run)

**Purpose.** Prove once, with real money, that a live T1 payment is charged correctly, recorded by the signed webhook, receipted, refunded and recorded as refunded.

**Preconditions, all required:**
- the deploy is live;
- §4 items 2–4 are done;
- `LEGAL-REVIEW-PACK.md` C1, C4 and C8 are resolved, or the owner accepts the rehearsal as an internal test with the owner as the "student";
- a real mailbox for the test account.

**Steps:**
1. The owner registers a student account with their own email (Registration must be open on production; it opens only after *Send test email* succeeds).
2. The owner starts an application. Then, as admin (two-step sign-in), approves the profile for service selection.
3. As the student, the owner chooses T1, ticks the box and pays £125 with the owner's own card on Stripe Checkout.
4. **Expected:**
   - the portal shows "Paid";
   - Admin → Payments shows SUCCEEDED £125.00;
   - Stripe Dashboard (live) shows one £125.00 payment with the catalogue price `smukn_t1_gbp`;
   - a Stripe receipt and the site's "Payment received" email arrive.
5. In the Stripe Dashboard, the owner refunds the £125 in full.
6. **Expected:**
   - Admin → Payments shows REFUNDED_FULL with £125.00 refunded;
   - the student's paid access ends;
   - the refund reaches the card in Stripe's normal time.
7. Record the evidence:
   - the Stripe payment and refund ids (not the card);
   - screenshots of the portal and Admin → Payments;
   - the *Live Verification* run id afterwards.
8. **Cost:** Stripe fees on the charge may not be returned on a refund (see Stripe's pricing page). That is the owner's decision.

**Stop conditions:**
- any amount other than £125.00;
- a payment marked paid without the webhook;
- no webhook within 10 minutes;
- any error page.

If any occurs, refund in the Dashboard and report it; do not retry until it is diagnosed.
