# Live server access check — 2026-10-03 12:19 UTC

Purpose: establish whether a READ-ONLY inspection of the Hostinger environment for studymedicineuknigeria.com is possible from the Claude cloud session. No changes were attempted on any server.

| Check | Result |
|---|---|
| Hostinger SSH credentials supplied (host, port, user, key or password) | **None.** No credentials in the session environment, no files in `~/.ssh`, none provided in the conversation. |
| SSH client in the build container | **Absent.** `ssh` is not installed; `apt-get install openssh-client` failed (package archives not reachable from the container). |
| DNS for studymedicineuknigeria.com | Resolves: 145.223.124.33 (IPv4) and two IPv6 addresses (Hostinger range 2a02:4780::/32). |
| TCP 22 to 145.223.124.33 | **Unreachable** (connection fails). |
| TCP 65002 (Hostinger shared-hosting SSH port) | **Unreachable** (connection fails). |
| TCP 21 (FTP) | Unreachable. |
| HTTPS to studymedicineuknigeria.com via the session proxy | **Refused by network policy** (CONNECT 403). The live site's homepage, robots.txt and SSL certificate therefore could not be read either. |
| Hostinger management endpoints (api.hostinger.com, hpanel.hostinger.com) via proxy | Refused by network policy. |
| Raw TCP 80/443 to 145.223.124.33 | Connects, but the reply is the sandbox's transparent egress gateway (HTTP 426), not the Hostinger server. Not a usable route. |

## Conclusion

A live read-only inspection is **not possible** from this session. Nothing about the current installation (PHP version, document root, CMS, database, SSL, deployment method, backups) has been observed, and nothing has been assumed.

## What is required to proceed

1. **Credentials**: SSH host (typically the server hostname or IP shown in hPanel → Advanced → SSH Access), SSH port (65002 on Hostinger shared/Business hosting; 22 on VPS), SSH username (hPanel shows it, e.g. `u123456789`), and either an SSH key (preferred: add the session's public key in hPanel) or the password. Share these through the environment's secrets, not in chat.
2. **Network**: the Claude cloud environment's network policy must allow outbound connections to the Hostinger host on the SSH port (and HTTPS to studymedicineuknigeria.com so the live site and certificate can be read). Edit the environment's Network access (broader level, or Custom with the host added).
3. **Tooling**: with network access restored, `openssh-client` can be installed in the container, or the inspection script `ops/inspect-hostinger.sh` can be run from any machine that can reach the server and its output pasted into `ops/reports/`.

Alternative if the above cannot be granted: run `ops/inspect-hostinger.sh` yourself over SSH (it is read-only) and share the output file; the 16-point report, staging deployment and backups will proceed from it.
