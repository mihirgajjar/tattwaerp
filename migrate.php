<?php

// CLI helper for deployment pipelines.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Migration.php';

$mode = $argv[1] ?? 'run';
$model = new Migration();

try {
    if ($mode === 'baseline') {
        $marked = $model->baselineMarkAll();
        echo "Baseline marked: " . count($marked) . " migration(s).\n";
        exit(0);
    }

    if ($mode === 'status') {
        $pending = $model->pending();
        echo "Pending migrations: " . count($pending) . "\n";
        foreach ($pending as $m) {
            echo " - {$m}\n";
        }
        exit(0);
    }

    $applied = $model->applyAll();
    echo "Applied migrations: " . count($applied) . "\n";
    foreach ($applied as $m) {
        echo " - {$m}\n";
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
