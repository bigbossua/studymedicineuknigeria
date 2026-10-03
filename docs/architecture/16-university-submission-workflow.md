# 16. University submission workflow

Status: ARCHITECTURE. Derived from brief sections 24, 32, 33, 34, 64, 65. Research on actual routes is in `docs/research/09-application-process-research.md`.

## 16.1 Submission routes (enumerated, extensible)

| Route code | Meaning | Who performs the final action | Platform's role |
|---|---|---|---|
| UCAS_STUDENT | Standard UCAS undergraduate application (A100/A101 etc.) | **the student**, in their own UCAS Hub account | prepare everything; step-by-step guided copy-across; checklist of UCAS sections; deadline tracking; the student records the UCAS Personal ID and submission date in our portal; we never hold the student's UCAS password |
| UCAS_CENTRE | UCAS application linked to a registered centre/adviser | the student submits; the centre adds the reference and approves | only available if/when we are a UCAS-registered centre — `NOT CURRENTLY AVAILABLE` until documented |
| DIRECT_PORTAL_STUDENT | University's own online application form (international/direct schools) | the student | guided completion; package provided; student records reference |
| DIRECT_AGENT | Submission by us through a university's agent portal / email channel | **staff**, on the student's behalf | only where a **signed agent/representative agreement** exists; `NOT CURRENTLY AVAILABLE` for every university until documentation is uploaded to the admin "Universities → Agreements" area; the UI must hide this route for universities without an agreement |
| PATHWAY_PROVIDER | Foundation/pathway provider application | student or staff per provider terms | as above, agreement-gated |

**Rule (enforced in code):** `DIRECT_AGENT` and `UCAS_CENTRE` are selectable only when `university.agreements` contains an active, staff-verified agreement record (document upload + start/end dates + scope). No agreement → route not offered → no claim of representation anywhere on the site.

## 16.2 Submission entity

```
submission {
  id, application_id, university_id, course_id, intake, route_code,
  status: PROPOSED → AUTHORISED → PACKAGE_READY → SUBMITTED → ACKNOWLEDGED → (INTERVIEW | OFFER_CONDITIONAL | OFFER_UNCONDITIONAL | REJECTED | WAITLISTED) → (ACCEPTED_BY_STUDENT | DECLINED) → CLOSED,
  authorisation_id, package_version, package_hash,
  submitted_at, submitted_by (user or 'STUDENT'), external_reference (UCAS Personal ID / university ref), submission_evidence_document_id (screenshot/confirmation email),
  university_response_notes[], response_documents[] (offer letter etc.),
  created_at, updated_at
}
```

One application may have several submissions (e.g. 4 UCAS choices are one UCAS_STUDENT submission with 4 `choices[]`; plus a direct-application school as a second submission).

## 16.3 Student approval package (what the student sees before approving)

Rendered page + downloadable PDF, generated from the snapshot:

1. **Target**: university, course title and code, intake, route and *who will press submit* in plain words.
2. **Applicant summary**: name as on passport, DOB, nationality, contact.
3. **Academic record**: structured tables as entered.
4. **Documents included**: list with version, upload date, status ACCEPTED, SHA-256 short hash.
5. **Personal statement**: full text / file.
6. **References**: referee details and whether the letter is included.
7. **Service & fee status**: tier, paid/not paid, what remains payable, and the sentence "University tuition and application fees are separate."
8. **Declarations**: accuracy, no-guarantee, data sharing with the named university only.
9. **Authorisation** (12.6): checkbox + typed name + button "Approve and authorise".

A "Request changes" secondary action returns the file to `ACTION_REQUIRED` with the student's note.

## 16.4 Package preparation (staff)

- PDF cover sheet (application number, target, contents list, hashes) + ordered documents merged into one PDF per university where the route requires attachments; original files retained.
- For `UCAS_STUDENT`: a **UCAS copy-across guide** generated from the snapshot: each UCAS Hub section with the exact text/values to enter, qualification mapping (WAEC subjects → UCAS "Other" qualification entries as the student must enter them), and the three personal-statement answers if the student drafted them in-portal.
- Package is immutable once `PACKAGE_READY`; any change requires a new version and re-authorisation.

## 16.5 Audit trail (brief 65)

`submission_events` insert-only: who, when, from→to, route, external reference, evidence document id, note. The admin "Submission record" view prints: student approval (date/time/name/IP), payment status at submission, package hash, submitter, confirmation evidence, every university response.

## 16.6 Deadlines

`courses.deadlines[]` drive countdowns in the portal and staff queue (UCAS 15 October for medicine; university direct deadlines; UCAT windows). Staff see "approval requested but not approved, deadline in N days" alerts.

## 16.7 What the public site may say about submission (copy rules)

- "We prepare your complete application and, where the university's process requires you to submit personally (for example through UCAS), we guide you step by step."
- Never: "we submit your UCAS application for you", "we are an authorised agent of X", "guaranteed admission".
- Where an agreement exists in future: "Official representative of X (agreement dated …)" with the agreement on file — only then.
