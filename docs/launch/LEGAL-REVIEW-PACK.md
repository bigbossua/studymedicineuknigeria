# Legal review pack (2026-10-30)

**For:** the owner and a qualified legal adviser. **Status of everything proposed here: DRAFT — LEGAL REVIEW REQUIRED.** Nothing in this pack is legal advice, and no wording in it has been approved.

**What was done in the repository (not deployed):**
- Every legal page (terms of use, privacy notice, refund policy, application service terms) now carries one consistent banner: "DRAFT — LEGAL REVIEW REQUIRED". It disappears only when `SITE_LEGAL_REVIEWED` records the review (`resources/views/components/legal-version.blade.php`, tested in `LegalIdentityTest`).
- The trading identity is read from one place (`App\Support\LegalIdentity`). Fallback wording no longer assumes a company ("on incorporation", "Registered address" removed).
- The legal pages' substantive wording is **unchanged**. Corrections are proposed below for the adviser, not applied.

**Sources.** Official texts, read today from GitHub runners with the read-only `page-excerpts.yml` (runs 41026720583 and 41027024210): legislation.gov.uk, ico.org.uk, eur-lex.europa.eu and gov.uk. Quotes are verbatim. Where a source could not be read as text, this pack says so and does not reconstruct it.

## 1. The business: what the law asks it to disclose

**The structure is unknown.** Nothing in the repository, server settings or records shows whether the trader is a sole trader, a partnership or a company. It must not be guessed. The disclosures differ:

| If the trader is… | It must publish (summary of the sources below) |
|---|---|
| A sole trader | The trader's own name (with any trading name), a geographic address where the business is established, and an email address |
| A partnership | The partners' names, or the partnership name if the regulations allow, a geographic address and an email address |
| A limited company or LLP | Its registered name on its websites; registered number; the part of the UK where it is registered; registered office address; an email address |
| Any, if VAT-registered | The VAT number |

**[Fact] Sources (verbatim):**
- E-Commerce Regulations 2002 reg. 6(1): "(a) the name of the service provider; (b) the geographic address at which the service provider is established; (c) the details of the service provider, including his electronic mail address…; (d) where the service provider is registered in a trade or similar register available to the public, details of the register … and his registration number".
- Company, LLP and Business (Names and Trading Disclosures) Regulations 2015 reg. 24(2): "Every company shall disclose its registered name on its websites."
- Companies Act 2006 s. 1202 (business names) requires a person to whom the Chapter applies to state the required information on "business letters", "written orders for goods or services", "invoices and receipts" and "written demands for payment".
- CCR 2013 Sch. 2 (b)–(d): the trader's identity, its geographical address and contact details. The Sch. 2 text read today covers paragraphs (h)–(x); paragraphs (b)–(d) are as cited in the owner checklist of 29 October.

**Where the identity appears once set:**
- footer;
- Our status;
- Contact;
- terms of use;
- privacy notice;
- every email footer;
- the admin launch checklist.

The settings are `SITE_LEGAL_NAME`, `SITE_ENTITY_TYPE`, `SITE_COMPANY_NUMBER`, `SITE_REGISTERED_IN`, `SITE_ADDRESS`, `SITE_ICO_NUMBER` and `SITE_VAT_NUMBER`. They are listed in `OWNER-ACTION-REGISTER.md` items O1–O3.

## 2. ICO data protection fee: self-assessment against the official criteria

**[Fact] The rule** (ico.org.uk, "Data protection fee", read today): "organisations (including sole traders) that use personal information need to pay a data protection fee, unless they are exempt."

**[Fact] The fee** (Data Protection (Charges and Information) Regulations 2018 reg. 3, and the ICO guide):
- tier 1 (micro organisations), "£52": "turnover of less than or equal to £632,000 … or … members of staff … less than or equal to 10";
- tier 2, "£78";
- tier 3, "£3,763".

The ICO page "Changes to the data protection fee" says the fees rose "On Monday 17 February 2025".

**[Fact] The exemptions** (2018 Regulations, Schedule para. 2): processing is exempt if it falls only within listed descriptions. Those read today include processing "not being processed wholly or partly by automated means", processing for "personal, family or household affairs" and processing "for the purpose of the maintenance of a public register". The ICO's own guide also lists staff administration, advertising/marketing/PR for one's own business, and accounts and records. The ICO's interactive self-assessment (`/data-protection-fee/data-protection-fee-self-assessment/`) is a question-by-question tool; its questions were not readable as text.

