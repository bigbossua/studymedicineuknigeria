#!/usr/bin/env python3
"""Builds uk-university-agent-contacts.csv from the reviewed evidence below.

Every record cites the official page it rests on and the page-excerpts run that read it on 2026-10-28
(.github/workflows/page-excerpts.yml; specs in specs/). Nothing here is taken from a search snippet or a
third-party directory. International Medicine status comes from the site's reference facts (Admin -> Facts),
which were verified on official course pages; a fact the source watcher has since flagged is marked "re-check".

Run: python3 research/university-agent-partners/build_contacts.py
"""
import csv
import os

VERIFIED_ON = '2026-10-28'
SUBJECT = 'International Student Recruitment Partnership Enquiry — Nigeria | StudyMedicineUKNigeria.com'
RUNS = {
    'b1': 'crawl batch 1, run 40667843991', 'b2': 'crawl batch 2, run 40667848213', 'b3': 'crawl batch 3, run 40667852149',
    'b4': 'crawl batch 4, run 40667857540', 'b5': 'crawl batch 5, run 40669265189', 'b6': 'crawl batch 6, run 40670755663',
    'p2a': 'page pass 2a, run 40669255569', 'p2b': 'page pass 2b, run 40669260249',
}
READY = 'READY TO SEND — NOT SENT (no mailbox access from this session)'

# Outreach statuses, as defined in OUTREACH-LOG.md:
#   READY TO SEND — NOT SENT, FORM REQUIRED — OWNER REVIEW, NOT ACCEPTING AGENTS, NO VERIFIED CONTACT,
#   PENDING VERIFICATION, HOLD — OWNER REVIEW
R = []


def add(**k):
    k.setdefault('contact_name', '')
    k.setdefault('agent_application_url', '')
    k.setdefault('recipient_email', '')
    k.setdefault('outreach_status', READY if k.get('recipient_email') else 'NO VERIFIED CONTACT')
    R.append(k)


# ---------------- Group A: international Medicine and a published route for new agents ----------------
add(university='University of Nottingham', medical_school='University of Nottingham School of Medicine', courses='BMBS Medicine (A100); healthcare (nursing, pharmacy and others)',
    intl_medicine='accepts international applicants (verified fact)', group='A', research_status='VERIFIED CONTACT',
    recipient_email='agents@nottingham.ac.uk', contact_team='Agent Relations Team',
    source_url='https://www.nottingham.ac.uk/studywithus/international-applicants/contact-us/representatives/representatives.aspx',
    evidence='"For any enquiries or correspondence about our representatives – including … enquiries about working as a University of Nottingham representative – please contact the Agent Relations Team"', run='p2b',
    agent_recruitment='Invites enquiries about becoming a representative (Agent Relations Team)', nigeria='Nigeria country page published; listed representatives require British Council training',
    commission='Not stated on the page read', next_action='Send the enquiry to the Agent Relations Team; ask about Nigeria and Medicine eligibility')
add(university='Lancaster University', medical_school='Lancaster Medical School', courses='MBChB Medicine and Surgery (A100); healthcare (nursing and others)',
    intl_medicine='accepts international applicants (verified fact)', group='A', research_status='VERIFIED CONTACT',
    recipient_email='internationalrecruitment@lancaster.ac.uk', contact_team='International Student Recruitment Office',
    source_url='https://www.lancaster.ac.uk/study/international-students/find-an-agent/',
    evidence='"If you have any general enquiries or are interested in working with us please contact internationalrecruitment@lancaster.ac.uk"', run='p2b',
    agent_recruitment='Invites organisations interested in working with the University to make contact', nigeria='"Our official agents for Nigeria are listed below" (existing Nigeria network)',
    commission='"Contracted recruitment agents … receive a commission-based payment … for every student they support and recruit"', next_action='Send the enquiry; ask whether Nigeria is open to a new partner')
add(university='Brighton and Sussex Medical School (University of Brighton)', medical_school='Brighton and Sussex Medical School (joint, Universities of Brighton and Sussex)', courses='BM BS Medicine (A100); Brighton healthcare (nursing, pharmacy, paramedic science and others)',
    intl_medicine='accepts international applicants (verified fact; 10 international places per year)', group='A', research_status='VERIFIED CONTACT',
    recipient_email='InternationalOffice@brighton.ac.uk', contact_team='University of Brighton International Office',
    source_url='https://www.brighton.ac.uk/international/international-agents/index.aspx',
    evidence='"New agents – If you\'re a new agent looking to partner with us, please contact our International Office on InternationalOffice@brighton.ac.uk with more information."', run='p2a',
    agent_recruitment='Explicitly invites new agents to contact the International Office', nigeria='Brighton hosted agent conferences in Nigeria and Ghana (official page)',
    commission='Commission paid twice a year; exempt courses listed in "schedule one" of the agent contract (not public)',
    next_action='Send the enquiry; ask whether BSMS Medicine is commissionable for Brighton agents (Sussex runs a separate network: agents@sussex.ac.uk)')
