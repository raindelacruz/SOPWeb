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

    assertTrue(
        file_exists(__DIR__ . '/../app/models/ProcedureSyncService.php') === false,
        'Terminal-state cleanup should remove the retired legacy sync service.'
    );

    echo "Terminal pointer clearing regression: OK\n";
}

runRegressionSuite();
