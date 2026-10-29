<x-layouts.admin :seo="$seo">
    <h1 class="text-h2">Universities</h1>
    <p class="text-ink-700 mt-1">Publishing puts a university page online. It enters the search index only when its five core facts are verified <em>and</em> its row in the decision register (Admin → SEO decisions, edited in the repository) is live with the Nigerian demand evidence recorded. Verified facts alone never index a page.</p>
    <div class="card mt-5 overflow-x-auto"><table class="text-[0.875rem]"><thead><tr><th>University</th><th>Policy</th><th>Facts</th><th>Verified</th><th>Published</th><th>Search index</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody>
        @foreach($universities as $u)<tr><td><a href="{{ route('schools.show',$u) }}" target="_blank">{{ $u->name }}</a><br><span class="text-ink-500">{{ $u->city }}, {{ $u->nation }} · GMC: {{ $u->gmc_status }}</span></td><td>{{ $u->international_policy }}</td><td>{{ $u->facts_count }}</td><td>{{ $u->verified_count }}</td><td>{{ $u->published ? 'yes' : 'no' }}</td><td>@php($why = $u->indexBlocker()){{ $why === null ? 'indexed' : 'noindex: '.$why }}</td>
            <td><form method="post" action="{{ route('admin.reference.university.publish',$u) }}" class="flex gap-1">@csrf<input type="hidden" name="published" value="{{ $u->published ? 0 : 1 }}"><button class="btn btn-secondary btn-sm">{{ $u->published ? 'Unpublish' : 'Publish' }}</button></form></td></tr>@endforeach
    </tbody></table></div>
</x-layouts.admin>
