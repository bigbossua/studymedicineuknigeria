<x-layouts.portal :seo="$seo" :application="$application">
    <h1 class="text-h2">Your application</h1>
    <p class="text-ink-700 mt-2 max-w-2xl">Complete each section in any order. Everything saves automatically; a section is marked complete when every required field is valid.</p>
    <ol class="mt-8 grid gap-3 md:grid-cols-2">
        @foreach($steps as $i => $meta)
            <li><a href="{{ route('portal.application.step', [$application, $i]) }}" class="card card-link flex items-center justify-between gap-3">
                <div><span class="font-mono text-[0.8125rem] text-ink-300">Step {{ $loop->iteration }}</span><p class="font-semibold">{{ $meta['title'] }}</p><p class="text-[0.875rem] text-ink-500">{{ $meta['intro'] }}</p></div>
                @if($application->sectionComplete($i))<span class="chip chip-verified shrink-0">Complete</span>@else<span class="chip chip-review shrink-0">To do</span>@endif
            </a></li>
        @endforeach
    </ol>
    <details class="mt-10 max-w-xl"><summary class="cursor-pointer text-[0.9375rem] text-ink-500">Withdraw this application</summary>
        <form method="post" action="{{ route('portal.application.withdraw', $application) }}" class="card mt-3 space-y-3">@csrf
            <p class="text-[0.9375rem] text-ink-700">Withdrawing stops reminders and schedules your documents for deletion under our retention policy. This cannot be undone.</p>
            <label class="flex items-center gap-2 text-[0.9375rem]"><input type="checkbox" name="confirm" value="1" class="w-4 h-4"> I want to withdraw</label>
            <button class="btn btn-secondary">Withdraw application</button>
        </form></details>
</x-layouts.portal>
