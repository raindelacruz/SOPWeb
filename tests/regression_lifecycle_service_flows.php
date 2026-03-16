<?php
if (!defined('DB_NAME')) {
    define('DB_NAME', 'sopweb');
}

require_once __DIR__ . '/../app/helpers/pdms_authoring_options.php';
require_once __DIR__ . '/../app/models/ProcedureAuthoringService.php';

class InMemoryAuthoringDatabase {
    public $procedures = [];
    public $procedureVersions = [];
    public $workflowActions = [];
    public $relationships = [];

    private $currentSql = '';
    private $bindings = [];
    private $lastInsertId = 0;

    public function __construct(array $seed = []) {
        $this->procedures = $seed['procedures'] ?? [];
        $this->procedureVersions = $seed['procedure_versions'] ?? [];
        $this->workflowActions = $seed['workflow_actions'] ?? [];
        $this->relationships = $seed['relationships'] ?? [];
        $this->lastInsertId = $seed['last_insert_id'] ?? $this->inferLastInsertId();
    }

    public function beginTransaction() {
        return true;
    }

    public function commit() {
        return true;
    }

    public function rollBack() {
        return true;
    }

    public function query($sql) {
        $this->currentSql = $sql;
        $this->bindings = [];
        return true;
    }

    public function bind($param, $value, $type = null) {
        unset($type);
        $this->bindings[$param] = $value;
        return true;
    }

    public function execute() {
        $sql = $this->normalizeSql($this->currentSql);

        if (strpos($sql, 'INSERT INTO procedures') === 0) {
            $id = $this->nextId();
            $this->procedures[$id] = (object) [
                'id' => $id,
                'procedure_code' => $this->bindings[':procedure_code'],
                'title' => $this->bindings[':title'],
                'description' => $this->bindings[':description'],
                'category' => $this->bindings[':category'] ?? null,
                'owner_office' => $this->bindings[':owner_office'] ?? null,
                'status' => $this->bindings[':status'],
                'current_version_id' => null,
                'created_by' => $this->bindings[':created_by'] ?? null
            ];
            $this->lastInsertId = $id;
            return true;
        }

        if (strpos($sql, 'INSERT INTO procedure_versions') === 0) {
            $id = $this->nextId();
            $this->procedureVersions[$id] = (object) [
                'id' => $id,
                'procedure_id' => (int) $this->bindings[':procedure_id'],
                'version_number' => $this->bindings[':version_number'],
                'document_number' => $this->bindings[':document_number'] ?? null,
                'title' => $this->bindings[':title'],
                'summary_of_change' => $this->bindings[':summary_of_change'] ?? null,
                'change_type' => $this->bindings[':change_type'],
                'effective_date' => $this->bindings[':effective_date'] ?? null,
                'registration_date' => $this->bindings[':registration_date'] ?? null,
                'status' => $this->bindings[':status'],
                'file_path' => $this->bindings[':file_path'] ?? null,
                'based_on_version_id' => $this->bindings[':based_on_version_id'] ?? null,
                'created_by' => $this->bindings[':created_by'] ?? null,
                'registered_by' => $this->bindings[':registered_by'] ?? null,
                'created_at' => sprintf('2026-03-13 00:00:%02d', $id)
            ];
            $this->lastInsertId = $id;
            return true;
        }

        if (strpos($sql, 'INSERT INTO document_relationships') === 0) {
            $id = $this->nextId();
            $this->relationships[$id] = (object) [
                'id' => $id,
                'source_version_id' => (int) $this->bindings[':source_version_id'],
                'target_version_id' => (int) $this->bindings[':target_version_id'],
                'relationship_type' => $this->bindings[':relationship_type'],
                'affected_sections' => $this->bindings[':affected_sections'] ?? null,
                'remarks' => $this->bindings[':remarks'] ?? null,
                'management_source' => $this->bindings[':management_source'] ?? null,
                'created_by' => $this->bindings[':created_by'] ?? null
            ];
            $this->lastInsertId = $id;
            return true;
        }

        if (strpos($sql, 'INSERT INTO workflow_actions') === 0) {
            $id = $this->nextId();
            $this->workflowActions[$id] = (object) [
                'id' => $id,
                'procedure_version_id' => (int) $this->bindings[':procedure_version_id'],
                'lifecycle_action_type' => $this->bindings[':lifecycle_action_type'],
                'from_status' => $this->bindings[':from_status'] ?? null,
                'to_status' => $this->bindings[':to_status'] ?? null,
                'acted_by' => $this->bindings[':acted_by'] ?? null,
                'remarks' => $this->bindings[':remarks'] ?? null
            ];
            $this->lastInsertId = $id;
            return true;
        }

        if (strpos($sql, 'UPDATE procedures SET current_version_id = :current_version_id') === 0) {
            $procedureId = (int) $this->bindings[':procedure_id'];
            $this->procedures[$procedureId]->current_version_id = (int) $this->bindings[':current_version_id'];
            return true;
        }

        if (strpos($sql, 'UPDATE procedures SET current_version_id = NULL') === 0) {
            $procedureId = (int) $this->bindings[':procedure_id'];
            $this->procedures[$procedureId]->current_version_id = null;
            return true;
        }

        if (strpos($sql, 'UPDATE procedures SET status = :status, current_version_id = NULL') === 0) {
            $procedureId = (int) $this->bindings[':id'];
            $this->procedures[$procedureId]->status = $this->bindings[':status'];
            $this->procedures[$procedureId]->current_version_id = null;
            return true;
        }

        if (strpos($sql, 'UPDATE procedures SET title = :title,') === 0) {
            $procedureId = (int) $this->bindings[':id'];
            $procedure = $this->procedures[$procedureId];
            $procedure->title = $this->bindings[':title'];
            $procedure->description = $this->bindings[':description'] ?? null;
            $procedure->category = $this->bindings[':category'] ?? null;
            $procedure->owner_office = $this->bindings[':owner_office'] ?? null;
            $procedure->status = $this->bindings[':status'];
            return true;
        }

        if (strpos($sql, 'UPDATE procedures SET status = :status WHERE id = :id') === 0) {
            $procedureId = (int) $this->bindings[':id'];
            $this->procedures[$procedureId]->status = $this->bindings[':status'];
            return true;
        }

        if (strpos($sql, 'UPDATE procedure_versions SET status = :status') === 0) {
            $versionId = (int) $this->bindings[':id'];
            $this->procedureVersions[$versionId]->status = $this->bindings[':status'];
            return true;
        }

        throw new RuntimeException('Unhandled execute SQL: ' . $this->currentSql);
    }

