<section class="container-site py-12 sm:py-16">
    <div class="mx-auto max-w-md">
        <div class="card-raised">
            <h1 class="text-h2">{{ $title }}</h1>
            @if($intro)<p class="mt-2 text-ink-700 text-[0.9375rem]">{{ $intro }}</p>@endif
            @if(session('status'))<div class="alert alert-success mt-4">{{ session('status') }}</div>@endif
            @if($errors->any())
                <div class="alert alert-danger mt-4" role="alert">
                    <p class="font-semibold">Please check the following</p>
                    <ul class="list-disc pl-5 mt-1 text-[0.9375rem]">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="mt-6">{!! $inner !!}</div>
        </div>
        <p class="mt-6 text-center text-[0.8125rem] text-ink-500">Your details are protected under UK GDPR and the Nigeria Data Protection Act. <a href="{{ route('legal.privacy') }}">Privacy notice</a></p>
    </div>
</section>
