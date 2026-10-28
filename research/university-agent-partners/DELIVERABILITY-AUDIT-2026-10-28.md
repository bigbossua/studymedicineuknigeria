# Deliverability audit: studymedicineuknigeria.com (2026-10-28)

**Scope.** This audits the domain behind the 23 partnership enquiries the owner reports were sent on 28 October 2026 from `info@studymedicineuknigeria.com` through a ChatGPT-connected Hostinger Mail account. It is read-only: nothing was sent, and no DNS, mailbox or server setting was changed.

**Evidence.** All public records were read on 2026-10-28 from GitHub-hosted runners. This session's container cannot resolve DNS itself.
- **Launch checks**, run 40691141004: `dig` for SPF, DMARC, MX and the DKIM selectors.
- **Page-excerpts run 40691154804:** full records through Google public DNS (DNS-over-HTTPS), domain blocklist lookups, and the registry's RDAP record.
- **Mail delivery diagnosis**, run 40691174950: the website's own mail settings and an SMTP sign-in test. That test sends nothing.

**Not available to this audit:**
- The mailbox: no browser or mailbox access, so Sent, Inbox, Junk and bounces were not read.
- The raw source of any sent message.

## 1. SPF: valid, one record, soft fail

- `studymedicineuknigeria.com TXT "v=spf1 include:_spf.mail.hostinger.com ~all"`. It is the only SPF record; the other TXT record is the Search Console token.
- It expands to `_spf.mail.hostinger.com TXT "v=spf1 include:relay.mail.hostinger.com include:relay.mailchannels.net ~all"`.
- **Result:** mail sent through Hostinger's servers (or its MailChannels relay) is authorised. Mail sent for this domain through any other server gets `softfail`.
- Not verified: the record's total DNS-lookup count, because the MailChannels include was not expanded.

## 2. DKIM: a valid key is published at one selector

- `hostingermail-a._domainkey` is a CNAME to `hostingermail-a.dkim.mail.hostinger.com`. It publishes `v=DKIM1;k=rsa;p=MIIBIjAN…` (a 2048-bit RSA key).
- `hostingermail-b` and `hostingermail-c` are CNAMEs to Hostinger records with an empty key (`v=DKIM1;p=`). These are Hostinger's rotation slots: an empty key means "not in use / revoked", so a message signed with b or c would fail.
- `default._domainkey` does not exist.
- **Not confirmed:** whether the 23 messages were actually DKIM-signed, and with which selector. That needs the received headers of one message.

## 3. DMARC: record exists, monitoring only, no reports

- `_dmarc.studymedicineuknigeria.com TXT "v=DMARC1; p=none"`. The syntax is valid.
- `p=none`: receivers take no action on failures. DMARC can therefore neither block nor protect these messages; the spam decision rests on the receiver's own filters.
- No `rua`, so no aggregate reports are sent anywhere. Nobody can see from reports whether the 23 messages passed SPF/DKIM alignment.

## 4. Alignment

**If the messages went out through Hostinger's SMTP as `info@studymedicineuknigeria.com`** (the normal path for a Hostinger mailbox; the website itself uses `smtp.hostinger.com:465`, sign-in OK):
- the From domain, the SPF domain (Return-Path normally the mailbox) and the DKIM `d=` (through the CNAMEd selector) should all be `studymedicineuknigeria.com`;
- DMARC would then pass.

**If the ChatGPT connection sent the mail from its own infrastructure rather than through Hostinger's authenticated SMTP:**
- SPF would softfail and there would be no aligned DKIM;
- DMARC would fail. With `p=none` the mail would still be delivered, but it would be far more likely to land in junk or be quarantined.

**Which path was used is unknown.** It is the most important open question.

## 5. Reputation and blocklists

**Domain blocklists:** `studymedicineuknigeria.com` returned NXDOMAIN (not listed) on three lists:
- Spamhaus DBL;
- SURBL multi;
- URIBL multi.

