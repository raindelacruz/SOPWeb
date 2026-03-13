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

    assertFileContains(
        $authoringServicePath,
        '$this->clearCurrentVersion($procedureId);',
        'Rescission should clear the controlling-version pointer in ProcedureAuthoringService.'
    );

    assertFileContains(
        $authoringServicePath,
        'SET status = :status,',
        'Superseding a target procedure should still update the procedure status in ProcedureAuthoringService.'
    );

    assertFileContains(
        $authoringServicePath,
        'current_version_id = NULL',
        'Superseding a target procedure should clear current_version_id in ProcedureAuthoringService.'
    );

    assertFileContains(
        $syncServicePath,
        '$this->clearCurrentVersion((int) $supersededProcedure->id);',
        'Legacy sync supersession should clear the controlling-version pointer in ProcedureSyncService.'
    );

    echo "Terminal pointer clearing regression: OK\n";
}

runRegressionSuite();
