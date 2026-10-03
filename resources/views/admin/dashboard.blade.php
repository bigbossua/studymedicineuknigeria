<x-layouts.admin :seo="$seo">
    <h1 class="text-h2">Operations</h1>
    <div class="mt-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach([['New leads',$counts['leads_new'],route('admin.leads',['status'=>'new'])],['Active applications',$counts['applications_active'],route('admin.applications.index')],['Documents to review',$counts['docs_review'],route('admin.applications.index',['stage'=>'DOCUMENTS_INCOMPLETE'])],['Files ready for review',$counts['docs_complete_awaiting'],route('admin.applications.index',['stage'=>'DOCUMENTS_COMPLETE'])],['Awaiting student approval',$counts['approvals_pending'],route('admin.applications.index',['stage'=>'READY_FOR_STUDENT_APPROVAL'])],['Approved — to submit',$counts['approved_to_submit'],route('admin.applications.index',['stage'=>'STUDENT_APPROVED'])],['Bank transfers to confirm',$counts['payments_manual'],route('admin.payments',['status'=>'MANUAL_REVIEW'])],['Facts awaiting verification',$counts['facts_pending'],route('admin.reference.index')],['Facts review due',$counts['facts_review_due'],route('admin.reference.index',['status'=>'REVIEW_DUE'])]] as [$l,$n,$u])
            <a href="{{ $u }}" class="card card-link"><p class="text-3xl font-serif font-semibold">{{ $n }}</p><p class="text-[0.875rem] text-ink-500">{{ $l }}</p></a>
        @endforeach
    </div>
    <div class="mt-8 grid lg:grid-cols-3 gap-6">
        <section class="card lg:col-span-2"><p class="eyebrow mb-3">Recent activity</p>
            <table class="text-[0.875rem]"><thead><tr><th>Application</th><th>Student</th><th>Service</th><th>Stage</th><th>Last activity</th></tr></thead><tbody>
            @foreach($recent as $a)<tr><td><a href="{{ route('admin.applications.show',$a) }}" class="font-mono">{{ $a->application_number }}</a></td><td>{{ $a->user->name }}</td><td>{{ $a->tier?->code }}</td><td>{{ $a->stage->label() }}</td><td>{{ $a->last_activity_at?->diffForHumans() }}</td></tr>@endforeach
            </tbody></table></section>
        <section class="card"><p class="eyebrow mb-3">By stage</p><ul class="text-[0.875rem] space-y-1">@foreach($byStage as $s=>$c)<li class="flex justify-between"><span>{{ \App\Enums\Stage::tryFrom($s)?->label() ?? $s }}</span><span class="font-semibold">{{ $c }}</span></li>@endforeach</ul></section>
    </div>
</x-layouts.admin>
