<?php

$scripts = [
    __DIR__ . '/regression_pdms_semantics.php',
    __DIR__ . '/regression_legacy_compatibility_gate.php',
    __DIR__ . '/regression_terminal_pointer_clearing.php',
    __DIR__ . '/regression_mapped_legacy_redirect.php',
    __DIR__ . '/regression_authoring_status_validation.php',
    __DIR__ . '/regression_historical_workflow_lane.php',
    __DIR__ . '/regression_procedure_master_status_model.php',
    __DIR__ . '/regression_registry_controlling_statuses.php',
    __DIR__ . '/regression_registry_first_writes.php',
    __DIR__ . '/regression_phase_c_cleanup.php',
    __DIR__ . '/regression_phase_d_cutover.php',
    __DIR__ . '/regression_phase_d_rollout_assets.php',
    __DIR__ . '/regression_phase_d_local_commands.php',
    __DIR__ . '/regression_phase_d_data_normalization.php',
    __DIR__ . '/regression_sync_relationship_fallbacks.php',
    __DIR__ . '/regression_section_history_surface.php',
    __DIR__ . '/regression_registry_schema_aliases.php'
];

$allPassed = true;

foreach ($scripts as $script) {
    $command = 'php ' . escapeshellarg($script);
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        $allPassed = false;
        fwrite(STDERR, "Regression failed: {$script}\n");
        break;
    }
}

if (!$allPassed) {
    exit(1);
}

echo "All lightweight PDMS regressions passed.\n";
