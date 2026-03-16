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
    $readModelPath = __DIR__ . '/../app/models/ProcedureReadModel.php';

    assertFileContains(
        $authoringServicePath,
        "return 'ACTIVE';",
        'ProcedureAuthoringService should continue to treat non-terminal procedure masters as ACTIVE.'
    );

    assertFileContains(
        $authoringServicePath,
        '$this->db->bind(\':status\', \'SUPERSEDED\');',
        'ProcedureAuthoringService should continue to mark superseded procedure masters as SUPERSEDED.'
    );

    assertFileContains(
        $authoringServicePath,
        '$this->db->bind(\':status\', \'RESCINDED\');',
        'ProcedureAuthoringService should continue to mark rescinded procedure masters as RESCINDED.'
    );

    assertFileContains(
        $authoringServicePath,
        'current_version_id = NULL',
        'Terminal procedure-master transitions should clear current_version_id in ProcedureAuthoringService.'
    );

    assertFileContains(
        $readModelPath,
        'PdmsAuthoringOptions::terminalProcedureStatuses()',
        'ProcedureReadModel dashboard should continue to treat terminal procedure-master states as non-current through the shared policy helper.'
    );

    assertTrue(
        file_exists(__DIR__ . '/../app/models/ProcedureSyncService.php') === false,
        'Procedure master status cleanup should remove the retired legacy sync service.'
    );

    echo "Procedure master status model regression: OK\n";
}

runRegressionSuite();
