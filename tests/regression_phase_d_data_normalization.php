<?php

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertFileContains($path, $needle, $message) {
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read file: ' . $path);
    }

    assertTrue(strpos($contents, $needle) !== false, $message);
}

function runRegressionSuite() {
    $migrationPath = __DIR__ . '/../database/migrations/2026_03_13_000006_normalize_registry_transition_data.sql';

    assertFileContains(
        $migrationPath,
        "WHERE pv.status = 'APPROVED'",
        'Phase D data normalization migration should retarget lingering APPROVED procedure version statuses.'
    );

    assertFileContains(
        $migrationPath,
        "SET current_version_id = NULL",
        'Phase D data normalization migration should clear controlling pointers from terminal procedures.'
    );

    assertFileContains(
        $migrationPath,
        "SET from_status = 'REGISTERED'",
        'Phase D data normalization migration should normalize workflow from_status values to REGISTERED.'
    );

    assertFileContains(
        $migrationPath,
        "SET to_status = 'REGISTERED'",
        'Phase D data normalization migration should normalize workflow to_status values to REGISTERED.'
    );

    echo "Phase D data normalization regression: OK\n";
}

runRegressionSuite();