    public function single() {
        $sql = $this->normalizeSql($this->currentSql);

        if (strpos($sql, 'SELECT COUNT(*) AS total FROM information_schema.tables') === 0) {
            $tableName = $this->bindings[':table_name'] ?? '';
            return (object) ['total' => $tableName === 'activity_logs' ? 0 : 1];
        }

        if (strpos($sql, 'SELECT COUNT(*) AS total FROM information_schema.columns') === 0) {
            $tableName = $this->bindings[':table_name'] ?? '';
            $columnName = $this->bindings[':column_name'] ?? '';
            $exists = $tableName === 'document_relationships' && $columnName === 'management_source';
            return (object) ['total' => $exists ? 1 : 0];
        }

        if (strpos($sql, 'SELECT * FROM procedure_versions WHERE id = :id') === 0) {
            $id = (int) $this->bindings[':id'];
            return $this->procedureVersions[$id] ?? null;
        }

        if (strpos($sql, 'SELECT * FROM procedures WHERE id = :id') === 0) {
            $id = (int) $this->bindings[':id'];
            return $this->procedures[$id] ?? null;
        }

        if (strpos($sql, 'SELECT pv.* FROM procedures p INNER JOIN procedure_versions pv ON pv.id = p.current_version_id') === 0) {
            $procedureId = (int) $this->bindings[':procedure_id'];
            $procedure = $this->procedures[$procedureId] ?? null;
            if (!$procedure || empty($procedure->current_version_id)) {
                return null;
            }

            return $this->procedureVersions[(int) $procedure->current_version_id] ?? null;
        }

        if (strpos($sql, 'SELECT pv.*, p.procedure_code, p.current_version_id, p.status AS procedure_status FROM procedure_versions pv') === 0) {
            $id = (int) $this->bindings[':id'];
            $version = $this->procedureVersions[$id] ?? null;
            if (!$version) {
                return null;
            }

            $procedure = $this->procedures[(int) $version->procedure_id] ?? null;
            if (!$procedure) {
                return null;
            }

            return (object) array_merge((array) $version, [
                'procedure_code' => $procedure->procedure_code,
                'current_version_id' => $procedure->current_version_id,
                'procedure_status' => $procedure->status
            ]);
        }

        if (strpos($sql, 'SELECT * FROM procedure_versions WHERE procedure_id = :procedure_id ORDER BY created_at DESC, id DESC LIMIT 1') === 0) {
            $procedureId = (int) $this->bindings[':procedure_id'];
            $versions = array_filter($this->procedureVersions, function ($version) use ($procedureId) {
                return (int) $version->procedure_id === $procedureId;
            });

            if (empty($versions)) {
                return null;
            }

            usort($versions, function ($left, $right) {
                return ((int) $right->id <=> (int) $left->id);
            });

            return $versions[0];
        }

        throw new RuntimeException('Unhandled single SQL: ' . $this->currentSql);
    }

