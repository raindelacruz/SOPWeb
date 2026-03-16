<?php

function assertNotContains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) !== false) {
        throw new RuntimeException($message);
    }
}

function runRegressionSuite() {
    $readModelPath = __DIR__ . '/../app/models/ProcedureReadModel.php';
    $readModel = file_get_contents($readModelPath);

    if ($readModel === false) {
        throw new RuntimeException('Unable to read ProcedureReadModel for mapped post context regression.');
    }

    assertNotContains(
        'public function getMappedPostCompatibilityRelationshipSummary($versionId, $legacyPostId)',
        $readModel,
        'ProcedureReadModel should no longer expose mapped-post compatibility helpers once the legacy bridge is retired.'
    );

    assertNotContains(
        '$legacyRelationships = $this->getMappedPostCompatibilityRelationshipSummary($contextVersionId, $legacyPostId);',
        $readModel,
        'ProcedureReadModel should no longer rebuild legacy compatibility relationships during PDMS-only runtime.'
    );

    assertNotContains(
        '$context[\'legacy_timeline\'] = $this->buildCompatibilityTimeline(',
        $readModel,
        'ProcedureReadModel should no longer rebuild legacy compatibility timelines during PDMS-only runtime.'
    );

    echo "Mapped post context retirement regression: OK\n";
}

runRegressionSuite();