add(university='University of Plymouth', medical_school='Peninsula Medical School', courses='BMBS Medicine; healthcare (nursing, dietetics, paramedic science and others)',
    intl_medicine='accepts international applicants (verified fact)', group='A', research_status='VERIFIED CONTACT',
    recipient_email='international@plymouth.ac.uk', contact_team='International Office',
    source_url='https://www.plymouth.ac.uk/international/international-agent-toolkit',
    evidence='Agent toolkit has a "Working with us as a new agent" section; the International Office handles queries about the agent network and contracts', run='p2b',
    agent_recruitment='Published "Working with us as a new agent" guidance (AQF and British Council certification)', nigeria='Existing agents in Nigeria listed on the Nigeria agents page',
    commission='Not stated on the pages read', next_action='Send the enquiry to the International Office')
add(university='Hull York Medical School (University of York)', medical_school='Hull York Medical School', courses='MBBS Medicine; York healthcare (nursing, midwifery and others)',
    intl_medicine='accepts international applicants (verified fact)', group='A', research_status='APPLICATION FORM REQUIRED',
    recipient_email='', contact_team='University of York International team (international-agents@york.ac.uk)',
    agent_application_url='https://www.york.ac.uk/study/international/agents/new-agents-form/',
    source_url='https://www.york.ac.uk/study/international/agents/',
    evidence='"If you\'d like to be considered as an overseas representative for York you\'ll need to complete our online form." "All University of York agents must have completed British Council training … prior to signing a partnership agreement." The form asks for two referees.', run='b2',
    agent_recruitment='Open: online new-agent application form', nigeria='Nigeria is selectable on the form; no restriction stated',
    commission='Privacy notice mentions "making commissions payments" to agents', outreach_status='FORM REQUIRED — OWNER REVIEW',
    next_action='Owner to decide whether to complete the York form (needs British Council certification and two referees); the University of Hull agent page returned 404')
add(university='University of Wolverhampton', medical_school='School of Medicine (Black Country)', courses='MBChB Medicine; healthcare (nursing, pharmacy, biomedical science and others)',
    intl_medicine='accepts international applicants (verified fact)', group='A', research_status='APPLICATION FORM REQUIRED',
    recipient_email='', contact_team='Global Opportunities Office',
    agent_application_url='https://www.wlv.ac.uk/international/education-agents-/ (expression of interest form linked from this page)',
    source_url='https://www.wlv.ac.uk/international/education-agents-/',
    evidence='"If you wish to become an education agent and represent the University of Wolverhampton, please complete the expression of interest form and this will be reviewed by the account manager and senior members of the Global Opportunities Office."', run='p2b',
    agent_recruitment='Open: expression of interest form ("we may look to compliment and strengthen our current portfolio")', nigeria='No restriction stated',
    commission='"Agents receive a commission for students who enrol through their services."', outreach_status='FORM REQUIRED — OWNER REVIEW',
    next_action='Owner to review and complete the expression of interest form')

# ---------------- Group B: international Medicine and a genuine recruitment contact, no published new-agent process ----------------
add(university='University of Edinburgh', medical_school='Edinburgh Medical School', courses='MBChB Medicine (6-year); healthcare (nursing and others)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='VERIFIED CONTACT',
    recipient_email='Representatives@ed.ac.uk', contact_team='International Recruitment Team (agent contact)', contact_name='Alistair Hudson',
    source_url='https://student-recruitment.ed.ac.uk/agent-guidance/contact',
    evidence='"Contact information for agents looking to get in touch. Alistair Hudson … Email: Representatives@ed.ac.uk"', run='p2a',
    agent_recruitment='Agent contact published; no published new-agent process', nigeria='No restriction stated',
    commission='"As per our agreement, the following programmes are ineligible for commission: … MBChB (Undergraduate Medicine)" (https://student-recruitment.ed.ac.uk/agent-guidance/commission-and-contracts)',
    next_action='Send the enquiry, acknowledging that MBChB is excluded from commission; ask about healthcare programmes')
add(university='Cardiff University', medical_school='Cardiff University School of Medicine', courses='MBBCh Medicine; healthcare (nursing, pharmacy, healthcare sciences)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='VERIFIED CONTACT',
    recipient_email='agents@cardiff.ac.uk', contact_team='Agents Team',
    source_url='https://www.cardiff.ac.uk/study/international/educational-advisors/for-advisors',
    evidence='"If you are unable to access the Agent Portal, contact the Agent Team: Agents Team agents@cardiff.ac.uk"; "We have a selective recruitment process" (for-students page)', run='p2a',
    agent_recruitment='Selective recruitment of contracted advisors; no published application route', nigeria='No restriction stated',
    commission='"contracted partners … are paid a commission/fee following the successful enrolment of a student" (for-students page)', next_action='Send the enquiry to the Agents Team')