**[Assessment, for the owner to confirm with the ICO tool].** The site processes, by automated means, student identity documents (passports), academic records, application data and payment records. It does so to provide a paid service to customers, not only for staff administration, own-business marketing or accounts.
- On the descriptions above, this processing does not appear to fall within an exemption.
- A tier 1 fee of £52 is likely if turnover is ≤ £632,000 or staff ≤ 10.
- This is not a conclusion: the owner must complete the ICO self-assessment, which also asks questions not visible here.

**Action:** see `OWNER-ACTION-REGISTER.md` O4. The site then shows the registration on Our status and in the privacy notice (`SITE_ICO_NUMBER`).

## 3. Consumer Contracts Regulations 2013: gap analysis

Students buy a service at a distance, so this is a distance contract.

| # | Requirement (verbatim, CCR 2013) | What the site does now | Gap | Severity |
|---|---|---|---|---|
| C1 | reg. 13(1)(b): the trader "must give or make available to the consumer a cancellation form as set out in part B of Schedule 3" | No form anywhere | **Missing** | Blocks commercial launch |
| C2 | Sch. 2 (k): "where applicable, the trader's complaint handling policy" | None published | **Missing** (draft in §5) | Blocks commercial launch |
| C3 | reg. 30(2): for a service contract "the cancellation period ends at the end of 14 days after the day on which the contract is entered into" | Refund policy: "within 14 days of paying" | Probably the same day in practice (the contract is made at payment), but the wording should follow the regulation | Fix before launch |
| C4 | reg. 34(4) and (6): reimbursement "without undue delay, and in any event not later than … the end of 14 days after the day on which the trader is informed of the consumer's decision to … cancel" | Refund policy: refunds paid "within 10 working days of agreement" | "Of agreement" adds a condition the regulation does not have, and 10 working days can exceed 14 calendar days | **Fix before launch** |
| C5 | reg. 36(1): the trader "must not begin the supply of a service before the end of the cancellation period … unless the consumer (a) has made an express request" | One **mandatory** checkbox combines accepting the terms with "I ask you to start work now…" | A request the student cannot decline may not be a free "express request". Option: a separate, optional checkbox, with work starting after 14 days otherwise | Adviser to decide |
| C6 | reg. 36(2): the right to cancel ends once the service "has been fully performed" only if performance began after the request "and (b) with the acknowledgement that the consumer would lose that right" | The checkbox includes "the right to cancel ends once the service is fully performed" | Present; evidence is the payment row (created only after the checkbox, with time and terms version). The exact checkbox wording is not versioned (recommendation R3 in §7) | Adviser to confirm |
| C7 | reg. 36(4): the consumer who cancels after requesting early supply pays "an amount" for the service supplied (proportionate; Sch. 2 (n) "reasonable costs in accordance with regulation 36(4)") | "A pro-rata refund based on the deliverables already completed" | The method needs the adviser's check against reg. 36(4)–(6), which was not read in full today | Adviser |
| C8 | reg. 16(1),(2),(4): "confirmation of the contract on a durable medium" with "all the information referred to in Schedule 2", "before performance begins of any service" | Payment email: "We have received your payment of £X. A receipt from Stripe follows separately." | **Missing**: the confirmation lacks the Sch. 2 information, the cancellation information, the form and the record of the early-start request | **Blocks commercial launch** (draft in §6) |
| C9 | reg. 31: if the cancellation information is not given, the period is extended (up to "12 months after the day on which it would have ended") | Information given on the refund page | Low risk once C1, C4 and C8 are fixed | — |
| C10 | Sch. 2 (x): "where applicable, the possibility of having recourse to an out-of-court complaint and redress mechanism, to which the trader is subject" | Nothing | See §4 (ADR) | Adviser |
| C11 | reg. 32(4): if the trader offers an online cancellation form, it "must communicate … an acknowledgement of receipt … on a durable medium without delay" | Cancellation is by email or portal message | If an online form is added, an automatic acknowledgement is needed | Later |

