<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\Stage;
use App\Models\Application;
use App\Models\Authorisation;
use App\Models\ServiceTier;
use App\Models\Submission;
use App\Models\University;
use App\Models\User;
use App\Services\Applications\FormSteps;
use App\Services\Applications\StageResolver;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('private');
        $this->seed(PlatformSeeder::class);
        $this->student = User::factory()->create(['email_verified_at' => now()]);
        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->forceFill(['role' => 'admin'])->save();
    }

    private function startApplication(): Application
    {
        $tier = ServiceTier::where('code', 'T2')->first();
        $this->actingAs($this->student)->post('/portal/start', ['service_tier_id' => $tier->id, 'intake_year' => 2028])->assertRedirect();

        return Application::firstOrFail();
    }

    private function completeForm(Application $a): void
    {
        $form = [
            'personal' => ['legal_first_names' => 'Ada', 'legal_surname' => 'Okonkwo', 'date_of_birth' => '2007-01-01', 'nationality' => 'Nigerian', 'phone' => '+234', 'country_of_residence' => 'Nigeria'],
            'study' => ['course_family' => 'medicine', 'entry_type' => 'standard', 'intake_year' => 2028, 'ucas_status' => 'not_started'],
            'secondary' => ['sittings' => [['board' => 'WAEC', 'year' => 2025, 'subjects' => [['subject' => 'English', 'grade' => 'B2'], ['subject' => 'Maths', 'grade' => 'A1'], ['subject' => 'Biology', 'grade' => 'A1']]]]],
            'post_secondary' => ['none' => 1],
            'english' => ['route' => 'NONE_YET'],
            'tests' => ['ucat_status' => 'planned', 'gamsat_status' => 'not_planned'],
            'experience' => ['statement_status' => 'draft'],
            'referees' => ['referees' => [['name' => 'A', 'role' => 'Teacher', 'institution' => 'School', 'email' => 'a@b.test']], 'consent_contact_referees' => 1],
            'declarations' => ['accurate' => 1, 'data_processing' => 1, 'terms' => 1, 'no_guarantee' => 1],
        ];
        $status = collect(array_keys(FormSteps::all()))->mapWithKeys(fn ($s) => [$s => 'complete'])->all();
        $a->forceFill(['form' => $form, 'section_status' => $status])->save();
    }

    public function test_starting_an_application_issues_a_number_and_a_personalised_checklist(): void
    {
        $a = $this->startApplication();
        $this->assertMatchesRegularExpression('/^SMN-2028-\d{6}$/', $a->application_number);
        $codes = $a->documents()->pluck('code')->all();
        $this->assertContains('PASSPORT', $codes);
        $this->assertContains('STATEMENT', $codes);
        $this->assertNotContains('WAEC', $codes);
    }

    public function test_answering_waec_adds_the_waec_document_to_the_checklist(): void
    {
        $a = $this->startApplication();
        $this->actingAs($this->student)->post("/portal/{$a->application_number}/application/secondary", ['sittings' => [['board' => 'WAEC', 'year' => 2025, 'subjects' => [['subject' => 'English', 'grade' => 'B2'], ['subject' => 'Maths', 'grade' => 'A1'], ['subject' => 'Biology', 'grade' => 'A1']]]], 'intent' => 'later'])->assertRedirect();
        $this->assertTrue($a->documents()->where('code', 'WAEC')->exists());
    }

    public function test_autosave_stores_partial_data_without_marking_the_step_complete(): void
    {
        $a = $this->startApplication();
        $this->actingAs($this->student)->postJson("/portal/{$a->application_number}/application/personal", ['legal_first_names' => 'Ada'])->assertOk()->assertJson(['saved' => true, 'complete' => false]);
        $this->assertSame('Ada', $a->fresh()->form['personal']['legal_first_names']);
    }

    public function test_another_student_cannot_read_or_write_the_application(): void
    {
        $a = $this->startApplication();
        $other = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($other)->get("/portal/{$a->application_number}/application")->assertForbidden();
        $this->actingAs($other)->get("/portal/{$a->application_number}/documents")->assertForbidden();
    }

    public function test_document_upload_rejects_disallowed_content_and_accepts_a_valid_pdf(): void
    {
        $a = $this->startApplication();
        $doc = $a->documents()->where('code', 'STATEMENT')->first();
        $bad = UploadedFile::fake()->createWithContent('evil.pdf', '<html>not a pdf</html>');
        $this->actingAs($this->student)->post("/portal/{$a->application_number}/documents/{$doc->id}", ['file' => $bad])->assertSessionHasErrors('file');
        $pdf = UploadedFile::fake()->createWithContent('statement.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
        $this->actingAs($this->student)->post("/portal/{$a->application_number}/documents/{$doc->id}", ['file' => $pdf])->assertRedirect();
        $this->assertSame(DocumentStatus::UNDER_REVIEW, $doc->fresh()->status);
        $this->assertCount(1, Storage::disk('private')->allFiles());
        $js = UploadedFile::fake()->createWithContent('script.pdf', '%PDF-1.4 /JavaScript (app.alert(1)) %%EOF');
        $this->actingAs($this->student)->post("/portal/{$a->application_number}/documents/{$doc->id}", ['file' => $js])->assertSessionHasErrors('file');
    }

    public function test_approval_is_impossible_until_form_documents_and_a_proposal_exist(): void
    {
        $a = $this->startApplication();
        $this->actingAs($this->student)->get("/portal/{$a->application_number}/approve")->assertNotFound();
        $this->actingAs($this->admin)->post("/admin/applications/{$a->application_number}/stage", ['stage_override' => 'READY_FOR_STUDENT_APPROVAL'])->assertSessionHas('error');
    }

    public function test_agreement_gated_submission_routes_are_refused(): void
    {
        $a = $this->startApplication();
        $this->actingAs($this->admin)->post("/admin/applications/{$a->application_number}/submissions", ['intake' => 'September 2028', 'route_code' => 'DIRECT_AGENT'])->assertSessionHas('error');
        $this->assertSame(0, Submission::count());
    }

    public function test_full_approval_gate(): void
    {
        $a = $this->startApplication();
        $this->completeForm($a);
        $a->documents()->get()->each(fn ($d) => $d->transition(DocumentStatus::ACCEPTED, $this->admin->id));
        $u = University::create(['slug' => 'leics', 'name' => 'University of Leicester', 'international_policy' => 'accepts']);
        $this->actingAs($this->admin)->post("/admin/applications/{$a->application_number}/submissions", ['university_id' => $u->id, 'intake' => 'September 2028', 'route_code' => 'UCAS_STUDENT', 'choices' => 'Leicester A100'])->assertSessionHas('status');
        $sub = Submission::first();

        // staff cannot mark submitted before the student approves
        $this->actingAs($this->admin)->post("/admin/applications/{$a->application_number}/submissions/{$sub->id}", ['status' => 'SUBMITTED', 'external_reference' => 'X'])->assertSessionHas('error');
        $this->assertSame('PROPOSED', $sub->fresh()->status);

        $this->actingAs($this->admin)->post("/admin/applications/{$a->application_number}/stage", ['stage_override' => 'READY_FOR_STUDENT_APPROVAL'])->assertSessionHas('status');
        app(StageResolver::class)->sync($a->fresh());
        $this->assertSame(Stage::READY_FOR_STUDENT_APPROVAL, $a->fresh()->stage);

        $page = $this->actingAs($this->student)->get("/portal/{$a->application_number}/approve")->assertOk();
        preg_match('/name="hash" value="([a-f0-9]{64})"/', $page->getContent(), $m);
        $this->actingAs($this->student)->post("/portal/{$a->application_number}/approve", ['typed_name' => 'Ada Okonkwo', 'confirm' => 1, 'hash' => str_repeat('0', 64)])->assertSessionHas('error');
        $this->assertSame(0, Authorisation::count());
        $this->actingAs($this->student)->post("/portal/{$a->application_number}/approve", ['typed_name' => 'Ada Okonkwo', 'confirm' => 1, 'hash' => $m[1]])->assertRedirect();
        $auth = Authorisation::first();
        $this->assertNotNull($auth);
        $this->assertSame('Ada Okonkwo', $auth->typed_name);
        $this->assertSame('AUTHORISED', $sub->fresh()->status);
        $this->assertSame(Stage::STUDENT_APPROVED, $a->fresh()->stage);

        // package changes after approval → authorisation invalidated when staff try to proceed
        $a->refresh(); $form = $a->form; $form['personal']['legal_surname'] = 'Changed'; $a->forceFill(['form' => $form])->save();
        $this->actingAs($this->admin)->post("/admin/applications/{$a->application_number}/submissions/{$sub->id}", ['status' => 'PACKAGE_READY'])->assertSessionHas('error');
        $this->assertNotNull($auth->fresh()->revoked_at);
        $this->assertSame('PROPOSED', $sub->fresh()->status);
    }

    public function test_student_can_withdraw(): void
    {
        $a = $this->startApplication();
        $this->actingAs($this->student)->post("/portal/{$a->application_number}/withdraw", ['confirm' => 1])->assertRedirect();
        $this->assertSame(Stage::WITHDRAWN, $a->fresh()->stage);
    }

    public function test_students_cannot_reach_admin_or_escalate_roles(): void
    {
        $this->actingAs($this->student)->get('/admin')->assertForbidden();
        $this->actingAs($this->student)->post("/admin/users/{$this->student->id}/role", ['role' => 'admin'])->assertForbidden();
    }
}
