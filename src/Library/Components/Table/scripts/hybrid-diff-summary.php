<?php

// packages/.../Table/scripts/hybrid-diff-summary.php
// Summarize latest inspector JSON files and print a markdown summary for GitHub Actions Job Summary.

$root = dirname(__DIR__, 8); // up to project root from scripts dir
$inspectorDir = $root.'/storage/app/datatable-inspector';
if (! is_dir($inspectorDir)) {
    fwrite(STDERR, "Inspector dir not found: $inspectorDir\n");
    exit(0); // non-fatal
}

$files = glob($inspectorDir.'/*.json');
if (! $files) {
    echo "No inspector JSON found in $inspectorDir\n";
    exit(0);
}

// Sort by modified time desc
usort($files, function ($a, $b) {
    return filemtime($b) <=> filemtime($a);
});

$top = array_slice($files, 0, 20);
$rows = [];
foreach ($top as $f) {
    $content = @file_get_contents($f);
    if ($content === false) {
        continue;
    }
    $json = json_decode($content, true);
    if (! is_array($json)) {
        continue;
    }

    $route = $json['route'] ?? ($json['meta']['route'] ?? 'n/a');
    $summary = $json['diff']['summary'] ?? [];
    $note = $json['diff']['note'] ?? ($json['note'] ?? '');
    $mismatch = $summary['mismatch'] ?? ($summary['score'] ?? null);
    $recordsTotal = $summary['recordsTotal'] ?? null;
    $recordsFiltered = $summary['recordsFiltered'] ?? null;
    $dataLen = $summary['data_length'] ?? null;

    // Normalize scalars for printing
    $routeStr = is_array($route) ? implode(' | ', array_map('strval', $route)) : (string) $route;
    $mismatchStr = is_array($mismatch) ? json_encode($mismatch) : ($mismatch === null ? 'n/a' : (string) $mismatch);
    $recordsTotalStr = is_array($recordsTotal) ? json_encode($recordsTotal) : ($recordsTotal === null ? 'n/a' : (string) $recordsTotal);
    $recordsFilteredStr = is_array($recordsFiltered) ? json_encode($recordsFiltered) : ($recordsFiltered === null ? 'n/a' : (string) $recordsFiltered);
    $dataLenStr = is_array($dataLen) ? json_encode($dataLen) : ($dataLen === null ? 'n/a' : (string) $dataLen);
    $noteStr = is_array($note) ? json_encode($note) : (string) $note;

    $rows[] = [
        'file' => basename($f),
        'route' => $routeStr,
        'mismatch' => $mismatchStr,
        'recordsTotal' => $recordsTotalStr,
        'recordsFiltered' => $recordsFilteredStr,
        'dataLen' => $dataLenStr,
        'note' => $noteStr,
    ];
}

$md = "# Datatable Inspector Summary (Top recent)\n\n";
$md .= "| File | Route | Mismatch/Score | Total | Filtered | Data len | Note |\n";
$md .= "|---|---|---:|---:|---:|---:|---|\n";
foreach ($rows as $r) {
    $md .= sprintf(
        "| %s | %s | %s | %s | %s | %s | %s |\n",
        $r['file'], $r['route'], $r['mismatch'], $r['recordsTotal'], $r['recordsFiltered'], $r['dataLen'], str_replace(["\n", "\r"], ' ', $r['note'])
    );
}

// Print to STDOUT (can be redirected to $GITHUB_STEP_SUMMARY in workflow)
echo $md, "\n";
