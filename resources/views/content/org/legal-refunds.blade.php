<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10"><div class="max-w-3xl prose-site">
    @include('content._page-head', ['eyebrow' => 'Legal', 'title' => 'Refund policy', 'lede' => 'Plain rules for refunds of our service fee. University and third-party fees are outside our control and are not covered here.', 'seo' => $seo])
    <p class="text-[0.8125rem] text-ink-500 mt-4">Version 0.9.1, 4 October 2026 (single service fee per service) — under legal review.</p>
    <p>This policy forms part of the <a href="{{ route('legal.application-terms') }}">application service terms</a>; use of the website itself is governed by the <a href="{{ route('legal.terms') }}">terms of use</a>.</p>
    <h2>Within 14 days, before work starts</h2><p>Full refund, no questions asked.</p>
    <h2>After work has started</h2><p>A pro-rata refund based on the deliverables already completed, itemised on your invoice.</p>
    <h2>Submission support (Full Medical Application Support)</h2><p>Nothing is submitted until you approve the proposed submission package. If you cancel before approving it, the refund follows the pro-rata rule above by deliverables completed. Once a submission has been made, no refund is due for submission support.</p>
    <h2>No route open</h2><p>If our assessment finds that no published UK medicine route is currently open to you and you choose not to proceed to foundation or alternative guidance, the assessment fee is refunded in full.</p>
    <h2>How to ask</h2><p>Write to us through the <a href="{{ route('contact') }}">contact page</a> or from your portal messages, quoting your application number. The <a href="{{ route('legal.application-terms') }}">application service terms</a> set out what each service includes, which is the basis for any pro-rata calculation.</p>
    <h2>How refunds are paid</h2><p>To the original payment method (Stripe) or by bank transfer for bank-transfer payments, within 10 working days of agreement.</p>
    <h2>Not refundable</h2><p>University, UCAS, test, English test, visa and health-surcharge fees paid to third parties; and our fee where false documents or information were supplied.</p>
</div></article>
</x-layouts.public>