**Service-specific cancellation** (the tiers have different deliverables, so the right to cancel is not identical in practice):
- **T1 Eligibility & Course Assessment (£125).** A single written assessment plus one follow-up thread. Once delivered, it may be "fully performed" within the 14 days, which ends the right to cancel if C5 and C6 are satisfied. Before delivery, cancellation gives a refund less the proportionate amount (C7).
- **T2 Medical Application Preparation (£695).** Several deliverables over time (shortlisting, document reviews, personal statement feedback, readiness review). It is unlikely to be fully performed within 14 days, so a cancellation within the period triggers a proportionate amount. The policy's "pro-rata by deliverables" needs the adviser's check (C7).
- **T3 Full Medical Application Support (£1,295).** It includes submission support and tracking until the university responds. The policy's "once a submission has been made, no refund is due for submission support" goes further than the statute's proportionate rule, so the adviser must review it (L4 in §7).
- **"No route open" full refund of the assessment fee:** more generous than the statute. It can stay if the owner wishes.

**[Fact] The official model text, for the adviser** (the UK Schedule 3 is published by legislation.gov.uk only as an image, on the web page, the "made" page and the PDF; runs 41026720583 and 41027024210 found no text). The UK regulations transposed Directive 2011/83/EU. Its Annex I, read today as text from EUR-Lex, gives:

