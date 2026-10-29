# Owner legal and payment-readiness checklist (2026-10-29)

**What this is.** Everything that stands between the live site and taking money lawfully. Only the owner (and their adviser) can supply it. Nothing here was changed on production.

**Sources** (official, read 2026-10-29 from GitHub runners via `page-excerpts.yml`):
- Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013 (SI 2013/3134): regulation 13, Schedule 2 and regulation 32;
- Electronic Commerce (EC Directive) Regulations 2002: regulation 6;
- Company, Limited Liability Partnership and Business (Names and Trading Disclosures) Regulations 2015: regulations 24 and 25;
- ICO guidance on the data protection fee.

This is a checklist, not legal advice. The legal review in item 6 is where an adviser confirms it.

## 1. Values the site needs (none can be invented)

| Setting | What to supply | Legal basis | Where it appears | Status today |
|---|---|---|---|---|
| `SITE_LEGAL_NAME` | The legal name of whoever trades: the registered company name, or the sole trader's own name (with any trading name) | CCR 2013 Sch. 2 (b); E-Commerce Regs reg. 6(1)(a); Trading Disclosures Regs reg. 24(2) for a company | footer, Privacy, Terms, Our status, About, every email footer | not set (empty on production; now shows "Study Medicine UK Nigeria" instead of a blank) |
| `SITE_ADDRESS` | A geographical address where the business is established (a company: its registered office) | CCR 2013 Sch. 2 (c)–(e); E-Commerce reg. 6(1)(b); Trading Disclosures reg. 25 | Contact, Terms, Privacy, Our status | not set ("To be published") |
| `SITE_COMPANY_NUMBER` | Only if incorporated: the registered number, plus the part of the UK it is registered in | E-Commerce reg. 6(1)(d); Trading Disclosures reg. 25 | Terms, Our status | not set |
| `SITE_ICO_NUMBER` | The ICO registration reference once the data protection fee is paid, or a recorded reason why the business is exempt | ICO: organisations that process personal data, sole traders included, pay unless exempt. This site processes student identity documents, so an exemption is unlikely | Privacy, Our status | not set ("To be published once registered") |
| `SITE_VAT_NUMBER` | Only if VAT-registered | E-Commerce reg. 6(1)(g) | Terms | not set (fine if not registered) |
| `SITE_LEGAL_REVIEWED` | The date the adviser approved the legal pages, for example `2026-11-15` | — | Admin launch checklist | not set |

**How to apply them after approval:**
1. GitHub → Settings → Environments → **production** → Environment variables: add each value above. These are not secrets; they are printed on public pages.
2. Actions → **Update server settings (Hostinger)** → Run workflow → target `production`. The production reviewer approves.
3. Actions → **Live Verification** → Run. The server-state block now reports `legal_name_set`, `address_set`, `ico_number_set` and `legal_reviewed_set` without printing the values.
4. Admin → Dashboard: "Trading identity published" turns green.

## 2. The model cancellation form (statutory text needed)

**What the law requires.** Customers here are consumers buying a service at a distance. CCR 2013 regulation 13(1)(b) requires the trader to give the model cancellation form in Schedule 3 Part B before the contract, as part of the information on the right to cancel. Regulation 32(3) lets the consumer cancel either by using that form or by "any other clear statement setting out the decision to cancel the contract".

**What the site does now.** The refund policy explains the 14-day right to cancel and says a short statement is enough. It does not give the model form.

**Why the form is not in the repository yet.** legislation.gov.uk publishes Schedule 3 only as an image, both on the web page and in the PDF of the regulations as made. Runs 40943491462 (404 for the Part A and Part B URLs), 40944434780 (no form text on `/schedule/3` or `/schedule/3/made`) and 40944753176 (the PDF goes from the Schedule 3 heading straight to page numbers 25 and 26) found no text. The form wording will not be typed from memory.

**Owner or adviser action:**
1. Copy Part B exactly from the image of Schedule 3 on legislation.gov.uk.
2. Fill the "To" line with the legal name, the geographical address and the email address above.
3. Send it for adding to `/refund-policy` and the payment confirmation page.

Until then the site meets regulation 32(3)(b), but not regulation 13(1)(b).

## 3. Complaints procedure (draft for the owner to approve; not published)

CCR 2013 Schedule 2 (o) requires "where applicable, the trader's complaint handling policy". The site has none. Below is a draft that uses only commitments the site already makes (a reply within 2 working days, the portal, the contact email). The two bracketed time limits are for the owner to decide.

> **How to complain**
> If you are unhappy with our service, tell us by email at the address on our Contact page, or through your portal messages, quoting your application number if you have one. Say what went wrong and what you would like us to do.
>
> We will acknowledge your complaint within 2 working days and send a full response within [10] working days. If we need longer, we will tell you why and when to expect it.
>
> If you are not satisfied with our response, reply asking for a review. The complaint will then be reviewed by [the director / the owner] and we will reply within [10] working days with our final position.
>
> A complaint about a university's decision, a UCAS process, a visa or a test provider must be made to that organisation. We will tell you where to send it and help you prepare it if it relates to our service.
>
> Complaining does not affect your right to cancel or your other legal rights.

When approved, the draft becomes a section of the application terms and is linked from Contact.

## 4. Stripe account settings (Dashboard; Claude has no access)

- **Settings → Public details → Support email.** Live Verification reports `support_email_set: false`. Stripe prints this address on receipts and on Checkout. Use the business mailbox; this checklist does not choose one.
- **Statement descriptor.** It should be recognisable as the business, so customers do not dispute charges they cannot place.
- **Business name and address.** They should match `SITE_LEGAL_NAME` and `SITE_ADDRESS` once those exist.
- **Test-mode key for the repeatable journey.** Add the repository secret `STRIPE_TEST_SECRET` (an `sk_test_…` key). The *Stripe test-mode journey* workflow then runs all three tiers against real Stripe test mode, including a declined card. It is blocked without it.

## 5. Payment availability decision

Live payments are open now (`payments_open: true`, live key present). Items 1–4 above are not done. That is the owner's decision to keep or reverse. Claude did not change it, and changing it needs the owner's approval. The safe options:
- **(a)** Keep payments open. Do not approve any student for service selection until items 1–4 are done. A student sees prices and the pay button only after staff approval, so nobody can pay today without the admin's own action.
- **(b)** Remove the live key (Update server settings with an empty value is not supported; the key would have to be deleted on the server). Not recommended; (a) already holds payments behind approval.

## 6. Legal review

An adviser reviews `/privacy`, `/terms`, `/application-terms` and `/refund-policy`, together with items 2 and 3.
- The refund policy still reads "Version 0.9.2, 4 October 2026 … under legal review"; change the version line after approval.
- Then set `SITE_LEGAL_REVIEWED` (item 1).

## 7. Analytics decision (`SITE_ANALYTICS_DECISION`)

- **What it is:** the owner's recorded choice, either `first_party` or `ga4`. `ops/update-env.sh` refuses any other value.
- **What it does:** it changes no behaviour.
  - GA4 loads only when `SITE_GA4_ID` is set, and then only after the visitor consents.
  - The value appears in Live Verification and the admin launch checklist, so that the choice is on record.
- **Status today:** "none recorded".
- **Recommendation:** `first_party` for now.
  - The first-party funnel already measures the conversion path without third-party cookies.
  - Revisit `ga4` after Search Console is connected and there is traffic worth segmenting.
