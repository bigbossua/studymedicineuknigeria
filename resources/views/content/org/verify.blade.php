<x-layouts.public :seo="$seo">
<article class="container-site pt-6 pb-10">
    @include('content._page-head', ['eyebrow' => 'Editorial and verification policy', 'title' => 'How we verify what we publish', 'lede' => 'Every requirement, fee, date and rule on this site is a record with a source, a status and a date. This page explains the labels you see next to each fact, where our information comes from, how often it is re-checked, and how to tell us when something is wrong.', 'seo' => $seo])
    <div class="mt-10 grid lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 space-y-12 prose-site">
            <section id="sources"><h2>Where information comes from, in order of authority</h2>
                <ol>
                    <li><strong>The university's own published pages</strong> for its entry requirements, international eligibility, fees and application route. When a university publishes a statement about WAEC or NECO we quote it; when it publishes nothing we say so rather than infer.</li>
                    <li><strong>UK Government (GOV.UK)</strong> for visa, work and immigration rules; <strong>the General Medical Council</strong> for registration and the Medical Licensing Assessment; <strong>UCAS</strong> for application dates and rules; <strong>the UCAT Consortium</strong> for test dates, fees and centres; <strong>the UK Foundation Programme Office</strong> and <strong>NHS England</strong> for training.</li>
                    <li><strong>Our own guidance</strong>, always labelled as such: route maps, planning advice, and the eligibility check. It never says you are eligible; it shows which routes appear open on published requirements.</li>
                </ol>
                <p>We do not use rankings, league tables, other agents' websites or forum posts as sources of fact. Secondary sources are used only to find an official page, never quoted in its place.</p>
            </section>
            <section id="statuses"><h2>What the labels mean</h2>
                <dl class="not-prose card divide-y divide-ink-100">
                    @foreach([
                        ['VERIFIED', 'A member of our team opened the official source, read the value on the page and recorded the date. Only facts with this label show figures in production.'],
                        ['VERIFY-ON-PAGE', 'Located on an official page during research but not yet re-read line by line. Hidden from the public site until verified.'],
                        ['REVIEW_DUE', 'Verified earlier, but the review date has passed (six months for fees and deadlines, twelve for everything else). Hidden until re-checked.'],
                        ['SOURCE_CHANGED', 'The official page has changed since we verified it. Hidden until re-verified.'],
                        ['NOT_PUBLISHED', 'We checked and the university or body does not publish this. We say so rather than guess.'],
                        ['NOT_FOUND', 'Research did not locate an official statement. Shown as "not yet confirmed"; never filled in from memory.'],
                    ] as [$status, $meaning])
                        <div class="py-3 grid sm:grid-cols-12 gap-3"><dt class="sm:col-span-4"><x-verified-badge :status="$status" /></dt><dd class="sm:col-span-8 text-[0.9375rem] text-ink-700">{{ $meaning }}</dd></div>
                    @endforeach
                </dl>
                <p class="mt-4">Whole pages follow the same rule: a page built from facts about visas, costs or registration stays out of search results until every fact on it is verified or recorded as not published.</p>
            </section>
            <section id="cadence"><h2>How often we re-check</h2>
                <ul>
                    <li>Fees and deadlines: at least every six months, and whenever a university publishes the next fee year.</li>
                    <li>Entry requirements, eligibility statements, test and registration rules: at least every twelve months, and at every GOV.UK Statement of Changes or UCAS cycle rollover.</li>
                    <li>Each verified fact carries the date it was last read. If a date looks old to you, it looks old to us too: the fact is automatically flagged for review when its date passes.</li>
                </ul>
            </section>
            <section id="independence"><h2>What we never do</h2>
                <ul>
                    <li>Rank medical schools or call any school "best", "top" or "easiest".</li>
                    <li>Claim a partnership, agency agreement or commission with any university unless a signed agreement exists and is listed on <a href="{{ route('status') }}">Our status</a>.</li>
                    <li>Publish testimonials, success rates or student numbers we cannot evidence.</li>
                    <li>Submit any application to a university without the student's recorded approval of the exact package.</li>
                </ul>
            </section>
            <section id="report"><h2>Report an error</h2>
                <p>If a figure or statement here differs from the official page, email <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a> with the page on this site, the official URL and what it says. We re-verify, correct the record and the new date shows on the page. Universities and medical schools are welcome to do the same; see <a href="{{ route('contact') }}">Contact</a>.</p>
            </section>
        </div>
        <aside class="lg:col-span-4 space-y-6">
            <div class="card"><p class="eyebrow mb-3">Current record</p>
                <ul class="text-[0.9375rem] space-y-2">
                    <li class="flex justify-between gap-3"><span>Verified facts</span><span class="font-semibold">{{ number_format($counts['VERIFIED'] ?? 0) }}</span></li>
                    <li class="flex justify-between gap-3"><span>Awaiting verification</span><span class="font-semibold">{{ number_format(($counts['VERIFY-ON-PAGE'] ?? 0) + ($counts['REVIEW_DUE'] ?? 0) + ($counts['SOURCE_CHANGED'] ?? 0)) }}</span></li>
                    <li class="flex justify-between gap-3"><span>Recorded as not published</span><span class="font-semibold">{{ number_format($counts['NOT_PUBLISHED'] ?? 0) }}</span></li>
                    <li class="flex justify-between gap-3"><span>Not yet located</span><span class="font-semibold">{{ number_format($counts['NOT_FOUND'] ?? 0) }}</span></li>
                </ul>
                <p class="hint mt-3">Live counts from our reference database. Unverified facts are not shown to readers.</p></div>
            <div class="card"><p class="eyebrow mb-2">Related</p><ul class="text-[0.9375rem] space-y-2"><li><a href="{{ route('status') }}">Our status: independence and agreements</a></li><li><a href="{{ route('schools.index') }}">Medical school directory</a></li><li><a href="{{ route('legal.privacy') }}">Privacy notice</a></li></ul></div>
        </aside>
    </div>
</article>
</x-layouts.public>