    public function resultSet() {
        return [];
    }

    public function lastInsertId() {
        return $this->lastInsertId;
    }

    private function inferLastInsertId() {
        $ids = array_merge(
            array_keys($this->procedures),
            array_keys($this->procedureVersions),
            array_keys($this->workflowActions),
            array_keys($this->relationships)
        );

        return empty($ids) ? 0 : max($ids);
    }

    private function nextId() {
        $this->lastInsertId++;
        return $this->lastInsertId;
    }

    private function normalizeSql($sql) {
        return preg_replace('/\s+/', ' ', trim((string) $sql));
    }
}

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function buildAuthoringService(InMemoryAuthoringDatabase $db) {
    $reflection = new ReflectionClass(ProcedureAuthoringService::class);
    $service = $reflection->newInstanceWithoutConstructor();
    $property = $reflection->getProperty('db');
    $property->setAccessible(true);
    $property->setValue($service, $db);
    return $service;
}

function workflowActionTypes(array $workflowActions) {
    return array_map(function ($action) {
        return $action->lifecycle_action_type;
    }, array_values($workflowActions));
}

function testRegisterProcedureCreatesControllingEffectiveVersion() {
    $db = new InMemoryAuthoringDatabase();
    $service = buildAuthoringService($db);

    $result = $service->registerProcedure([
        'user_id' => 9,
        'procedure_code' => 'PROC-100',
        'title' => 'Registry Procedure',
        'description' => 'Initial registration',
        'category' => 'Operations',
        'owner_office' => 'QA',
        'document_number' => 'DOC-100',
        'summary_of_change' => 'Initial issue',
        'change_type' => 'NEW',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-03-13',
        'file_path' => 'procedure.pdf'
    ]);

    $procedure = $db->procedures[$result['procedure_id']] ?? null;
    $version = $db->procedureVersions[$result['version_id']] ?? null;

    assertTrue($procedure !== null, 'Register procedure flow should create a procedure master.');
    assertTrue($version !== null, 'Register procedure flow should create an initial version.');
    assertTrue((int) $procedure->current_version_id === (int) $version->id, 'Effective initial registration should become the controlling version.');
    assertTrue($version->status === 'EFFECTIVE', 'Initial registration should persist the normalized EFFECTIVE status.');
    assertTrue(in_array('PDMS_REGISTER_PROCEDURE', workflowActionTypes($db->workflowActions), true), 'Register procedure flow should record a PDMS_REGISTER_PROCEDURE lifecycle action.');
}

