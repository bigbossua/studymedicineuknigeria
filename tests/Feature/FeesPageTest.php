<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\University;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_fee_table_can_be_ordered_by_lowest_published_fee_without_changing_the_canonical(): void
    {
        foreach ([['zed', 'Zed University', 30000], ['alpha', 'Alpha University', 50000], ['mid', 'Mid University', null]] as [$slug, $name, $fee]) {
            $u = University::create(['slug' => $slug, 'name' => $name, 'nation' => 'England', 'international_policy' => 'accepts', 'published' => true]);
            $c = Course::create(['university_id' => $u->id, 'slug' => 'medicine', 'title' => 'Medicine', 'entry_type' => 'standard']);
            $c->facts()->create(['key' => 'international_fee_gbp', 'value_number' => $fee, 'academic_year' => '2026/27', 'verification_status' => $fee ? 'VERIFY-ON-PAGE' : 'NOT_PUBLISHED', 'source_type' => 'official']);
        }
        $byName = $this->get('/fees')->assertOk()->getContent();
        $this->assertLessThan(strpos($byName, 'Zed University'), strpos($byName, 'Alpha University'));

        $byFee = $this->get('/fees?sort=fee')->assertOk()->getContent();
        $this->assertLessThan(strpos($byFee, 'Alpha University'), strpos($byFee, 'Zed University'), 'lowest fee first');
        $this->assertLessThan(strpos($byFee, 'Mid University'), strpos($byFee, 'Alpha University'), 'no publishable fee sinks to the end');
        $this->assertStringContainsString('rel="canonical" href="'.url('/fees').'"', $byFee);
        $this->assertStringContainsString('lowest fee a university has published', $byFee);
    }
}
