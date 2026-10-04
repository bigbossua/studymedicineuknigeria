<?php

return [
    'name' => 'Study Medicine UK Nigeria',
    'short_name' => 'SMUKN',
    'domain' => 'studymedicineuknigeria.com',
    'email' => 'info@studymedicineuknigeria.com',
    'whatsapp' => env('SITE_WHATSAPP') ?: '447842292527', // E.164 without plus (owner's number +44 7842 292527); an empty .env value keeps this default
    'legal_name' => env('SITE_LEGAL_NAME'), // set when the registered company name is confirmed
    'company_number' => env('SITE_COMPANY_NUMBER'),
    'address' => env('SITE_ADDRESS'),
    'tagline' => 'Evidence. Guidance. Application.',
    'default_description' => 'Independent, evidence-led guidance for Nigerian students applying to study Medicine in the United Kingdom: published entry requirements, verified fees, the UK medical school directory, UCAT and UCAS timelines, and an online application service.',
    'og_image' => '/images/brand/og-default.png',
    'status_statement' => 'StudyMedicineUKNigeria is an independent application-support service. We are not an agent of, or affiliated with, any university, UCAS, the British Council, the GMC or any other body unless expressly stated on our Our status page. We do not receive commission from any university.',

    // Staging-only HTTP basic auth (architecture 21.6). Both must be set for the gate to engage; ignored in production.
    'staging_basic_user' => env('STAGING_BASIC_USER'),
    'staging_basic_password' => env('STAGING_BASIC_PASSWORD'),

    // Set to any value (e.g. the review date) once a solicitor has reviewed the legal pages; clears the launch checklist item.
    'legal_reviewed' => env('SITE_LEGAL_REVIEWED'),

    // GA4 measurement ID (G-XXXXXXX). Blank = no third-party analytics at all. When set, the public site shows a
    // consent banner and loads gtag only after consent; the portal and admin never load it (funnel_events covers them).
    'ga4_id' => env('SITE_GA4_ID'),
    // GA4 Measurement Protocol API secret (GA4 → Admin → Data streams → the web stream → Measurement Protocol API
    // secrets). Lets application-journey events reach GA4 from the server, so no third-party script ever loads in
    // the portal; sent only for visitors who accepted analytics. Blank = journey events stay first-party only.
    'ga4_api_secret' => env('SITE_GA4_API_SECRET'),
    // Google Search Console HTML-tag verification token (the content="…" value Google shows for a URL-prefix
    // property). Only needed if ownership is not verified with the DNS TXT record of a Domain property.
    'google_site_verification' => env('SITE_GOOGLE_VERIFICATION'),

    // Facts whose verification_status is not VERIFIED are hidden in production unless this is true.
    'publish_unverified' => env('SITE_PUBLISH_UNVERIFIED', false),
    // Bank transfer as a payment method: off until the owner confirms the account to give students (owner step).
    'bank_transfer' => (bool) env('SITE_BANK_TRANSFER', false),
    // Student registration: production starts closed and opens only after a real test email has been sent from the
    // production mailbox (Send test email workflow), so verification and reset links are deliverable from day one.
    'registration_open' => (bool) env('SITE_REGISTRATION_OPEN', true),

    'nav' => [
        ['label' => 'Medicine', 'route' => 'medicine.index'],
        ['label' => 'Medical Schools', 'route' => 'schools.index'],
        ['label' => 'Requirements', 'route' => 'requirements.index'],
        ['label' => 'Fees', 'route' => 'fees.index'],
        ['label' => 'Admissions', 'route' => 'admissions.index'],
        ['label' => 'Student Portal', 'route' => 'login'],
    ],
];
