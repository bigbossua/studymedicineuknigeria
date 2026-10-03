<x-layouts.auth :seo="$seo" title="Verify your email address" intro="We have sent a link to {{ auth()->user()->email }}. Open it to confirm your address and continue to your portal.">
    <form method="post" action="{{ route('verification.send') }}" class="space-y-4">
        @csrf
        <button type="submit" class="btn btn-secondary w-full">Send the link again</button>
        <p class="text-[0.875rem] text-ink-500 text-center">Wrong address? <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Sign out</a> and register again.</p>
    </form>
    <form id="logout-form" method="post" action="{{ route('logout') }}" class="hidden">@csrf</form>
</x-layouts.auth>
