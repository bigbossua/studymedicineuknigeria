# Security audit — 2026-10-03

Scope: read-only review of authorisation, two-step verification, the document pipeline, Stripe webhooks and payments, mass assignment, session/CSRF/headers, information leakage and file writes. Method: a delegated code audit whose every finding was re-verified by reading the code; `finfo` behaviour checked against crafted files.

## Findings and resolution

See `docs/IMPLEMENTATION-LOG.md` stage 25 for the table. All ten findings are fixed in commit "Fix the security audit findings" except the PDF object-stream limitation, which is recorded as an open backlog item.

## Controls verified as sound

- Every portal controller checks `application.user_id === auth()->id()` and that nested document, version and submission records belong to the application; admin nested routes 404 on mismatch.
- `EnsureStaff` plus inline admin checks on role changes and two-step resets; self-demotion blocked.
- TOTP: RFC 6238 with constant-time comparison, per-counter replay prevention, 8-attempt / 15-minute limiter plus route throttle, session regenerated on pass, pass marker cleared on every login, secret and recovery codes encrypted and hidden, recovery codes hashed and single-use; staff cannot disable.
- Stripe: signature verified, idempotent by event id, amounts only from `tier_prices`, price must belong to the application's tier, return page read-only.
- Uploads: content-sniffed MIME allow-list (HTML/SVG rejected), JPEG/PNG re-encoded through GD, per-type size caps, UUID paths on the non-served private disk, downloads logged, `nosniff` + `no-store`.
- Mass assignment: `User` uses `$fillable`; every `update()` on guarded-empty models receives validated keys only; `role` never user-settable.
- CSRF everywhere except the webhook; cookies encrypted (consent cookie deliberately plain), SameSite lax, HttpOnly, secure in production.
- Headers: nonce CSP, `frame-ancestors 'none'`, HSTS, `noindex`/`no-store` on private paths; no third-party scripts in portal or admin.
- StagingGate constant-time comparison, exempting only `/up` and webhooks.
- Rate limits on login, registration, reset and two-step endpoints; non-enumerating reset message; session regenerated on login, invalidated on logout.
- No log call writes secrets or document contents; audit payloads hold validated fields only; OG card writes only to a slug-bound path; console commands take paths from CLI options.
- No raw SQL with interpolated input.
