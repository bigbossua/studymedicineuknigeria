<x-layouts.portal :seo="$seo" :application="$application">
    <div class="max-w-2xl">
        <h1 class="text-h2">Messages</h1>
        <p class="text-ink-700 mt-2">Messages with our team about application <span class="font-mono">{{ $application->application_number }}</span>. We reply here and notify you by email.</p>
        <ol class="mt-6 space-y-3">
            @forelse($messages as $m)
                <li class="card {{ $m->sender_user_id === auth()->id() ? 'ml-8 bg-navy-50' : 'mr-8' }}"><p class="text-[0.8125rem] text-ink-500">{{ $m->sender_user_id === auth()->id() ? 'You' : 'Study Medicine UK Nigeria' }} · {{ $m->created_at->format('j M Y H:i') }}</p><p class="mt-1 whitespace-pre-line text-[0.9375rem]">{{ $m->body }}</p></li>
            @empty<li class="text-ink-500">No messages yet.</li>@endforelse
        </ol>
        <form method="post" action="{{ route('portal.messages.store', $application) }}" class="card mt-6 space-y-3">@csrf
            <div class="field"><label for="body" class="label">Write a message</label><textarea id="body" name="body" rows="4" required maxlength="4000" class="input min-h-24"></textarea><p class="hint">Please upload documents in the Documents section rather than describing them here.</p></div>
            <button class="btn btn-primary">Send</button></form>
    </div>
</x-layouts.portal>
