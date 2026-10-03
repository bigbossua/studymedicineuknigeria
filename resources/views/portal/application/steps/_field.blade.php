{{-- generic text field: name, label, hint, type, value, required --}}
@props(['name', 'label', 'hint' => null, 'type' => 'text', 'value' => '', 'required' => false, 'placeholder' => '', 'autocomplete' => null])
<div class="field">
    <label for="{{ str_replace(["[","]"], "-", $name) }}" class="label">{{ $label }}@if($required) <span class="text-accent-600" aria-hidden="true">*</span>@endif</label>
    <input id="{{ str_replace(["[","]"], "-", $name) }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" class="input @error($name) input-error @enderror" placeholder="{{ $placeholder }}" @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($required) aria-required="true" @endif>
    @if($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="error-text">{{ $message }}</p>@enderror
</div>
