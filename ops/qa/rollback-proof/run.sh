#!/usr/bin/env bash
# Rollback proof on a production-mirror database (local throwaway SQLite; production is never touched).
# Usage: ops/qa/rollback-proof/run.sh <old-release-worktree> <inputs-dir> <work-dir>
#   old-release-worktree: `git worktree add <dir> <live commit>` with vendor/ and public/build/ copied in and
#                         `composer dump-autoload` run there
#   inputs-dir:           flagged.txt, prod-core.json and live-sitemap.txt taken from an Operations Summary log and a
#                         read of the live sitemap (counts and public URLs only)
# NEW is this checkout. Steps: mirror production with the live release's code, compare with the live sitemap, deploy
# (reference-sync + ops/import-decisions.sh), roll back the code with and without ops/revert-release-facts.sh, redeploy.
set -euo pipefail
NEW=$(cd "$(dirname "$0")/../../.." && pwd); H=$(cd "$(dirname "$0")" && pwd)
OLD=${1:?old release worktree}; IN=$(cd "${2:?inputs dir}" && pwd); D=${3:?work dir}; rm -rf $D; mkdir -p $D
SCHOOLS="cambridge kings-college-london queens-belfast aberdeen buckingham brighton-sussex cardiff edinburgh manchester aston imperial"
envp(){ env APP_ENV=production APP_DEBUG=false APP_URL=https://studymedicineuknigeria.com DB_CONNECTION=sqlite DB_DATABASE=$1 CACHE_STORE=database SESSION_DRIVER=database QUEUE_CONNECTION=sync MAIL_MAILER=array SITE_PUBLISH_UNVERIFIED=false STRIPE_SECRET= STRIPE_KEY= "${@:2}"; }
facts(){ php $H/q.php $1 "select id,verification_status,value_text,value_number,value_bool,academic_year,source_url,verified_at,review_due_at,notes from reference_facts order by id"; }
cnt(){ grep -c . $1; }

echo "== 1. mirror of production (inputs: $IN) =="
touch $D/base.sqlite
( cd $OLD && envp $D/base.sqlite php artisan migrate --force -q && envp $D/base.sqlite php artisan smukn:reference-sync -q
  for f in data/verification/decisions-*.csv; do envp $D/base.sqlite php artisan smukn:facts-import "$f" -q; done
  envp $D/base.sqlite php artisan smukn:reference-sync -q   # every later deploy syncs again after the earlier imports
  envp $D/base.sqlite S=$IN php artisan tinker --execute="require '$H/state.php';" )
echo "   (built with the live release's own code and data, as production was)"
cp $D/base.sqlite $D/a.sqlite

echo "== 2. live release (OLD code) on the mirror: must equal today's live sitemap =="
$H/serve.sh $OLD $D/a.sqlite $D/s0.txt >/dev/null
grep -v '/sitemap.xml$' $IN/live-sitemap.txt | sort > $D/live.txt
echo "   old code: $(cnt $D/s0.txt) URLs; live: $(cnt $D/live.txt); differences: $(diff $D/s0.txt $D/live.txt | grep -c '^[<>]' || true)"

echo "== 3. deploy steps with NEW code: reference-sync, then ops/import-decisions.sh (as ops/deploy.sh) =="
facts $D/a.sqlite > $D/facts-before.csv
php $H/q.php $D/a.sqlite "select sha256 from fact_imports order by sha256" > $D/ledger-before.txt
( cd $NEW && envp $D/a.sqlite php artisan smukn:reference-sync -q && envp $D/a.sqlite bash ops/import-decisions.sh php | tail -5 )
echo "   recorded for rollback: $(cat $NEW/.facts-imported | tr '\n' ' ')"
echo "== 4. NEW code after deploy =="
$H/serve.sh $NEW $D/a.sqlite $D/s1.txt $SCHOOLS | sed 's/^/   /'
echo "   new code: $(cnt $D/s1.txt) URLs; added vs live: $(comm -13 $D/live.txt $D/s1.txt | sed 's#https://studymedicineuknigeria.com##' | tr '\n' ' '); removed: $(comm -23 $D/live.txt $D/s1.txt | wc -l)"

echo "== 5. FAILURE MODE (code rollback only, facts left): OLD code on the deployed database =="
$H/serve.sh $OLD $D/a.sqlite $D/s_bad.txt >/dev/null
echo "   old code without fact revert: $(cnt $D/s_bad.txt) URLs; extra vs live: $(comm -13 $D/live.txt $D/s_bad.txt | sed 's#https://studymedicineuknigeria.com/medical-schools/##; s#https://studymedicineuknigeria.com##' | tr '\n' ' ')"

echo "== 6. ROLLBACK: ops/revert-release-facts.sh with the NEW release's code, then OLD code =="
( envp $D/a.sqlite bash $NEW/ops/revert-release-facts.sh $NEW php | sed 's/^/   /' )
$H/serve.sh $OLD $D/a.sqlite $D/s2.txt $SCHOOLS | sed 's/^/   /'
facts $D/a.sqlite > $D/facts-after.csv
php $H/q.php $D/a.sqlite "select sha256 from fact_imports order by sha256" > $D/ledger-after.txt
echo "   old code after rollback: $(cnt $D/s2.txt) URLs; differences vs pre-deploy: $(diff $D/s0.txt $D/s2.txt | grep -c '^[<>]' || true)"
echo "   reference_facts rows differing from pre-deploy (status, values, source, dates, notes): $(diff $D/facts-before.csv $D/facts-after.csv | grep -c '^>' || true) of $(cnt $D/facts-before.csv)"
echo "   fact_imports ledger identical to pre-deploy: $(cmp -s $D/ledger-before.txt $D/ledger-after.txt && echo yes || echo no)"
echo "   revert trail rows in fact_changes: $(php $H/q.php $D/a.sqlite "select count(*) from fact_changes where via like 'worksheet revert %'")"

echo "== 7. REDEPLOY after the rollback: the same files apply again =="
( cd $NEW && envp $D/a.sqlite bash ops/import-decisions.sh php | tail -1 )
$H/serve.sh $NEW $D/a.sqlite $D/s3.txt >/dev/null
echo "   new code after redeploy: $(cnt $D/s3.txt) URLs; identical to step 4: $(cmp -s $D/s1.txt $D/s3.txt && echo yes || echo no)"
rm -f $NEW/.facts-imported $NEW/.facts-imported.reverted-*