add(university='University of Kent (Kent and Medway Medical School)', medical_school='Kent and Medway Medical School (joint, Kent and Canterbury Christ Church)', courses='BM BS Medicine; Kent and CCCU healthcare',
    intl_medicine='accepts international applicants (verified fact; core facts flagged by the source watcher, re-check)', group='B', research_status='VERIFIED CONTACT',
    recipient_email='agents@kent.ac.uk', contact_team='University of Kent agents team',
    source_url='https://media.www.kent.ac.uk/se/55341/AgentHandbook2026-27.pdf',
    evidence='Agent Handbook 2026-27 lists agents@kent.ac.uk; "The commission percentage payable is set out in your agreement"; counsellors must complete British Council training', run='b2',
    agent_recruitment='Agent network with handbook; no published new-agent process', nigeria='Nigeria named in the 2026-27 handbook among markets',
    commission='Commission percentage set in the agent agreement (handbook)', next_action='Send the enquiry; ask whether KMMS Medicine falls under Kent agreements')
add(university='Aston University', medical_school='Aston Medical School', courses='MBChB Medicine; healthcare (pharmacy, optometry, audiology and others)',
    intl_medicine='accepts international applicants (verified 2026-10-05; core facts flagged by the source watcher, re-check)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='africa@aston.ac.uk', contact_team='Africa regional team', contact_name='Babajide Ogundeji, Regional Manager for Africa',
    source_url='https://www.aston.ac.uk/international/aston-in-your-country/africa/nigeria',
    evidence='"You are welcome to contact our Regional Manager for Africa, Babajide Ogundeji, by email at b.ogundeji@aston.ac.uk or africa@aston.ac.uk."', run='b1',
    agent_recruitment='Overseas agents network (education-agents page); no published new-agent process', nigeria='Existing Nigerian agents listed (Lagos, Abuja)',
    commission='Not stated on the pages read', next_action='Send the enquiry to the Africa team address')
add(university='University of Glasgow', medical_school='University of Glasgow School of Medicine', courses='MBChB Medicine; healthcare (nursing, dentistry)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='mark.stansfield@glasgow.ac.uk', contact_team='International Recruitment (Africa)', contact_name='Mark Stansfield, International Recruitment Manager for Africa',
    source_url='https://www.gla.ac.uk/international/counsellors/keycontactsandrepresentatives/',
    evidence='"Mark Stansfield, International Recruitment Manager for Africa & Glasgow International College (based in Glasgow) mark.stansfield@glasgow.ac.uk"; also "Chisom Okpala, International Officer for West Africa (based in Lagos, Nigeria)"', run='b5',
    agent_recruitment='International representatives listed; no published new-agent process', nigeria='Nigeria listed among representative countries',
    commission='Not stated on the pages read', next_action='Send the enquiry to the Recruitment Manager for Africa')
add(university='Queen Mary University of London', medical_school='Barts and The London School of Medicine and Dentistry', courses='MBBS Medicine (A100); dentistry',
    intl_medicine='accepts international applicants (verified fact; flagged by the source watcher 2026-10-28, re-check)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='africa@qmul.ac.uk', contact_team='Regional team for Africa', contact_name='Anita Walton, Regional Manager – Africa',
    source_url='https://www.qmul.ac.uk/international-students/regions/africa/',
    evidence='"Your Queen Mary team for Africa: Anita Walton, Regional Manager – Africa, africa@qmul.ac.uk"; agents page: "If you have any questions, please contact globalengagement@qmul.ac.uk"', run='b5',
    agent_recruitment='Contracted agents; no published new-agent process', nigeria='Existing Nigeria representatives: Gateway Abroad, IDP Education, StudyIn',
    commission='"the agent may receive a commission" (agents page)', next_action='Send the enquiry to the Africa regional team')
add(university="Queen's University Belfast", medical_school="School of Medicine, Dentistry and Biomedical Sciences", courses='MB Medicine (A100); healthcare (nursing, pharmacy)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='africa@qub.ac.uk', contact_team='Sub-Saharan Africa recruitment team',
    source_url='https://www.qub.ac.uk/Study/international-students/your-country/sub-saharan-africa/',
    evidence='Sub-Saharan Africa page lists africa@qub.ac.uk; agents page links "Agents by Region" (Nigeria listed); agent@qub.ac.uk is for existing Queen\'s Portal agent accounts', run='b3',
    agent_recruitment='Agents and partner information published; no new-agent process', nigeria='Nigeria included in the Middle East and Africa agent region',
    commission='Not stated on the pages read', next_action='Send the enquiry to the Africa team')
