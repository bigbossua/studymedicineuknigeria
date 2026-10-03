# 14. Document architecture

Status: ARCHITECTURE. Derived from brief sections 27, 28, 29, 63, 108, 109.

## 14.1 Document types (catalogue)

| Code | Name | Typical format | Notes |
|---|---|---|---|
| PASSPORT | International passport data page | JPG/PNG/PDF | validity ≥ 6 months past intended course end is a *staff* check, not automated |
| WAEC | WASSCE certificate / statement of result | PDF/JPG | scratch-card verification status recorded by staff |
| NECO | NECO SSCE certificate | PDF/JPG | |
| ALEVEL | A-level certificates / statements | PDF | |
| IB | IB diploma results | PDF | |
| DEGREE_CERT | Degree certificate | PDF | GEM route |
| TRANSCRIPT | Academic transcript | PDF | may be multiple |
| ENGLISH | English test report (IELTS/TOEFL/PTE) | PDF | expiry 2 years is a staff check |
| UCAT | UCAT score report | PDF | |
| GAMSAT | GAMSAT result | PDF | |
| STATEMENT | Personal statement | PDF/DOCX or typed in portal | |
| REFERENCE | Reference letter | PDF | |
| CV | CV | PDF | GEM / direct-application schools |
| EXPERIENCE | Work-experience evidence | PDF | optional |
| FINANCIAL | Proof of funds (for CAS/visa stage only) | PDF | **collected only after offer**, never at application |
| OTHER | Staff-defined | any allowed | title required |

## 14.2 Document status machine

```
NOT_REQUIRED → (rule or staff) → REQUIRED
REQUIRED → upload → UPLOADED (scan pending) → scan ok → UNDER_REVIEW
                                            → scan fail → REJECTED (reason: security)
UNDER_REVIEW → staff → ACCEPTED
UNDER_REVIEW → staff → REJECTED (reason text required) → student → REPLACEMENT_REQUIRED → upload → UPLOADED …
ACCEPTED → staff "request replacement" (e.g. expired) → REPLACEMENT_REQUIRED
any → NOT_REQUIRED (staff waives, reason logged)
```

"Awaiting upload" in the brief = `REQUIRED` with no file. Every transition writes `document_events` (who, when, from, to, reason).

## 14.3 Personalised checklist rules engine

Rules are data (`checklist_rules` table), evaluated at `APPLICATION_STARTED` and re-evaluated whenever the relevant form answers change. Each rule: `when` (JSON predicate over application fields) → `require` (document codes) with `reason` text shown to the student.

Seed rules (v1):

| When | Require | Reason shown |
|---|---|---|
| always | PASSPORT, STATEMENT | identity; every medical school requires a statement/application text |
| secondary.board includes WAEC | WAEC | you told us you sat WASSCE |
| secondary.board includes NECO | NECO | you told us you sat NECO |
| post_secondary includes A-level | ALEVEL | |
| post_secondary includes IB | IB | |
| entry_type = graduate OR post_secondary includes degree | DEGREE_CERT, TRANSCRIPT, CV | graduate-entry and direct-application schools ask for these |
| english.route in (IELTS, TOEFL, PTE) and score present | ENGLISH | |
| tests.ucat.taken = true | UCAT | |
| tests.gamsat.taken = true | GAMSAT | |
| referees.count ≥ 1 and tier ∈ {2,3} | REFERENCE | |
| target university route = DIRECT | university-specific list from `courses.required_documents[]` | that university's published list |
| stage ≥ OFFER | FINANCIAL | only needed for CAS/visa |

Staff can add a `REQUIRED` item ad hoc (brief 63 "Request document") with a reason; this triggers the `document.requested` event and the student email "New document required".

## 14.4 Upload pipeline (security)

1. **Client** → pre-check: allowed MIME (`application/pdf`, `image/jpeg`, `image/png`; DOCX only for STATEMENT), size ≤ 10 MB (PDF) / 8 MB (image), max 5 files per document item.
2. **Server** (authenticated, CSRF, rate-limited 20 uploads/10 min/user) receives to a quarantine disk outside the web root.
3. **Validation**: magic-byte sniffing (not extension), image re-encode (strips EXIF/metadata and neutralises polyglots), PDF parsed with a hardened library; reject encrypted PDFs and PDFs with JavaScript/embedded files.
4. **Malware scan**: ClamAV daemon where available (VPS); on shared hosting, fall back to a queued external scanner API *only if* the data-processing agreement is acceptable, otherwise flag `scan=UNAVAILABLE` and require staff to open files only in the admin's sandboxed viewer (PDF rendered to images server-side). Document the choice in the DPIA.
5. **Storage**: private disk (`storage/app/private/applications/{application_number}/{document_code}/{version}-{uuid}.{ext}`) or S3-compatible bucket with no public ACL. Filenames are UUIDs; original filename stored in DB only.
6. **Encryption**: at rest — disk-level on VPS or SSE on object storage; additionally application-level AES-256-GCM for PASSPORT and FINANCIAL (key in environment/secret manager, rotated yearly, key id stored per file).
7. **Delivery**: never a public URL. Downloads via authenticated controller that checks ownership/role, logs access (`document_access_log`: user, doc, version, ip, time, purpose), streams with `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`. Temporary signed URLs (≤ 5 min) only for in-admin previews.
8. **Robots**: `/storage` not web-exposed; `Disallow` is irrelevant because nothing is reachable.

## 14.5 Versioning

Replacing creates version n+1; previous versions kept until application `COMPLETED`+90 days or deletion, whichever first; only the current version appears in packages. Hash (SHA-256) stored per version, used in the authorisation snapshot (12.6).

## 14.6 Staff review UI (admin)

Queue sorted by oldest `UNDER_REVIEW`. Viewer shows rendered pages (server-side rasterised, so staff never execute the file), the student's form data for cross-check (name on passport vs profile), and buttons: **Accept** · **Reject (reason template + free text)** · **Request replacement**. Reason templates: unreadable, cropped, wrong document, name mismatch, expired, missing pages, not certified (if required).

## 14.7 Notifications tied to document events

| Event | Student | Staff |
|---|---|---|
| document.requested | email + dashboard | — |
| document.uploaded | in-app "Received — under review" | queue count |
| document.accepted | email (batched daily if several) | — |
| document.rejected | email immediately, with reason and Replace link | — |
| all required accepted | email "Documents complete — your file is now in review" | notification |
| reminder (REQUIRED, no upload) | day 3, day 10, then weekly digest | — |

## 14.8 Retention and deletion (UK GDPR + NDPA aligned; INFERENCE — confirm with counsel)

- Active applications: retained for the cycle.
- After `COMPLETED`/`CLOSED`/`WITHDRAWN`: documents deleted 12 months after closure; application metadata anonymised at 24 months except financial records (6 years, UK tax) and authorisation records (6 years, contract evidence) which keep application number, dates and hashes but not documents.
- Deletion requests: honoured within 30 days unless a legal hold applies; student informed.
- Access logs retained 24 months.
- Backups: encrypted, 30-day rolling; deletion propagates on backup expiry (documented in privacy notice).

## 14.9 Audit trail

Tables: `document_events`, `document_access_log`, `application_events`, `admin_actions`. Immutable (insert-only; no update/delete grants for the app DB user on these tables). Exportable per application as a PDF appendix for disputes.
