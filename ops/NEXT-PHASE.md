# Next launch phase: runbook (in this order)

The site is live (stage 50) and the visual pass is done (stage 51). Nothing below is configured yet. Each step starts only when the one before it is done and the live site is stable. **You** marks an owner-only action; everything else Claude runs and verifies.

## 1. First admin account and portal

1. **You:**
   - Register at https://studymedicineuknigeria.com/register with your own email.
   - Open the confirmation email (from info@) and click the link.
2. Claude runs *Account role* (production, your email, `admin`). **You** approve it in GitHub (Review deployments).
3. **You:** sign in at /login. The first admin sign-in asks you to set up two-step verification with an authenticator app.
   - Store the recovery codes it shows you.
   - Without the authenticator, `smukn:two-factor-reset` is the only way back in.
4. Claude verifies from outside and on the server, without touching personal data:
   - admin pages answer for the admin role only;
   - the audit log records the role grant;
   - a nightly database backup now contains the account (*Backup Hostinger*, scope `db`, restore-tested).
5. Optional:
   - staff accounts are granted the same way with role `staff`;
   - a test student account (your second email) lets you walk the student journey end to end. No real student data, and payment stays closed.

## 2. Stripe (live)

1. **You:** in the Stripe Dashboard (live mode), create a restricted or secret key. Then in GitHub → Environments → production → secrets, add `STRIPE_KEY` (pk_live_…) and `STRIPE_SECRET` (sk_live_…).
2. Claude runs *Stripe catalogue* in check mode, then in create mode:
   - It creates three products, `smukn_t1`–`smukn_t3`, each with one GBP one-time price taken from `tier_prices`: T1 £125, T2 £695 (Most Popular), T3 £1,295.
   - No Payment Links are created.
   - Claude reports the IDs.
3. Claude creates the webhook endpoint (`smukn:stripe-webhook --create`, the six events in `StripeService::WEBHOOK_EVENTS`). **You** copy its signing secret into the production secret `STRIPE_WEBHOOK_SECRET`.
4. Claude runs *Update server settings* (production). The preflight then accepts live keys only, and payment opens (`StripeService::paymentsOpen`).
5. Claude verifies, in order:
   - the services page for an approved student shows the three fees and a working Checkout button;
   - prices are still absent from every public page (live leak check);
   - one real low-value payment, if you choose: a webhook marks it paid, and it can be refunded from the Dashboard;
   - `smukn:payments-open-notify` tells waiting students once.

## 3. Google Search Console

1. **You:** in Search Console, add a **Domain** property for `studymedicineuknigeria.com`. Then add the TXT record it shows in hPanel → Domains → DNS. Only add it; change nothing else in DNS.
2. Claude confirms the TXT record from public DNS (*Launch checks*) and records the verification date.

## 4. Google Analytics 4

1. **You:** create a GA4 property and web stream for https://studymedicineuknigeria.com, and send Claude the measurement ID (G-…). The ID is public, not a secret.
2. Claude adds `SITE_GA4_ID` to the settings the update workflow can write, deploys, and verifies:
   - the consent banner appears;
   - nothing loads before consent;
   - funnel events reach GA4 after consent;
   - CSP stays clean.

## 5. Sitemap submission

**You** (or Claude, once given Search Console access): submit `https://studymedicineuknigeria.com/sitemap.xml`. It lists the 28 indexable pages only. Unverified-fact pages stay noindex until verified.

## 6. Indexing verification

- **Day 1:** URL Inspection on the homepage, the Medicine pillar, the directory, Requirements, Fees, Admissions and Apply Online. Request indexing for each.
- **Week 1:**
  - the Pages report shows the 28 sitemap URLs moving to indexed;
  - the old site's 65 redirected URLs report as "Page with redirect";
  - the 7 retired URLs report as "Not found (404)". These are expected outcomes, not errors.
- **Weekly:** *Live verification* and *Launch checks* (both read-only) run from GitHub; any regression is fixed before new content.

## 7. Measurement and ongoing organic SEO

- **Search Console:** queries, impressions and CTR per page, read against `data/seo/decision-register.csv` (one page per intent).
- **GA4:** consented funnel (eligibility → register → start → documents → services → payment).
- **Semrush:**
  - position tracking for the register's priority clusters (Nigeria `ng` database);
  - quarterly competitor and keyword-gap review;
  - no figures are copied into the site or reports without the export that backs them.
- **Content:**
  - verify facts first (each verified fact un-gates its page);
  - upgrade thin pages before adding siblings;
  - every new page needs a register row and an asset-register row.
