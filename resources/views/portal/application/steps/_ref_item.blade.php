<fieldset class="card grid sm:grid-cols-2 gap-4" data-row-item>
    <div class="field"><label class="label" for="ref-name-{{ $i }}">Name</label><input id="ref-name-{{ $i }}" name="referees[{{ $i }}][name]" value="{{ $r['name'] ?? '' }}" class="input"></div>
    <div class="field"><label class="label" for="ref-role-{{ $i }}">Role</label><input id="ref-role-{{ $i }}" name="referees[{{ $i }}][role]" value="{{ $r['role'] ?? '' }}" class="input" placeholder="e.g. Chemistry teacher"></div>
    <div class="field"><label class="label" for="ref-inst-{{ $i }}">School / institution</label><input id="ref-inst-{{ $i }}" name="referees[{{ $i }}][institution]" value="{{ $r['institution'] ?? '' }}" class="input"></div>
    <div class="field"><label class="label" for="ref-email-{{ $i }}">Email</label><input id="ref-email-{{ $i }}" name="referees[{{ $i }}][email]" type="email" value="{{ $r['email'] ?? '' }}" class="input"></div>
    <div class="sm:col-span-2 text-right"><button type="button" data-remove-row class="btn btn-tertiary text-[0.875rem]">Remove</button></div>
</fieldset>
