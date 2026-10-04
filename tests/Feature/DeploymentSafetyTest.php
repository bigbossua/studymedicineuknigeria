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
        $this->assertStringContainsString('has no successful staging deploy', $workflow);
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
}
