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
    $authoringServicePath = __DIR__ . '/../app/models/ProcedureAuthoringService.php';
    $controllerPath = __DIR__ . '/../app/controllers/Procedures.php';

    assertFileNotContains(
        $authoringServicePath,
        'public function createProcedureWithInitialVersion($data)',
        'ProcedureAuthoringService should remove the legacy createProcedureWithInitialVersion wrapper during Phase C.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'public function issueVersionForProcedure($procedureId, $data)',
        'ProcedureAuthoringService should remove the legacy issueVersionForProcedure wrapper during Phase C.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'public function promoteCurrentVersionToEffective($procedureId, $userId = null)',
        'ProcedureAuthoringService should remove the deprecated promoteCurrentVersionToEffective wrapper during Phase C.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'public function markLatestRegisteredVersionEffective($procedureId, $userId = null)',
        'ProcedureAuthoringService should remove the deprecated markLatestRegisteredVersionEffective wrapper during Phase C.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'public function transitionLatestWorkflowVersion($procedureId, $toStatus, $userId = null)',
        'ProcedureAuthoringService should remove the deprecated transitionLatestWorkflowVersion API during Phase C.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'private function recordWorkflowAction($data)',
        'ProcedureAuthoringService should remove the recordWorkflowAction alias during Phase C.'
    );

    assertFileNotContains(
        $controllerPath,
        'public function promote($id)',
        'Procedures controller should remove the deprecated promote endpoint during Phase C.'
    );

    assertFileNotContains(
        $controllerPath,
        'public function transition($id)',
        'Procedures controller should remove the deprecated transition endpoint during Phase C.'
    );

    assertFileContains(
        $controllerPath,
        'return $this->registerRevision($id);',
        'Procedures::issue should remain as the supported route compatibility wrapper.'
    );

    echo "Phase C cleanup regression: OK\n";
}

runRegressionSuite();
