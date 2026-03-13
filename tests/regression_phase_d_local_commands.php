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
    $commandsPath = __DIR__ . '/../PHASE_D_LOCAL_COMMANDS_2026-03-13.md';
    $scriptPath = __DIR__ . '/../database/migrations/apply_phase_d_local.ps1';

    assertFileContains(
        $commandsPath,
        'apply_phase_d_local.ps1',
        'Phase D local commands guide should point to the rollout helper script.'
    );

    assertFileContains(
        $commandsPath,
        'phase_d_cutover_preflight.sql',
        'Phase D local commands guide should include the preflight SQL command.'
    );

    assertFileContains(
        $scriptPath,
        'phase_d_cutover_preflight.sql',
        'Phase D rollout helper should run the preflight SQL before the migration.'
    );

    assertFileContains(
        $scriptPath,
        '2026_03_13_000005_drop_approval_compatibility_columns.sql',
        'Phase D rollout helper should apply the hard cutover migration.'
    );

    assertFileContains(
        $scriptPath,
        "php 'tests/run_regressions.php'",
        'Phase D rollout helper should run the lightweight regression bundle after the migration.'
    );

    echo "Phase D local commands regression: OK\n";
}

runRegressionSuite();
