# Email deliverability checklist (owner action, £0)

The application sends verification links, password resets, document and approval notifications and staff alerts from `info@studymedicineuknigeria.com` through the Hostinger mailbox (`MAIL_*` in `shared/.env`, see `.env.production.example`). Nigerian and UK recipients on Gmail, Yahoo and Outlook will junk or reject mail from a domain without authentication. Do these once, before the first student registers.

| Step | Where | What to set | How to verify |
|---|---|---|---|
| 1. Mailbox | hPanel → Emails | the `info@` mailbox exists; note the SMTP host, port 465 (SSL) or 587 (TLS), username = full address | send one test message from webmail |
| 2. SPF | hPanel → Domains → DNS | one TXT record at `@` that authorises Hostinger's mail servers (hPanel's Emails section shows the exact value; there must be only **one** SPF record) | `dig TXT studymedicineuknigeria.com` shows a single `v=spf1 … -all` or `~all` record |
| 3. DKIM | hPanel → Emails → DKIM (or "Email authentication") | enable; hPanel adds the selector record itself or shows the TXT to add | hPanel shows DKIM "active"; a test message to a Gmail account shows `DKIM: PASS` under "Show original" |
| 4. DMARC | DNS | TXT at `_dmarc` with `v=DMARC1; p=none; rua=mailto:info@studymedicineuknigeria.com; fo=1` to start; move to `p=quarantine` after two weeks of clean reports | `dig TXT _dmarc.studymedicineuknigeria.com` |
| 5. App config | GitHub → Actions secret `SMUKN_MAIL_PASSWORD`; `MAIL_HOST/PORT/USERNAME/FROM_ADDRESS` in the bootstrap workflow variables | `MAIL_FROM_ADDRESS=info@studymedicineuknigeria.com`, `MAIL_FROM_NAME="Study Medicine UK Nigeria"` | after staging bootstrap: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@…')->subject('SMUKN test'))` |
| 6. Reply handling | mailbox | replies to notifications land in `info@`; check it daily or forward to the operator | reply to a test notification |

Rules the application already follows: one message per real event, plain-text alternative on every mail, unsubscribe is not needed (transactional only), no marketing mail is sent at all. Do not buy a third-party sending service unless Hostinger's mailbox limits are hit; record the decision in `ops/reports/` if that day comes.
