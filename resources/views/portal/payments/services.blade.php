<x-layouts.portal :seo="$seo" :application="$application">
    {{-- Reached only by an approved student (PaymentController::services); fees come from the service price records. --}}
    <div class="max-w-5xl">
        <p class="eyebrow">Application {{ $application->application_number }}</p>
        <h1 class="text-h2 mt-1">Choose your service</h1>
        <p class="lede mt-3">Our team has reviewed your profile. These are the services open to you, each with its exact fee. Choose the one that suits you; on the next page you see the full terms and confirm before anything is charged.</p>
        <x-fee-disclaimer class="mt-5" />
    </div>
    @unless($canChange)
        <x-alert type="info" class="mt-6" title="Service already paid">A payment has been made or is being confirmed for {{ $application->tier?->name }}, so the service can no longer be changed here. Message our team if you need a different service.</x-alert>
    @endunless
    <form method="post" action="{{ route('portal.payments.service', $application) }}" class="mt-8 grid gap-5 lg:grid-cols-3">
        @csrf
        @foreach($tiers as $tier)
            @php($price = $tier->priceFor('full'))
            @continue(! $price?->amount_minor)
            <label class="card-raised flex flex-col cursor-pointer relative has-[:checked]:border-navy-700 has-[:checked]:ring-2 has-[:checked]:ring-navy-700/20 {{ $tier->badge ? 'border-2 border-navy-700' : '' }}" data-service="{{ $tier->code }}">
                @if($tier->badge)<span class="absolute -top-3 left-6 rounded-full bg-navy-700 px-3 py-1 text-[0.75rem] font-semibold uppercase tracking-wide text-white">{{ $tier->badge }}</span>@endif
                <div class="flex items-start justify-between gap-3">
                    <div><p class="eyebrow">{{ $tier->tagline ?? $tier->code }}</p><h2 class="text-xl font-serif font-semibold mt-1">{{ $tier->name }}</h2></div>
                    <input type="radio" name="service_tier_id" value="{{ $tier->id }}" class="mt-1 w-5 h-5" aria-label="{{ $tier->name }}, {{ $price->formatted() }}" required @checked(old('service_tier_id', $application->service_tier_id) == $tier->id) @disabled(! $canChange)>
                </div>
                <p class="mt-4"><span class="text-3xl font-serif font-semibold text-ink-900" data-service-price="{{ $tier->code }}">{{ $price->formatted() }}</span> <span class="text-[0.8125rem] text-ink-500">GBP, one-off service fee</span></p>
                <p class="mt-3 text-[0.9375rem] text-ink-700">{{ $tier->summary }}</p>
                <p class="mt-4 font-semibold text-[0.875rem]">Included</p>
                <ul class="mt-1 space-y-1.5 text-[0.875rem] text-ink-700 list-disc pl-5 flex-1">@foreach($tier->deliverables as $d)<li>{{ $d }}</li>@endforeach</ul>
                <p class="mt-4 text-[0.8125rem] text-ink-500"><span class="font-semibold">Not included:</span> {{ implode('; ', $tier->exclusions ?? []) }}.</p>
            </label>
        @endforeach
        @error('service_tier_id')<p class="error-text lg:col-span-3">{{ $message }}</p>@enderror
        @if($canChange)
            <div class="lg:col-span-3 flex flex-col sm:flex-row gap-3 sm:items-center">
                <button type="submit" class="btn btn-primary btn-lg">Continue with this service</button>
                <p class="hint">Next: the exact fee, what is and is not included, the refund and payment terms. Nothing is charged until you confirm and pay.</p>
            </div>
        @endif
    </form>
</x-layouts.portal>
