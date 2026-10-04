{{-- never on account-security pages: their URLs can carry a reset token and an email address --}}
@php $ga = request()->routeIs('password.*', 'verification.*', 'two-factor.*') ? null : config('site.ga4_id'); $consent = request()->cookie('smukn_consent'); @endphp
@if($ga)
<meta name="ga4-id" content="{{ $ga }}">
<meta name="csp-nonce" content="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
@php $events = \App\Support\Funnel::pullClientEvents(); @endphp
@if($events)<meta name="funnel-events" content="{{ json_encode($events) }}">@endif
@if(!in_array($consent, ['granted', 'denied'], true))
@push('body-end')
<div data-consent-banner class="fixed bottom-0 inset-x-0 z-40 border-t border-ink-200 bg-paper shadow-lg" role="region" aria-label="Analytics cookies">
    <div class="container-site py-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6 text-[0.9375rem]">
        <p class="flex-1 text-ink-700">We use strictly necessary cookies for sign-in. With your consent we also use Google Analytics to understand which pages help Nigerian applicants most. No advertising cookies. <a href="{{ route('legal.privacy') }}">Privacy notice</a></p>
        <div class="flex gap-2 shrink-0"><button type="button" class="btn btn-tertiary" data-consent="denied">Decline</button><button type="button" class="btn btn-primary" data-consent="granted">Accept analytics</button></div>
    </div>
</div>
@endpush
@endif
@endif
