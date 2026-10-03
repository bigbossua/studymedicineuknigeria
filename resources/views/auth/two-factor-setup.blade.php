<x-layouts.auth :seo="$seo" title="Set up two-step verification" :intro="$required ? 'Staff accounts must confirm every sign-in with a code from an authenticator app. This protects student documents and personal data.' : 'Add a second step to your sign-in with a code from an authenticator app on your phone.'">
    <ol class="list-decimal pl-5 space-y-3 text-[0.9375rem] text-ink-700">
        <li>Install an authenticator app if you do not have one (for example Google Authenticator, Microsoft Authenticator, Aegis or 1Password).</li>
        <li>In the app, choose <strong>Enter a setup key</strong> and type the key below, or <a href="{{ $uri }}" class="font-semibold">open this link on your phone</a>. Choose <em>time-based</em> if asked.
            <div class="source-box mt-3"><p class="eyebrow">Setup key</p><p class="font-mono text-[1.0625rem] tracking-wider break-all select-all">{{ $secret }}</p><p class="hint mt-1">Account: {{ auth()->user()->email }} · Issuer: {{ config('site.name') }} · SHA1 · 6 digits · 30 seconds</p></div>
        </li>
        <li>Enter the 6-digit code the app shows to finish.</li>
    </ol>
    <form method="post" action="{{ route('two-factor.confirm') }}" class="mt-6 space-y-5" novalidate>
        @csrf
        <div class="field"><label for="code" class="label">6-digit code</label><input id="code" name="code" inputmode="numeric" pattern="[0-9 ]*" autocomplete="one-time-code" required autofocus class="input font-mono tracking-widest text-[1.25rem] @error('code') input-error @enderror"></div>
        <button type="submit" class="btn btn-primary w-full btn-lg">Confirm and continue</button>
        <p class="text-center text-[0.875rem] text-ink-500"><button type="submit" form="logout-form" class="underline">Sign out</button></p>
    </form>
    <form id="logout-form" method="post" action="{{ route('logout') }}" class="hidden">@csrf</form>
</x-layouts.auth>
