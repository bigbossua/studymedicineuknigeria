<x-layouts.auth :seo="$seo" title="Registration opens shortly" intro="We are completing the final checks on our student portal, including the emails that verify your account.">
    <div class="space-y-4 text-[0.9375rem] text-ink-700">
        <p>Student accounts open as soon as those checks are complete. In the meantime you can read the published requirements, use the <a href="{{ route('apply.eligibility') }}">eligibility check</a> (no account needed), or write to us at <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>.</p>
        <p>Already have an account? <a href="{{ route('login') }}">Sign in</a>.</p>
    </div>
</x-layouts.auth>
