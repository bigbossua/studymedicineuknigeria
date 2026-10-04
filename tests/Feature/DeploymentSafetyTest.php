<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ReferenceFact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Guards found in the 2026-10-04 MariaDB deployment rehearsal (ops/reports/deployment-rehearsal-2026-10-04.md):
 * the server path must stay safe without anyone re-reading the workflows.
 */
class DeploymentSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function file(string $path): string
    {
        return (string) file_get_contents(base_path($path));
    }

    public function test_env_reader_returns_special_character_secrets_exactly_as_laravel_reads_them(): void
    {
        $dir = sys_get_temp_dir().'/smukn-env-'.bin2hex(random_bytes(4));
        mkdir($dir);
        $secret = 'Pa$$w0rd"\\x`#! ${HOME}';
        file_put_contents($dir.'/.env', "DB_PASSWORD='".$secret."'\nDB_DATABASE=smukn\n");

        $out = Process::path($dir)->run(['php', base_path('ops/env-shell.php'), 'DB_PASSWORD', 'DB_DATABASE', 'MISSING_KEY'])->throw()->output();
        $read = Process::run(['bash', '-c', 'eval "$1"; printf "%s|%s|%s" "$DB_PASSWORD" "$DB_DATABASE" "$MISSING_KEY"', '_', $out])->throw()->output();

        $this->assertSame($secret.'|smukn|', $read);
        @unlink($dir.'/.env');
        @rmdir($dir);
    }

    public function test_bootstrap_writes_literal_values_and_never_passes_secrets_on_a_command_line(): void
    {
        $script = $this->file('ops/server-bootstrap.sh');
        $this->assertStringContainsString('DB_PASSWORD=$(q "$DB_PASSWORD")', $script);
        $this->assertStringContainsString('MYSQL_PWD="$DB_PASSWORD" mysql', $script);
        $this->assertStringNotContainsString('-p"$DB_PASSWORD"', $script);
        $this->assertStringContainsString('staging needs STAGING_BASIC_USER', $script);

        $workflow = $this->file('.github/workflows/bootstrap-hostinger.yml');
        $this->assertStringContainsString("printf 'export %s=%q\\n'", $workflow);
        $this->assertStringNotContainsString("DB_PASSWORD='\$DB_PASSWORD'", $workflow);
    }

    public function test_deploy_backs_up_with_shared_hosting_privileges_and_ships_no_local_state(): void
    {
        $deploy = $this->file('ops/deploy.sh');
        $this->assertStringContainsString('--no-tablespaces', $deploy);
        $this->assertStringContainsString('Dump completed', $deploy);
        $this->assertStringContainsString('refusing to migrate', $deploy);
        $this->assertStringContainsString("--exclude 'database/*.sqlite*'", $deploy);
        $this->assertStringContainsString('--exclude public/hot', $deploy);
        $this->assertStringContainsString('releases/.history', $deploy);
        $this->assertStringContainsString('ops/smoke.sh "$SITE_URL" "$TARGET"', $deploy);

        $workflow = $this->file('.github/workflows/deploy-hostinger.yml');
        $this->assertStringContainsString('composer install --no-dev', $workflow);
        $this->assertStringContainsString('if [ "${REHEARSE:-0}" = 1 ]; then rm -f .env; ops/production-rehearsal.sh 8090; fi', $workflow, 'without a staging deploy of the commit, production is rehearsed on the runner first');
        $this->assertStringContainsString('required_reviewers', $workflow);
        $this->assertStringContainsString('STAGING_URL(variable)', $workflow);
    }

    public function test_scheduled_backups_are_not_held_by_the_production_approval_rule(): void
    {
        $this->assertDoesNotMatchRegularExpression('/^\s+environment:/m', $this->file('.github/workflows/backup-hostinger.yml'));
    }

    public function test_every_server_workflow_checks_host_keys_strictly_once_pinned(): void
    {
        foreach (glob(base_path('.github/workflows/*-hostinger.yml')) as $path) {
            $yml = (string) file_get_contents($path);
            if (! str_contains($yml, 'ssh')) {
                continue;
            }
            $this->assertStringContainsString('SSH_STRICT=yes', $yml, basename($path));
            $this->assertDoesNotMatchRegularExpression('/StrictHostKeyChecking=accept-new/', $yml, basename($path).' bypasses the pinned key');
        }
    }

    public function test_smoke_test_fails_on_exposed_files_debug_output_and_an_open_staging_site(): void
    {
        $smoke = $this->file('ops/smoke.sh');
        foreach (['/.env', '/composer.json', '/storage/logs/laravel.log', '/.git/HEAD', '/database/database.sqlite'] as $path) {
            $this->assertStringContainsString($path, $smoke);
        }
        $this->assertStringContainsString('Stack trace', $smoke);
        $this->assertStringContainsString('staging asks for credentials', $smoke);
        $this->assertStringContainsString('directory lists', $smoke);
    }

    public function test_reference_sync_fits_mysql_columns_and_keeps_unclear_years_as_recorded(): void
    {
        Artisan::call('smukn:reference-sync');

        $this->assertSame(0, Course::whereNotNull('ucas_code')->get()->filter(fn ($c) => ! preg_match('/^[A-Z]\d{3}$/', $c->ucas_code))->count(), 'course rows keep the bare UCAS code');
        $this->assertSame(0, ReferenceFact::whereNotNull('academic_year')->get()->filter(fn ($f) => mb_strlen($f->academic_year) > 16)->count());

        $unclear = ReferenceFact::where('key', 'international_fee_gbp')->where('notes', 'like', 'Year as recorded: UNCLEAR%')->first();
        $this->assertNotNull($unclear, 'an unclear fee year is kept word for word in the notes');
        $this->assertNull($unclear->academic_year);
        $this->assertNotSame(ReferenceFact::VERIFIED, $unclear->verification_status);

        $notes = ReferenceFact::where('key', 'ucas_code')->where('value_text', 'like', '%(also A104%')->first();
        $this->assertNotNull($notes, 'the full UCAS code text survives as a fact');
    }

    public function test_every_staging_response_is_noindex_even_behind_the_password(): void
    {
        $this->get('/fees')->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->app['env'] = 'staging';
        try {
            $this->get('/fees')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_server_scripts_only_run_artisan_with_php_8_3_or_newer(): void
    {
        // Hostinger keeps several PHP binaries; plain `php` can be an older CLI and /opt/alt/php83 holds 8.3 on CloudLinux.
        foreach (['ops/server-bootstrap.sh', 'ops/deploy.sh', 'ops/rollback.sh', 'ops/update-env.sh', '.github/workflows/account-role-hostinger.yml'] as $path) {
            $script = $this->file($path);
            $this->assertStringContainsString('/opt/alt/php83/usr/bin/php', $script, $path);
            $this->assertStringContainsString('PHP_VERSION_ID >= 80300', $script, $path);
            $this->assertStringNotContainsString('command -v php8.3 || command -v php)', $script, $path.' still trusts the first php it finds');
        }
    }

    /** Runs a server script in a throwaway home with the given environment; returns [exit code, output]. */
    private function runScript(string $script, array $env, ?callable $prepare = null): array
    {
        $home = sys_get_temp_dir().'/smukn-home-'.bin2hex(random_bytes(4));
        mkdir($home.'/apps/smukn-'.($env['TARGET'] ?? 'staging').'/shared', 0777, true);
        file_put_contents($home.'/apps/smukn-'.($env['TARGET'] ?? 'staging').'/shared/.env', "APP_ENV=x\n");
        if ($prepare) {
            $prepare($home);
        }
        $r = Process::env(['HOME' => $home, 'PATH' => getenv('PATH')] + $env)->input((string) file_get_contents(base_path($script)))->run(['bash', '-s']);
        Process::run(['rm', '-rf', $home]);

        return [$r->exitCode(), $r->output().$r->errorOutput()];
    }

    public function test_production_takes_only_live_stripe_keys_and_staging_only_test_keys(): void
    {
        [$code, $out] = $this->runScript('ops/update-env.sh', ['TARGET' => 'production', 'STRIPE_SECRET' => 'sk_test_abc']);
        $this->assertSame(4, $code);
        $this->assertStringContainsString('production takes live keys only', $out);

        [$code, $out] = $this->runScript('ops/update-env.sh', ['TARGET' => 'staging', 'STRIPE_SECRET' => 'sk_live_abc']);
        $this->assertSame(4, $code);
        $this->assertStringContainsString('takes test keys only', $out);

        [$code, $out] = $this->runScript('ops/update-env.sh', ['TARGET' => 'production', 'STRIPE_KEY' => 'pk_live_a', 'STRIPE_SECRET' => 'sk_live_b', 'STRIPE_WEBHOOK_SECRET' => 'whsec_c']);
        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('set STRIPE_SECRET', $out);
        $this->assertStringNotContainsString('sk_live_b', $out, 'a secret value is never printed');
    }

    public function test_production_bootstrap_needs_the_public_url_and_real_email_and_never_switches_the_document_root(): void
    {
        $base = ['TARGET' => 'production', 'APP_URL' => 'https://studymedicineuknigeria.com', 'DB_DATABASE' => 'd', 'DB_USERNAME' => 'u', 'DB_PASSWORD' => 'p', 'MAIL_PASSWORD' => 'm'];
        foreach ([
            'needs SMUKN_MAIL_PASSWORD' => ['MAIL_PASSWORD' => ''],
            'APP_URL must be https://studymedicineuknigeria.com' => ['APP_URL' => 'http://studymedicineuknigeria.com'],
            'switched by Deploy to Hostinger' => ['LINK_DOCROOT' => '1', 'DOCROOT' => '~/public_html'],
            'production takes live keys only' => ['STRIPE_SECRET' => 'sk_test_x'],
        ] as $message => $change) {
            [$code, $out] = $this->runScript('ops/server-bootstrap.sh', $change + $base);
            $this->assertSame(4, $code, $message);
            $this->assertStringContainsString($message, $out);
        }
    }

    public function test_deploy_preflights_production_settings_and_cuts_over_with_an_automatic_way_back(): void
    {
        $deploy = $this->file('ops/deploy.sh');
        foreach (['APP_ENV must be $TARGET', 'APP_DEBUG must be false', 'MAIL_MAILER must send real email', 'STRIPE_SECRET is not a live key', 'SITE_PUBLISH_UNVERIFIED must not be true', 'APP_URL must be https://studymedicineuknigeria.com'] as $check) {
            $this->assertStringContainsString($check, $deploy);
        }
        // the preflight runs before the backup, migrations and the switch
        $this->assertLessThan(strpos($deploy, 'artisan migrate --force'), strpos($deploy, 'preflight failed'));
        // the existing site is archived and moved aside, never deleted, and restored when the smoke test fails
        $this->assertStringContainsString('could not archive $D: refusing to switch', $deploy);
        $this->assertStringContainsString('mv "$D" "$D.pre-smukn-$TS"', $deploy);
        $this->assertStringContainsString('previous site restored at $D', $deploy);
        $this->assertDoesNotMatchRegularExpression('/rm -rf "?\$D/', $deploy);
        $this->assertStringContainsString('RESTORE_PREVIOUS_SITE', $this->file('ops/rollback.sh'));
        $this->assertStringContainsString('CUTOVER_DOCROOT: ${{ github.event.inputs.cutover_docroot }}', $this->file('.github/workflows/deploy-hostinger.yml'));
        $this->assertStringContainsString("target: \${{ github.event.inputs.target || 'staging' }}", $this->file('.github/workflows/deploy-hostinger.yml'), 'production deploys are reviewed too');
    }

    public function test_the_existing_site_is_backed_up_with_its_wordpress_database_before_launch(): void
    {
        $this->assertStringContainsString('SCOPE=site', $this->file('ops/backup.sh'));
        $this->assertStringContainsString('parsed as text (never executed)', $this->file('ops/backup.sh'));
        $this->assertStringContainsString('Pre-launch site backup restored', $this->file('.github/workflows/backup-hostinger.yml'));

        // a static site without wp-config.php: files only, encrypted, no database step
        $out = '';
        [$code, $out] = $this->runScript('ops/backup.sh', ['TARGET' => 'production', 'SCOPE' => 'site', 'SITE_DOCROOT' => '~/domains/x/public_html', 'BACKUP_PASSPHRASE' => 'test-pass'], function ($home) {
            mkdir($home.'/domains/x/public_html', 0777, true);
            file_put_contents($home.'/domains/x/public_html/index.html', 'old site');
        });
        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('database: no', $out);
        $this->assertMatchesRegularExpression('#backup=.*/backups/offsite/smukn-production-site-\d{8}T\d{6}Z\.tar\.enc#', $out);
    }

    public function test_production_smoke_and_review_fail_on_accidental_noindex_or_a_staging_password(): void
    {
        $smoke = $this->file('ops/smoke.sh');
        $this->assertStringContainsString('HSTS header missing on production', $smoke);
        $this->assertStringContainsString('chk 301 /index.php/fees', $smoke);
        $this->assertStringContainsString('staging gate left on?', $smoke);
        $this->assertStringContainsString('production /fees sends X-Robots-Tag noindex', $smoke);
        $review = $this->file('ops/qa/staging-review.cjs');
        $this->assertStringContainsString('sitemap page is noindex on production', $review);
        $this->assertStringContainsString("process.env.CANONICAL_ORIGIN || 'https://studymedicineuknigeria.com'", $review);
    }

    public function test_production_starts_with_registration_closed_and_only_true_or_false_can_switch_it(): void
    {
        $this->assertStringContainsString('SITE_REGISTRATION_OPEN=$([ "$TARGET" = production ] && echo false || echo true)', $this->file('ops/server-bootstrap.sh'));
        [$code, $out] = $this->runScript('ops/update-env.sh', ['TARGET' => 'production', 'SITE_REGISTRATION_OPEN' => 'yes please']);
        $this->assertSame(4, $code);
        $this->assertStringContainsString('must be true or false', $out);
        [$code, $out] = $this->runScript('ops/update-env.sh', ['TARGET' => 'production', 'SITE_REGISTRATION_OPEN' => 'true']);
        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('set SITE_REGISTRATION_OPEN', $out);
        $workflow = $this->file('.github/workflows/mail-hostinger.yml');
        $this->assertStringContainsString('email DNS is not ready', $workflow);
        $this->assertLessThan(strpos($workflow, 'Open or close student registration'), strpos($workflow, 'smukn:mail-test'), 'the test message goes first; a failure stops the run');
    }

    public function test_production_cutover_needs_https_staging_and_a_restore_tested_backup_of_the_current_site(): void
    {
        $deploy = $this->file('.github/workflows/deploy-hostinger.yml');
        $this->assertStringContainsString('STAGING_URL-must-start-with-https://', $deploy);
        $this->assertStringContainsString('smukn-production-site-', $deploy);
        $this->assertStringContainsString("github.event.inputs.cutover_docroot != ''", $deploy);
        $this->assertStringContainsString('name: smukn-${{ env.TARGET }}-${{ env.SCOPE }}-${{ github.run_id }}', $this->file('.github/workflows/backup-hostinger.yml'));
        $this->assertStringContainsString('application database dump is incomplete', $this->file('ops/backup.sh'));
    }

    public function test_settings_added_after_bootstrap_travel_on_stdin_and_staging_takes_only_test_stripe_keys(): void
    {
        $script = $this->file('ops/update-env.sh');
        $this->assertStringContainsString('takes test keys only', $script);
        $this->assertStringContainsString('ENVIRON["L"]', $script, 'values reach awk unaltered');
        $workflow = $this->file('.github/workflows/update-env-hostinger.yml');
        $this->assertStringContainsString("printf 'export %s=%q\\n'", $workflow);
        $this->assertStringContainsString("'bash -s'", $workflow);
    }

    public function test_the_no_staging_launch_rehearses_backs_up_and_waits_for_approval_before_touching_the_site(): void
    {
        $launch = $this->file('.github/workflows/launch-production.yml');
        $this->assertStringContainsString('[ "$CONFIRM" = LAUNCH ]', $launch, 'nothing runs without the typed confirmation');
        $this->assertStringContainsString('production-required-reviewer', $launch);
        $this->assertStringContainsString("needs: rehearse\n", $launch);
        $this->assertStringContainsString("needs: backup\n", $launch);
        $this->assertStringContainsString('environment: production', $launch);
        $this->assertLessThan(strpos($launch, 'environment: production'), strpos($launch, 'ops/production-rehearsal.sh'));
        $this->assertLessThan(strpos($launch, 'environment: production'), strpos($launch, 'restore_test'), 'the current site is restore-tested before the approval');
        $this->assertStringContainsString('CUTOVER_DOCROOT: ${{ needs.backup.outputs.docroot }}', $launch);
        $this->assertStringContainsString('the new site needs PHP 8.3', $launch);
        $this->assertStringNotContainsString('STRIPE', $launch, 'payment stays closed: no Stripe key reaches the server at launch');
        $this->assertStringContainsString('[ "$sent" = yes ]', $launch, 'registration opens only after a real test email');

        $rehearsal = $this->file('ops/production-rehearsal.sh');
        foreach (['APP_ENV=production', 'APP_DEBUG=false', 'SITE_REGISTRATION_OPEN=false', 'SITE_BANK_TRANSFER=false', 'legacy-redirects.csv', 'evil.example', 'paymentsOpen', '£695'] as $needle) {
            $this->assertStringContainsString($needle, $rehearsal);
        }
        $this->assertStringContainsString('if [ "$D" = auto ]', $this->file('ops/deploy.sh'));
        $this->assertStringContainsString('if [ "$D" = auto ]', $this->file('ops/backup.sh'));
    }
}
