<?php

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function testLegacyWriteActionsAreRetiredInController() {
    assertTrue(
        file_exists(__DIR__ . '/../app/controllers/Posts.php') === false,
        'Posts controller should be removed once the legacy write surface is fully retired.'
    );
}

function testLegacyEditEntryPointIsRemovedFromPostDetailUi() {
    assertTrue(
        file_exists(__DIR__ . '/../app/views/posts/show.php') === false,
        'Post detail view should be removed once the legacy write surface is fully retired.'
    );
}

function runRegressionSuite() {
    testLegacyWriteActionsAreRetiredInController();
    testLegacyEditEntryPointIsRemovedFromPostDetailUi();
    echo "Legacy write retirement regression: OK\n";
}

runRegressionSuite();
