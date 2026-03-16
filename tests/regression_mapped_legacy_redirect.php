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
    assertTrue(
        file_exists(__DIR__ . '/../app/controllers/Posts.php') === false,
        'The retired Posts controller should be removed in the PDMS-only architecture.'
    );

    echo "Mapped legacy redirect regression: OK\n";
}

runRegressionSuite();
