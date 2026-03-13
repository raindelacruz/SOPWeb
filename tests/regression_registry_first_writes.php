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
    $authoringServicePath = __DIR__ . '/../app/models/ProcedureAuthoringService.php';
    $syncServicePath = __DIR__ . '/../app/models/ProcedureSyncService.php';
    $workflowActionPath = __DIR__ . '/../app/models/WorkflowAction.php';
    $procedureVersionPath = __DIR__ . '/../app/models/ProcedureVersion.php';

    assertFileContains(
        $authoringServicePath,
        'effective_date, registration_date, status, file_path, based_on_version_id, created_by, registered_by)',
        'ProcedureAuthoringService should use registry-first version inserts when alias columns exist.'
    );

    assertFileContains(
        $syncServicePath,
        'effective_date, registration_date, status, file_path, based_on_version_id, created_by, registered_by)',
        'ProcedureSyncService should use registry-first version inserts when alias columns exist.'
    );

    assertFileContains(
        $procedureVersionPath,
        'registration_date, status, file_path, based_on_version_id, created_by, registered_by)',
        'ProcedureVersion should support registry-first inserts when alias columns exist.'
    );

    assertFileContains(
        $authoringServicePath,
        'registered_by = :registered_by',
        'ProcedureAuthoringService alias-aware updates should write registered_by.'
    );

    assertFileContains(
        $syncServicePath,
        'registered_by = :registered_by',
        'ProcedureSyncService alias-aware updates should write registered_by.'
    );

    assertFileContains(
        $authoringServicePath,
        '(procedure_version_id, lifecycle_action_type, from_status, to_status, acted_by, remarks)',
        'ProcedureAuthoringService should write lifecycle_action_type directly during the hard cutover.'
    );

    assertFileContains(
        $syncServicePath,
        '(procedure_version_id, lifecycle_action_type, from_status, to_status, acted_by, remarks)',
        'ProcedureSyncService should write lifecycle_action_type directly during the hard cutover.'
    );

    assertFileContains(
        $workflowActionPath,
        '(procedure_version_id, lifecycle_action_type, from_status, to_status, acted_by, remarks)',
        'WorkflowAction model should record lifecycle_action_type directly during the hard cutover.'
    );

    echo "Registry-first write regression: OK\n";
}

runRegressionSuite();
