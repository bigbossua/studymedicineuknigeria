# 15. Payment architecture (Stripe)

Status: ARCHITECTURE. Derived from brief sections 22, 23, 24, 45, 46, 73. **No prices are set in this document** (brief 22: define deliverables first, then price transparently).

## 15.1 What is sold

Three service tiers (brief 22). The platform models each as a `service_tier` row with: name, deliverables (list shown on pricing page and in checkout), `payment_gate` (12.4), price per currency, Stripe Price IDs, active flag, terms version. Prices are **not** hard-coded in templates.

Suggested deliverable definitions to price later (RECOMMENDATION — these are what the team can genuinely deliver with the portal described in this repo):

| Tier | Name | Deliverables (what the student receives) | Payment gate |
|---|---|---|---|
| 1 | Eligibility & Course Assessment | structured profile review by staff; written assessment of which UK medicine routes are open (standard / graduate / foundation) based on *published* requirements with sources; personalised requirements checklist; list of relevant schools from the directory; 1 follow-up message thread | AT_START |
| 2 | Medical Application Preparation | Tier 1 + university shortlisting support; document checklist and document review (accept/reject with feedback); personal-statement structural feedback (no ghost-writing); UCAT/GAMSAT planning guidance; application-readiness review before the student submits via UCAS/direct | AT_START |
| 3 | Full Medical Application Support | Tier 2 + preparation of the complete package; student approval workflow; submission support by the permitted route (guided UCAS or direct where permitted); submission tracking; university correspondence support; interview-preparation guidance | configurable (default BEFORE_SUBMISSION for the submission component; AT_START for the preparation component — i.e. a two-part price) |

A tier may be split into **two Stripe Prices** (e.g. Tier 3 = preparation fee now + submission fee at approval). The data model supports N payments per application.

## 15.2 Currency

- Charge in **GBP** as the base price (UK service, UK bank). Stripe Checkout can present local currency; **NGN** acceptance depends on Stripe account capabilities and Nigerian card scheme behaviour (international card payments from Nigeria are frequently declined by issuing banks due to FX limits — INFERENCE from widely reported constraints; DATA UNAVAILABLE for a precise rate).
- RECOMMENDATION: enable Stripe's adaptive pricing / local payment methods where the account supports it; display the GBP price as canonical and the local-currency amount as "approximately"; offer a bank-transfer fallback (Stripe "bank transfer" customer balance where available, else manual with staff reconciliation) to avoid losing students whose cards fail. Record the fallback as a `Payment` with `method=MANUAL_TRANSFER` and `status=MANUAL_REVIEW` until staff confirm.

## 15.3 Flow

```
Student selects tier → /portal/payments → POST /payments/checkout
  → server creates Stripe Checkout Session (mode=payment, line_items=[price], customer_email, 
    client_reference_id=application_number, metadata={application_id, tier_id, payment_id},
    success_url=/portal/payments/return?session_id={CHECKOUT_SESSION_ID}, cancel_url=/portal/payments)
  → Payment row INITIATED
  → redirect to Stripe-hosted Checkout (PCI SAQ-A; no card data touches our servers)
Stripe → webhook POST /webhooks/stripe (signature verified, idempotent by event id)
  checkout.session.completed / payment_intent.succeeded → Payment SUCCEEDED → application.events += payment.succeeded → receipt email
  payment_intent.payment_failed → FAILED (student sees reason category + retry)
  charge.refunded → REFUNDED (partial/full amounts stored)
  charge.dispute.created → DISPUTED → staff alert; application ON_HOLD if gate depends on it
Return page: never trusts the URL; shows "Confirming…" and polls Payment status (webhook is the source of truth).
```

## 15.4 Payment status machine

`REQUIRED → INITIATED → SUCCEEDED | FAILED | EXPIRED`; `SUCCEEDED → REFUNDED_PARTIAL | REFUNDED_FULL | DISPUTED → DISPUTE_WON | DISPUTE_LOST`; `MANUAL_REVIEW → SUCCEEDED | REJECTED`. "Payment manually reviewed" in the brief = `MANUAL_REVIEW`.

## 15.5 Pre-payment disclosure (brief 45)

Checkout page (ours, before redirect) must show, in this order: what is included; what is **not** included (university tuition, UCAS/university application fees, UCAT/GAMSAT fees, English tests, visa and IHS, document certification/translation); when work begins; refund terms (summarised + link); "admission decisions are made by universities; no admission, scholarship or visa is guaranteed"; student responsibilities (accurate information, deadlines, own UCAS account where applicable); terms version checkbox. The accepted terms version is stored on the Payment.

## 15.6 Refund policy (RECOMMENDATION — legal review required)

- Cooling-off: full refund if requested within 14 days **and** no staff work has started (consistent with UK Consumer Contracts Regulations where the consumer is in the UK; applied voluntarily to all customers for fairness).
- After work begins: pro-rata by deliverables completed, listed on the invoice.
- Submission component: refundable until `STUDENT_APPROVED`; non-refundable after submission.
- Always refundable in full: if we determine at Tier 1 that **no** published UK medicine route is open to the student and they do not wish to proceed to foundation/alternative guidance.
Refunds are executed in Stripe by admin role only; recorded with reason.

## 15.7 Receipts, invoices, VAT

Stripe-generated receipts emailed automatically; our own invoice PDF (with application number, tier, terms version) generated on SUCCEEDED and stored with the application. VAT treatment of a UK-supplied service to a consumer outside the UK is a tax question — `DATA UNAVAILABLE / seek accountant advice`; model `tax_behaviour` as configurable in Stripe Tax.

## 15.8 Admin

Payments list with filters (status, tier, date), link to Stripe dashboard object, refund action, manual-transfer confirmation, export CSV. Reconciliation job nightly: Stripe balance transactions vs local Payments; mismatches flagged.

## 15.9 Security

Webhook secret rotation; signature verification; replay protection by event id; no price taken from client (server selects Price ID from tier); Checkout Session reuse prevented (one open session per payment; expire old on new); all Stripe keys in environment secrets; test mode until launch checklist signed off.
