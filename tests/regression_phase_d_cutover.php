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

function assertFileNotContains($path, $needle, $message) {
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read file: ' . $path);
    }

    assertTrue(strpos($contents, $needle) === false, $message);
}

function runRegressionSuite() {
    $migrationPath = __DIR__ . '/../database/migrations/2026_03_13_000005_drop_approval_compatibility_columns.sql';
    $authoringServicePath = __DIR__ . '/../app/models/ProcedureAuthoringService.php';
    $versionModelPath = __DIR__ . '/../app/models/ProcedureVersion.php';
    $workflowActionPath = __DIR__ . '/../app/models/WorkflowAction.php';
    $readModelPath = __DIR__ . '/../app/models/ProcedureReadModel.php';
    $procedureShowViewPath = __DIR__ . '/../app/views/procedures/show.php';
    $procedureVersionViewPath = __DIR__ . '/../app/views/procedures/version.php';

    assertFileContains(
        $migrationPath,
        "MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'REGISTERED'",
        'Phase D cutover migration should make REGISTERED the procedure_versions default status.'
    );

    assertFileContains(
        $migrationPath,
        'DROP COLUMN approval_date',
        'Phase D cutover migration should remove approval_date.'
    );

    assertFileContains(
        $migrationPath,
        'DROP COLUMN approved_by',
        'Phase D cutover migration should remove approved_by.'
    );

    assertFileContains(
        $migrationPath,
        'DROP COLUMN action_type',
        'Phase D cutover migration should remove workflow_actions.action_type.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'approval_date',
        'ProcedureAuthoringService should no longer reference approval_date after Phase D.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'approved_by',
        'ProcedureAuthoringService should no longer reference approved_by after Phase D.'
    );

    assertFileNotContains(
        $workflowActionPath,
        '(procedure_version_id, action_type,',
        'WorkflowAction model should no longer insert into the legacy action_type column after Phase D.'
    );

    assertFileNotContains(
        $workflowActionPath,
        ':action_type',
        'WorkflowAction model should no longer bind the legacy action_type parameter after Phase D.'
    );

    assertFileNotContains(
        $readModelPath,
        '$action->action_type = $action->lifecycle_action_type;',
        'ProcedureReadModel should no longer alias lifecycle_action_type back into action_type after Phase D.'
    );

    assertFileContains(
        $procedureShowViewPath,
        '$action->lifecycle_action_type',
        'Procedure detail view should render lifecycle_action_type directly after Phase D.'
    );

    assertFileContains(
        $procedureVersionViewPath,
        '$action->lifecycle_action_type',
        'Procedure version view should render lifecycle_action_type directly after Phase D.'
    );

    assertFileContains(
        $versionModelPath,
        'registration_date, status, file_path, based_on_version_id, created_by, registered_by)',
        'ProcedureVersion should be registry-only for version inserts after Phase D.'
    );

    assertTrue(
        file_exists(__DIR__ . '/../app/models/ProcedureSyncService.php') === false,
        'ProcedureSyncService should be removed during the PDMS-only cleanup sweep.'
    );

    assertTrue(
        file_exists(__DIR__ . '/../app/views/posts/show.php') === false,
        'Legacy post detail views should be removed during the PDMS-only cleanup sweep.'
    );

    echo "Phase D cutover regression: OK\n";
}

runRegressionSuite();
