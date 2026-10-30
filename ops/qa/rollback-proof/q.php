<?php

// php q.php <sqlite-file> "<select …>"  → one CSV line per row
$pdo = new PDO('sqlite:'.$argv[1]);
$out = fopen('php://stdout', 'w');
foreach ($pdo->query($argv[2], PDO::FETCH_NUM) as $row) {
    fputcsv($out, $row, ',', '"', '');
}
