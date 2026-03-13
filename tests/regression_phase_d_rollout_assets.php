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
    $checklistPath = __DIR__ . '/../PHASE_D_ROLLOUT_CHECKLIST_2026-03-13.md';
    $preflightPath = __DIR__ . '/../database/migrations/phase_d_cutover_preflight.sql';

    assertFileContains(
        $checklistPath,
        '2026_03_13_000005_drop_approval_compatibility_columns.sql',
        'Phase D rollout checklist should point to the hard cutover migration.'
    );

    assertFileContains(
        $checklistPath,
        'php tests/run_regressions.php',
        'Phase D rollout checklist should require the lightweight regression bundle.'
    );

    assertFileContains(
        $preflightPath,
        'missing_registration_date',
        'Phase D preflight should check for missing registration_date values.'
    );

    assertFileContains(
        $preflightPath,
        'missing_registered_by',
        'Phase D preflight should check for missing registered_by values.'
    );

    assertFileContains(
        $preflightPath,
        'missing_lifecycle_action_type',
        'Phase D preflight should check for missing lifecycle action labels.'
    );

    echo "Phase D rollout assets regression: OK\n";
}

runRegressionSuite();
