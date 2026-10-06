# Live £125 payment and refund rehearsal (owner only)

One real card payment through the live Stripe account, then a full refund. It proves end to end what the automated checks
can only prove in parts: a real card, real Stripe Checkout, the signed live webhook, the receipt, the refund webhook.
Only the owner can do it, because it moves real money on the owner's card and Stripe account.

## Already checked automatically (no action)

Last run 2026-10-06 (release `2026-10-06T07-21-36`, `live-verify.yml` with `payments: open`):
- payments are open; the live secret key and the webhook signing secret are installed; Stripe reports charges enabled;
- the live catalogue holds the three GBP prices (T1 £125, T2 £695, T3 £1,295), checked read-only;
- the webhook refuses an unsigned request (HTTP 400) and the self-test accepts a valid signature and refuses an invalid one;
- no service price appears on any public page; failed jobs 0; the scheduler ran within the last minute.

Locally (2026-10-06, `ops/qa/payment-journey.cjs`, phone and desktop): the price on the page equals the amount sent to
Checkout; cancel and retry work; the return page waits for the webhook; the signed webhook marks the payment paid.
The test suite covers replayed events (processed once), out-of-order events (never downgrade a paid record), a second
payment for an already-paid fee (held for refund, never counted), declined cards and refunds.

## Procedure (about 15 minutes)

Use an email address you control (for example a `+rehearsal` alias of your own address). Do not upload any real
student document; the payment does not need one.

1. **Create the test student.** Open `https://studymedicineuknigeria.com/register`, register with the alias, and click the
   link in the verification email.
2. **Start an application.** Portal → *Create my application*. Fill the first step with test values (name "Payment
   Rehearsal", any course). Note the application number (`SMUKN-…`).
3. **Approve service selection (as staff).** In another browser (or a private window) sign in to
   `https://studymedicineuknigeria.com/admin` with your admin account and authenticator code → Applications → open the
   application → *Approve for service selection*. The student now sees the three services and their fees.
4. **Choose T1 and pay.** Back in the student browser: Portal → the application → *Services* → choose *T1* (£125) →
   *Pay* → Stripe Checkout. Check the amount is **£125.00 GBP** and the business name is the one you set in Stripe.
   Pay with your own card.
5. **Confirm success.** The return page says *Thank you*; Portal → *Payments* shows the service as paid. In Admin →
   Payments the record reads `SUCCEEDED`, £125.00. In the Stripe Dashboard (`https://dashboard.stripe.com/payments`)
   the payment shows *Succeeded*; if *Successful payments* receipts are switched on in Stripe → Settings → Customer
   emails, the receipt arrives at the alias.
6. **Refund.** In the Stripe Dashboard open the payment → *Refund* → full amount → reason *Requested by customer*.
   Within a minute Admin → Payments shows `REFUNDED_FULL` and the application's audit trail has `payment.refunded`.
   The Dashboard shows the refund's status; how long it takes to reach the card depends on the card issuer.
7. **Clean up.** Student portal → the application → *Withdraw application*, then *Profile* → tick *I understand* →
   *Request deletion*.
8. **Record the result** in `data/ops/payment-rehearsal-YYYY-MM-DD.md`: date, application number, the four statuses seen
   (Checkout amount, `SUCCEEDED`, receipt if switched on, `REFUNDED_FULL`). Never record card details.

If any step differs from the above, stop and send the application number and the step; nothing else is needed.
