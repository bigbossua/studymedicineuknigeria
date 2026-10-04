<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Contact', 'title' => 'Contact us', 'lede' => 'The quickest route for anything about your application is the Messages page in your portal; it keeps everything on your record and alerts our team.', 'seo' => $seo])
    <div class="mt-10 grid md:grid-cols-2 gap-6 max-w-3xl">
        <div class="card"><p class="eyebrow mb-2">Email</p><a href="mailto:{{ config('site.email') }}" class="text-lg font-semibold break-all">{{ config('site.email') }}</a><p class="mt-2 text-[0.9375rem] text-ink-500">We reply within two working days. Please do not email documents; upload them in your portal where they are protected.</p></div>
        <div class="card"><p class="eyebrow mb-2">Student portal</p><a href="{{ route('login') }}" class="btn btn-secondary">Sign in and message us</a><p class="mt-2 text-[0.9375rem] text-ink-500">Quote your application number (SMUKN-…) in any message.</p></div>
        @if(config('site.whatsapp'))<div class="card"><p class="eyebrow mb-2">WhatsApp</p><a href="https://wa.me/{{ config('site.whatsapp') }}?text={{ rawurlencode('Hello, I would like help studying Medicine in the UK from Nigeria.') }}" rel="noopener" target="_blank" class="btn btn-tertiary">Message on WhatsApp</a><p class="mt-2 text-[0.9375rem] text-ink-500">For quick questions only; your portal remains the record.</p></div>@endif
        <div class="card"><p class="eyebrow mb-2">Address</p><p class="text-[0.9375rem]">{{ config('site.address') ?? 'Registered address to be published on Our status.' }}</p></div>
    </div>
    <div class="mt-12 grid md:grid-cols-2 gap-8 max-w-3xl">
        <section class="prose-site"><h2>Before you write</h2>
            <p>Tell us where you are in the process so the first reply is useful rather than a list of questions:</p>
            <ul>
                <li>your application number (SMUKN-…) if you have one, or the intake year you are aiming for;</li>
                <li>your qualification route: <a href="{{ route('requirements.waec') }}">WAEC</a>, <a href="{{ route('requirements.neco') }}">NECO</a>, <a href="{{ route('requirements.alevels') }}">A-levels</a> or a <a href="{{ route('requirements.gem') }}">Nigerian degree</a>;</li>
                <li>whether you have registered for or sat the <a href="{{ route('admissions.ucat') }}">UCAT</a>;</li>
                <li>the specific page or medical school your question is about, so we can check the published source with you.</li>
            </ul>
            <p>Please never email passports, certificates or transcripts. Upload them in your portal, where they are encrypted and every access is logged.</p>
        </section>
        <section class="prose-site"><h2>What to expect</h2>
            <ul>
                <li>Portal messages alert our team immediately and are answered first; email within two working days (Lagos and UK working days).</li>
                <li>We answer from what universities publish and tell you when something is not published or not yet verified, rather than guessing.</li>
                <li>We do not predict admission outcomes, promise places, or contact any university on your behalf without your written approval in the portal.</li>
                <li>Questions about other courses or countries are outside what we do; the <a href="{{ route('faq.index') }}">questions page</a> covers what we are asked most.</li>
            </ul>
            <h2>Universities, schools and media</h2>
            <p>If you represent a medical school and a statement on this site about your institution is out of date, email us with the current source URL and we will re-verify it and show a new last-verified date. See <a href="{{ route('status') }}">Our status</a> for how we work and what we are not.</p>
        </section>
    </div>
    <x-cta-band class="mt-12" title="Before you write: check your route" :href="route('apply.eligibility')" label="Check your eligibility" :secondary-href="route('apply.index')" secondary-label="Apply Online">Most first questions are answered by the eligibility check (seven questions, no account needed). Include its result when you contact us and we can answer faster.</x-cta-band>
</article>
</x-layouts.public>
