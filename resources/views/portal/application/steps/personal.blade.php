<div class="grid sm:grid-cols-2 gap-5">
    @include('portal.application.steps._field', ['name' => 'legal_first_names', 'label' => 'First and middle names', 'value' => $d['legal_first_names'] ?? '', 'required' => true, 'autocomplete' => 'given-name', 'hint' => 'Exactly as printed in your passport.'])
    @include('portal.application.steps._field', ['name' => 'legal_surname', 'label' => 'Surname', 'value' => $d['legal_surname'] ?? '', 'required' => true, 'autocomplete' => 'family-name'])
    @include('portal.application.steps._field', ['name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'value' => $d['date_of_birth'] ?? '', 'required' => true, 'hint' => 'Most medical schools require you to be 18 by the start of the course.'])
    @include('portal.application.steps._field', ['name' => 'nationality', 'label' => 'Nationality', 'value' => $d['nationality'] ?? 'Nigerian', 'required' => true])
    @include('portal.application.steps._field', ['name' => 'passport_number', 'label' => 'Passport number', 'value' => $d['passport_number'] ?? '', 'hint' => 'Optional now; needed before any submission.'])
    @include('portal.application.steps._field', ['name' => 'country_of_residence', 'label' => 'Country where you live now', 'value' => $d['country_of_residence'] ?? 'Nigeria', 'required' => true])
    @include('portal.application.steps._field', ['name' => 'nigeria_state', 'label' => 'State (if in Nigeria)', 'value' => $d['nigeria_state'] ?? ''])
    @include('portal.application.steps._field', ['name' => 'city', 'label' => 'City', 'value' => $d['city'] ?? ''])
    @include('portal.application.steps._field', ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'value' => $d['phone'] ?? auth()->user()->phone, 'required' => true, 'placeholder' => '+234', 'autocomplete' => 'tel'])
    @include('portal.application.steps._field', ['name' => 'whatsapp', 'label' => 'WhatsApp', 'type' => 'tel', 'value' => $d['whatsapp'] ?? auth()->user()->whatsapp, 'placeholder' => '+234'])
</div>
