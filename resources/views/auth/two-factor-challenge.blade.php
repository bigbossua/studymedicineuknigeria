<x-layouts.auth :seo="$seo" title="Two-step verification" intro="Open your authenticator app and enter the current 6-digit code, or use one of your recovery codes.">
    <form method="post" action="{{ route('two-factor.verify') }}" class="space-y-5" novalidate>
        @csrf
        <div class="field"><label for="code" class="label">Code</label><input id="code" name="code" inputmode="text" autocomplete="one-time-code" required autofocus class="input font-mono tracking-widest text-[1.25rem] @error('code') input-error @enderror"><p class="hint">Recovery codes look like ABCDE-FGHJK.</p></div>
        <button type="submit" class="btn btn-primary w-full btn-lg">Verify</button>
        <p class="text-center text-[0.875rem] text-ink-500">Not you? <button type="submit" form="logout-form" class="underline">Sign out</button></p>
    </form>
    <form id="logout-form" method="post" action="{{ route('logout') }}" class="hidden">@csrf</form>
</x-layouts.auth>
