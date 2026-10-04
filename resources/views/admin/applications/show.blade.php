<x-layouts.admin :seo="$seo">
    @php $a = $application; @endphp
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div><p class="eyebrow">{{ $a->tier?->name }} · {{ $a->intake_year }} entry</p><h1 class="text-h2 font-mono">{{ $a->application_number }}</h1><p class="text-ink-700">{{ $a->user->name }} · <a href="mailto:{{ $a->user->email }}">{{ $a->user->email }}</a> · {{ $a->user->phone }} {{ $a->user->whatsapp ? '· WA '.$a->user->whatsapp : '' }}</p></div>
        <div class="text-right"><span class="chip chip-info text-sm">{{ $a->stage->label() }}</span><p class="text-[0.8125rem] text-ink-500 mt-1">{{ $a->completion_pct }}% · last activity {{ $a->last_activity_at?->diffForHumans() }}</p>@if($a->stage_override)<p class="text-[0.8125rem] text-warning-600">Staff override: {{ $a->stage_override }}</p>@endif</div>
    </div>

    <div class="mt-6 grid xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <section class="card"><p class="eyebrow mb-3">Documents</p>
                <table class="text-[0.875rem]"><thead><tr><th>Document</th><th>Status</th><th>File</th><th>Decision</th></tr></thead><tbody>
                @foreach($a->documents as $d)
                    <tr><td>{{ $d->title }}<br><span class="text-ink-500">{{ $d->required_reason }}</span>@if($d->staff_note)<br><span class="text-danger-600">Note: {{ $d->staff_note }}</span>@endif</td>
                        <td><span class="chip {{ $d->status->chipClass() }}">{{ $d->status->label() }}</span></td>
                        <td>@if($d->currentVersion)<a href="{{ route('admin.applications.document',[$a,$d]) }}" target="_blank" rel="noopener">v{{ $d->currentVersion->version }} ↗</a><br><span class="text-ink-500">{{ round($d->currentVersion->size_bytes/1024) }} KB · scan: {{ $d->currentVersion->scan_status }}{{ $d->currentVersion->encrypted ? ' · encrypted' : '' }}</span>@else —@endif</td>
                        <td>@if($d->currentVersion && in_array($d->status->value,['UPLOADED','UNDER_REVIEW','ACCEPTED']))
                            <form method="post" action="{{ route('admin.applications.document.review',[$a,$d]) }}" class="flex flex-col gap-1">@csrf
                                <select name="decision" class="input min-h-9 py-1 text-[0.8125rem]" aria-label="Document decision"><option value="accept">Accept</option><option value="reject">Reject (reason)</option><option value="replace">Request replacement</option><option value="waive">Waive (not required)</option></select>
                                <input name="reason" class="input min-h-9 py-1 text-[0.8125rem]" placeholder="Reason (shown to student)"><button class="btn btn-secondary btn-sm">Apply</button></form>
                        @elseif($d->status->value==='REQUIRED')<form method="post" action="{{ route('admin.applications.document.review',[$a,$d]) }}">@csrf<input type="hidden" name="decision" value="waive"><input type="hidden" name="reason" value="Waived by staff"><button class="btn btn-tertiary text-[0.8125rem]">Waive</button></form>@endif</td></tr>
                @endforeach</tbody></table>
                <details class="mt-4"><summary class="cursor-pointer text-[0.9375rem] font-medium">Request a document</summary>
                    <form method="post" action="{{ route('admin.applications.document.request',$a) }}" class="grid sm:grid-cols-3 gap-2 mt-2">@csrf
                        <select name="code" class="input" aria-label="Document type to request">@foreach($catalogue as $code=>$m)<option value="{{ $code }}">{{ $m['title'] }}</option>@endforeach</select>
                        <input name="title" class="input" placeholder="Custom title (optional)"><input name="reason" required class="input" placeholder="Why it is needed (shown to student)">
                        <button class="btn btn-secondary sm:col-span-3">Request document</button></form></details>
            </section>

            <section class="card"><p class="eyebrow mb-3">Application form</p>
                @foreach($steps as $key=>$meta)<details class="border-t border-ink-100 py-2 first:border-0"><summary class="cursor-pointer text-[0.9375rem]">{{ $meta['title'] }} <span class="chip {{ $a->sectionComplete($key)?'chip-verified':'chip-review' }} ml-2">{{ $a->sectionComplete($key)?'complete':'incomplete' }}</span></summary>
                    <pre class="mt-2 text-[0.8125rem] whitespace-pre-wrap font-sans text-ink-700">{{ json_encode($a->formSection($key), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre></details>@endforeach
            </section>

            <section class="card"><p class="eyebrow mb-3">Submissions</p>
                @foreach($a->submissions as $s)
                    <div class="border border-ink-200 rounded-md p-4 mb-3">
                        <div class="flex justify-between gap-3 flex-wrap"><p class="font-semibold">{{ $s->university?->name ?? 'UCAS' }} — {{ $s->course?->title ?? 'Medicine' }} · {{ $s->intake }}</p><span class="chip chip-info">{{ $s->statusLabel() }}</span></div>
                        <p class="text-[0.875rem] text-ink-500">{{ $s->routeLabel() }}</p>
                        @if($s->choices->isNotEmpty())<p class="text-[0.875rem]">Choices: {{ $s->choices->pluck('label')->join('; ') }}</p>@endif
                        <p class="text-[0.875rem]">Authorisation: {{ $s->authorisation ? ($s->authorisation->revoked_at ? 'REVOKED ('.$s->authorisation->revoked_reason.')' : 'valid — '.$s->authorisation->approved_at->format('j M Y H:i').' by "'.$s->authorisation->typed_name.'" from '.$s->authorisation->ip.' · hash '.substr($s->authorisation->snapshot_hash,0,12)) : 'none' }}</p>
                        @if($s->external_reference)<p class="text-[0.875rem]">Reference: <span class="font-mono">{{ $s->external_reference }}</span></p>@endif
                        @if(!in_array($s->status,['PROPOSED','CLOSED']))
                        <form method="post" action="{{ route('admin.applications.submission.update',[$a,$s]) }}" class="grid sm:grid-cols-4 gap-2 mt-3">@csrf
                            <select name="status" class="input" aria-label="New submission status">@foreach(['PACKAGE_READY','SUBMITTED','ACKNOWLEDGED','INTERVIEW','OFFER_CONDITIONAL','OFFER_UNCONDITIONAL','REJECTED','WAITLISTED','ACCEPTED_BY_STUDENT','DECLINED','CLOSED'] as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach</select>
                            <input name="external_reference" class="input" placeholder="Reference (UCAS ID / university ref)"><input name="note" class="input" placeholder="Note (sent to student for responses)"><button class="btn btn-secondary">Update</button></form>
                        @endif
                        <ul class="mt-2 text-[0.8125rem] text-ink-500">@foreach($s->events as $e)<li>{{ $e->created_at->format('j M H:i') }} → {{ $e->to_status }} {{ $e->note }}</li>@endforeach</ul>
                    </div>
                @endforeach
                <details><summary class="cursor-pointer text-[0.9375rem] font-medium">Propose a submission target</summary>
                    <form method="post" action="{{ route('admin.applications.submission.propose',$a) }}" class="grid sm:grid-cols-2 gap-2 mt-2">@csrf
                        <select name="university_id" class="input" aria-label="University"><option value="">University (optional for UCAS multi-choice)</option>@foreach($universities as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
                        <select name="course_id" class="input" aria-label="Course"><option value="">Course</option>@foreach($courses as $c)<option value="{{ $c->id }}">{{ $c->university->name }} — {{ $c->title }} {{ $c->ucas_code }}</option>@endforeach</select>
                        <input name="intake" class="input" value="September {{ $a->intake_year }}" required>
                        <select name="route_code" class="input" aria-label="Application route">@foreach($routes as $code=>$label)<option value="{{ $code }}">{{ $code }}</option>@endforeach</select>
                        <textarea name="choices" class="input sm:col-span-2 min-h-20" placeholder="UCAS choices, one per line (max 4 medicine + 1 other)"></textarea>
                        <textarea name="notes" class="input sm:col-span-2 min-h-16" placeholder="Internal notes"></textarea>
                        <button class="btn btn-secondary sm:col-span-2">Propose</button></form>
                    <p class="hint mt-2">DIRECT_AGENT and UCAS_CENTRE are refused until a signed agreement is recorded for the university.</p></details>
            </section>

            <section class="card"><p class="eyebrow mb-3">Messages</p>
                <ol class="space-y-2 max-h-96 overflow-y-auto" tabindex="0" aria-label="Messages">@foreach($a->messages as $m)<li class="text-[0.9375rem] {{ $m->sender_user_id===$a->user_id ? '' : 'pl-6' }}"><span class="text-ink-500 text-[0.8125rem]">{{ $m->sender->name }} · {{ $m->created_at->format('j M H:i') }}</span><p class="whitespace-pre-line">{{ $m->body }}</p></li>@endforeach</ol>
                <form method="post" action="{{ route('admin.applications.reply',$a) }}" class="mt-3 flex flex-col gap-2">@csrf<textarea name="body" required class="input min-h-20" placeholder="Reply to the student"></textarea><button class="btn btn-secondary self-start">Send reply</button></form>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card"><p class="eyebrow mb-3">Stage control</p>
                <form method="post" action="{{ route('admin.applications.stage',$a) }}" class="space-y-2">@csrf
                    <select name="stage_override" class="input" aria-label="Set stage"><option value="INTERNAL_REVIEW">Start internal review</option><option value="ACTION_REQUIRED">Action required from student</option><option value="READY_FOR_STUDENT_APPROVAL">Ready for student approval</option><option value="ON_HOLD">On hold</option><option value="CLOSED">Close</option><option value="CLEAR">Clear override (derive)</option></select>
                    <input name="hold_until" type="date" class="input" placeholder="Hold until"><textarea name="note" class="input min-h-16" placeholder="Note to student (sent as a message)"></textarea>
                    <button class="btn btn-primary w-full">Apply</button></form>
                <p class="hint mt-2">Derived stages (form, documents, payment, submission) update automatically; only judgement stages are set here.</p>
            </section>
            <section class="card" aria-labelledby="svc-approval"><p class="eyebrow mb-3" id="svc-approval">Service selection</p>
                @if($a->servicesApproved())
                    <p class="text-[0.875rem]">Approved {{ $a->services_approved_at->format('j M Y H:i') }}: the student sees the service options and fees{{ $a->tier ? ' and chose '.$a->tier->name : '' }}.</p>
                    @unless($a->payments->whereIn('status', ['SUCCEEDED', 'MANUAL_REVIEW', 'INITIATED'])->isNotEmpty())
                        <form method="post" action="{{ route('admin.applications.services',$a) }}" class="mt-2">@csrf<input type="hidden" name="decision" value="revoke"><button class="btn btn-tertiary">Withdraw approval</button></form>
                    @endunless
                @else
                    @unless(app(\App\Services\Payments\StripeService::class)->paymentsOpen())<p class="text-[0.875rem] text-accent-700 mb-2">Payment is not open yet (no Stripe key, bank transfer off): an approved student can see the fees and choose a service but cannot pay until it opens.</p>@endunless
                    <p class="text-[0.875rem] text-ink-700">Profile: {{ collect(array_keys(\App\Services\Applications\FormSteps::all()))->filter(fn ($s) => $a->sectionComplete($s))->count() }} of {{ count(\App\Services\Applications\FormSteps::all()) }} sections complete. Approving shows the student the service options with their fees and lets them choose and pay.</p>
                    <form method="post" action="{{ route('admin.applications.services',$a) }}" class="space-y-2 mt-2">@csrf<input type="hidden" name="decision" value="approve">
                        <textarea name="note" class="input min-h-16" placeholder="Optional note to the student (included in the email)"></textarea>
                        <button class="btn btn-primary w-full">Approve for service selection</button></form>
                @endif
            </section>
            <section class="card"><p class="eyebrow mb-3">Assignment and notes</p>
                <form method="post" action="{{ route('admin.applications.assign',$a) }}" class="space-y-2">@csrf
                    <select name="assigned_staff_id" class="input" aria-label="Assigned staff member"><option value="">Unassigned</option>@foreach($staff as $s)<option value="{{ $s->id }}" @selected($a->assigned_staff_id===$s->id)>{{ $s->name }}</option>@endforeach</select>
                    <textarea name="staff_notes" class="input min-h-24" placeholder="Internal notes (never shown to student)">{{ $a->staff_notes }}</textarea><button class="btn btn-secondary w-full">Save</button></form></section>
            <section class="card"><p class="eyebrow mb-3">Payments</p>
                @forelse($a->payments as $p)<div class="text-[0.875rem] border-t border-ink-100 py-2 first:border-0"><p>{{ $p->tierPrice?->tier?->code }} {{ $p->formattedAmount() }} · {{ $p->method === 'STRIPE' ? 'Card' : 'Transfer' }} · <span class="font-medium">{{ $p->statusLabel() }}</span>@if($p->refunded_minor) · refunded {{ $p->formattedRefund() }}@endif</p><p class="text-ink-500">Started {{ $p->created_at->format('j M Y H:i') }}@if($p->succeeded_at) · paid {{ $p->succeeded_at->format('j M Y H:i') }}@endif {{ $p->note }}</p>@if($p->stripe_payment_intent_id || $p->stripe_checkout_session_id)<p class="text-ink-500 font-mono text-[0.75rem] break-all">{{ $p->stripe_payment_intent_id ?: $p->stripe_checkout_session_id }}</p>@endif
                    @if($p->status==='MANUAL_REVIEW')<form method="post" action="{{ route('admin.applications.payment.confirm',[$a,$p]) }}" class="flex gap-1 mt-1">@csrf<select name="decision" aria-label="Payment decision" class="input min-h-9 py-1"><option value="confirm">Confirm received</option><option value="reject">Not received</option></select><input name="note" class="input min-h-9 py-1" placeholder="Note"><button class="btn btn-secondary btn-sm">Go</button></form>@endif</div>
                @empty<p class="text-[0.875rem] text-ink-500">No payments.</p>@endforelse
                <p class="hint mt-2">{{ $a->tier?->name }} · fee {{ $a->tier?->priceFor('full')?->formatted() ?? 'not set' }} · {{ $a->hasSucceededPayment() ? 'paid' : 'unpaid' }}</p></section>
            <section class="card"><p class="eyebrow mb-3">Timeline</p><ol class="text-[0.8125rem] space-y-1 max-h-80 overflow-y-auto" tabindex="0" aria-label="Timeline">@foreach($a->events as $e)<li><span class="text-ink-500">{{ $e->created_at->format('j M H:i') }}</span> {{ $e->humanLabel() }}{{ $e->actor ? ' · '.$e->actor->name : '' }}</li>@endforeach</ol></section>
        </aside>
    </div>
</x-layouts.admin>
