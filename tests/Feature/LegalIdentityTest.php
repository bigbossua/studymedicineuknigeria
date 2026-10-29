<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactor;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * Regression for 2026-10-29: production had SITE_LEGAL_NAME set to an empty string, and the templates' `??` fallback
 * only catches null, so the privacy notice read '("we") is the data controller' with no name and the footer, the
 * Our status page and every email footer printed a blank. An empty or whitespace-only identity setting is now unset.
 */
class LegalIdentityTest extends TestCase
{
    use RefreshDatabase;

    private const KEYS = ['SITE_LEGAL_NAME', 'SITE_COMPANY_NUMBER', 'SITE_ADDRESS', 'SITE_ICO_NUMBER', 'SITE_VAT_NUMBER', 'SITE_LEGAL_REVIEWED'];

    /** Reads config/site.php with the given environment, as the production config cache would. */
    private function siteConfig(array $env): array
    {
        $saved = [];
        foreach (self::KEYS as $k) {
            $saved[$k] = [$_ENV[$k] ?? null, $_SERVER[$k] ?? null];
            if (array_key_exists($k, $env)) {
                $_ENV[$k] = $_SERVER[$k] = $env[$k];
            } else {
                unset($_ENV[$k], $_SERVER[$k]);
            }
        }
        try {
            return require config_path('site.php');
        } finally {
            foreach ($saved as $k => [$e, $s]) {
                $e === null ? Arr::forget($_ENV, $k) : $_ENV[$k] = $e;
                $s === null ? Arr::forget($_SERVER, $k) : $_SERVER[$k] = $s;
            }
        }
    }

    public function test_empty_or_blank_identity_settings_count_as_unset(): void
    {
        $c = $this->siteConfig(['SITE_LEGAL_NAME' => '', 'SITE_ADDRESS' => '   ', 'SITE_ICO_NUMBER' => '', 'SITE_LEGAL_REVIEWED' => ' ']);
        $this->assertNull($c['legal_name']);
        $this->assertNull($c['address']);
        $this->assertNull($c['ico_number']);
        $this->assertNull($c['legal_reviewed'], 'a blank value must not tick "Legal pages reviewed"');
        $this->assertNull($c['company_number'], 'an absent value stays null');
    }

    public function test_a_real_identity_value_is_kept_and_trimmed(): void
    {
        $c = $this->siteConfig(['SITE_LEGAL_NAME' => '  Example Trading Ltd ', 'SITE_ADDRESS' => '1 Example Street, London']);
        $this->assertSame('Example Trading Ltd', $c['legal_name']);
        $this->assertSame('1 Example Street, London', $c['address']);
    }

    public function test_without_a_legal_name_every_page_shows_the_fallback_not_a_blank(): void
    {
        config(['site.legal_name' => null, 'site.address' => null, 'site.ico_number' => null]);

        $this->get(route('legal.privacy'))->assertOk()->assertSee('Study Medicine UK Nigeria ("we") is the data controller', false);
        $this->get(route('status'))->assertOk()->assertSee('To be published on incorporation')->assertSee('To be published once registered');
        $this->get(route('about'))->assertOk()->assertSee('operated by an independent team');
        $this->get(route('contact'))->assertOk()->assertSee('Registered address to be published');
        $this->get(route('legal.terms'))->assertOk()->assertSee('operated by Study Medicine UK Nigeria');
        $this->get(route('home'))->assertOk()->assertSee('&copy; '.date('Y').' Study Medicine UK Nigeria', false);
    }

    public function test_an_empty_string_in_config_still_falls_back(): void
    {
        // Belt and braces: the templates use ?: so even an empty string set at runtime cannot print a blank.
        config(['site.legal_name' => '', 'site.address' => '']);

        $this->get(route('legal.privacy'))->assertSee('Study Medicine UK Nigeria ("we") is the data controller', false)->assertDontSee('<p> ("we")', false);
        $this->get(route('status'))->assertSee('To be published on incorporation');
        $this->get(route('contact'))->assertSee('Registered address to be published');
    }

    public function test_a_published_identity_appears_on_the_legal_pages(): void
    {
        config(['site.legal_name' => 'Example Trading Ltd', 'site.company_number' => '01234567', 'site.address' => '1 Example Street, London', 'site.ico_number' => 'ZA000000']);

        $this->get(route('legal.privacy'))->assertSee('Example Trading Ltd ("we") is the data controller', false);
        $this->get(route('status'))->assertSee('Example Trading Ltd · 01234567', false)->assertSee('1 Example Street, London')->assertSee('Registration ZA000000');
        $this->get(route('legal.terms'))->assertSee('Example Trading Ltd, company number 01234567, 1 Example Street, London', false);
        $this->get(route('contact'))->assertSee('1 Example Street, London');
    }

    public function test_the_launch_checklist_flags_a_missing_trading_identity(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        config(['site.legal_name' => null, 'site.address' => null, 'site.ico_number' => null]);

        $this->actingAs($admin)->withSession([EnsureTwoFactor::SESSION_KEY => $admin->id])->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Trading identity published')->assertSee('not set: SITE_LEGAL_NAME, SITE_ADDRESS, SITE_ICO_NUMBER');
    }
}
