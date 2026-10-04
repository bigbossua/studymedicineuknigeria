# Legal readiness review (2026-10-04)

A technical and editorial review of the public legal pages against the main UK requirements for an online, paid,
consumer-facing service that handles personal data and documents. **This is not legal advice.** It prepares the pages
as far as the facts allow and lists what only the owner or a solicitor can supply or decide. Nothing below invents a
company number, address, ICO number, VAT number, accreditation or university agreement.

## What the site now does (version 0.9.2 drafts, all marked "under legal review")

| Requirement | Where | State |
|---|---|---|
| Controller identity and contact (UK GDPR Art. 13) | Privacy → Who we are | Brand name and info@ shown; legal name, address and ICO number appear automatically once set (`SITE_LEGAL_NAME`, `SITE_ADDRESS`, `SITE_ICO_NUMBER`). |
| Purposes and lawful bases | Privacy → Why we use it | Contract, legitimate interests, legal obligation, consent for marketing. |
| Recipients and processors named | Privacy → Who we share it with | Hostinger (hosting, database, documents, email), Stripe (payments; also a controller), GitHub (encrypted backups, key not held by GitHub), Google (Analytics only with consent, never in the portal), WhatsApp (only if the student uses it). |
| International transfers | Privacy → International transfers | US processing by Stripe, GitHub and Google stated with the safeguard relied on; hosting location shown once `SITE_HOSTING_REGION` is set (the previous "UK/EU" wording was unverified and has been removed). |
| Retention periods | Privacy → How long we keep it | Now **enforced**: `smukn:retention` runs weekly (documents deleted 12 months after an application closes; content anonymised after 24; access logs deleted after 24; payment and approval records kept 6 years). Backups expire within 30 days (database 14, full 28). |
| Security description | Privacy → Security | Corrected: "checked on upload" (content-type detection, active-content and macro refusal, image re-encoding) instead of "scanned"; antivirus runs only where the server provides it, which shared hosting does not. Passports and financial documents encrypted at rest; access logged; two-step verification for staff. |
| Automated decision-making | Privacy → Decisions about you | Stated: eligibility check is guidance; no solely automated decisions with legal effect. |
| Data-subject rights and complaints | Privacy → Your rights | Access, correction, export (portal), deletion request, objection, restriction, consent withdrawal; ICO and Nigeria Data Protection Commission. |
| Cookies (PECR) | Privacy → Cookies; consent banner | Strictly necessary cookies only, unless the visitor accepts analytics; nothing loads before consent. |
| Trader identity (E-Commerce Regulations 2002, reg. 6; trading disclosures) | Terms → Who we are; footer; Our status | Brand and email shown; legal name, company number, address and VAT number appear automatically once set. |
| Pre-contract information (Consumer Contracts Regulations 2013) | Services page, portal confirmation page, application service terms | Deliverables, exclusions, the exact fee (shown before payment), when work starts. |
| Right to cancel within 14 days (CCR 2013 regs. 29–36) | Refund policy → Your right to cancel; checkout consent | Added: the 14-day right, how to cancel (email or portal message), and the **express request** to start within the cancellation period with acknowledgement that a proportionate amount is payable and the right ends on full performance. The student ticks this before paying. |
| Admissions and outcome disclaimers | Terms, application terms, About, Our status, checkout | No guarantee of offers, interviews, visas, scholarships, GMC registration or employment; universities decide. |
| Independence (no agency or partnership) | Header bar, About, Our status, Terms | Stated everywhere; no university agreement is claimed. |
| No unevidenced credentials | About | "a small team with UK and Nigerian experience of medical-school admissions" and "training certificates" removed until evidenced. |

## OWNER LEGAL DECISION LIST (only these need you or a solicitor)

1. **Business identity.** Are you trading as a sole trader or through a company? Supply the legal name (and company number and registered office if a company). The trading-disclosure rules and the E-Commerce Regulations require a geographic address for a trader; if you do not want a home address published, a solicitor can advise on a registered-office or business-address service. Set `SITE_LEGAL_NAME`, `SITE_COMPANY_NUMBER`, `SITE_ADDRESS`.
2. **ICO registration.** Most organisations processing personal data must pay the ICO data protection fee. Register and set `SITE_ICO_NUMBER`.
3. **VAT status.** Are you VAT-registered? If so, set `SITE_VAT_NUMBER`, and confirm the fees are VAT-inclusive.
4. **Hosting location.** Confirm the data-centre location shown in hPanel (Websites → studymedicineuknigeria.com → Dashboard → server details) and set `SITE_HOSTING_REGION`.
5. **Applicants under 18.** Medicine applicants are often 16–17. Decide (with a solicitor) whether minors may contract and pay directly or need a parent or guardian, and how the ICO Children's Code applies.
6. **Refund terms.** Confirm the pro-rata method, the "no route open" full refund and the "no refund once submitted" rule, and whether a cancellation form or complaints procedure (with response times) should be published.
7. **Legal review sign-off.** When a solicitor has reviewed Privacy, Terms, Application terms and Refunds, set `SITE_LEGAL_REVIEWED` to the review date; the dashboard item turns complete and the "under legal review" notes can be removed.

All of these are GitHub repository **variables** (not secrets); after adding them, *Update server settings* (production) writes them to the server.

## References (primary sources)

- UK GDPR right to be informed — ICO: https://ico.org.uk/for-organisations/uk-gdpr-guidance-and-resources/individual-rights/individual-rights/right-to-be-informed/
- Data protection fee — ICO: https://ico.org.uk/for-organisations/data-protection-fee/
- Cookies and similar technologies (PECR) — ICO: https://ico.org.uk/for-organisations/direct-marketing-and-privacy-and-electronic-communications/guide-to-pecr/cookies-and-similar-technologies/
- Children's code — ICO: https://ico.org.uk/for-organisations/uk-gdpr-guidance-and-resources/childrens-information/childrens-code-guidance-and-resources/
- Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013: https://www.legislation.gov.uk/uksi/2013/3134/contents
- Electronic Commerce (EC Directive) Regulations 2002, regulation 6: https://www.legislation.gov.uk/uksi/2002/2013/regulation/6
- Company, LLP and Business (Names and Trading Disclosures) Regulations 2015: https://www.legislation.gov.uk/uksi/2015/17/contents
- Consumer Rights Act 2015: https://www.legislation.gov.uk/ukpga/2015/15/contents
- Digital Markets, Competition and Consumers Act 2024 (unfair commercial practices, including fake reviews): https://www.legislation.gov.uk/ukpga/2024/13/contents
- Nigeria Data Protection Commission (Nigeria Data Protection Act 2023): https://ndpc.gov.ng/
