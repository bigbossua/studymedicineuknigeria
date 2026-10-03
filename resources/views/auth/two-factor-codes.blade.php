<x-layouts.auth :seo="$seo" title="Save your recovery codes" intro="Two-step verification is on. If you lose your phone, one of these codes signs you in instead. Each code works once. They are shown only now.">
    <div class="source-box"><ul class="grid grid-cols-2 gap-2 font-mono text-[1rem] tracking-wider">@foreach($codes as $c)<li class="select-all">{{ $c }}</li>@endforeach</ul></div>
    <p class="mt-4 text-[0.9375rem] text-ink-700">Store them in a password manager or print them and keep them somewhere safe. Do not keep them on the same phone as your authenticator app.</p>
    <a href="{{ $next }}" class="btn btn-primary w-full btn-lg mt-6">I have saved my codes</a>
</x-layouts.auth>
