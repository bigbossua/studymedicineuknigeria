<x-layouts.auth :seo="$seo" title="Create your account" intro="Your application is saved automatically and given a reference number from the start.">
    <form method="post" action="{{ route('register') }}" class="space-y-5" novalidate>
        @csrf
        <div class="field"><label for="name" class="label">Full name (as in your passport)</label><input id="name" name="name" required autocomplete="name" value="{{ old('name', $lead['name'] ?? '') }}" class="input"></div>
        <div class="field"><label for="email" class="label">Email address</label><input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email', $lead['email'] ?? '') }}" class="input"><p class="hint">We will send a verification link here.</p></div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="field"><label for="phone" class="label">Phone</label><input id="phone" name="phone" inputmode="tel" autocomplete="tel" placeholder="+234" value="{{ old('phone') }}" class="input"></div>
            <div class="field"><label for="whatsapp" class="label">WhatsApp (optional)</label><input id="whatsapp" name="whatsapp" inputmode="tel" placeholder="+234" value="{{ old('whatsapp') }}" class="input"></div>
        </div>
        <div class="field"><label for="password" class="label">Password</label><input id="password" name="password" type="password" required autocomplete="new-password" class="input"><p class="hint">At least 10 characters with letters and numbers.</p></div>
        <div class="field"><label for="password_confirmation" class="label">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input"></div>
        <label class="flex items-start gap-3 text-[0.9375rem]"><input type="checkbox" name="terms" value="1" required class="mt-1 w-4 h-4"><span>I agree to the <a href="{{ route('legal.terms') }}">terms of use</a> and have read the <a href="{{ route('legal.privacy') }}">privacy notice</a>. I understand that admission decisions are made solely by universities and that no outcome is guaranteed.</span></label>
        <button type="submit" class="btn btn-primary w-full btn-lg">Create account</button>
        <p class="text-center text-[0.9375rem] text-ink-700">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
    </form>
</x-layouts.auth>
