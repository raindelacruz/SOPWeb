<?php
require_once __DIR__ . '/../app/helpers/pdms_authoring_options.php';

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
    $helperPath = __DIR__ . '/../app/helpers/pdms_authoring_options.php';
    $syncServicePath = __DIR__ . '/../app/models/ProcedureSyncService.php';
    $versionModelPath = __DIR__ . '/../app/models/ProcedureVersion.php';

    assertTrue(
        PdmsAuthoringOptions::controllingWorkflowStatuses() === ['EFFECTIVE'],
        'Registry-only controlling fallback should treat EFFECTIVE as the sole controlling status.'
    );

    assertTrue(
        PdmsAuthoringOptions::storedControllingWorkflowStatuses() === ['EFFECTIVE'],
        'Legacy controlling-status helper should now delegate to the registry-only controlling set.'
    );

    assertFileContains(
        $helperPath,
        'public static function controllingWorkflowStatuses()',
        'Shared authoring options should expose a registry-native controlling-status helper.'
    );

    assertFileContains(
        $syncServicePath,
        'PdmsAuthoringOptions::controllingWorkflowStatuses()',
        'ProcedureSyncService fallback current-version recovery should use registry-only controlling statuses.'
    );

    assertFileNotContains(
        $syncServicePath,
        'WHEN status = "APPROVED" THEN 1',
        'ProcedureSyncService should no longer preserve APPROVED ordering in controlling-version fallback.'
    );

    assertFileContains(
        $versionModelPath,
        'PdmsAuthoringOptions::controllingWorkflowStatuses()',
        'ProcedureVersion controlling candidates should use the registry-only controlling set.'
    );

    echo "Registry controlling status regression: OK\n";
}

runRegressionSuite();
