<x-layouts.portal :seo="$seo" :application="$application">
    <div class="max-w-3xl">
        <div class="flex items-center justify-between gap-4">
            <p class="eyebrow">Step {{ $index }} of {{ $total }}</p>
            <p data-save-indicator class="text-[0.8125rem] text-ink-500" aria-live="polite">{{ $application->sectionComplete($step) ? 'Saved · section complete' : 'Autosave on' }}</p>
        </div>
        <div class="mt-2 h-1.5 bg-ink-100 rounded-pill overflow-hidden" aria-hidden="true"><div class="h-full bg-navy-700" style="width: {{ round($index / $total * 100) }}%"></div></div>
        <h1 class="text-h2 mt-5">{{ $meta['title'] }}</h1>
        <p class="text-ink-700 mt-2">{{ $meta['intro'] }}</p>
        @if($locked)<x-alert type="warning" class="mt-5">This section is locked while your approved package is being submitted. Message us if something must change.</x-alert>@endif
        @if($errors->any())<x-alert type="danger" class="mt-5" title="Some fields need attention"><ul class="list-disc pl-5 text-[0.9375rem]">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-alert>@endif

        <form method="post" action="{{ route('portal.application.save', [$application, $step]) }}" data-autosave="{{ route('portal.application.save', [$application, $step]) }}" class="mt-8 space-y-6" @if($locked) inert @endif novalidate>
            @csrf
            @include('portal.application.steps.'.$step, ['d' => $data])
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-ink-100">
                <button type="submit" name="intent" value="continue" class="btn btn-primary btn-lg">{{ $next ? 'Save and continue' : 'Save and finish' }}</button>
                <button type="submit" name="intent" value="later" class="btn btn-secondary btn-lg">Save and return later</button>
                @if($prev)<a href="{{ route('portal.application.step', [$application, $prev]) }}" class="btn btn-tertiary">Back</a>@endif
            </div>
        </form>
    </div>
</x-layouts.portal>
