# UK universities: recruitment-agent and partner contacts for Nigeria (2026-10-28)

**Purpose.** Find out which UK universities that teach Medicine might appoint StudyMedicineUKNigeria.com as an education agent or recruitment partner for Nigeria. For each one, record the official route and contact.

**Files:**
- `uk-university-agent-contacts.csv`: one row per university, with the evidence and the outreach status. It is rebuilt by `build_contacts.py`.
- `OUTREACH-DRAFTS.md`: one individually addressed enquiry per ready recipient. It is rebuilt by `make_drafts.py`.
- `OUTREACH-LOG.md`: what was sent, what was not sent, and why.
- `evidence/`: the decisive excerpts of every official page used, with the GitHub Actions run that read it.
- `specs/`: the exact crawl and page specs, so every read can be repeated.

## Method

1. **Scope.** The scope was all 55 rows of the site's university database. That is 50 universities with a medical school (Pears Cumbria is counted under Imperial and ScotGEM under St Andrews), plus two healthcare-only universities (Manchester Metropolitan, Northumbria). The result is 53 entries.
2. **Reading official pages.** Each university's own website was read on 2026-10-28 by the repository's official-page reader (`page-excerpts.yml`).
   - Six crawl runs started at each university's international or agent page and followed links about agents, representatives, partners and Nigeria/Africa.
   - Two further runs read the decisive agent pages with wider context.
   - No search-engine snippet, third-party directory or guessed address was used.
3. **E-mail addresses.** An address is recorded only where it is printed on the university's own page. It must also be relevant to international recruitment, agent relations or partnerships. `build_contacts.py` rejects duplicate universities and duplicate addresses.
   - Every recipient address was checked against the saved evidence files (24 of 24 found).
4. **International Medicine status.** This comes from the site's reference facts, which were verified on the universities' official course pages.
   - Where the source watcher has since flagged the fact, the CSV says "re-check".
   - Agent appointment and international admission were checked separately.
5. **Access limits.** Seven sites answered the reader with a bot challenge (403 or 405): Oxford, UCL, Dundee, Leicester, East Anglia, Edge Hill and Manchester Metropolitan. These are recorded as PENDING VERIFICATION, not as negative findings.

## Summary

| Measure | Count |
|---|---|
| Universities researched (entries) | 53 (covering all 55 database rows) |
| Verified agent-relations contacts (an address the university publishes for agents or new partners) | 7 |
| Official agent application or expression-of-interest forms | 4 (York for Hull York Medical School, Wolverhampton, Northumbria, Bangor) |
| General international-recruitment contacts only | 18 |
| Not accepting new agents, or not using agents | 3 (Warwick, Swansea, Imperial) |
| Nigeria held by an exclusive agency | 1 (Manchester: "IDP is the only agency representing The University of Manchester in Nigeria") |
| Still needing verification | 21 (7 of them blocked by bot protection) |
| Ready-to-send enquiries (verified address, nothing ruling agents out) | 24 (23 medical schools and Sunderland for healthcare) |

## Priority groups

### A. International Medicine and a published route for new agents (6)

| University | Route | Evidence |
|---|---|---|
| University of Nottingham | agents@nottingham.ac.uk (Agent Relations Team) | Invites "enquiries about working as a University of Nottingham representative" |
| Lancaster University | internationalrecruitment@lancaster.ac.uk | "If you have any general enquiries or are interested in working with us please contact…"; has official Nigeria agents; pays commission |
| Brighton and Sussex Medical School (University of Brighton) | InternationalOffice@brighton.ac.uk | "If you're a new agent looking to partner with us, please contact our International Office"; holds agent conferences in Nigeria |
| University of Plymouth | international@plymouth.ac.uk | Agent toolkit section "Working with us as a new agent" |
| Hull York Medical School (University of York) | Online new-agents form | British Council training required before an agreement; two referees; **FORM REQUIRED — OWNER REVIEW** |
| University of Wolverhampton | Expression of interest form | "If you wish to become an education agent… please complete the expression of interest form"; commission paid; **FORM REQUIRED — OWNER REVIEW** |

### B. International Medicine and a genuine recruitment contact, but no published new-agent process (20)

