<?php

// Mirror production's state (an Operations Summary log) in a throwaway database (ops/qa/rollback-proof/run.sh):
// the watcher flags and the set of schools published in Admin. Local simulation only.
use App\Models\Course;
use App\Models\ReferenceFact;
use App\Models\Topic;
use App\Models\University;

$S = getenv('S');
$lines = array_filter(explode("\n", file_get_contents("$S/flagged.txt")));
$matched = 0;
$missing = [];
foreach ($lines as $l) {
    [$st, $subj, $key, $url] = array_map('trim', explode(' | ', $l));
    $url = str_replace('\/', '/', $url);
    [$kname, $year] = array_pad(explode(' ', $key, 2), 2, null);
    [$type, $slug] = explode(' ', $subj, 2);
    $q = ReferenceFact::where('key', $kname)->where('source_url', $url);
    $year ? $q->where('academic_year', str_replace('\/', '/', $year)) : $q->where(fn ($w) => $w->whereNull('academic_year')->orWhere('academic_year', ''));
    if ($type === 'University') {
        $q->where('subject_type', University::class)->where('subject_id', University::where('slug', $slug)->value('id'));
    } elseif ($type === 'Topic') {
        $q->where('subject_type', Topic::class)->where('subject_id', Topic::where('slug', $slug)->value('id'));
    } else {
        $q->where('subject_type', Course::class);
    }
    $n = $q->update(['verification_status' => 'SOURCE_CHANGED']);
    $n ? $matched++ : $missing[] = $l;
}
$published = array_keys(json_decode(file_get_contents("$S/prod-core.json"), true));
University::query()->update(['published' => false]);
$p = University::whereIn('slug', $published)->update(['published' => true]);
echo "flags matched $matched of ".count($lines)."; published $p of ".count($published).PHP_EOL;
foreach ($missing as $m) {
    echo "UNMATCHED: $m".PHP_EOL;
}
// Align core-fact statuses with production's report where they still differ (a production fact the repository's
// data cannot reproduce): a core fact production does not report as VERIFIED/NOT_PUBLISHED is not verified here either.
$core = ['international_accepted', 'waec_neco_statement', 'english_requirement', 'international_fee_gbp', 'application_route'];
$prod = json_decode(file_get_contents("$S/prod-core.json"), true);
foreach ($prod as $slug => $d) {
    $u = University::firstWhere('slug', $slug);
    if (! $u) {
        echo "NO SCHOOL $slug".PHP_EOL;

        continue;
    }
    $courseIds = $u->courses()->pluck('id');
    foreach ($core as $k) {
        $pst = $d['core'][$k];
        $q = fn () => ReferenceFact::where('key', $k)->where(fn ($w) => $w->where(fn ($a) => $a->where('subject_type', University::class)->where('subject_id', $u->id))->orWhere(fn ($b) => $b->where('subject_type', Course::class)->whereIn('subject_id', $courseIds)));
        $local = $q()->pluck('verification_status')->unique()->values()->all();
        $lv = in_array('VERIFIED', $local, true) ? 'VERIFIED' : implode('/', $local);
        if (! in_array($pst, ['VERIFIED', 'NOT_PUBLISHED'], true) && in_array('VERIFIED', $local, true)) {
            $n = $q()->where('verification_status', 'VERIFIED')->update(['verification_status' => explode('/', $pst)[0] === 'none' ? 'VERIFY-ON-PAGE' : explode('/', $pst)[0]]);
            echo "aligned $slug $k: local VERIFIED ($n row) -> production $pst".PHP_EOL;
        } elseif ($pst === 'VERIFIED' && $lv !== 'VERIFIED') {
            echo "CANNOT ALIGN $slug $k: production VERIFIED, local $lv".PHP_EOL;
        }
    }
}