add(university='University of Sheffield', medical_school='Sheffield School of Medicine and Population Health', courses='MBChB Medicine; healthcare (nursing, orthoptics, dentistry)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='udochika.ezeocha@sheffield.ac.uk', contact_team='Regional Representative for Sub-Saharan Africa (based in Nigeria)', contact_name='Udochika Ezeocha',
    source_url='https://www.sheffield.ac.uk/international/representatives',
    evidence='"Our Regional Representative for Sub-Saharan Africa is based in Nigeria and she can offer advice and support on your application to study at Sheffield." (udochika.ezeocha@sheffield.ac.uk listed)', run='b3',
    agent_recruitment='Official agents listed; no published new-agent process', nigeria='University staff representative based in Nigeria',
    commission='"The University of Sheffield pays a commission to our official agents"', next_action='Send the enquiry to the regional representative')
add(university='University of Leeds', medical_school='Leeds School of Medicine', courses='MBChB Medicine and Surgery; healthcare (nursing, midwifery, dentistry)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='internationaloffice@leeds.ac.uk', contact_team='International Office',
    source_url='https://www.leeds.ac.uk/international-meet-us/doc/representatives-country-region',
    evidence='Representatives page lists internationaloffice@leeds.ac.uk; "If an agent is not listed on this page, then the University does not maintain a relationship with them"', run='p2b',
    agent_recruitment='Appointed agents listed; no published new-agent process', nigeria='Representatives by country (Nigeria included in listings)',
    commission='"The University pays a commission to agents if you receive their support and enrol"', next_action='Send the enquiry to the International Office')
add(university='University of Bristol', medical_school='Bristol Medical School', courses='MB ChB Medicine; healthcare (dentistry, veterinary)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='international-office@bristol.ac.uk', contact_team='International Office',
    source_url='https://www.bristol.ac.uk/international/guidance-for-agents/',
    evidence='"If you have any issues with the agent portal or require further information, contact international-office@bristol.ac.uk."', run='p2a',
    agent_recruitment='Guidance for agents and portal; no published new-agent process', nigeria='No restriction stated',
    commission='Not stated on the pages read', next_action='Send the enquiry to the International Office')
add(university='University of Exeter', medical_school='University of Exeter Medical School', courses='BMBS Medicine; healthcare (nursing, sport sciences)',
    intl_medicine='accepts international applicants (verified 2026-10-05; flagged by the source watcher, re-check)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='intoff@exeter.ac.uk', contact_team='International Office',
    source_url='https://www.exeter.ac.uk/international-students/university-agents/',
    evidence='"The University of Exeter has entered into a commercial arrangement with the following education agents"; intoff@exeter.ac.uk receives agent forms. The "Request for Representation Form" is for students changing agent, not an agent application.', run='p2a',
    agent_recruitment='Contracted agents; no published new-agent process', nigeria='Nigeria listed among agent countries',
    commission='"If you enrol with us, we pay the education agent a commission/fee."', next_action='Send the enquiry to the International Office')
add(university='Keele University', medical_school='Keele University School of Medicine', courses='MBChB Medicine; healthcare (nursing, pharmacy, physiotherapy)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='international@keele.ac.uk', contact_team='International team',
    source_url='https://www.keele.ac.uk/study/internationalstudents/applyingwithanagent/',
    evidence='Agent framework 2024: "When looking to appoint new agents, a standardised process … research regarding the agent; initial contact and references; development of a clear business case"', run='p2a',
    agent_recruitment='Appoints new agents through an internal business-case process; no public application', nigeria='No restriction stated',
    commission='"If you enrol with us, we pay the education agent a commission."', next_action='Send the enquiry to the International team')
add(university='University of Chester', medical_school='Chester Medical School', courses='Graduate-entry MBChB (A101, international places); healthcare (nursing, midwifery)',
    intl_medicine='accepts international applicants for graduate entry (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='international@chester.ac.uk', contact_team='International Office',
    source_url='https://www.chester.ac.uk/international/working-with-agents/',
    evidence='Working with Agents page lists International@chester.ac.uk for agent verification and complaints', run='p2a',
    agent_recruitment='Works with selected agents; no published new-agent process', nigeria='No restriction stated',
    commission='Not stated on the page read', next_action='Send the enquiry; graduate-entry Medicine only')
add(university="St Mary's University, Twickenham", medical_school="St Mary's University School of Medicine", courses='Medicine (new school); healthcare and sport science',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='international@stmarys.ac.uk', contact_team='International team',
    source_url='https://www.stmarys.ac.uk/international/agent-directory.aspx',
    evidence='"If you have any questions, feel free to contact our international team at international@stmarys.ac.uk."; works with agents meeting British Council and BUILA standards', run='p2b',
    agent_recruitment='Agent directory; no published new-agent process', nigeria='A Nigerian agent is listed in the directory',
    commission='Not stated on the page read', next_action='Send the enquiry; confirm the Medicine programme start and GMC status first')
