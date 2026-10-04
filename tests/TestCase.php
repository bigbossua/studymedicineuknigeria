<?php

namespace Tests;

use App\Models\Application;
use App\Models\ServiceTier;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Shortcut for tests about later stages: our team has reviewed the profile (services approved) and, when a code is
     * given, the student has chosen that service. The full approval and selection journey is tested on its own.
     */
    protected function approveServices(Application $application, ?string $code = null): Application
    {
        $application->forceFill(['services_approved_at' => now()] + ($code ? ['service_tier_id' => ServiceTier::where('code', $code)->value('id')] : []))->save();

        return $application->refresh();
    }
}
