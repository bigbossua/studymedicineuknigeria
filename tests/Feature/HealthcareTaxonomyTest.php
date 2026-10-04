<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Course;
use App\Models\Profession;
use App\Models\University;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\ProfessionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The healthcare course universe (data/healthcare/subjects.json → professions) decides which subjects may have a public
 * page. These tests keep the taxonomy honest: register vocabulary only, Medicine the only live subject until another
 * reaches PUBLISHED on its own evidence, every course record attached to a subject, and no research label on a public page.
 */
class HealthcareTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    private function taxonomy(): array
    {
        return json_decode(File::get(base_path('data/healthcare/subjects.json')), true);
    }

    public function test_every_subject_uses_the_register_vocabulary_and_records_evidence(): void
    {
        $data = $this->taxonomy();
        $this->assertSame(Profession::STATUSES, $data['_meta']['status_values']);
        $slugs = [];
        foreach ($data['subjects'] as $s) {
            $this->assertContains($s['status'], Profession::STATUSES, $s['slug']);
            $this->assertNotEmpty($s['status_reason'], "{$s['slug']} needs a decision reason");
            $this->assertNotEmpty($s['regulator'], "{$s['slug']} needs a regulator (or an explicit 'none')");
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $s['slug']);
            $this->assertNotContains($s['slug'], $slugs, 'duplicate slug');
            $slugs[] = $s['slug'];
            if ($s['status'] !== 'RESEARCH') {
                $this->assertNotEmpty($s['sources'], "{$s['slug']} left RESEARCH without recorded sources");
            }
        }
        $this->assertGreaterThanOrEqual(25, count($slugs));
    }

    public function test_medicine_is_the_only_flagship_and_the_only_subject_allowed_a_public_page(): void
    {
        $this->seed(ProfessionSeeder::class);
        $flagships = Profession::where('flagship', true)->pluck('slug')->all();
        $this->assertSame(['medicine'], $flagships);
        $this->assertSame('PUBLISHED', Profession::where('slug', 'medicine')->value('status'));
        $live = Profession::whereIn('status', Profession::LIVE)->pluck('slug')->all();
        $this->assertSame(['medicine'], $live, 'another subject reached a live status: it needs an asset-register row, a decision-register URL and its own page tests first');
        $this->assertFalse(Profession::where('slug', 'nursing')->first()->mayHavePublicPage());
    }

    public function test_every_course_record_belongs_to_a_known_subject(): void
    {
        $this->seed(ProfessionSeeder::class);
        $known = Profession::pluck('slug')->all();
        foreach (Course::pluck('profession')->unique() as $profession) {
            $this->assertContains($profession, $known, "course profession '{$profession}' is not in the taxonomy");
        }
    }

    public function test_subject_register_rows_mirror_the_taxonomy_status(): void
    {
        $rows = array_map('str_getcsv', file(base_path('data/seo/decision-register.csv')));
        $head = array_shift($rows);
        $byFamily = [];
        foreach ($rows as $r) {
            $row = array_combine($head, $r);
            if ($row['cluster'] === 'V') {
                $byFamily[$row['query_family']] = $row;
            }
        }
        $this->assertArrayHasKey('allied health and healthcare courses in the UK for Nigerian students (overview hub)', $byFamily);
        foreach ($this->taxonomy()['subjects'] as $s) {
            if ($s['flagship']) {
                continue;
            }
            $row = $byFamily["{$s['name']} in the UK for Nigerian students (subject cluster)"] ?? null;
            $this->assertNotNull($row, "{$s['slug']} has no cluster V register row: run ops/seo/build-register.py");
            $this->assertSame($s['status'], $row['status'], "{$s['slug']}: taxonomy and register disagree");
            $this->assertContains($row['id'], $s['decision_register_ids'], "{$s['slug']} does not reference its register row {$row['id']}");
            if (! in_array($s['status'], Profession::LIVE, true)) {
                $this->assertSame('—', $row['current_url'], "{$s['slug']} is not live but the register gives it a URL");
            }
        }
    }

    public function test_research_labels_never_reach_a_public_page(): void
    {
        $this->seed(ProfessionSeeder::class);
        foreach (['/', '/study-medicine-in-the-uk', '/medical-schools', '/faq', '/apply-online/eligibility'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/\b(VERIFY-ON-PAGE|NOT RESEARCHED|LEAD [A-Z][a-z]+ £)/', $html, $path);
        }
        $this->get('/admin/professions')->assertRedirect();
    }

    public function test_staff_can_review_the_subjects_screen(): void
    {
        $this->seed(ProfessionSeeder::class);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'staff', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])
            ->get('/admin/professions')->assertOk()->assertSee('Healthcare subjects')->assertSee('Dentistry')->assertSee('approximately 15 international places')
            ->assertSee('<meta name="robots" content="noindex', false);
    }

    public function test_a_course_of_another_subject_never_appears_on_a_medicine_page(): void
    {
        $u = University::create(['slug' => 'alpha', 'name' => 'Alpha University', 'nation' => 'England', 'international_policy' => 'accepts', 'published' => true]);
        $med = Course::create(['university_id' => $u->id, 'slug' => 'medicine', 'title' => 'Medicine MBBS', 'entry_type' => 'standard', 'application_route' => 'UCAS', 'admissions_test' => 'UCAT']);
        $med->facts()->create(['key' => 'international_fee_gbp', 'value_number' => 41000, 'academic_year' => '2026/27', 'verification_status' => 'VERIFY-ON-PAGE', 'source_type' => 'official']);
        // A nursing course at the same university, shaped to match every Medicine filter (test, route, foundation, graduate, fee).
        foreach (['standard', 'foundation', 'graduate'] as $type) {
            $nur = Course::create(['university_id' => $u->id, 'profession' => 'nursing', 'slug' => "nursing-{$type}", 'title' => "Adult Nursing Zqx {$type}", 'entry_type' => $type, 'application_route' => 'BOTH', 'admissions_test' => 'UCAT', 'official_url' => "https://alpha.example/nursing-zqx-{$type}"]);
            $nur->facts()->create(['key' => 'international_fee_gbp', 'value_number' => 17777, 'academic_year' => '2026/27', 'verification_status' => 'VERIFY-ON-PAGE', 'source_type' => 'official']);
        }

        foreach (['/medical-schools', '/medical-schools?test=UCAT', '/medical-schools?route=BOTH', '/medical-schools/alpha', '/fees', '/fees?sort=fee', '/admissions/ucat', '/admissions/how-to-apply',
            '/study-medicine-in-the-uk/foundation-routes', '/requirements/nigerian-degree-graduate-entry'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertStringNotContainsString('Zqx', $html, "{$path} shows a nursing course");
            $this->assertStringNotContainsString('17,777', $html, "{$path} shows a nursing fee");
            $this->assertStringNotContainsString('17777', $html, "{$path} shows a nursing fee");
        }
        $this->assertStringContainsString('Medicine MBBS', $this->get('/medical-schools/alpha')->getContent());
        $this->assertStringNotContainsString('Alpha University', $this->get('/medical-schools?route=BOTH')->getContent(), 'a nursing course must not make a school match a Medicine filter');
    }

    public function test_a_university_recorded_only_for_another_subject_is_not_a_medical_school(): void
    {
        $med = University::create(['slug' => 'medschool', 'name' => 'Medschool University', 'nation' => 'England', 'international_policy' => 'accepts', 'published' => true]);
        Course::create(['university_id' => $med->id, 'slug' => 'medicine', 'title' => 'Medicine MBBS', 'entry_type' => 'standard']);
        $nurse = University::create(['slug' => 'nursingonly', 'name' => 'Qzv Nursing University', 'nation' => 'England', 'international_policy' => 'accepts', 'published' => true]);
        Course::create(['university_id' => $nurse->id, 'profession' => 'nursing', 'slug' => 'adult-nursing', 'title' => 'Adult Nursing', 'entry_type' => 'standard']);
        $nurse->facts()->create(['key' => 'waec_neco_statement', 'value_text' => 'Qzv statement about WASSCE for nursing applicants only.', 'verification_status' => 'VERIFY-ON-PAGE', 'source_type' => 'official']);

        $this->assertSame(['medschool'], University::medicalSchools()->pluck('slug')->all());
        $this->assertStringNotContainsString('Qzv', $this->get('/medical-schools')->assertOk()->getContent());
        $this->get('/medical-schools/nursingonly')->assertNotFound();
        $this->get('/medical-schools/medschool')->assertOk();
        foreach (['/requirements/waec', '/study-medicine-in-the-uk/from-nigeria', '/requirements/neco'] as $path) {
            $this->assertStringNotContainsString('Qzv', $this->get($path)->assertOk()->getContent(), "{$path} shows a non-medical university's statement");
        }
    }
}
