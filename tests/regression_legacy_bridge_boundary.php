<?php

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertContains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function assertNotContains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) !== false) {
        throw new RuntimeException($message);
    }
}

function runRegressionSuite() {
    $procedureShowView = file_get_contents(__DIR__ . '/../app/views/procedures/show.php');

    if ($procedureShowView === false) {
        throw new RuntimeException('Unable to read legacy-bridge files for boundary regression.');
    }

    assertNotContains(
        'Open Legacy Mirror',
        $procedureShowView,
        'Procedure detail should no longer expose legacy mirror actions once PDMS procedure screens are PDMS-only.'
    );

    assertTrue(
        file_exists(__DIR__ . '/../app/controllers/Posts.php') === false,
        'Posts controller should be removed once the legacy schema bridge is retired.'
    );

    assertTrue(
        file_exists(__DIR__ . '/../app/models/Post.php') === false,
        'Post model should be removed once the legacy schema bridge is retired.'
    );

    assertTrue(
        file_exists(__DIR__ . '/../app/views/posts/show.php') === false,
        'Legacy posts detail view should be removed once the legacy schema bridge is retired.'
    );

    echo "Legacy bridge boundary regression: OK\n";
}

runRegressionSuite();
