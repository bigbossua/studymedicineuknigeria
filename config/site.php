<?php

return [
    'name' => 'Study Medicine UK Nigeria',
    'short_name' => 'SMUKN',
    'domain' => 'studymedicineuknigeria.com',
    'email' => 'info@studymedicineuknigeria.com',
    'whatsapp' => env('SITE_WHATSAPP'), // E.164 without plus, e.g. 2348000000000; null hides WhatsApp links
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

    // Facts whose verification_status is not VERIFIED are hidden in production unless this is true.
    'publish_unverified' => env('SITE_PUBLISH_UNVERIFIED', false),

    'nav' => [
        ['label' => 'Medicine', 'route' => 'medicine.index'],
        ['label' => 'Medical Schools', 'route' => 'schools.index'],
        ['label' => 'Requirements', 'route' => 'requirements.index'],
        ['label' => 'Fees', 'route' => 'fees.index'],
        ['label' => 'Admissions', 'route' => 'admissions.index'],
        ['label' => 'Student Portal', 'route' => 'login'],
    ],
];