> B. Model withdrawal form
> (complete and return this form only if you wish to withdraw from the contract)
> — To [here the trader's name, geographical address and, where available, his fax number and e-mail address are to be inserted by the trader]:
> — I/We (*) hereby give notice that I/We (*) withdraw from my/our (*) contract of sale of the following goods (*)/for the provision of the following service (*),
> — Ordered on (*)/received on (*),
> — Name of consumer(s),
> — Address of consumer(s),
> — Signature of consumer(s) (only if this form is notified on paper),
> — Date
> (*) Delete as appropriate.

For services, Annex I A point 6 adds: "If you requested to begin the performance of services … during the withdrawal period, you shall pay us an amount which is in proportion to what has been provided until you have communicated us your withdrawal from this contract, in comparison with the full coverage of the contract."

**The UK Part B uses UK wording.** The adviser must take the form from the image of Schedule 3 Part B on legislation.gov.uk, not from the Directive. The draft in §6 is a placeholder until the adviser confirms that text.

## 4. Alternative dispute resolution (ADR): the law changed in 2026

**[Fact]**
- legislation.gov.uk now titles SI 2015/542 "The Alternative Dispute Resolution for Consumer Disputes (Competent Authorities and Information) Regulations 2015 (revoked)". Regulation 19 of that SI was the old duty to name an ADR entity.
- The Digital Markets, Competition and Consumers Act 2024, Part 4 Chapter 4 ("Alternative dispute resolution for consumer contract disputes"), shows "S. 291 in force at 6.4.2026 by S.I. 2026/284". It defines ADR as an out-of-court resolution "carried out by an independent third party acting in relation to both parties", and regulates accredited and exempt ADR providers.

**[Not established today].** Whether the 2024 Act or regulations under it now require a trader like this one to tell consumers about ADR, and in what words, was not found in the text read. **The adviser must confirm.** The site must not claim membership of an ADR scheme it has not joined. CCR Sch. 2 (x) applies only to a mechanism "to which the trader is subject".

## 5. Complaints procedure (DRAFT — LEGAL REVIEW REQUIRED)

**No statutory deadline is claimed.** The time limits are internal targets for the owner to choose; the brackets mark owner decisions.

> **How to complain** — DRAFT — LEGAL REVIEW REQUIRED
>
> If you are unhappy with our service, tell us by email at [complaints address — owner decision; may be info@studymedicineuknigeria.com] or through your portal messages, quoting your application number if you have one. Say what went wrong and what you would like us to do.
>
> **Stage 1.** We acknowledge your complaint within [2] working days and send a full written response within [10] working days. If we need longer, we tell you why and when to expect it.
>
> **Stage 2.** If you are not satisfied, reply asking for a review. [The owner / a named senior person] reviews the complaint and sends our final written position within [10] working days.
>
> **After our final response.** [ADR sentence only if the adviser confirms one applies — see the pack §4.] You may also contact Citizens Advice (consumer service) for advice, or take the matter to court.
>
> A complaint about a university's decision, UCAS, a visa or a test provider must be made to that organisation. We will tell you where to send it.
>
> Complaining does not affect your right to cancel or your other legal rights.

**Recording.** Complaints arrive as portal messages or email. Staff log them in Admin → Messages with the stage and dates. A dedicated complaints log is recommendation R4.

## 6. Cancellation information, form and contract confirmation (DRAFT — LEGAL REVIEW REQUIRED)

**Proposed replacement for "Your right to cancel" on the refund policy** (wording follows the regulations' terms; brackets are adviser or owner choices):

> You may cancel this contract within 14 days after the day on which the contract is entered into (the day you pay), without giving any reason. To cancel, tell us clearly — by email to [address], through your portal messages, or using the cancellation form below. You do not have to use the form. You meet the deadline if you send your message before the 14 days end.
>
> If you cancel, we refund the money you paid without undue delay and no later than 14 days after the day you tell us, using the same payment method [adviser to confirm reg. 34 wording], unless you agreed otherwise.
>
> If you asked us to start work during the 14 days, you pay a proportionate amount for the service supplied up to the time you tell us you are cancelling [method per reg. 36(4)–(6) — adviser]. If we have fully performed the service, you no longer have a right to cancel, because you asked us to start and acknowledged this when you paid.

**Cancellation form:** the UK Schedule 3 Part B text, taken by the adviser from legislation.gov.uk, with the "To" line filled with `SITE_LEGAL_NAME`, `SITE_ADDRESS` and the email address.

**Payment confirmation email (reg. 16), proposed content.** This is a template change only; it is not built yet, because its wording is legal wording awaiting review. It should say:
1. who the trader is: the identity line from `LegalIdentity::summary()`;
2. the service name and deliverables;
3. the total price paid and the date;
4. the terms version accepted and the time of the express early-start request and acknowledgement (the payment record already holds both);
5. the cancellation information above;
6. the cancellation form (UK Part B);
7. the complaints procedure (§5);
8. links to the versioned terms.

## 7. Clauses requiring qualified legal review

| Ref | Document and clause | Question for the adviser |
|---|---|---|
| L1 | Refund policy, "Your right to cancel" | Replace with §6 wording; confirm the reg. 34 refund method and timing (C3, C4) |
| L2 | Refund policy, "After work has started: a pro-rata refund based on the deliverables" | Is "by deliverables" a lawful way to calculate the reg. 36(4) amount? (C7) |
| L3 | Checkout checkbox (portal payments page) | Must the early-start request be optional and separate from accepting the terms? (C5) |
| L4 | Refund policy, "Once a submission has been made, no refund is due for submission support" (T3) | Compatible with reg. 36 and with fairness under the Consumer Rights Act 2015 Part 2? |
| L5 | Refund policy, "our fee where false documents or information were supplied" is not refundable | Fairness (CRA 2015 Part 2); compatibility with statutory cancellation |
| L6 | Terms of use, "Liability": not liable for losses from information an official source has changed | CRA 2015 s. 57: a term "is not binding on the consumer to the extent that it would exclude the trader's liability arising under section 49" (reasonable care and skill) or s. 50 (information binding). Is the clause safe as worded? |
| L7 | Terms of use, "Governing law: England and Wales" | Correct for the trader's location (not yet known), and for consumers resident in Nigeria? |
| L8 | Application terms §3 "When work begins: on confirmed payment" | Align with reg. 36(1): work starts early only on the express request |
| L9 | Application terms §9 "Additional charges: none unless … agree a written quote" | Fine in principle; confirm wording |
| L10 | Privacy notice: controller identity, international transfers ("Our hosting provider's data-centre location is stated in our records"), Nigeria Data Protection Act 2023 basis, retention periods | Confirm the lawful bases, the transfer mechanism and the NDPA position; supply `SITE_HOSTING_REGION` |
| L11 | Fees shown only to approved students (owner decision 2026-10-04) | Under the DMCCA 2024 unfair commercial practices regime (CMA guidance CMA207, "last updated 18 November 2025 … new guidance on price transparency"), confirm the total price is given at the right point. The price is shown in full in the portal before payment |
| L12 | Complaints procedure (§5) and ADR (§4) | Approve the text; confirm whether any ADR information is now required |
| L13 | Payment confirmation email (§6) | Approve the content list and wording |

**Recommendations (technical, after approval):**
- **R1.** Publish the approved texts and set `SITE_LEGAL_REVIEWED`.
- **R2.** Build the reg. 16 confirmation email from the approved wording.
- **R3.** Version the exact checkbox wording with each payment (needs a migration; keep it out of the pending deploy).
- **R4.** Add a complaints log with stage dates.