- **Have an agent-team address:** Edinburgh, Cardiff, Kent (Kent and Medway Medical School).
- **Have a regional or international-office address:**
  - Aston, Glasgow, Queen Mary, Queen's Belfast, Sheffield, Leeds;
  - Bristol, Exeter, Keele, Chester, St Mary's, Surrey;
  - Greater Manchester, Newcastle, Hertfordshire, Birmingham.
- **Manchester:** also in this group but on hold, because Nigeria is exclusive to IDP.
- **Edinburgh:** its agent guidance says "MBChB (Undergraduate Medicine)" is **ineligible for commission**.

### C. Further investigation: healthcare-only universities, or medical schools where the route is not established (24)

- **Healthcare-only, or Medicine closed to international applicants:**
  - Northumbria and Bangor: both have a form;
  - Sunderland: has an International Office address and an office in Nigeria;
  - Anglia Ruskin, Edge Hill, Lincoln, Manchester Metropolitan.
- **Medical schools without a verified agent contact:**
  - Aberdeen, Brunel, Buckingham, Cambridge, King's College London;
  - Lancashire, Liverpool, Southampton, St Andrews, City St George's;
  - Worcester, Ulster.
- **Blocked to the reader:** Leicester, Oxford, UCL, Dundee, East Anglia.

### Not to be contacted (3)

- **Warwick:** "not currently looking for any new agents or representation".
- **Swansea:** "We are not currently recruiting new agents."
- **Imperial:** "We do not have agreements with any agents… We do not pay commissions". This also covers the Pears Cumbria School of Medicine.

## The first 15 to approach, and why

1. **Nottingham.** It publicly invites enquiries about becoming a representative, to a named agent-relations team.
2. **Lancaster.** It invites organisations "interested in working with us", already has official Nigeria agents, and pays commission per student.
3. **Brighton (Brighton and Sussex Medical School).** It explicitly invites new agents and runs agent conferences in Nigeria. Commission exemptions are in "schedule one" of the contract, so ask whether Brighton and Sussex Medicine is commissionable.
4. **Plymouth.** It publishes guidance for new agents, and has an International Office contact and an existing Nigeria network.
5. **York (Hull York Medical School).** It has an open online form, but requires British Council certification and two referees: owner decision.
6. **Wolverhampton.** It has an open expression-of-interest form, international MBChB and published commission: owner decision.
7. **Kent (Kent and Medway Medical School).** It has an agents team address and an agent handbook that names Nigeria, and sets commission by agreement.
8. **Cardiff.** It has a dedicated agents team, a selective but active advisor network and commission on enrolment.
9. **Hertfordshire.** It has a new medical school open to international applicants, and a university team for Nigeria and Ghana.
10. **Glasgow.** It has a named International Recruitment Manager for Africa and a West Africa officer based in Lagos.
11. **Aston.** It has a named Regional Manager for Africa and an existing Nigerian agent network.
12. **Newcastle.** It has an Africa regional address and pays commission to its approved representatives.
13. **Queen Mary.** It has a named Regional Manager for Africa, and pays commission to contracted agents.
14. **Sheffield.** It has a staff representative based in Nigeria, and pays commission to official agents.
15. **Leeds.** It has an International Office contact on its representatives page and pays commission.

Edinburgh has an agent contact but excludes MBChB from commission, so it ranks below these. It is still worth contacting about healthcare programmes.

## Points the owner should weigh before replies arrive

- **Agents must be certified.**
  - York requires British Council training before an agreement.
  - Kent requires its counsellors to be trained.
  - Nottingham lists only British Council-trained representatives.
  - The free British Council UK Agent and Counsellor training is the practical first step.
- **Our public statements would have to change.** The site currently states "We receive no commission from universities" and "We are not an agent of… any university" (About, Our status).
  - If any agreement is signed, those pages must be updated before the agreement is used.
  - The National Code requires agents to disclose when both the student and the university pay.
  - This is why the drafts include one transparency sentence that is not in the original template.
- **Each Medicine programme must be confirmed.**
  - Programme-level commission exclusions are usually in contract schedules, not on public pages.
  - Edinburgh is the only one published.
- **References.** Several processes ask for references from institutions the agent already works with (York's form asks for two referees). A first-time agency should expect this question.
