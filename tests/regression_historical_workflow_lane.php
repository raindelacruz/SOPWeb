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
    $controllerPath = __DIR__ . '/../app/controllers/Procedures.php';
    $viewPath = __DIR__ . '/../app/views/procedures/show.php';

    assertFileContains(
        $controllerPath,
        '$detail[\'next_workflow_status\'] = null;',
        'Procedures::show should no longer expose guided workflow transitions on the primary procedure screen.'
    );

    assertFileContains(
        $controllerPath,
        '$detail[\'show_workflow_lane\'] = false;',
        'Procedures::show should suppress the workflow lane in the registry-first UI.'
    );

    $contents = file_get_contents($viewPath);
    if ($contents === false) {
        throw new RuntimeException('Unable to read file: ' . $viewPath);
    }

    assertTrue(
        strpos($contents, 'Workflow Lane') === false,
        'Procedure detail should no longer render a workflow lane heading in the registry-first UI.'
    );

    echo "Historical workflow lane regression: OK\n";
}

runRegressionSuite();