function testRegisterRevisionSupersedesPreviousCurrentVersion() {
    $db = new InMemoryAuthoringDatabase([
        'procedures' => [
            10 => (object) [
                'id' => 10,
                'procedure_code' => 'PROC-200',
                'title' => 'Existing Procedure',
                'description' => 'Current description',
                'category' => 'Operations',
                'owner_office' => 'QA',
                'status' => 'ACTIVE',
                'current_version_id' => 20
            ]
        ],
        'procedure_versions' => [
            20 => (object) [
                'id' => 20,
                'procedure_id' => 10,
                'version_number' => '1.0',
                'document_number' => 'DOC-200',
                'title' => 'Existing Procedure',
                'summary_of_change' => 'Initial issue',
                'change_type' => 'NEW',
                'effective_date' => '2026-03-01',
                'registration_date' => '2026-03-01',
                'status' => 'EFFECTIVE',
                'file_path' => 'old.pdf',
                'based_on_version_id' => null,
                'created_by' => 3,
                'registered_by' => 3,
                'created_at' => '2026-03-01 00:00:20'
            ]
        ],
        'last_insert_id' => 20
    ]);
    $service = buildAuthoringService($db);

    $result = $service->registerRevisionForProcedure(10, [
        'user_id' => 9,
        'title' => 'Revised Procedure',
        'description' => 'Revised description',
        'category' => 'Operations',
        'owner_office' => 'QA',
        'document_number' => 'DOC-201',
        'summary_of_change' => 'Full revision',
        'change_type' => 'FULL_REVISION',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-03-13',
        'file_path' => 'revised.pdf'
    ]);

    $newVersion = $db->procedureVersions[$result['version_id']] ?? null;
    $previousVersion = $db->procedureVersions[20] ?? null;
    $procedure = $db->procedures[10] ?? null;
    $actionTypes = workflowActionTypes($db->workflowActions);

    assertTrue($newVersion !== null, 'Register revision flow should create a new procedure version.');
    assertTrue($newVersion->version_number === '2.0', 'Full revision should advance the major version number.');
    assertTrue((int) $procedure->current_version_id === (int) $newVersion->id, 'Effective revision should replace the controlling version pointer.');
    assertTrue($previousVersion->status === 'SUPERSEDED', 'Replacing full revisions should supersede the previous controlling version.');
    assertTrue(in_array('PDMS_REGISTER_REVISION', $actionTypes, true), 'Register revision flow should record a PDMS_REGISTER_REVISION lifecycle action.');
    assertTrue(in_array('PDMS_REGISTERED_REPLACEMENT', $actionTypes, true), 'Replacing the current version should record a replacement lifecycle action.');
}

function testSupersedingProcedureMakesTargetHistorical() {
    $db = new InMemoryAuthoringDatabase([
        'procedures' => [
            40 => (object) [
                'id' => 40,
                'procedure_code' => 'PROC-300',
                'title' => 'Target Procedure',
                'description' => 'To be replaced',
                'category' => 'Operations',
                'owner_office' => 'QA',
                'status' => 'ACTIVE',
                'current_version_id' => 30
            ]
        ],
        'procedure_versions' => [
            30 => (object) [
                'id' => 30,
                'procedure_id' => 40,
                'version_number' => '1.0',
                'document_number' => 'DOC-300',
                'title' => 'Target Procedure',
                'summary_of_change' => 'Initial issue',
                'change_type' => 'NEW',
                'effective_date' => '2026-03-01',
                'registration_date' => '2026-03-01',
                'status' => 'EFFECTIVE',
                'file_path' => 'target.pdf',
                'based_on_version_id' => null,
                'created_by' => 3,
                'registered_by' => 3,
                'created_at' => '2026-03-01 00:00:30'
            ]
        ],
        'last_insert_id' => 40
    ]);
    $service = buildAuthoringService($db);

    $result = $service->registerProcedure([
        'user_id' => 9,
        'procedure_code' => 'PROC-301',
        'title' => 'Replacement Procedure',
        'description' => 'Superseding issue',
        'category' => 'Operations',
        'owner_office' => 'QA',
        'document_number' => 'DOC-301',
        'summary_of_change' => 'Supersedes PROC-300',
        'change_type' => 'SUPERSEDING_PROCEDURE',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-03-13',
        'target_version_id' => 30,
        'relationship_type' => 'SUPERSEDES',
        'relationship_remarks' => 'Replacement',
        'file_path' => 'replacement.pdf'
    ]);

    $newProcedure = $db->procedures[$result['procedure_id']] ?? null;
    $targetProcedure = $db->procedures[40] ?? null;
    $targetVersion = $db->procedureVersions[30] ?? null;
    $actionTypes = workflowActionTypes($db->workflowActions);

    assertTrue($newProcedure !== null, 'Superseding procedure flow should create a new procedure master.');
    assertTrue($targetProcedure->status === 'SUPERSEDED', 'Superseding procedure flow should mark the replaced procedure as historical.');
    assertTrue($targetProcedure->current_version_id === null, 'Superseding procedure flow should clear the replaced procedure current-version pointer.');
    assertTrue($targetVersion->status === 'SUPERSEDED', 'Superseding procedure flow should mark the replaced version as superseded.');
    assertTrue(count($db->relationships) === 1, 'Superseding procedure flow should record a normalized SUPERSEDES relationship.');
    assertTrue(in_array('PDMS_REGISTERED_SUPERSESSION', $actionTypes, true), 'Superseding procedure flow should record a historical supersession lifecycle action for the target version.');
}

