<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\University;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** University record pages: computed Nigerian-applicant guidance never implies a fact that is not published, and schema follows the fee's verification. */
class SchoolPagesTest extends TestCase
{
    use RefreshDatabase;

    private function university(string $policy, array $courseAttrs = []): University
    {
        $u = University::create(['slug' => 'testville-'.$policy, 'name' => 'University of Testville '.$policy, 'nation' => 'England', 'international_policy' => $policy, 'published' => true]);
        Course::create(['university_id' => $u->id, 'slug' => 'medicine', 'title' => 'Medicine MBBS', 'ucas_code' => 'A100', 'entry_type' => 'standard', 'application_route' => 'UCAS', 'admissions_test' => 'UCAT'] + $courseAttrs);

        return $u;
    }

    public function test_home_only_schools_say_so_plainly_and_accepting_schools_describe_what_is_and_is_not_published(): void
    {
        $this->get('/medical-schools/'.$this->university('home_only')->slug)->assertOk()
            ->assertSee('Not open to you.')->assertSee('What this means if you are applying from Nigeria')->assertSee('How to apply to this school')
            ->assertSee('no Nigeria-specific statement located')->assertSee('UCAS, by the medicine deadline');

        $u = $this->university('accepts');
        $u->facts()->create(['key' => 'international_places', 'value_text' => '18 places for 2027 entry', 'verification_status' => 'VERIFY-ON-PAGE', 'source_type' => 'official']);
        $u->facts()->create(['key' => 'waec_neco_statement', 'value_text' => 'WASSCE holders complete a recognised foundation programme first.', 'verification_status' => 'VERIFY-ON-PAGE', 'source_type' => 'official']);
        $html = $this->get('/medical-schools/'.$u->slug)->assertOk()->getContent();
        $this->assertStringContainsString('Open to international applicants', $html);
        $this->assertStringContainsString('published number of international places: 18 places for 2027 entry', $html);
        $this->assertStringContainsString('the university addresses it (statement above)', $html);
        $this->assertStringContainsString('no school-specific statement located', $html, 'English is not published for this fixture');
    }

    public function test_course_schema_carries_an_offer_only_when_the_fee_is_verified(): void
    {
        $u = $this->university('accepts');
        $course = $u->courses()->first();
        $fee = $course->facts()->create(['key' => 'international_fee_gbp', 'value_number' => 45000, 'academic_year' => '2026/27', 'verification_status' => 'VERIFY-ON-PAGE', 'source_type' => 'official']);
        $html = $this->get('/medical-schools/'.$u->slug)->assertOk()->getContent();
        $this->assertStringContainsString('"@type":"Course"', $html);
        $this->assertStringContainsString('"courseCode":"A100"', $html);
        $this->assertStringNotContainsString('"@type":"Offer"', $html, 'an unverified fee must not become a schema Offer');

        $fee->update(['verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now()]);
        $html = $this->get('/medical-schools/'.$u->slug)->assertOk()->getContent();
        $this->assertStringContainsString('"@type":"Offer"', $html);
        $this->assertStringContainsString('"price":"45000"', $html);
        $this->assertStringContainsString('"priceCurrency":"GBP"', $html);
    }

    public function test_in_production_unverified_facts_are_hidden_and_the_guidance_does_not_refer_to_them(): void
    {
        $u = $this->university('accepts');
        $u->facts()->create(['key' => 'waec_neco_statement', 'value_text' => 'WASSCE holders complete a recognised foundation programme first.', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => 'https://example.ac.uk/nigeria', 'source_type' => 'official']);
        $u->courses()->first()->facts()->create(['key' => 'international_fee_gbp', 'value_number' => 45000, 'academic_year' => '2026/27', 'verification_status' => 'VERIFY-ON-PAGE', 'source_url' => 'https://example.ac.uk/fees', 'source_type' => 'official']);

        $this->app['env'] = 'production';
        config(['site.publish_unverified' => false]);
        $html = $this->get('/medical-schools/'.$u->slug)->assertOk()->getContent();
        $this->assertStringNotContainsString('WASSCE holders complete a recognised foundation programme first.', $html, 'unverified wording must not render in production');
        $this->assertStringNotContainsString('45,000', $html);
        $this->assertStringContainsString('This detail is being verified against the official source', $html);
        $this->assertStringContainsString('no Nigeria-specific statement located', $html, 'the computed guidance must not claim a statement the reader cannot see');
        $fees = $this->get('/fees')->assertOk()->getContent();
        $this->assertStringContainsString('Being verified', $fees);
        $this->assertStringNotContainsString('£45,000', $fees);
        $u->facts()->create(['key' => 'gem_international', 'value_text' => 'A101 page exists; international eligibility: DATA UNAVAILABLE', 'verification_status' => 'NOT_FOUND', 'source_type' => 'official']);
        $this->assertStringNotContainsString('international eligibility not yet established (', $this->get('/requirements/nigerian-degree-graduate-entry')->assertOk()->getContent(), 'research notes about located programmes stay hidden in production');
    }

    public function test_the_faq_hub_carries_the_newly_sourced_answers_with_their_anchors(): void
    {
        $html = $this->get('/faq')->assertOk()->getContent();
        foreach ([5, 10, 13, 14] as $id) {
            $this->assertStringContainsString('id="q'.$id.'"', $html);
        }
        $this->assertStringContainsString('We do not rank or recommend A-level providers', $html);
        $this->assertSame(1, substr_count($html, '"@type":"FAQPage"'));
    }
}
