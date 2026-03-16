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
    $proceduresControllerPath = __DIR__ . '/../app/controllers/Procedures.php';

    assertFileContains(
        $authoringServicePath,
        'INSERT INTO procedures',
        'ProcedureAuthoringService should still create PDMS procedures after legacy bridge retirement.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'maybeCreateLegacyPost',
        'ProcedureAuthoringService should no longer include legacy mirror helpers once the bridge is retired.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'createLegacyPost',
        'ProcedureAuthoringService should no longer create legacy post records once the bridge is retired.'
    );

    assertFileNotContains(
        $authoringServicePath,
        'legacy_post_id',
        'ProcedureAuthoringService should no longer write legacy bridge columns once the bridge is retired.'
    );

    assertTrue(
        file_exists(__DIR__ . '/../app/models/Post.php') === false,
        'The retired Post model should be removed once the PDMS-only cleanup sweep is complete.'
    );

    assertFileContains(
        $proceduresControllerPath,
        'PDMS procedure created successfully.',
        'Procedures controller success copy should no longer imply that PDMS-first authoring always creates a legacy mirror row.'
    );

    echo "Authoring legacy mirror minimization regression: OK\n";
}

runRegressionSuite();