These lookups went through Google's public resolver, which some list operators refuse or answer differently. Treat this as an indicator, not a guarantee.

**Domain age:**
- Registered 2025-10-28T17:37:59Z at Hostinger (registry RDAP), so exactly one year old.
- Last changed 2026-10-04.
- DNSSEC is not signed.

A young domain with little sending history is a reputation **risk indicator**, not a finding of bad reputation.

**Sending IP: not checked.** The IP the 23 messages left from is unknown without the received headers, and it is a shared Hostinger (or MailChannels) relay that we do not control.

**Other public records:**
- No MTA-STS or TLS-RPT records. These are not delivery requirements.
- MX is `mx1/mx2.hostinger.com`, so replies and bounces can be received.

## 6. The 23 sent messages

**Not inspected.** This session has no mailbox access. These remain unestablished:
- that the 23 messages exist in Sent;
- their From, Return-Path, Message-ID and Authentication-Results;
- the server's acceptance or SMTP responses;
- bounces, rejection or quarantine notices;
- replies.

**The website's mail log** (application log, last 3 days) has no mail errors. That covers only the website's own notifications, not mail sent from the ChatGPT connection.

**Inbox versus spam placement cannot be confirmed from our side at all:**
- Receivers report placement only to themselves.
- A Sent-folder copy shows only that our server accepted the message.
- Only a reply, a non-delivery report, or the absence of bounces after a few days gives partial evidence.

**Risk indicators specific to this send** (not findings):
- 23 near-identical first-contact messages in one session;
- from a one-year-old domain with a generic `info@` address;
- to university gateways that commonly filter unsolicited messages (Microsoft 365, Mimecast, Proofpoint).

## 7. Proposed actions (none applied; each needs the owner's approval)

1. **Establish the sending path (highest priority, no DNS change).**
   - Open one of the 23 messages in Hostinger Webmail → *View source* and record:
     - `From` and `Return-Path`;
     - `Message-ID`;
     - the `Received` lines, which show the sending server and IP;
     - any `DKIM-Signature` header: `d=` and `s=`.
   - If there is no `DKIM-Signature`, or the first `Received` hop is not a Hostinger or MailChannels host, the messages were not authenticated as the domain.
2. **Read the mailbox for outcomes.** Check Inbox, Junk and any "Undelivered Mail Returned to Sender" or "Delivery Status Notification" messages from 28 October onwards, and record each one per recipient in `OUTREACH-LOG.md`.
3. **One test message to the owner's own Gmail or Outlook address**, from the same connected account in the same way. *Show original* then gives SPF, DKIM and DMARC verdicts. This is a single message to ourselves, not a campaign, but it needs approval.
4. **Turn on DMARC reporting (DNS change, approval needed).** Replace the `_dmarc` TXT with:
   - `v=DMARC1; p=none; rua=mailto:info@studymedicineuknigeria.com; fo=1`
   - This keeps `p=none`, so nothing is weakened or blocked, and starts daily aggregate reports.
   - After two to four weeks of reports showing only aligned Hostinger sources, consider `p=quarantine; pct=25`.
5. **Check DKIM is active in hPanel → Emails → DKIM** (or Email authentication). Selector a's key is published; hPanel shows whether signing is on.
6. **Keep SPF as it is** (`~all`).
   - Add a sender only if action 1 shows mail leaving through a non-Hostinger service. In that case either send through Hostinger's SMTP, or add that service's documented include (one record only, still `~all`).
7. **Pace any follow-up.**
   - No re-send to the 23 while their outcome is unknown. A duplicate to a university gateway increases filtering.
   - Space any later follow-ups over several days.
8. **Optional, low priority:** MTA-STS and TLS-RPT (`_smtp._tls` TXT `v=TLSRPTv1; rua=mailto:info@studymedicineuknigeria.com`), and DNSSEC at the registrar.
