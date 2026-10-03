<x-layouts.admin :seo="$seo">
    <div class="flex items-center justify-between gap-4 flex-wrap"><h1 class="text-h2">Applications</h1>
        <form class="flex gap-2 items-end"><input name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Number, name or email">
            <select name="stage" class="input" aria-label="Filter by stage"><option value="">All stages</option>@foreach($stages as $s)<option value="{{ $s->value }}" @selected(($filters['stage'] ?? '')===$s->value)>{{ $s->label() }}</option>@endforeach</select><button class="btn btn-secondary">Filter</button></form></div>
    <div class="card mt-5 overflow-x-auto"><table class="text-[0.875rem]"><thead><tr><th>Number</th><th>Student</th><th>Service</th><th>Intake</th><th>Stage</th><th>%</th><th>Assigned</th><th>Last activity</th></tr></thead><tbody>
        @foreach($applications as $a)<tr><td><a href="{{ route('admin.applications.show',$a) }}" class="font-mono">{{ $a->application_number }}</a></td><td>{{ $a->user->name }}<br><span class="text-ink-500">{{ $a->user->email }}</span></td><td>{{ $a->tier?->code }}</td><td>{{ $a->intake_year }}</td><td>{{ $a->stage->label() }}</td><td>{{ $a->completion_pct }}</td><td>{{ $a->assignedStaff?->name ?? '—' }}</td><td>{{ $a->last_activity_at?->diffForHumans() }}</td></tr>@endforeach
    </tbody></table>{{ $applications->links() }}</div>
</x-layouts.admin>
