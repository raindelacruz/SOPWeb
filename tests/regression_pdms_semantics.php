<?php
require_once __DIR__ . '/../app/models/ProcedureReadModel.php';

class StubDatabaseForHistoricalAnchor {
    private $singleResults;
    private $lastResult = null;

    public function __construct(array $singleResults) {
        $this->singleResults = $singleResults;
    }

    public function query($sql) {
        if (empty($this->singleResults)) {
            throw new RuntimeException('No stubbed database result remains for query: ' . $sql);
        }

        $this->lastResult = array_shift($this->singleResults);
    }

    public function bind($param, $value, $type = null) {
        return true;
    }

    public function single() {
        return $this->lastResult;
    }
}

class TestableProcedureReadModel extends ProcedureReadModel {
    public function __construct($db) {
        $reflection = new ReflectionClass(ProcedureReadModel::class);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($this, $db);
    }
}

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function testHistoricalProcedureUsesAuditAnchor() {
    $overview = (object) [
        'id' => 77,
        'status' => 'SUPERSEDED',
        'current_version_id' => null,
        'current_version_number' => null,
        'current_document_number' => null,
        'current_change_type' => null,
        'current_summary_of_change' => null,
        'current_legacy_post_id' => null,
        'current_version_status' => null,
        'current_effective_date' => null,
        'current_file_path' => null
    ];

    $anchorVersion = (object) [
        'id' => 901,
        'version_number' => '4.0',
        'document_number' => 'PROC-4',
        'change_type' => 'FULL_REVISION',
        'summary_of_change' => 'Final historical issuance',
        'legacy_post_id' => 122,
        'status' => 'SUPERSEDED',
        'effective_date' => '2026-03-01',
        'file_path' => 'anchor.pdf'
    ];

    $db = new StubDatabaseForHistoricalAnchor([$anchorVersion]);
    $model = new TestableProcedureReadModel($db);

    $method = new ReflectionMethod(ProcedureReadModel::class, 'applyHistoricalAnchorVersion');
    $method->setAccessible(true);
    $resolved = $method->invoke($model, $overview);

    assertTrue((int) $resolved->current_version_id === 901, 'Historical procedures should expose the latest version as an audit anchor.');
    assertTrue($resolved->current_version_number === '4.0', 'Historical anchor version number should be copied into the overview.');
    assertTrue($resolved->current_version_status === 'SUPERSEDED', 'Historical anchor status should match the latest historical version.');
    assertTrue(!empty($resolved->historical_anchor_version), 'Historical procedures should be flagged as using an audit anchor.');
}

function testActiveProcedureDoesNotUseAuditAnchor() {
    $overview = (object) [
        'id' => 88,
        'status' => 'ACTIVE',
        'current_version_id' => 321,
        'current_version_number' => '2.0'
    ];

    $db = new StubDatabaseForHistoricalAnchor([]);
    $model = new TestableProcedureReadModel($db);

    $method = new ReflectionMethod(ProcedureReadModel::class, 'applyHistoricalAnchorVersion');
    $method->setAccessible(true);
    $resolved = $method->invoke($model, $overview);

    assertTrue((int) $resolved->current_version_id === 321, 'Active procedures must retain their controlling-version pointer.');
    assertTrue(empty($resolved->historical_anchor_version), 'Active procedures must not be marked as using a historical audit anchor.');
}

function runRegressionSuite() {
    testHistoricalProcedureUsesAuditAnchor();
    testActiveProcedureDoesNotUseAuditAnchor();
    echo "PDMS regression semantics: OK\n";
}

runRegressionSuite();
