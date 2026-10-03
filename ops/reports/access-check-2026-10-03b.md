# Access check — 2026-10-03, second cycle (after "credentials configured")

## What was run

| Run | Result |
|---|---|
| Inspect Hostinger (read-only) #3 and #4 | failed at "Check configuration": `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_USER`, `HOSTINGER_SSH_KEY` all empty inside the job. No SSH attempt was made. |
| Diagnose Actions settings #1 and #2 | repository scope: every expected secret and variable **missing**. Environments discovered via the API: `production`, `staging` (both created by earlier workflow runs). Inside each: every expected secret and variable **missing**. |
| Direct SSH from the Claude environment | still blocked by network policy (`studymedicineuknigeria.com:65002`, `:22`, previous IPs). No Hostinger-related environment variables are present here either. |

The diagnosis never prints values, only PRESENT / MISSING, and can be re-run by the owner at any time: Actions → "Diagnose Actions settings" → Run workflow.

## Conclusion

Nothing was fabricated and nothing was deployed. The repository `bigbossua/studymedicineuknigeria` currently holds **no GitHub Actions secrets and no Actions variables** at repository level or in either environment, so the credentials were saved somewhere else. The most common places this happens:

1. **Settings → Secrets and variables → Codespaces** or **→ Dependabot** instead of **→ Actions** (three separate tabs; only Actions reaches workflows).
2. A different repository or a fork (the public key "SMUKN-GitHub-Actions" being in hPanel is independent of where the private half was stored).
3. The Claude cloud environment's settings. Those would reach a new Claude session, not GitHub Actions, and the Claude environment cannot open SSH connections anyway, so they would not enable the deployment path.

## Exactly what must exist (names are case-sensitive)

Repository: https://github.com/bigbossua/studymedicineuknigeria

- Secrets tab (https://github.com/bigbossua/studymedicineuknigeria/settings/secrets/actions → "New repository secret"):
  - `HOSTINGER_SSH_KEY` = full private key file of `smukn_deploy` (the one whose public key is in hPanel as "SMUKN-GitHub-Actions"), including the `-----BEGIN … PRIVATE KEY-----` and `-----END …-----` lines
  - `BACKUP_PASSPHRASE` = a long random passphrase (also kept in the owner's password manager)
- Variables tab (https://github.com/bigbossua/studymedicineuknigeria/settings/variables/actions → "New repository variable"):
  - `HOSTINGER_SSH_HOST` = hostname or IP shown in hPanel → Advanced → SSH Access
  - `HOSTINGER_SSH_PORT` = `65002` (shared/Business/Cloud) or `22` (VPS)
  - `HOSTINGER_SSH_USER` = the SSH username shown there (shared hosting: `u` followed by digits)

Since this cycle the inspection workflow also accepts `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT` and `HOSTINGER_SSH_USER` stored as *secrets* instead of variables, so either tab works for those three. Environment-level placement (`production` / `staging`) is also fine for the deploy, bootstrap and backup workflows; the inspection workflow reads repository level.

## Next automated step

The moment "Diagnose Actions settings" shows `S_KEY: PRESENT` and `V_HOST`/`V_USER` (or `S_HOST`/`S_USER`) PRESENT, run "Inspect Hostinger (read-only)". Its artifact `hostinger-inspection` is the input for the live-server report; nothing is deployed before that report is reviewed.
