@props(['seo' => null, 'title' => null, 'intro' => null])
@include('layouts.public', ['seo' => $seo, 'hideFloatingCta' => true, 'bodyClass' => 'bg-paper-warm', 'slot' => new \Illuminate\Support\HtmlString(view('auth._frame', ['title' => $title, 'intro' => $intro, 'inner' => $slot])->render())])
