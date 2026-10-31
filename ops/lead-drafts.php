<?php

use App\Models\Lead;
use App\Support\LeadReplyDraft;
use Illuminate\Contracts\Console\Kernel;

// Writes one reply draft per eligibility-checker lead still marked "new" into a private folder, for the owner to review,
// edit and send from the business mailbox (Workstream 3, 2026-10-31). Nothing is sent and no lead record is changed.
// Run from the app root on the server by .github/workflows/lead-drafts.yml, which encrypts the folder with the backup
// passphrase before anything leaves the server; only the count is printed.
//   php ops/lead-drafts.php <path to LeadReplyDraft.php> <output folder>

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! class_exists(LeadReplyDraft::class)) {
    require $argv[1]; // the release on the server predates the class: use the copy sent with the workflow
}

$n = 0;
foreach (Lead::where('status', 'new')->orderBy('created_at')->get() as $lead) {
    $days = $lead->created_at->diffInWeekdays(now());
    $draft = LeadReplyDraft::for($lead, $days > 2);
    $n++;
    file_put_contents($argv[2].'/lead-'.$n.'.txt', implode("\n", [
        'DRAFT - NOT SENT. Review and edit, send it yourself from the business mailbox, then set the lead to "contacted" in Admin -> Leads.',
        'To: '.$lead->email,
        'Subject: '.$draft['subject'],
        'Enquiry received: '.$lead->created_at->format('j F Y').' ('.$days.' working days ago)',
        '',
        $draft['body'],
        '',
    ]));
}
echo 'drafts='.$n.PHP_EOL;