function testRescindProcedureClearsControllingPointer() {
    $db = new InMemoryAuthoringDatabase([
        'procedures' => [
            50 => (object) [
                'id' => 50,
                'procedure_code' => 'PROC-400',
                'title' => 'Rescindable Procedure',
                'description' => 'Current record',
                'category' => 'Operations',
                'owner_office' => 'QA',
                'status' => 'ACTIVE',
                'current_version_id' => 60
            ]
        ],
        'procedure_versions' => [
            60 => (object) [
                'id' => 60,
                'procedure_id' => 50,
                'version_number' => '1.0',
                'document_number' => 'DOC-400',
                'title' => 'Rescindable Procedure',
                'summary_of_change' => 'Initial issue',
                'change_type' => 'NEW',
                'effective_date' => '2026-03-10',
                'registration_date' => '2026-03-10',
                'status' => 'EFFECTIVE',
                'file_path' => 'current.pdf',
                'based_on_version_id' => null,
                'created_by' => 3,
                'registered_by' => 3,
                'created_at' => '2026-03-10 00:00:30'
            ]
        ],
        'last_insert_id' => 60
    ]);
    $service = buildAuthoringService($db);

    $service->rescindProcedure(50, 'Obsolete', 9);

    $procedure = $db->procedures[50] ?? null;
    $version = $db->procedureVersions[60] ?? null;
    $actionTypes = workflowActionTypes($db->workflowActions);

    assertTrue($procedure->status === 'RESCINDED', 'Rescind flow should mark the procedure master as rescinded.');
    assertTrue($procedure->current_version_id === null, 'Rescind flow should clear the controlling-version pointer.');
    assertTrue($version->status === 'RESCINDED', 'Rescind flow should mark the current version as rescinded.');
    assertTrue(in_array('PDMS_RESCIND', $actionTypes, true), 'Rescind flow should record a PDMS_RESCIND lifecycle action.');
}

function testArchiveHistoricalVersionMarksArchived() {
    $db = new InMemoryAuthoringDatabase([
        'procedures' => [
            70 => (object) [
                'id' => 70,
                'procedure_code' => 'PROC-500',
                'title' => 'Historical Procedure',
                'description' => 'Historical record',
                'category' => 'Operations',
                'owner_office' => 'QA',
                'status' => 'SUPERSEDED',
                'current_version_id' => null
            ]
        ],
        'procedure_versions' => [
            71 => (object) [
                'id' => 71,
                'procedure_id' => 70,
                'version_number' => '1.0',
                'document_number' => 'DOC-500',
                'title' => 'Historical Procedure',
                'summary_of_change' => 'Initial issue',
                'change_type' => 'NEW',
                'effective_date' => '2026-02-01',
                'registration_date' => '2026-02-01',
                'status' => 'SUPERSEDED',
                'file_path' => 'historical.pdf',
                'based_on_version_id' => null,
                'created_by' => 3,
                'registered_by' => 3,
                'created_at' => '2026-02-01 00:00:30'
            ]
        ],
        'last_insert_id' => 71
    ]);
    $service = buildAuthoringService($db);

    $service->archiveHistoricalVersion(71, 9);

    $version = $db->procedureVersions[71] ?? null;
    $actionTypes = workflowActionTypes($db->workflowActions);

    assertTrue($version->status === 'ARCHIVED', 'Archive flow should mark superseded or rescinded historical versions as archived.');
    assertTrue(in_array('PDMS_ARCHIVE_VERSION', $actionTypes, true), 'Archive flow should record a PDMS_ARCHIVE_VERSION lifecycle action.');
}

function runRegressionSuite() {
    testRegisterProcedureCreatesControllingEffectiveVersion();
    testRegisterRevisionSupersedesPreviousCurrentVersion();
    testSupersedingProcedureMakesTargetHistorical();
    testRescindProcedureClearsControllingPointer();
    testArchiveHistoricalVersionMarksArchived();
    echo "Lifecycle service flow regression: OK\n";
}

runRegressionSuite();
