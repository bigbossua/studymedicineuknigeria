<x-layouts.auth :seo="$seo" title="Choose a new password">
    <form method="post" action="{{ route('password.update') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field"><label for="email" class="label">Email address</label><input id="email" name="email" type="email" required value="{{ old('email', $email) }}" class="input"></div>
        <div class="field"><label for="password" class="label">New password</label><input id="password" name="password" type="password" required autocomplete="new-password" class="input"><p class="hint">At least 10 characters with letters and numbers.</p></div>
        <div class="field"><label for="password_confirmation" class="label">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" required class="input"></div>
        <button type="submit" class="btn btn-primary w-full btn-lg">Change password</button>
    </form>
</x-layouts.auth>
