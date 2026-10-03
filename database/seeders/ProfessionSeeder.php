<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/** Upserts the master taxonomy from data/healthcare/subjects.json (idempotent; the JSON is the source of truth). */
class ProfessionSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(File::get(base_path('data/healthcare/subjects.json')), true);
        foreach ($data['subjects'] as $s) {
            $attrs = array_intersect_key($s, array_flip([
                'name', 'flagship', 'official_terminology', 'alternative_names', 'regulator', 'register_route', 'professional_body', 'undergraduate_entry', 'typical_length_years',
                'international_availability', 'nigerian_relevance', 'waec_neco_relevance', 'a_level_requirements', 'foundation_routes', 'graduate_routes', 'admissions_test', 'application_route',
                'fees', 'english_language', 'nigeria_statement', 'deadlines', 'career_pathway', 'nigerian_search_demand', 'long_tail_demand', 'commercial_intent', 'competition', 'sources', 'decision_register_ids', 'status', 'status_reason', 'last_researched',
            ]));
            Profession::updateOrCreate(['slug' => $s['slug']], $attrs);
        }
    }
}
