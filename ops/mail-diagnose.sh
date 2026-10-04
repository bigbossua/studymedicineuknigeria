#!/usr/bin/env bash
# Read-only diagnosis of the production email path, run on the server by .github/workflows/mail-diagnose.yml.
# Prints settings (never the password), queue and failed-job state (class names only, never payloads), the cron entry
# and the queue worker's overlap lock, mail errors from the application log, and an SMTP sign-in test that sends nothing.
# Every email address in the output is masked: the repository and its Actions logs are public.
set -uo pipefail
APP=~/apps/smukn-${TARGET:-production}/current
cd "$APP" || { echo "no $APP"; exit 2; }
PHP=""; for c in php83 php8.3 /opt/alt/php83/usr/bin/php php; do p=$(command -v "$c" 2>/dev/null) && "$p" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' && { PHP=$p; break; }; done
mask(){ sed -E 's/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/[email]/g'; }

echo "== cron (the queue worker runs from schedule:run every minute) =="
if command -v crontab >/dev/null; then
  echo "crontab command: available; entries in this account's crontab: $(crontab -l 2>/dev/null | grep -cvE '^\s*(#|$)')"
  crontab -l 2>/dev/null | grep -F 'schedule:run' | sed -E 's#/home/[^/]+#~#g' || echo "NO schedule:run line in crontab"
else
  echo "crontab command: NOT available over SSH (cron jobs are managed in hPanel → Advanced → Cron Jobs)"
fi
for d in /var/spool/cron /var/spool/cron/crontabs; do [ -r "$d/$(whoami)" ] && echo "spool file readable: $d"; done

echo "== settings, queue, failed jobs, worker lock, admin account =="
$PHP artisan tinker --execute '
  $m = config("mail.mailers.".config("mail.default"), []);
  $from = (string) config("mail.from.address");
  $q = config("queue.default");
  $jobs = DB::table("jobs")->get(["queue", "attempts", "available_at", "created_at", "payload"]);
  $failed = DB::table("failed_jobs")->orderByDesc("failed_at")->limit(5)->get(["failed_at", "payload", "exception"]);
  $name = fn ($p) => json_decode($p, true)["displayName"] ?? "?";
  $worker = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => str_contains($e->command ?? "", "queue:work"));
  $admin = App\Models\User::where("email", "info@studymedicineuknigeria.com")->first();
  echo json_encode([
    "mailer" => config("mail.default"), "host" => $m["host"] ?? null, "port" => $m["port"] ?? null,
    "scheme" => $m["scheme"] ?? ($m["encryption"] ?? null), "password_set" => filled($m["password"] ?? null),
    "username_is_sender" => ($m["username"] ?? null) === $from, "from_is_business_address" => $from === "info@studymedicineuknigeria.com",
    "from_name" => config("mail.from.name"), "queue_connection" => $q,
    "jobs_pending" => $jobs->count(),
    "jobs_pending_by_class" => $jobs->groupBy(fn ($j) => $name($j->payload))->map->count(),
    "oldest_pending_minutes" => $jobs->min("created_at") ? round((time() - $jobs->min("created_at")) / 60) : null,
    "max_attempts_pending" => $jobs->max("attempts"),
    "failed_jobs_total" => DB::table("failed_jobs")->count(),
    "failed_recent" => $failed->map(fn ($f) => ["at" => $f->failed_at, "class" => $name($f->payload), "error" => mb_substr(strtok($f->exception, "\n"), 0, 240)]),
    "worker_scheduled" => (bool) $worker,
    "worker_overlap_lock_held" => $worker ? $worker->mutex->exists($worker) : null,
    "admin_account_exists" => (bool) $admin, "admin_role" => $admin?->role,
    "admin_email_verified" => $admin ? $admin->email_verified_at !== null : null,
    "admin_two_step" => $admin ? $admin->hasTwoFactorEnabled() : null,
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;' 2>&1 | mask

echo "== scheduler: last worker runs (from the schedule log, if any) =="
$PHP artisan schedule:list 2>&1 | grep -i 'queue:work' | mask

echo "== application log: mail and queue errors in the last 3 days =="
find storage/logs -name '*.log' -mtime -3 -print0 2>/dev/null | xargs -0 -r grep -hE '^\[[0-9]{4}-' 2>/dev/null | grep -iE 'mail|smtp|transport|mailer|queue|VerifyEmail' \
  | grep -iE 'error|exception|fail|refused|denied|timed out|authenticat' | tail -8 | cut -c1-300 | mask || true
echo "(matching lines: $(find storage/logs -name '*.log' -mtime -3 -print0 2>/dev/null | xargs -0 -r grep -hiE 'mail|smtp|transport' 2>/dev/null | grep -ciE 'error|exception|fail' || echo 0))"

echo "== SMTP sign-in test (connects and authenticates, sends nothing) =="
$PHP artisan tinker --execute '
  try {
    $t = app("mail.manager")->mailer()->getSymfonyTransport();
    if (method_exists($t, "start")) { $t->start(); echo "SMTP connection and sign-in: OK (", get_class($t), ")", PHP_EOL; $t->stop(); }
    else { echo "transport ", get_class($t), " has no connection to test", PHP_EOL; }
  } catch (Throwable $e) { echo "SMTP FAILED: ", get_class($e), ": ", mb_substr($e->getMessage(), 0, 300), PHP_EOL; }' 2>&1 | mask
