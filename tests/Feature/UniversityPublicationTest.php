<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\ReferenceFact;
use App\Models\University;
use App\Models\User;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\UniversityPublicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** data/medical-schools/publication.json: schools that met the threshold are published from the repository, enter the sitemap, and staff decisions win. */
class UniversityPublicationTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<array{slug: string, register: string, decided_on: string}> */
    private function listed(): array
    {
        return json_decode(file_get_contents(base_path('data/medical-schools/publication.json')), true)['published'];
    }

    public function test_exactly_the_listed_schools_are_published(): void
    {
        $this->seed([ReferenceDataSeeder::class, UniversityPublicationSeeder::class, UniversityPublicationSeeder::class]);
        $this->assertEqualsCanonicalizing(array_column($this->listed(), 'slug'), University::where('published', true)->pluck('slug')->all());
    }

    public function test_an_admin_unpublish_is_never_overwritten_by_the_repository_list(): void
    {
        $this->seed([ReferenceDataSeeder::class, UniversityPublicationSeeder::class]);
        $u = University::where('slug', $this->listed()[0]['slug'])->firstOrFail();
        $u->forceFill(['published' => false])->save();
        AdminAction::create(['admin_user_id' => User::factory()->create()->id, 'action' => 'university.publish', 'target_type' => University::class, 'target_id' => $u->id, 'payload' => ['published' => false]]);

        $this->seed(UniversityPublicationSeeder::class);
        $this->assertFalse($u->refresh()->published);
        $this->get('/medical-schools/'.$u->slug)->assertOk()->assertSee('noindex', false);
        $this->assertStringNotContainsString('/medical-schools/'.$u->slug.'<', $this->get('/sitemap.xml')->getContent());
    }

    public function test_published_schools_enter_the_sitemap_with_their_latest_verification_as_lastmod(): void
    {
        $this->seed([ReferenceDataSeeder::class, UniversityPublicationSeeder::class]);
        $first = University::where('slug', $this->listed()[0]['slug'])->firstOrFail();
        $first->facts()->create(['key' => 'test_fact', 'value_text' => 'x', 'verification_status' => ReferenceFact::VERIFIED, 'verified_at' => '2026-10-05 09:00:00', 'source_type' => 'official']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        foreach ($this->listed() as $entry) {
            $this->assertStringContainsString(route('schools.show', $entry['slug']).'</loc>', $xml);
            $html = $this->get('/medical-schools/'.$entry['slug'])->assertOk()->getContent();
            $this->assertStringNotContainsString('noindex', $html, "{$entry['slug']} is published but noindex");
            $this->assertStringContainsString('<link rel="canonical" href="'.route('schools.show', $entry['slug']).'"', $html);
        }
        $this->assertStringContainsString(route('schools.show', $first)."</loc>\n    <lastmod>2026-10-05</lastmod>", $xml);
        $unlisted = University::medicalSchools()->where('published', false)->first();
        $this->assertStringNotContainsString(route('schools.show', $unlisted).'</loc>', $xml);
    }

    public function test_every_listed_school_has_a_live_decision_register_row_that_points_at_it(): void
    {
        $h = fopen(base_path('data/seo/decision-register.csv'), 'r');
        $header = fgetcsv($h);
        $rows = [];
        while (($r = fgetcsv($h)) !== false) {
            $r = array_combine($header, $r);
            $rows[$r['id']] = $r;
        }
        fclose($h);
        foreach ($this->listed() as $entry) {
            $this->assertArrayHasKey($entry['register'], $rows);
            $this->assertSame('/medical-schools/'.$entry['slug'], $rows[$entry['register']]['current_url']);
            $this->assertContains($rows[$entry['register']]['status'], ['PUBLISHED', 'INDEXING', 'MEASURING', 'UPDATE']);
        }
    }
}
