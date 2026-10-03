<x-layouts.admin :seo="$seo"><h1 class="text-h2">Audit log</h1>
    <div class="card mt-5 overflow-x-auto"><table class="text-[0.8125rem]"><thead><tr><th>When</th><th>Who</th><th>Action</th><th>Target</th><th>Payload</th></tr></thead><tbody>
        @foreach($actions as $x)<tr><td>{{ $x->created_at->format('j M Y H:i') }}</td><td>{{ $x->admin?->name }}</td><td>{{ $x->action }}</td><td>{{ $x->target_type ? class_basename($x->target_type).' #'.$x->target_id : '' }}</td><td class="font-mono max-w-md truncate">{{ json_encode($x->payload) }}</td></tr>@endforeach
    </tbody></table>{{ $actions->links() }}</div></x-layouts.admin>
