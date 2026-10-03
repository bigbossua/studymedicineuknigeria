# 13. Student portal architecture

Status: ARCHITECTURE. Derived from brief sections 25, 26, 60, 61, 62, 106, 110.

## 13.1 Account model

- One **user** = one email, verified. Password + optional magic-link login (reduces "forgot password" support for mobile users). Password reset by signed, expiring link.
- One user may have **one active application per intake year** (a student re-applying for 2028 after 2027 keeps history). Admin can merge duplicate accounts.
- Roles: `student`, `staff`, `admin`. Staff/admin accounts live in the same user table with role flags and mandatory 2FA (TOTP). Students may opt into 2FA.
- Sessions: HTTP-only, Secure, SameSite=Lax cookies; idle timeout 60 min for students, 30 min for staff; "remember this device" 30 days for students only.
- Rate limiting on login, reset and upload endpoints. Lockout after 10 failures/15 min with email notice.

## 13.2 Routes (private, `noindex`, behind auth)

```
/login  /register  /verify-email  /forgot-password  /reset-password
/portal                       → Dashboard
/portal/application           → step list (resumable)
/portal/application/{step}    → one step form, autosave
/portal/documents             → checklist with statuses
/portal/documents/{id}        → upload/replace, history, staff notes
/portal/payments              → tier, status, receipts, pay now
/portal/approve               → package preview + authorisation (only when stage allows)
/portal/submissions           → per-university target status + timeline
/portal/messages              → threaded messages with staff (system of record for comms)
/portal/profile               → contact details, password, 2FA, data export, delete request
```

All `/portal/*` responses send `X-Robots-Tag: noindex, nofollow` and `Cache-Control: private, no-store`.

## 13.3 Dashboard composition (mobile-first)

Order on a 360px screen:
1. Greeting + application number + intake.
2. **Next action card** (12.7) with one primary button. Never two primaries.
3. Progress ring (percent) + stage label in plain English.
4. Section checklist (Personal ✓ / Academic ✓ / Course preference ✓ / Passport ✓ / WAEC-NECO ⚠ Upload required …) — each row is a link.
5. Deadlines block: UCAS 15 Oct / university-specific / internal deadlines, with "days remaining".
6. Recent updates (last 5 events, human-readable).
7. Help: message staff; WhatsApp link (deep-link with application number prefilled in message text, never carrying documents).

No public marketing CTAs inside the portal (brief 21).

## 13.4 Application form: steps and autosave

Default steps (configurable per tier; each step has `required_for_tiers[]`):

1. **Personal details** — legal name as on passport, DOB, nationality, passport number (optional until documents), contact, WhatsApp, Nigerian state/city, current country of residence.
2. **Intended study** — course family (Medicine primary), entry type (standard / graduate / foundation), intake year, up to 4 preferred universities (optional), UCAS status (not started / started / submitted / have an offer).
3. **Secondary education** — school, exam board (WAEC / NECO / Cambridge / IB / other), year, subjects + grades (structured rows), English grade. Multiple sittings allowed.
4. **Post-secondary** — A-levels / IB / foundation / Nigerian degree(s): institution, award, class/CGPA, dates, transcript availability.
5. **English language** — route: IELTS / TOEFL / PTE / university assessment / none yet; score; date.
6. **Admissions tests** — UCAT (year, score, band) / GAMSAT / none yet / planned.
7. **Experience & statement** — work/volunteer experience rows; personal statement status (draft text area with word count; file upload alternative).
8. **Referees** — name, role, institution, email (we do not contact referees without student consent toggle).
9. **Declarations** — accuracy, data processing (UK GDPR + NDPA notices), terms of service, no-guarantee acknowledgement.

Autosave: PATCH on field blur and every 10 s when dirty; optimistic UI with "Saved · just now" indicator; conflict policy last-write-wins per field (single user). Server validates per step; the step is `complete` only when server validation passes. Students can jump between steps freely.

## 13.5 Document centre (student view)

- Checklist grouped: **Required now · Optional · Not required for you · Accepted**.
- Each item: name, why we need it (one line), accepted formats, example of a good scan, status chip, uploaded date, staff note if rejected, **Replace** button.
- Upload: drag/drop or camera on mobile; client-side size check; progress bar; immediate "Received — under review" state.
- Document history visible (v1, v2 …), only the latest version is "current".

## 13.6 Messages

Thread per application. Staff replies from admin. Email notification with excerpt and link, never full content (privacy). Attachments in messages go through the same secure document pipeline (14.x) and are typed `OTHER`.

## 13.7 Profile, privacy and account lifecycle

- Download my data (JSON + documents zip, generated async, link expires in 24 h).
- Request deletion → staff review (legal hold if a submission is in progress) → anonymisation job (14.8 retention).
- Communication preferences: transactional emails cannot be disabled; reminders can be set to weekly digest.

## 13.8 Accessibility and performance targets

- WCAG 2.2 AA; all forms keyboard-navigable; error summaries at top of step; labels always visible (no placeholder-only).
- Mobile data budget: portal pages < 150 KB JS; images lazy; works on 3G (test with throttling).
- Offline tolerance: if a save fails, keep field values client-side and retry with visible banner.
