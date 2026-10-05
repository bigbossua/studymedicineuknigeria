<?php

namespace Database\Seeders;

use App\Models\AdminAction;
use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Publishes the university pages listed in data/medical-schools/publication.json (each one met the publication
 * threshold and has a decision-register row). Run by smukn:reference-sync on every deploy. Staff decisions win: a school
 * published or unpublished in Admin → Universities on or after the list's decision date is never changed from here.
 */
class UniversityPublicationSeeder extends Seeder
{
    public function run(): void
    {
        $list = json_decode((string) file_get_contents(base_path('data/medical-schools/publication.json')), true)['published'] ?? [];
        foreach ($list as $entry) {
            $u = University::where('slug', $entry['slug'] ?? '')->first();
            if (! $u || $u->published) {
                continue;
            }
            $staffDecided = AdminAction::where('action', 'university.publish')->where('target_type', University::class)->where('target_id', $u->id)
                ->where('created_at', '>=', $entry['decided_on'] ?? '1970-01-01')->exists();
            if ($staffDecided) {
                continue;
            }
            $u->forceFill(['published' => true])->save();
            Log::info('University published from data/medical-schools/publication.json', ['slug' => $u->slug, 'register' => $entry['register'] ?? null]);
        }
    }
}