add(university='University of Surrey', medical_school='University of Surrey School of Medicine', courses='Graduate-entry Medicine; healthcare (nursing, paramedic science)',
    intl_medicine='accepts international applicants (verified fact; flagged by the source watcher, re-check)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='international@surrey.ac.uk', contact_team='International team',
    source_url='https://www.surrey.ac.uk/international/university-surrey-and-agent-quality-framework',
    evidence='AQF page lists international@surrey.ac.uk', run='p2b',
    agent_recruitment='AQF commitment published; no published new-agent process', nigeria='No restriction stated',
    commission='Not stated on the page read', next_action='Send the enquiry to the International team')
add(university='University of Greater Manchester', medical_school='University of Greater Manchester Medical School', courses='MBChB Medicine (international applicants only); healthcare',
    intl_medicine='international applicants only (verified fact; flagged by the source watcher, re-check)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='afbo@greatermanchester.ac.uk', contact_team='Africa Branch Office (AFBO)',
    source_url='https://greatermanchester.ac.uk/international/afbo',
    evidence='"The staff will be the first and only point of contact for all African Applicants and will manage the entire admission\'s process from application to enrolment."', run='p2b',
    agent_recruitment='Direct Africa branch office; whether agents are appointed for Africa is not stated', nigeria='Africa branch office handles all African applicants',
    commission='Not stated on the page read', next_action='Send the enquiry; the branch-office model may exclude agents')

add(university='Newcastle University', medical_school='Newcastle University School of Medicine', courses='MBBS Medicine (A100); healthcare (dentistry, pharmacy, nursing-related programmes)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='africa.io@ncl.ac.uk', contact_team='International Office Africa team',
    source_url='https://www.ncl.ac.uk/international/contact/',
    evidence='"Regional contacts – Africa – africa.io@ncl.ac.uk"; agents page: "Contact the international recruitment team for further registration guidance"; the Nigeria page names Joan Apeh as a regional officer', run='b6',
    agent_recruitment='Approved representatives worked with by the Global Recruitment Team; no published new-agent process', nigeria='Nigeria country page with a named regional officer',
    commission='"The University pays commission to these agents if you receive their support and enrol at Newcastle."', next_action='Send the enquiry to the Africa regional address')
add(university='University of Hertfordshire', medical_school='Hertfordshire Medical School', courses='MBBS Medicine (new school, international entry requirements published); healthcare (nursing, pharmacy, paramedic science)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='Nigeria@herts.ac.uk', contact_team='In-country team for Nigeria and Ghana', contact_name='Bolanle Durodola',
    source_url='https://www.herts.ac.uk/international/contact-us/in-country-representatives',
    evidence='"Nigeria and Ghana – Bolanle Durodola – Bolanle and her team support students from: Nigeria and Ghana – Contact the team on Nigeria@herts.ac.uk"', run='b6',
    agent_recruitment='"Find an agent" page and university in-country teams; no published new-agent process', nigeria='University in-country team for Nigeria',
    commission='Not stated on the pages read', next_action='Send the enquiry to the Nigeria team; ask for the agent-relations contact')
add(university='University of Birmingham', medical_school='Birmingham Medical School', courses='MBChB Medicine and Surgery; healthcare (nursing, pharmacy, dentistry)',
    intl_medicine='accepts international applicants (verified 2026-10-05; flagged by the source watcher, re-check)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='africa@contacts.bham.ac.uk', contact_team='Africa recruitment team',
    source_url='https://www.birmingham.ac.uk/study/scholarships-funding/africa-high-fliers-undergraduate-scholarships',
    evidence='Africa scholarship pages list africa@contacts.bham.ac.uk and name "our agent partners / local representatives in Nigeria"; the representatives page routes enquiries through a feedback form', run='b6',
    agent_recruitment='Agent partners listed for Nigeria; no published new-agent process', nigeria='Existing Nigerian agent partners listed',
    commission='Not stated on the pages read', next_action='Send the enquiry to the Africa team')

# ---------------- Held or excluded ----------------
add(university='University of Manchester', medical_school='Manchester Medical School', courses='MBChB Medicine; healthcare (nursing, pharmacy, dentistry)',
    intl_medicine='accepts international applicants (verified fact)', group='B', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='', contact_team='International team (international@manchester.ac.uk)',
    source_url='https://www.manchester.ac.uk/study/international/country-specific-information/nigeria/contacts/',
    evidence='"IDP is the only agency representing The University of Manchester in Nigeria."', run='p2b',
    agent_recruitment='Contracted agents; Nigeria represented by one agency', nigeria='EXCLUSIVE: IDP is the only agency for Nigeria',
    commission='"The University pays commission to a contracted agent"', outreach_status='HOLD — OWNER REVIEW',
    next_action='Do not send a first-contact enquiry for Nigeria: the territory is held exclusively by IDP')
