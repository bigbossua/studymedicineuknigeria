# 17. Data model (relational)

Status: ARCHITECTURE. One schema serves the public directory, the application engine and the admin.

## 17.1 Reference data (public, SEO-facing)

```
universities(id, slug, name, short_name, nation, city, region, website_url, medical_school_name, msc_member bool, logo_policy enum(none|text_only), created_at, updated_at)
courses(id, university_id, slug, title, award enum(MBBS|MBChB|BMBS|MB BChir|BM BCh|MBBCh|other), ucas_code, entry_type enum(standard|graduate|foundation|gateway|international_only), length_years, intake_month, application_route enum(UCAS|DIRECT|BOTH), international_eligible enum(yes|no|not_published), international_places int null, admissions_test enum(UCAT|GAMSAT|none|not_published), interview_format enum(MMI|panel|other|not_published), official_course_url, status enum(active|suspended|archived), notes)
course_requirements(id, course_id, qualification_code enum(ALEVEL|IB|WASSCE|NECO|DEGREE|ENGLISH_IELTS|ENGLISH_OTHER|GCSE_EQUIV|UCAT_CUTOFF|AGE|OTHER), requirement_text, structured_json, applies_to enum(international|home|all), academic_year, source_url, verified_at, verified_by, verification_status enum(VERIFIED|VERIFY-ON-PAGE|NOT_PUBLISHED|REVIEW_DUE|SOURCE_CHANGED|ARCHIVED))
course_fees(id, course_id, fee_year, fee_status enum(international|home), amount_gbp, applies_to_years text (e.g. "1-2" or "all"), notes, source_url, verified_at, verified_by, verification_status)
course_deadlines(id, course_id, deadline_type enum(UCAS_MAIN|UNIVERSITY_DIRECT|UCAT_REG|UCAT_TEST|INTERVIEW_WINDOW|DEPOSIT|OTHER), date, cycle_year, source_url, verified_at, verification_status)
qualifications(code, name, country, description, ucas_tariff_note)                -- WAEC, NECO, A-level, IB, Nigerian degree …
university_qualification_statements(id, university_id, course_id null, qualification_code, statement_text, grades_json, source_url, verified_at, verification_status)  -- "University A says X"
sources(id, url, title, organisation, type enum(university|MSC|UCAS|GOVUK|GMC|UKCISA|UCAT|other), first_seen, last_checked, http_status, content_hash)
university_agreements(id, university_id, type enum(agent|recruitment_partner|referral|representative|other), document_id, starts_on, ends_on, scope_text, verified_by, active bool)
```

Every `verified_at` older than `review_interval` (fees 12 months, deadlines per cycle, requirements 12 months) → nightly job sets `verification_status=REVIEW_DUE` and lists it in admin "Verification". Public pages render "Last verified: {date}" from these fields and show a "review due" marker internally (not to students unless > 18 months, in which case the fee is shown as "{fee_year} fee — confirm with university").

## 17.2 Content (public)

```
pages(id, slug, template enum(landing|guide|requirements|fees|test|qualification|comparison|faq|legal|hub), title, meta_title, meta_description, canonical_url, h1, body_blocks json, intake_year, last_reviewed_at, reviewed_by, status enum(draft|published|archived), noindex bool)
faqs(id, question, answer_html, cluster, source_urls json, last_reviewed_at, status)
faq_links(faq_id, page_id)        -- where a FAQ appears
page_links(from_page_id, to_page_id, anchor_text, reason)  -- deliberate internal-link graph (brief 95–96)
related_content(page_id, related_page_id, position)       -- curated "You may also need"
asset_register(page_id, search_intent, nigerian_demand_evidence, semrush_json, serp_notes, competition_notes, cta, funnel_role, sources, priority, decision enum(BUILD_NOW|BUILD_LATER|MERGE|UPDATE_EXISTING|DO_NOT_BUILD), decided_at, rationale)
redirects(from_path, to_path, code, reason, created_at)
```

## 17.3 CRM / applications (private)

```
users(id, email, email_verified_at, password_hash, role enum(student|staff|admin), two_factor_secret, name, phone, whatsapp, country, nigeria_state, created_at, last_login_at, deleted_at)
leads(id, user_id null, email, name, phone, source_page, utm_json, eligibility_answers json, status enum(new|contacted|qualified|converted|closed), created_at)
service_tiers(id, code, name, deliverables json, payment_gate enum(AT_START|BEFORE_REVIEW|BEFORE_SUBMISSION), terms_version, active)
tier_prices(id, tier_id, currency, amount, stripe_price_id, component enum(full|preparation|submission), active)
applications(id, application_number, user_id, tier_id, intake_year, stage, stage_overridden_by null, form_json, form_section_status json, completion_pct, assigned_staff_id, created_at, updated_at, withdrawn_at, closed_at, hold_until)
application_events(id, application_id, type, actor_user_id null, payload json, created_at)        -- insert-only
checklist_rules(id, name, predicate json, require_codes json, reason_text, active, sort)
documents(id, application_id, document_code, title, status, required_reason, requested_by null, current_version_id, created_at, updated_at)
document_versions(id, document_id, version, storage_path, original_filename, mime, size_bytes, sha256, scan_status enum(pending|clean|infected|unavailable), encrypted bool, key_id, uploaded_by, uploaded_at)
document_events(id, document_id, from_status, to_status, actor_user_id, reason, created_at)         -- insert-only
document_access_log(id, document_version_id, user_id, ip, purpose, created_at)                   -- insert-only
payments(id, application_id, tier_price_id, status, amount, currency, method enum(STRIPE|MANUAL_TRANSFER), stripe_checkout_session_id, stripe_payment_intent_id, terms_version_accepted, receipt_url, refunded_amount, created_at, succeeded_at)
stripe_events(id, stripe_event_id unique, type, payload json, processed_at)                     -- idempotency
submissions(... as in 16.2)
submission_choices(id, submission_id, course_id, choice_order)        -- UCAS 4+1
submission_events(... insert-only)
authorisations(... as in 12.6)
messages(id, application_id, sender_user_id, body, created_at, read_at)
notifications(id, user_id, type, channel enum(email|in_app), payload json, sent_at, opened_at)
reminders(id, application_id, type, scheduled_for, sent_at, cancelled_at)
admin_actions(id, admin_user_id, action, target_type, target_id, payload json, created_at)       -- insert-only
```

## 17.4 Analytics

`analytics_events(id, application_id null, lead_id null, user_hash, event_name, params json, created_at)` mirrors the GA4 events (12.9) server-side so the funnel is measurable even with ad-blockers; PII excluded.

## 17.5 Relationships that power the site

- Directory listing = `courses ⋈ universities ⋈ course_fees(latest intl) ⋈ course_requirements(UCAT) ⋈ course_deadlines(UCAS_MAIN)`.
- Compare = same join for N selected courses, rendered as columns; no ranking columns exist in the schema by design.
- Requirements engine (brief 35) = `university_qualification_statements` filtered by `(university, course, qualification_code)`; empty result renders "Requirements not confirmed — contact university" with the official URL.
- Internal links = `page_links` + automatic entity links (a course page links to its university, its fee record's page, its test's page) — reasons stored, nothing random.
