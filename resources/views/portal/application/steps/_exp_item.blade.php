<fieldset class="card grid sm:grid-cols-2 gap-4" data-row-item>
    <div class="field"><label class="label" for="exp-role-{{ $i }}">Role</label><input id="exp-role-{{ $i }}" name="experience[{{ $i }}][role]" value="{{ $e['role'] ?? '' }}" class="input" placeholder="e.g. Volunteer, Pharmacy assistant"></div>
    <div class="field"><label class="label" for="exp-org-{{ $i }}">Organisation</label><input id="exp-org-{{ $i }}" name="experience[{{ $i }}][organisation]" value="{{ $e['organisation'] ?? '' }}" class="input"></div>
    <div class="field"><label class="label" for="exp-hours-{{ $i }}">Approximate hours</label><input id="exp-hours-{{ $i }}" name="experience[{{ $i }}][hours]" inputmode="numeric" value="{{ $e['hours'] ?? '' }}" class="input"></div>
    <div class="field sm:col-span-2"><label class="label" for="exp-sum-{{ $i }}">What you did and learned</label><textarea id="exp-sum-{{ $i }}" name="experience[{{ $i }}][summary]" rows="3" maxlength="600" class="input min-h-20">{{ $e['summary'] ?? '' }}</textarea></div>
    <div class="sm:col-span-2 text-right"><button type="button" data-remove-row class="btn btn-tertiary text-[0.875rem]">Remove</button></div>
</fieldset>
