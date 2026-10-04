<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\University;
use App\Support\FactSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Imports data/healthcare/courses.json: non-Medicine courses with the facts research recorded from official pages.
 * Every fact enters as VERIFY-ON-PAGE (hidden in production) and reaches a page only after a reviewer reads its source.
 * Idempotent; a fact a reviewer has already verified is never overwritten. Universities that are not medical schools are
 * created with international_policy not_published (that field describes Medicine) and are excluded from every Medicine
 * listing by University::medicalSchools().
 */
class HealthcareCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(File::get(base_path('data/healthcare/courses.json')), true);
        foreach ($data['courses'] as $c) {
            if (($c['profession'] ?? 'medicine') === 'medicine') {
                throw new RuntimeException("{$c['university']}/{$c['slug']}: Medicine courses come from the medical school dataset, not here.");
            }
            $u = University::firstWhere('slug', $c['university']);
            if (! $u) {
                if (empty($c['name'])) {
                    throw new RuntimeException("{$c['university']}: a new provider needs a name");
                }
                $u = University::create(['slug' => $c['university'], 'name' => $c['name'], 'nation' => $c['nation'] ?? null, 'city' => $c['city'] ?? null,
                    'international_policy' => 'not_published', 'published' => false]);
            }
            $course = Course::updateOrCreate(['university_id' => $u->id, 'slug' => $c['slug']], [
                'profession' => $c['profession'], 'title' => $c['title'], 'official_url' => $c['official_url'] ?? null, 'entry_type' => $c['entry_type'] ?? 'standard',
            ]);
            foreach ($c['facts'] as $f) {
                $attrs = ['value_text' => $f['text'] ?? null, 'value_number' => $f['number'] ?? null, 'source_url' => $f['source'], 'source_type' => 'official',
                    'applies_to' => 'international', 'notes' => trim('Research 2026-10-04 (search snippet of the official page). '.($f['notes'] ?? ''))];
                FactSeeding::upsert($course, ['key' => $f['key'], 'academic_year' => $f['year'] ?? null], $attrs, ReferenceFact::VERIFY_ON_PAGE);
            }
        }
    }
}