add(university='University of Warwick', medical_school='Warwick Medical School', courses='Graduate-entry MBChB; healthcare',
    intl_medicine='accepts international applicants (verified fact)', group='X', research_status='NOT ACCEPTING AGENTS',
    recipient_email='', contact_team='int.recruitment@warwick.ac.uk (not to be used for a new-agent enquiry)',
    source_url='https://warwick.ac.uk/study/international/agent/',
    evidence='"University of Warwick is not currently looking for any new agents or representation."', run='p2b',
    agent_recruitment='NOT ACCEPTING new agents', nigeria='n/a', commission='Commission paid to authorised agents only',
    outreach_status='NOT ACCEPTING AGENTS', next_action='Do not contact; re-check the agent page in six months')
add(university='Swansea University', medical_school='Swansea University Medical School', courses='Graduate-entry MBBCh; healthcare (nursing, paramedic science)',
    intl_medicine='accepts international applicants (verified fact)', group='X', research_status='NOT ACCEPTING AGENTS',
    recipient_email='', contact_team='iro@swansea.ac.uk (current agents only)',
    source_url='https://www.swansea.ac.uk/international-students/our-agents/agent-resources/',
    evidence='"Are you recruiting new agents? We are not currently recruiting new agents."', run='p2b',
    agent_recruitment='NOT ACCEPTING new agents', nigeria='n/a', commission='"If you enrol with us, we pay the education agent working with us a commission."',
    outreach_status='NOT ACCEPTING AGENTS', next_action='Do not contact; re-check in six months')
add(university='Imperial College London (including the Pears Cumbria School of Medicine)', medical_school='Imperial College London Faculty of Medicine', courses='MBBS Medicine (A100); Pears Cumbria graduate entry is home-only',
    intl_medicine='accepts international applicants (verified 2026-10-06; flagged by the source watcher, re-check)', group='X', research_status='NOT ACCEPTING AGENTS',
    recipient_email='', contact_team='international-recruitment@imperial.ac.uk (student and adviser enquiries only)',
    source_url='https://www.imperial.ac.uk/study/international-students/agents/',
    evidence='"No agreements: We do not have agreements with any agents or counsellors for the purposes of student recruitment. No commissions: We do not pay commissions for student referrals."', run='p2b',
    agent_recruitment='DOES NOT USE AGENTS', nigeria='n/a', commission='None paid', outreach_status='NOT ACCEPTING AGENTS', next_action='Do not contact')

