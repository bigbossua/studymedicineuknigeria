<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StagingGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_staging_requires_basic_auth_except_health_and_webhooks(): void
    {
        $this->app['env'] = 'staging';
        config(['site.staging_basic_user' => 'preview', 'site.staging_basic_password' => 'secret-pass']);

        $this->get('/')->assertStatus(401)->assertHeader('WWW-Authenticate')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/up')->assertOk();
        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('preview:wrong')])->get('/')->assertStatus(401);
        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('preview:secret-pass')])->get('/')->assertOk();
    }

    public function test_gate_is_inert_without_credentials_or_outside_staging(): void
    {
        $this->app['env'] = 'staging';
        config(['site.staging_basic_user' => 'preview', 'site.staging_basic_password' => null]);
        $this->get('/')->assertOk();

        $this->app['env'] = 'production';
        config(['site.staging_basic_user' => 'preview', 'site.staging_basic_password' => 'secret-pass']);
        $this->get('/')->assertOk();
    }
}
