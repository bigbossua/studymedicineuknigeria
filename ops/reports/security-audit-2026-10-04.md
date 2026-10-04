# Security audit — 2026-10-04 (second full audit; first: security-audit-2026-10-03.md)

Read-only review of authentication, two-step verification, authorisation and IDOR, documents, output encoding, CSRF and webhooks, rate limits, mass assignment, payments, audit logging, configuration, and code added since 2026-10-03. Every finding was verified in code (most with a throwaway probe test) and is now fixed with a regression test in `tests/Feature/SecurityAuditFixesTest.php` unless noted.

| # | Severity | Finding | Fix | Test |
|---|---|---|---|---|
| 1 | Medium | Any `Host` header was trusted: a forged host produced a real password-reset email whose link carried the token to the attacker's domain; the `/index.php` redirect echoed the host | `TrustHosts` accepts only the APP_URL host (www is redirected to the apex by `.htaccess` first); `CanonicalHost` middleware and the console boot force every generated URL onto APP_URL outside local; the redirect uses APP_URL | forged host → reset link on APP_URL; production refuses a foreign host with 400; redirect never echoes the host |
| 2 | Medium | `ops/deploy.sh` replays every `decisions-*.csv` on each deploy with no ledger: a later review (SOURCE_CHANGED, REVIEW_DUE) was reverted to VERIFIED with the old value | `fact_imports` ledger keyed by file content hash; a file applies once per environment (`--force` to re-apply) | import, change to SOURCE_CHANGED, replay → unchanged |
| 3 | Low | `new_source_url` accepted `javascript:` and any scheme | https only, validated | `javascript:` row skipped, fact unchanged |
| 4 | Low | Worksheet export open to spreadsheet formula injection | cells starting `= + - @` tab or CR are prefixed with an apostrophe | `=HYPERLINK(...)` exported escaped |
| 5 | Low | The admin document preview's sandbox CSP was overwritten by the site CSP | `SecurityHeaders` keeps a response's own policy | preview response starts `sandbox` |
| 6 | Low | Non-admin staff could change prices and delete redirects; omitting the Stripe field raised a 500 | admin only; `?? null` | staff get 403; admin save works without the field |
| 7 | Low | `//login` passed the reserved-path rule and was stored as `/login` | normalise before validating; `/` and empty refused | `//login`, `//admin`, `/`, empty all refused |
| 8 | Low | A pending TOTP secret survived sign-in and `confirm()` could replace an enrolled authenticator | confirm redirects to the challenge when enrolled; sign-in discards any pending secret | both scenarios |
| 9 | Low | Student text rendered as Markdown in staff emails (working links) | Markdown escaped in every staff notification line | planted link renders as text |
| 10 | Low | The CLI import's audit log used `info`, dropped by production's `warning` level | `Log::warning` plus the ledger row | `Log::spy` warning |
| 11 | Low | A password change or reset did not end other sessions or remember-me cookies | `auth.session` on the signed-in groups; password change rotates the remember token and calls `logoutOtherDevices` | an old session is signed out after a change |
| 12 | Low | Login throttling was per email and IP only | second limit per account (50 failures an hour) | 50 failures from 50 addresses lock the account |
| 13 | Low | Decompression bombs: unbounded `gzuncompress` on PDF streams and image decoding without a size check | incremental inflation capped at 16 MB a stream and 64 MB in total, an object stream that does not fit is refused; images over 40 megapixels refused before decoding | 17 MB object stream refused; 20,000² PNG refused |
| 14 | Low | A never-approved proposal could be moved straight to a university-response status | response statuses only after SUBMITTED | ACKNOWLEDGED / OFFER / INTERVIEW refused on a proposal (`ApplicationWorkflowTest`) |
| 15 | Info | The reset form revealed whether an email had an account | unknown email gets the invalid-token message | same message for known and unknown |
| 16 | Info | worksheet commands accepted `../` paths; `mysqldump -p` exposed the password in the process list; approval did not check a withdrawn application | paths confined to the project; password passed through `MYSQL_PWD`; approval refused on terminal applications | path test; others by review |

Confirmed still in place from the first audit: the two-step disable needs the passed challenge, the email-verification import, the data-export allow-list, macro-enabled Word refusal and safe download names, autosave field allow-lists, active-price-only checkout, admin-only relative redirects (now also normalised), late payment-failure handling, `JSON_HEX_TAG` JSON-LD, PDF stream inspection (now capped), constant-time staging gate.

Clean areas: email verification, IDOR across all 113 routes, two-step replay and recovery codes, document storage and encryption, raw output, CSRF and Stripe signatures, public POST rate limits, mass assignment, payment amounts, admin audit coverage, demo-account seeding, configuration defaults.
