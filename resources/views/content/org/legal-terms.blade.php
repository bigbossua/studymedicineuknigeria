<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10"><div class="max-w-3xl prose-site">
    @include('content._page-head', ['eyebrow' => 'Legal', 'title' => 'Terms of use', 'lede' => 'The terms on which you may use this website and the student portal.', 'seo' => $seo])
    <p class="text-[0.8125rem] text-ink-500 mt-4">Version 0.9, 3 October 2026 — under legal review.</p>
    <h2>Information on this site</h2><p>We take care to record what universities and official bodies publish, with sources and verification dates. Requirements, fees and deadlines change; the official source always prevails over our record, and you must confirm with the university before relying on any figure. Nothing on this site is an admissions decision or a guarantee.</p>
    <h2>Independence</h2><p>We are an independent application-support service. We are not an agent of, or affiliated with, any university, UCAS, the British Council or the GMC unless expressly stated on <a href="{{ route('status') }}">Our status</a>.</p>
    <h2>Your account</h2><p>Keep your password secure and your details accurate. You are responsible for activity under your account. We may suspend accounts used to upload unlawful or malicious content.</p>
    <h2>Intellectual property</h2><p>The site's text, design and data compilations are ours; university information remains the property of the universities and is referenced for information only. You may not scrape or republish our compilations.</p>
    <h2>Liability</h2><p>We are not liable for decisions made by universities, UCAS, UKVI or any other body, nor for losses arising from information that an official source has changed. Nothing in these terms limits liability that cannot be limited by law.</p>
    <h2>Paid services</h2><p>Paid support is governed by the <a href="{{ route('legal.application-terms') }}">application service terms</a> and the <a href="{{ route('legal.refunds') }}">refund policy</a>.</p>
    <h2>Governing law</h2><p>The laws of England and Wales, without prejudice to mandatory consumer protections where you live.</p>
</div></article>
</x-layouts.public>
