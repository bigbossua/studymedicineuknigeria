# Live access investigation — 2026-10-03 (12:30–12:50 UTC)

Goal: get the Laravel 13 application from this repository onto Hostinger (staging first, then production) with backup and rollback, despite the Claude cloud environment being unable to reach the Hostinger server.

## 1. What was tested and what failed

| # | Test | Result | Why it failed |
|---|---|---|---|
| 1 | Outbound SSH from the cloud container to the Hostinger host (both resolved IPs 147.79.79.12 and 145.223.124.33, ports 65002 and 22), using the generated ed25519 key | **Connection timed out** before any SSH banner | The cloud environment ("Default — trusted network access") routes outbound traffic through an egress proxy that permits only approved HTTPS hosts; raw TCP to arbitrary hosts/ports is dropped. SSH never reaches the server, so the installed public key could not even be tested. |
| 2 | HTTPS to studymedicineuknigeria.com, hpanel.hostinger.com, api.hostinger.com via the proxy | CONNECT 403 (policy denial) | Hosts not in the environment's allowed list. Hostinger's own API/hPanel are therefore not usable from here either. |
| 3 | `HOSTINGER_SSH_*` environment variables | Not present in this session | Variables are injected at session start; anything added to the environment after this session began is not visible until a new session. |
| 4 | Can this session change the environment's network policy or add variables itself? | **No** | Environment settings are owner-controlled in the claude.ai UI (environment menu → Edit). No tool in this session can edit them. |
| 5 | Could the session restart itself into a less restricted environment? | **No** | `list_environments` shows a single environment ("Default"); `create_session` can only target existing environments, and this one carries the same policy. |
| 6 | Remote Control (Claude running on the owner's own machine) | Not available from this session | It must be started from the owner's computer (Claude Desktop or `claude remote-control`). It would make the owner's machine the SSH client; viable as a manual fallback, not as automation. |
| 7 | `git push` to github.com through the session's git proxy | 403 "Claude doesn't have GitHub access to bigbossua/studymedicineuknigeria for your organization" | **The Claude GitHub App is not installed on the repository.** |
| 8 | GitHub REST API reads via the session token (`/user`, `/repos/...`, `/actions/runs`, `/actions/workflows`) | 200, admin permissions reported | Reads are permitted; the repository is public and empty (no branches). |
| 9 | GitHub REST API Git Data writes (`POST /git/blobs`, `/git/trees`) | 403 "Write access to this GitHub API path is not permitted through this proxy" | The proxy's write policy for raw API calls excludes Git Data writes. |
| 10 | GitHub REST API Actions secrets/variables/permissions (`/actions/secrets/public-key`, `/actions/variables`, `/actions/permissions`) | 403 "Access to this GitHub Actions path is not permitted through this proxy" | Deliberate proxy restriction: this session cannot create or read repository secrets. |
| 11 | GitHub MCP connector `push_files` (Contents API) to the designated branch | 403 "Resource not accessible by integration" | Same root cause as #7: the GitHub App integration has no access to this repository. |
| 12 | Hostinger "Git deployment" (hPanel → Git) as an alternative to SSH | Not testable from here (hPanel unreachable) | Would also require the repository to exist on GitHub first (#7), and cannot run Composer, migrations or backups by itself. |

## 2. Root cause (two independent blockers)

1. **Network**: the Claude cloud environment cannot open SSH connections to Hostinger. This is by design of the environment's network policy and cannot be changed from inside a session.
2. **GitHub**: the Claude GitHub App is not installed on `bigbossua/studymedicineuknigeria`, so no code can be published from any Claude session (git, REST or MCP) until it is.

## 3. Alternatives investigated

| Route | Verdict |
|---|---|
| A. Open the environment's network policy for the Hostinger host and connect directly from Claude cloud | Works for SSH, but (a) only the owner can change it, (b) a new session is probably needed to pick it up, which loses the key generated in this container, and (c) it does nothing for the GitHub blocker. Not chosen as primary. |
| B. **GitHub Actions as the deployment agent**: the repository lives on GitHub; a workflow runner (unrestricted outbound network) performs the read-only inspection and the staged deployment over SSH using `ops/inspect-hostinger.sh` and `ops/deploy.sh`; the private key lives in GitHub Actions Secrets, never in a Claude session or the repository | **Chosen.** Persistent (no dependence on an ephemeral container), auditable (every run logged), supports backup, migrations and rollback, and is the industry-standard shape. Requires the GitHub App installation (#7) and the owner to add one secret and three variables in the repository settings (#10 prevents the session from doing it). |
| C. Hostinger Git deployment pulling a pre-built `deploy` branch | Possible later as a simplification for production pulls, but needs B's build step anyway and cannot handle `.env`, database creation, migrations or rollback. Kept as an optional add-on, not the main route. |
| D. Remote Control from the owner's computer | Valid manual fallback for a one-off inspection if B is delayed. Not automation. |

## 4. Solution selected and what has been built (non-destructive)

- `.github/workflows/inspect-hostinger.yml` — manual dispatch; checks that `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_USER` (variables) and `HOSTINGER_SSH_KEY` (secret) exist and fails with a clear message if not; verifies SSH authentication; runs the read-only inspection script; redacts anything password-like; uploads the report as a workflow artifact (7-day retention). Nothing on the server is modified.
- `.github/workflows/deploy-hostinger.yml` — manual dispatch with `staging` or `production` (production only by explicit dispatch; pushes to a `staging` branch deploy staging automatically); runs the full test suite before any deploy; uses `ops/deploy.sh`.
- `ops/deploy.sh` — now takes a target (`staging` | `production`), deploys into `~/apps/smukn-<target>/releases/<timestamp>`, backs up the database before migrating, switches a `current` symlink, keeps five releases, runs `ops/smoke.sh` and rolls back automatically on failure. It refuses to run if `shared/.env` is missing on the server.
- `ops/github-mirror.py` — history-preserving mirror via the Git Data API; blocked today by #9 but kept for environments where API writes are allowed.
- `ops/ssh-setup.sh` — loads a key from `HOSTINGER_SSH_PRIVATE_KEY` for any future Claude session that is allowed to SSH directly.

## 5. What remains blocked, and the one action that unblocks the chain

**Install the Claude GitHub App on the repository** (https://github.com/apps/claude/installations/select_target → select `bigbossua/studymedicineuknigeria`), or reconnect GitHub at https://claude.ai/customize/connectors?auth_start=github&auth_start_force=1 to re-link an existing installation. This single step unblocks publishing the branch; the workflows then exist on GitHub.

After that, the deployment agent needs its credentials, which the session is prevented from setting (#10): in the repository, Settings → Secrets and variables → Actions, add the secret `HOSTINGER_SSH_KEY` (an SSH private key whose public half is in hPanel) and the variables `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT` (65002 on shared hosting), `HOSTINGER_SSH_USER`, plus `STAGING_URL` once a staging subdomain exists. Because the key generated in this container cannot leave it safely, the recommended key for the secret is one generated on the owner's machine (`ssh-keygen -t ed25519 -C smukn-deploy`) with its public half added in hPanel alongside the existing one.

## 6. Observations recorded for the server report

- DNS for studymedicineuknigeria.com resolved to 145.223.124.33 at 12:16 UTC and to 147.79.79.12 at 12:25 UTC; both are Hostinger ranges. hPanel's SSH Access page is authoritative for the SSH hostname.
- The repository on GitHub is **public**. The application contains no secrets, but the owner should decide whether it should be private before the first push. (The Actions route works for either.)

## 7. Update — 2026-10-03 13:05 UTC: GitHub blocker cleared

- The owner installed the Claude GitHub App on `bigbossua/studymedicineuknigeria` (only). `git push` succeeded; branch `claude/new-session-p6gdm6` is now on GitHub at the same commit as the local HEAD and is the repository's default branch.
- Pre-push secret scan: no `.env`, keys, SQLite database or private storage files are tracked; no secret patterns in tracked content. `vendor/`, `node_modules/` and `public/build/` are absent on GitHub as intended.
- The push triggered the CI workflow on a clean runner: Composer install, asset build, Pint and the 21-test suite **passed** (run 37124727045).
- Remaining: the Hostinger inspection and deploy workflows must be registered by GitHub Actions (dispatch-only workflows can take a few minutes to appear), and the repository needs the SSH secret and connection variables before any run can reach the server.

## 8. Update — 2026-10-03 13:30 UTC: deployment agent verified on GitHub

- Repository published: default branch `claude/new-session-p6gdm6`; CI passed on every push (6 runs). Framework-skeleton workflows removed; remaining: CI, Inspect Hostinger (read-only), Deploy to Hostinger, Dependabot updates. Dependabot PR #1 (Actions version bumps) is open and untouched.
- Both Hostinger workflows registered after a re-index push (GitHub had indexed only the push-triggered CI workflow from the first push).
- Dispatched the inspection workflow with no credentials configured (run 37125079386): it failed at "Check configuration" with the intended message naming the missing settings, and all SSH steps were skipped. No connection to the server was attempted. The guard works.
- Blocked on: `HOSTINGER_SSH_KEY` (secret) and `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_USER` (variables) in the repository's Actions settings.