# ---------------- Needs further verification (international Medicine, no verified agent contact yet) ----------------
for u, ms, intl, src, ev, run, nxt in [
    ('University of Aberdeen', 'University of Aberdeen School of Medicine', 'accepts international applicants (verified fact)', 'https://www.abdn.ac.uk/study/international/working-recruitment-partners/',
     'Works with contracted recruitment partners after "a thorough review before being contracted"; commission-based payment; no agent contact e-mail published (ukteam@abdn.ac.uk is the UK schools team)', 'p2a', 'Find the Africa recruitment contact (enquiry form only so far)'),
    ('Brunel University of London', 'Brunel Medical School', 'accepts international applicants (verified 2026-10-05; flagged, re-check)', 'https://www.brunel.ac.uk/international/Applying-to-Brunel-University-of-London-through-an-Agent',
     'Agents paid commission; recruitment-support@brunel.ac.uk is for complaints about agents, not partnerships; regional managers are on "Your Country" pages', 'p2a', 'Read the Nigeria "Your Country" page for the regional manager'),
    ('University of Buckingham', 'University of Buckingham Medical School', 'accepts international applicants (verified fact)', 'https://www.buckingham.ac.uk/international/coming-from/nigeria/',
     'Nigeria page lists appointed Nigerian representatives; "suspended the recruitment of students from Nigeria for Postgraduate Research programmes … January 2027" (not Medicine); no agent-appointment contact', 'p2a', 'Find the International Officer for Nigeria'),
    ('University of Cambridge', 'School of Clinical Medicine', 'accepts international applicants (verified fact)', 'https://www.undergraduate.study.cam.ac.uk/international-students',
     'No agent page (404); only general admissions (admissions@cam.ac.uk) and counsellor events; no evidence that Cambridge appoints agents', 'b1', 'Low priority; do not e-mail general admissions about agency'),
    ("King's College London", "GKT School of Medical Education", 'accepts international applicants (verified fact)', 'https://www.kcl.ac.uk/study/international/region/africa',
     'Africa page read; no agent or recruitment e-mail found; agent URLs returned 404', 'b5', 'Further verification'),
    ('University of Lancashire', 'School of Medicine and Dentistry', 'accepts international applicants (verified fact)', 'https://www.lancashire.ac.uk/international-students/agencies',
     '"international agencies and representatives are paid commission"; AQF; no contact e-mail on the page', 'p2b', 'Find the Nigeria/Africa recruitment contact'),
    ('University of Liverpool', 'School of Medicine', 'accepts international applicants (verified fact)', 'https://www.liverpool.ac.uk/international/about-agents/choosing-and-managing-agents/',
     'Approved-agent network incl. Nigeria (Bridge House Counselling, Sable, TAG); commission paid; no contact e-mail on the pages', 'p2b', 'Find the agent-relations contact'),
    ('University of Southampton', 'Faculty of Medicine', 'accepts international applicants (verified fact)', 'https://www.southampton.ac.uk/study/international',
     'No agent page found from the international hub', 'b3', 'Further verification'),
    ('University of St Andrews', 'School of Medicine', 'BSc Medicine (A100) accepts international applicants; ScotGEM home-only (verified facts)', 'https://www.st-andrews.ac.uk/subjects/entry/local-representatives/',
     '"We do not work with any agent, counsellor, or sub-agent whose details do not appear on our official pages." No new-agent route or e-mail', 'p2b', 'Further verification'),
    ("City St George's, University of London", "City St George's School of Health and Medical Sciences", 'accepts international applicants (verified fact)', 'https://www.citystgeorges.ac.uk/prospective-students/international-students',
     'No agent page found from the international hub', 'b5', 'Further verification'),
    ('University of Worcester', 'Three Counties Medical School', 'accepts international applicants (verified fact)', 'https://www.worcester.ac.uk/international/',
     'No agent page found', 'b5', 'Further verification'),
    ('Ulster University', 'School of Medicine', 'accepts international applicants (verified fact)', 'https://www.ulster.ac.uk/global',
     'Agent URLs returned 404; study-abroad advisers are not agent contacts', 'b5', 'Further verification'),
    ('University of Leicester', 'Leicester Medical School', 'accepts international applicants (verified fact)', 'https://le.ac.uk/study/international-students/how-to-apply/university-of-leicester-agents-and-representatives',
     'BLOCKED: le.ac.uk answered 403 (bot challenge) to the official-page reader', 'b2', 'Owner to open the page in a browser'),
    ('University of Oxford', 'Oxford Medical School', 'international status not recorded in our facts', 'https://www.ox.ac.uk/admissions/undergraduate/applying-to-oxford/guide/agents',
     'BLOCKED: ox.ac.uk answered 403 (bot challenge)', 'b5', 'Owner to open the page in a browser'),
    ('University College London', 'UCL Medical School', 'international status not recorded in our facts', 'https://www.ucl.ac.uk/prospective-students/international/',
     'BLOCKED: ucl.ac.uk answered 403 (bot challenge)', 'b5', 'Owner to open the page in a browser'),
    ('University of Dundee', 'School of Medicine', 'international status not recorded in our facts', 'https://www.dundee.ac.uk/countries/nigeria',
     'BLOCKED: dundee.ac.uk answered 403 (bot challenge)', 'b5', 'Owner to open the page in a browser'),
    ('University of East Anglia', 'Norwich Medical School', 'accepts international applicants (fact not yet verified)', 'https://www.uea.ac.uk/study/international/agents',
     'BLOCKED: uea.ac.uk answered 405 (human verification)', 'b5', 'Owner to open the page in a browser'),
]:
    add(university=u, medical_school=ms, courses='Medicine (see the school page)', intl_medicine=intl, group='C', research_status='NEEDS FURTHER VERIFICATION',
        contact_team='', source_url=src, evidence=ev, run=run, agent_recruitment='Not established', nigeria='Not established', commission='See evidence',
        outreach_status='PENDING VERIFICATION', next_action=nxt)

# ---------------- Group C: healthcare-only, or Medicine closed to international applicants ----------------
add(university='Northumbria University', medical_school='No medical school (healthcare: nursing, midwifery, allied health)', courses='Nursing, midwifery, physiotherapy, occupational therapy and others',
    intl_medicine='no Medicine degree', group='C', research_status='APPLICATION FORM REQUIRED',
    contact_team='Agent communications and events (u1.agent.comms.events@northumbria.ac.uk)',
    agent_application_url='https://www.northumbria.ac.uk/international/agent-information/ (New Agent and Representatives Application Form linked from this page)',
    source_url='https://www.northumbria.ac.uk/international/agent-information/',
    evidence='"If you are interested in becoming an Agent with Northumbria University please complete the Agent & Representative Application Form."', run='b5',
    agent_recruitment='Open: new agent application form', nigeria='Nigeria listed among representative countries', commission='Not stated on the page read',
    outreach_status='FORM REQUIRED — OWNER REVIEW', next_action='Secondary priority (healthcare only); owner to review the form')
