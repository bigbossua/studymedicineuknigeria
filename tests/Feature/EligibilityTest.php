<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligibility_check_creates_a_lead_and_gives_a_cautious_route_map(): void
    {
        Notification::fake();
        $this->get('/apply-online/eligibility')->assertOk()->assertSee('Show my route map');
        $r = $this->post('/apply-online/eligibility', ['qualification' => 'waec_only', 'sciences' => 'yes', 'english' => 'none', 'ucat' => 'none', 'intake_year' => 2027, 'name' => 'Ada', 'email' => 'ada@example.test', 'consent' => 1]);
        $r->assertRedirect('/apply-online/eligibility');
        $lead = Lead::first();
        $this->assertNotNull($lead);
        $this->assertSame('new', $lead->status);
        $states = collect($lead->eligibility_result['routes'])->pluck(1);
        $this->assertTrue($states->contains('closed'));   // direct A100 on WASSCE and 2027 via UCAT schools
        $this->assertFalse($states->contains('open'));    // never "open" for WAEC-only
        $this->followRedirects($r)->assertSee('Your route map')->assertSee('Create your account');
    }

    public function test_release_one_pages_are_indexable_and_in_the_sitemap(): void
    {
        foreach (['/study-medicine-in-the-uk/from-nigeria', '/requirements/waec', '/fees', '/admissions/ucat', '/faq', '/our-status'] as $p) {
            $this->get($p)->assertOk()->assertSee('index, follow', false)->assertSee('Last reviewed');
        }
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('/requirements/waec</loc>', $xml);
        $this->assertStringNotContainsString('/fees/cost-of-studying-medicine-in-the-uk</loc>', $xml); // noindex until gaps verified
    }
}
