<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('site.legal_name') ?? config('site.name') }} · {{ config('site.email') }}<br>
Independent application-support service. We are not an agent of any university, UCAS, the British Council or the GMC. Admission decisions are made solely by universities.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