add(university='Bangor University', medical_school='North Wales Medical School (Medicine home-only)', courses='Healthcare (nursing, midwifery, radiography and others)',
    intl_medicine='home applicants only (verified fact)', group='C', research_status='APPLICATION FORM REQUIRED',
    contact_team='International team', contact_name='Kerry Jones',
    agent_application_url='https://www.bangor.ac.uk/international/agent-tool-kit (application form linked from this page)',
    source_url='https://www.bangor.ac.uk/international/agent-tool-kit',
    evidence='"If you\'re interested in becoming an agent for Bangor University you will need to complete the following application form and return the completed form … to Kerry Jones: kerry.jones@bangor.ac.uk"', run='p2b',
    agent_recruitment='Open: application form returned by e-mail', nigeria='No restriction stated', commission='Not stated on the pages read',
    outreach_status='FORM REQUIRED — OWNER REVIEW', next_action='Secondary priority: Medicine is home-only; healthcare courses only')
add(university='University of Sunderland', medical_school='School of Medicine (Medicine home-only)', courses='Healthcare (nursing, pharmacy, paramedic science and others)',
    intl_medicine='home applicants only (verified fact)', group='C', research_status='GENERAL ENQUIRY ONLY',
    recipient_email='international.office@sunderland.ac.uk', contact_team='International Office',
    source_url='https://www.sunderland.ac.uk/international/agents-and-partners/',
    evidence='Agents and partners pages list international.office@sunderland.ac.uk; "We also manage overseas offices in … Nigeria"', run='b4',
    agent_recruitment='Agents and partners programme with compliance requirements; no published new-agent form', nigeria='University runs an overseas office in Nigeria',
    commission='Not stated on the pages read', next_action='Secondary priority (healthcare only); send after the Medicine group')
for u, ms, intl, src, ev, run in [
    ('Anglia Ruskin University', 'School of Medicine (Medicine home-only)', 'home applicants only (MSC listing)', 'https://www.aru.ac.uk/international', 'Agent URLs returned 404; international@aru.ac.uk is a general international address', 'b5'),
    ('Edge Hill University', 'Medical School (Medicine home-only)', 'home applicants only (MSC listing)', 'https://www.edgehill.ac.uk/international/', 'BLOCKED: edgehill.ac.uk answered 403 (browser check)', 'b5'),
    ('University of Lincoln', 'Lincoln Medical School (Medicine home-only)', 'home applicants only (verified fact)', 'https://www.lincoln.ac.uk/international/agents/', 'Agent URL redirected to the home page; no agent page found', 'b4'),
    ('Manchester Metropolitan University', 'No medical school (healthcare courses)', 'no Medicine degree', 'https://www.mmu.ac.uk/international/agents', 'BLOCKED: mmu.ac.uk answered 403 (bot challenge)', 'b5'),
]:
    add(university=u, medical_school=ms, courses='Healthcare courses', intl_medicine=intl, group='C', research_status='NEEDS FURTHER VERIFICATION',
        contact_team='', source_url=src, evidence=ev, run=run, agent_recruitment='Not established', nigeria='Not established', commission='Not established',
        outreach_status='PENDING VERIFICATION', next_action='Secondary priority (healthcare only)')

COLS = ['university', 'medical_school', 'courses', 'intl_medicine', 'group', 'research_status', 'source_url', 'evidence', 'evidence_run',
        'recipient_email', 'contact_team', 'contact_name', 'agent_application_url', 'agent_recruitment', 'nigeria', 'commission',
        'verified_on', 'outreach_status', 'sent_at', 'email_subject', 'delivery_status', 'next_action']
seen = set()
rows = []
for r in R:
    assert r['university'] not in seen, f"duplicate university {r['university']}"
    seen.add(r['university'])
    e = r.get('recipient_email', '')
    assert not e or e.lower() not in [x.get('recipient_email', '').lower() for x in rows], f'duplicate e-mail {e}'
    rows.append({**{c: '' for c in COLS}, **{k: v for k, v in r.items() if k in COLS}, 'evidence_run': RUNS[r['run']],
                 'verified_on': VERIFIED_ON, 'email_subject': SUBJECT if r['outreach_status'] == READY else '', 'sent_at': '', 'delivery_status': ''})
order = {'A': 0, 'B': 1, 'C': 2, 'X': 3}
rows.sort(key=lambda x: (order[x['group']], x['outreach_status'] != READY, x['university']))
out = os.path.join(os.path.dirname(__file__), 'uk-university-agent-contacts.csv')
with open(out, 'w', newline='') as fh:
    w = csv.DictWriter(fh, fieldnames=COLS, lineterminator='\n')
    w.writeheader()
    w.writerows(rows)
print(len(rows), 'universities ->', out)
