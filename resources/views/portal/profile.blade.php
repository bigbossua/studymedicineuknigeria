<x-layouts.portal :seo="$seo" :application="$application">
    <div class="max-w-2xl space-y-8">
        <h1 class="text-h2">Your profile</h1>
        <form method="post" action="{{ route('portal.profile.update') }}" class="card space-y-4">@csrf @method('put')
            <p class="eyebrow">Contact details</p>
            <div class="field"><label for="name" class="label">Full name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="input"></div>
            <div class="grid sm:grid-cols-2 gap-4"><div class="field"><label for="phone" class="label">Phone</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input"></div><div class="field"><label for="whatsapp" class="label">WhatsApp</label><input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" class="input"></div></div>
            <div class="field"><label for="nigeria_state" class="label">State</label><input id="nigeria_state" name="nigeria_state" value="{{ old('nigeria_state', $user->nigeria_state) }}" class="input"></div>
            <p class="hint">Email: {{ $user->email }} (contact us to change it).</p>
            <button class="btn btn-secondary">Save details</button></form>
        <form method="post" action="{{ route('portal.profile.password') }}" class="card space-y-4">@csrf @method('put')
            <p class="eyebrow">Change password</p>
            <div class="field"><label for="current_password" class="label">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" class="input"></div>
            <div class="field"><label for="password" class="label">New password</label><input id="password" name="password" type="password" autocomplete="new-password" class="input"></div>
            <div class="field"><label for="password_confirmation" class="label">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" class="input"></div>
            <button class="btn btn-secondary">Change password</button></form>
        <section class="card space-y-3"><p class="eyebrow">Your data</p>
            <p class="text-[0.9375rem] text-ink-700">Download everything we hold about you, or ask us to delete your account. Deletion is completed within 30 days unless an in-progress submission requires us to retain records; we will tell you if so.</p>
            <div class="flex flex-col sm:flex-row gap-3"><a href="{{ route('portal.profile.export') }}" class="btn btn-secondary">Download my data (JSON)</a>
                <form method="post" action="{{ route('portal.profile.delete') }}" class="flex items-center gap-3">@csrf<label class="text-[0.875rem] flex items-center gap-2"><input type="checkbox" name="confirm" value="1" class="w-4 h-4"> I understand</label><button class="btn btn-tertiary text-danger-600">Request deletion</button></form></div></section>
    </div>
</x-layouts.portal>
