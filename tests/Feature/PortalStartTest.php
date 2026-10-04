<?php

namespace Tests\Feature;

use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\TopicFactsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The portal never states a dated fact or a requirement that is not sourced and publishable. */
class PortalStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_entry_year_hint_states_the_ucas_deadline_only_when_the_fact_is_publishable(): void
    {
        $this->seed([PlatformSeeder::class, TopicFactsSeeder::class]);
        $student = User::factory()->create();
        $deadline = Topic::bySlug('ucas-2027')->fact('deadline_medicine');

        $this->app['env'] = 'production';
        config(['site.publish_unverified' => false]);
        $html = $this->actingAs($student)->get('/portal')->assertOk()->getContent();
        $this->assertStringNotContainsString($deadline->value_text, $html, 'an unverified deadline must not appear in production');
        $this->assertStringContainsString('Medicine applications close in October of the year before entry', $html);
        $this->assertStringNotContainsString('15 October 2026', $html);

        $deadline->update(['verification_status' => ReferenceFact::VERIFIED, 'verified_at' => now()]);
        $this->assertStringContainsString($deadline->value_text.' (official source: UCAS)', $this->actingAs($student)->get('/portal')->getContent());
    }

    public function test_the_default_entry_year_follows_the_calendar_not_a_fixed_year(): void
    {
        $this->seed(PlatformSeeder::class);
        $student = User::factory()->create();
        $this->travelTo(now()->setDate(2027, 2, 1));
        $this->assertMatchesRegularExpression('/<option value="2028" selected/', $this->actingAs($student)->get('/portal')->getContent());
        $this->travelTo(now()->setDate(2027, 10, 1));
        $this->assertMatchesRegularExpression('/<option value="2029" selected/', $this->actingAs($student)->get('/portal')->getContent());
    }

    public function test_the_date_of_birth_hint_makes_no_unsourced_age_claim(): void
    {
        $html = file_get_contents(resource_path('views/portal/application/steps/personal.blade.php'));
        $this->assertStringNotContainsString('require you to be 18', $html);
        foreach (['study', 'experience', 'tests'] as $step) {
            $this->assertDoesNotMatchRegularExpression('/\\b20(2[6-9]|30)\\b/', preg_replace("/bySlug\\('[a-z0-9-]+'\\)/", '', file_get_contents(resource_path("views/portal/application/steps/{$step}.blade.php"))), "{$step} step hard-codes a year (topic slugs aside)");
        }
    }
}
