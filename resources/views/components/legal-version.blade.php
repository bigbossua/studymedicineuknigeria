{{-- Version line of a legal page. Until SITE_LEGAL_REVIEWED records the adviser's approval, every legal page says plainly that its wording is an unapproved draft (owner instruction 2026-10-30). --}}
@props(['version', 'date'])
@if(config('site.legal_reviewed'))
    <p class="text-[0.8125rem] text-ink-500 mt-4">Version {{ $version }}, {{ $date }}. Legal review recorded {{ config('site.legal_reviewed') }}. {{ $slot }}</p>
@else
    <p class="mt-4 rounded-md border border-warning-600 bg-warning-100 px-4 py-3 text-[0.875rem]" role="note"><strong>DRAFT — LEGAL REVIEW REQUIRED.</strong> Version {{ $version }}, {{ $date }}. This wording is published for transparency and has not yet been approved by a legal adviser; the reviewed version will replace it here. {{ $slot }}</p>
@endif
