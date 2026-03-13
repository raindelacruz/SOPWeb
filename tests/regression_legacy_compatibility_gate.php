<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../app/controllers/Posts.php';

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function newPostsControllerWithoutConstructor() {
    $reflection = new ReflectionClass(Posts::class);
    return $reflection->newInstanceWithoutConstructor();
}

function invokeCompatibilityIntentCheck($controller) {
    $method = new ReflectionMethod(Posts::class, 'hasLegacyCompatibilityIntent');
    $method->setAccessible(true);
    return $method->invoke($controller);
}

function resetRequestState() {
    $_GET = [];
    $_POST = [];
}

function testCompatibilityIntentAcceptsExplicitQueryFlag() {
    resetRequestState();
    $_GET['legacy_compatibility_intent'] = '1';

    $controller = newPostsControllerWithoutConstructor();
    assertTrue(
        invokeCompatibilityIntentCheck($controller) === true,
        'Legacy compatibility intent should be accepted from the query string when explicitly set to 1.'
    );
}

function testCompatibilityIntentAcceptsExplicitPostFlag() {
    resetRequestState();
    $_POST['legacy_compatibility_intent'] = '1';

    $controller = newPostsControllerWithoutConstructor();
    assertTrue(
        invokeCompatibilityIntentCheck($controller) === true,
        'Legacy compatibility intent should be accepted from POST when explicitly set to 1.'
    );
}

function testCompatibilityIntentRejectsMissingOrUnexpectedValues() {
    resetRequestState();
    $controller = newPostsControllerWithoutConstructor();
    assertTrue(
        invokeCompatibilityIntentCheck($controller) === false,
        'Legacy compatibility intent should be false when the request flag is missing.'
    );

    resetRequestState();
    $_GET['legacy_compatibility_intent'] = 'yes';
    assertTrue(
        invokeCompatibilityIntentCheck($controller) === false,
        'Legacy compatibility intent should reject unexpected values.'
    );
}

function runRegressionSuite() {
    testCompatibilityIntentAcceptsExplicitQueryFlag();
    testCompatibilityIntentAcceptsExplicitPostFlag();
    testCompatibilityIntentRejectsMissingOrUnexpectedValues();
    echo "Legacy compatibility gate regression: OK\n";
}

runRegressionSuite();
