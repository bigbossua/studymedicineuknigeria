<x-layouts.auth :seo="$seo" title="Sign in to your student portal" intro="Continue your application exactly where you left it.">
    <form method="post" action="{{ route('login') }}" class="space-y-5" novalidate>
        @csrf
        <div class="field"><label for="email" class="label">Email address</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="input @error('email') input-error @enderror"></div>
        <div class="field"><label for="password" class="label">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required class="input"></div>
        <div class="flex items-center justify-between gap-4 text-[0.9375rem]">
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="w-4 h-4"> Keep me signed in on this device</label>
            <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary w-full btn-lg">Sign in</button>
        <p class="text-center text-[0.9375rem] text-ink-700">New here? <a href="{{ route('register') }}">Create your account</a></p>
    </form>
</x-layouts.auth>
