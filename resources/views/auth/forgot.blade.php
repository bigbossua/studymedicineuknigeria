<x-layouts.auth :seo="$seo" title="Reset your password" intro="Enter your email and we will send you a link to choose a new password.">
    <form method="post" action="{{ route('password.email') }}" class="space-y-5" novalidate>
        @csrf
        <div class="field"><label for="email" class="label">Email address</label><input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}" class="input"></div>
        <button type="submit" class="btn btn-primary w-full btn-lg">Send reset link</button>
        <p class="text-center text-[0.9375rem]"><a href="{{ route('login') }}">Back to sign in</a></p>
    </form>
</x-layouts.auth>
