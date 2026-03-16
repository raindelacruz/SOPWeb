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
    $migrationPath = __DIR__ . '/../database/migrations/2026_03_13_000004_add_registry_schema_aliases.sql';
    $cutoverMigrationPath = __DIR__ . '/../database/migrations/2026_03_13_000005_drop_approval_compatibility_columns.sql';
    $authoringServicePath = __DIR__ . '/../app/models/ProcedureAuthoringService.php';
    $workflowActionPath = __DIR__ . '/../app/models/WorkflowAction.php';
    $readModelPath = __DIR__ . '/../app/models/ProcedureReadModel.php';
    $controllerPath = __DIR__ . '/../app/controllers/Procedures.php';

    assertFileContains(
        $migrationPath,
        'ADD COLUMN registration_date',
        'Registry schema migration should add procedure_versions.registration_date.'
    );

    assertFileContains(
        $migrationPath,
        'ADD COLUMN registered_by',
        'Registry schema migration should add procedure_versions.registered_by.'
    );

    assertFileContains(
        $migrationPath,
        'ADD COLUMN lifecycle_action_type',
        'Registry schema migration should add workflow_actions.lifecycle_action_type.'
    );

    assertFileContains(
        $cutoverMigrationPath,
        'DROP COLUMN approval_date',
        'Phase D cutover migration should drop procedure_versions.approval_date.'
    );

    assertFileContains(
        $cutoverMigrationPath,
        'DROP COLUMN approved_by',
        'Phase D cutover migration should drop procedure_versions.approved_by.'
    );

    assertFileContains(
        $cutoverMigrationPath,
        'DROP COLUMN action_type',
        'Phase D cutover migration should drop workflow_actions.action_type.'
    );

    assertFileContains(
        $workflowActionPath,
        'lifecycle_action_type',
        'WorkflowAction model should understand lifecycle_action_type as the registry-native action label.'
    );

    assertFileContains(
        $readModelPath,
        'FROM workflow_actions',
        'ProcedureReadModel should continue to read lifecycle workflow actions from the workflow_actions table.'
    );

    assertFileContains(
        $authoringServicePath,
        'registration_date, status, file_path, based_on_version_id, created_by, registered_by)',
        'ProcedureAuthoringService should write registry-native version metadata directly after the cutover.'
    );

    assertFileContains(
        $authoringServicePath,
        'PDMS_REGISTER_PROCEDURE',
        'ProcedureAuthoringService should log registry-native procedure registration actions.'
    );

    assertFileContains(
        $authoringServicePath,
        'PDMS_REGISTER_REVISION',
        'ProcedureAuthoringService should log registry-native revision registration actions.'
    );

    assertFileContains(
        $controllerPath,
        'public function registerRevision($id)',
        'Procedures controller should expose a registry-native revision registration entry point.'
    );

    assertFileContains(
        $controllerPath,
        'return $this->registerRevision($id);',
        'Procedures::issue should remain as a compatibility wrapper around registerRevision.'
    );

    assertTrue(
        file_exists(__DIR__ . '/../app/models/ProcedureSyncService.php') === false,
        'Registry schema cleanup should remove the retired legacy sync service.'
    );

    echo "Registry schema alias regression: OK\n";
}

runRegressionSuite();
