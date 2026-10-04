<?php

namespace App\Console\Commands;

use Database\Seeders\HealthcareCoursesSeeder;
use Database\Seeders\LegacyRedirectsSeeder;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\ProfessionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\TopicFactsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Brings the repository's reference data into this environment: medical schools and their facts, service tiers and
 * checklist rules, topic facts (UCAS, UCAT, visas, GMC), the healthcare taxonomy and allied course facts. Run by
 * ops/deploy.sh after migrations on every deploy, so a first deploy is complete and later dataset changes arrive.
 * Safe to repeat: facts follow App\Support\FactSeeding (reviewed facts are never touched), tier prices set by the owner
 * are kept (firstOrCreate), and demo accounts are never part of it.
 */
class SyncReferenceData extends Command
{
    protected $signature = 'smukn:reference-sync';

    protected $description = 'Create or refresh reference data from the repository without touching reviewed facts or owner prices';

    public const SEEDERS = [ReferenceDataSeeder::class, PlatformSeeder::class, TopicFactsSeeder::class, ProfessionSeeder::class, HealthcareCoursesSeeder::class, LegacyRedirectsSeeder::class];

    public function handle(): int
    {
        foreach (self::SEEDERS as $seeder) {
            Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
            $this->line('synced '.class_basename($seeder));
        }
        Log::warning('reference.sync', ['seeders' => array_map('class_basename', self::SEEDERS)]);

        return self::SUCCESS;
    }
}
