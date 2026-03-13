<?php
require_once __DIR__ . '/../app/helpers/pdms_authoring_options.php';
require_once __DIR__ . '/../app/models/ProcedureSyncService.php';

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function newInstanceWithoutConstructor($className) {
    $reflection = new ReflectionClass($className);
    return $reflection->newInstanceWithoutConstructor();
}

function invokePrivateMethod($className, $instance, $methodName, array $args = []) {
    $method = new ReflectionMethod($className, $methodName);
    $method->setAccessible(true);
    return $method->invokeArgs($instance, $args);
}

function testSyncFallbackUsesChangeTypeDefaultRelationship() {
    $service = newInstanceWithoutConstructor(ProcedureSyncService::class);

    $referenceRelationship = invokePrivateMethod(
        ProcedureSyncService::class,
        $service,
        'resolveRelationshipType',
        ['', PdmsAuthoringOptions::defaultRelationshipTypeForChangeType('REFERENCE')]
    );

    $rescissionRelationship = invokePrivateMethod(
        ProcedureSyncService::class,
        $service,
        'resolveRelationshipType',
        ['', PdmsAuthoringOptions::defaultRelationshipTypeForChangeType('RESCISSION')]
    );

    assertTrue(
        $referenceRelationship === 'REFERENCES',
        'Sync relationship fallback should preserve REFERENCES for reference change types.'
    );
    assertTrue(
        $rescissionRelationship === 'RESCINDS',
        'Sync relationship fallback should preserve RESCINDS for rescission change types.'
    );
}

function runRegressionSuite() {
    testSyncFallbackUsesChangeTypeDefaultRelationship();
    echo "Sync relationship fallback regression: OK\n";
}

runRegressionSuite();
