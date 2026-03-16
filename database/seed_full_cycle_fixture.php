<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/helpers/pdms_authoring_options.php';
require_once __DIR__ . '/../app/models/ProcedureAuthoringService.php';

function execSql(PDO $pdo, $sql) {
    $pdo->exec($sql);
}

function fetchOne(PDO $pdo, $sql, array $params = []) {
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
}

function fetchAllRows(PDO $pdo, $sql, array $params = []) {
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function tableExists(PDO $pdo, $tableName) {
    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM information_schema.tables
         WHERE table_schema = :schema_name
           AND table_name = :table_name'
    );
    $statement->execute([
        ':schema_name' => DB_NAME,
        ':table_name' => $tableName
    ]);

    $row = $statement->fetch(PDO::FETCH_ASSOC);
    return $row && (int) $row['total'] > 0;
}

function resetProceduralData(PDO $pdo) {
    $tables = [
        'section_change_log',
        'procedure_sections',
        'document_relationships',
        'workflow_actions',
        'procedure_versions',
        'procedures',
        'activity_logs'
    ];

    execSql($pdo, 'SET FOREIGN_KEY_CHECKS = 0');

    foreach ($tables as $table) {
        if (tableExists($pdo, $table)) {
            execSql($pdo, 'TRUNCATE TABLE `' . $table . '`');
        }
    }

    execSql($pdo, 'SET FOREIGN_KEY_CHECKS = 1');
}

function resolveActorIds(PDO $pdo) {
    $users = fetchAllRows(
        $pdo,
        "SELECT id, email, role, status
         FROM users
         WHERE status = 'active'
         ORDER BY
            CASE role
                WHEN 'super_admin' THEN 1
                WHEN 'admin' THEN 2
                ELSE 3
            END,
            id ASC"
    );

    if (empty($users)) {
        throw new RuntimeException('No active users were found. Add at least one active admin or super admin before seeding.');
    }

    $primary = null;
    $secondary = null;

    foreach ($users as $user) {
        if ($primary === null && in_array($user['role'], ['super_admin', 'admin'], true)) {
            $primary = $user;
            continue;
        }

        if ($secondary === null && in_array($user['role'], ['super_admin', 'admin'], true)) {
            $secondary = $user;
            continue;
        }
    }

    if ($primary === null) {
        $primary = $users[0];
    }

    if ($secondary === null) {
        $secondary = $primary;
    }

    return [
        'primary_id' => (int) $primary['id'],
        'secondary_id' => (int) $secondary['id'],
        'users' => [$primary, $secondary]
    ];
}

function setCurrentVersionPointer(PDO $pdo, $procedureId, $versionId) {
    $statement = $pdo->prepare(
        'UPDATE procedures
         SET current_version_id = :current_version_id
         WHERE id = :id'
    );
    $statement->execute([
        ':current_version_id' => (int) $versionId,
        ':id' => (int) $procedureId
    ]);
}

function fetchProcedureVersionStatuses(PDO $pdo, $procedureId) {
    return fetchAllRows(
        $pdo,
        'SELECT id, version_number, change_type, status
         FROM procedure_versions
         WHERE procedure_id = :procedure_id
         ORDER BY id ASC',
        [':procedure_id' => (int) $procedureId]
    );
}

function archiveVersionsWithStatuses(PDO $pdo, ProcedureAuthoringService $service, $procedureId, $userId, array $statuses) {
    foreach (fetchProcedureVersionStatuses($pdo, $procedureId) as $version) {
        if (in_array($version['status'], $statuses, true)) {
            $service->archiveHistoricalVersion((int) $version['id'], $userId);
        }
    }
}

function createRegisteredProcedure(PDO $pdo, ProcedureAuthoringService $service, array $actors, array $data) {
    $registered = $service->registerProcedure([
        'user_id' => $actors['primary_id'],
        'procedure_code' => $data['procedure_code'],
        'title' => $data['title'],
        'description' => $data['description'],
        'category' => $data['category'],
        'owner_office' => $data['owner_office'],
        'document_number' => $data['document_number'],
        'summary_of_change' => $data['summary_of_change'],
        'change_type' => $data['change_type'] ?? 'NEW',
        'status' => 'REGISTERED',
        'effective_date' => $data['effective_date'],
        'registration_date' => $data['registration_date'],
        'relationship_type' => $data['relationship_type'] ?? '',
        'target_version_id' => $data['target_version_id'] ?? null,
        'affected_sections' => $data['affected_sections'] ?? null,
        'relationship_remarks' => $data['relationship_remarks'] ?? null,
        'file_path' => $data['file_path']
    ]);

    setCurrentVersionPointer($pdo, $registered['procedure_id'], $registered['version_id']);
    $service->markCurrentVersionEffective($registered['procedure_id'], $actors['secondary_id']);

    return $registered;
}

function seedPalayProcurement(PDO $pdo, ProcedureAuthoringService $service, array $actors, array $files) {
    $base = createRegisteredProcedure($pdo, $service, $actors, [
        'procedure_code' => 'PALAY-PROC-001',
        'title' => 'Palay Procurement',
        'description' => 'Registry sample for palay procurement activities.',
        'category' => 'Operations',
        'owner_office' => 'CPMSD',
        'document_number' => 'PALAY-PROC-001-2026',
        'summary_of_change' => 'Initial registry capture for palay procurement.',
        'registration_date' => '2026-01-06',
        'effective_date' => '2026-01-10',
        'file_path' => $files[0]
    ]);

    $amendment = $service->registerRevisionForProcedure($base['procedure_id'], [
        'user_id' => $actors['secondary_id'],
        'title' => 'Palay Procurement',
        'description' => 'Registry sample for palay procurement activities with amended controls.',
        'category' => 'Operations',
        'owner_office' => 'CPMSD',
        'document_number' => 'PALAY-PROC-001-2026',
        'summary_of_change' => 'Amendment updates approval routing and required procurement forms.',
        'change_type' => 'AMENDMENT',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-01-22',
        'registration_date' => '2026-01-18',
        'relationship_type' => 'AMENDS',
        'affected_sections' => '1. Purpose; 4. Procurement Steps; 6. Documentation',
        'relationship_remarks' => 'Amendment clarifies field documentation and sign-off requirements.',
        'file_path' => $files[1]
    ]);

    $partial = $service->registerRevisionForProcedure($base['procedure_id'], [
        'user_id' => $actors['primary_id'],
        'title' => 'Palay Procurement',
        'description' => 'Registry sample for palay procurement activities with revised operational controls.',
        'category' => 'Operations',
        'owner_office' => 'CPMSD',
        'document_number' => 'PALAY-PROC-001-2026',
        'summary_of_change' => 'Partial revision updates supplier verification and lot acceptance steps.',
        'change_type' => 'PARTIAL_REVISION',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-02-04',
        'registration_date' => '2026-01-30',
        'relationship_type' => 'REVISES',
        'affected_sections' => '2. Scope; 4. Procurement Steps; 7. Monitoring Controls',
        'relationship_remarks' => 'Partial revision refreshes the supplier verification path.',
        'file_path' => $files[2]
    ]);

    $full = $service->registerRevisionForProcedure($base['procedure_id'], [
        'user_id' => $actors['secondary_id'],
        'title' => 'Palay Procurement',
        'description' => 'Registry sample for palay procurement activities in its current controlling form.',
        'category' => 'Operations',
        'owner_office' => 'CPMSD',
        'document_number' => 'PALAY-PROC-001-2026',
        'summary_of_change' => 'Full revision consolidates earlier amendments and revisions into the current release.',
        'change_type' => 'FULL_REVISION',
        'status' => 'EFFECTIVE',
        'effective_date' => '2026-02-18',
        'registration_date' => '2026-02-12',
        'relationship_type' => 'REVISES',
        'affected_sections' => '2. Scope; 4. Procurement Steps; 6. Documentation; 7. Monitoring Controls',
        'relationship_remarks' => 'Full revision issues the current controlling palay procurement document.',
        'file_path' => $files[3]
    ]);

    archiveVersionsWithStatuses($pdo, $service, $base['procedure_id'], $actors['primary_id'], ['SUPERSEDED']);

    return [
        'family' => 'Palay Procurement',
        'procedure_ids' => [$base['procedure_id']],
        'version_ids' => [$base['version_id'], $amendment['version_id'], $partial['version_id'], $full['version_id']]
    ];
}

function seedPalayMilling(PDO $pdo, ProcedureAuthoringService $service, array $actors, array $files) {
    $base = createRegisteredProcedure($pdo, $service, $actors, [
        'procedure_code' => 'PALAY-MILL-002',
        'title' => 'Palay Milling',
        'description' => 'Registry sample for palay milling operations.',
        'category' => 'Operations',
        'owner_office' => 'NFA Region 3',
        'document_number' => 'PALAY-MILL-002-2026',
        'summary_of_change' => 'Initial registry capture for palay milling.',
        'registration_date' => '2026-01-08',
        'effective_date' => '2026-01-13',
        'file_path' => $files[0]
    ]);

    $rescissionNotice = createRegisteredProcedure($pdo, $service, $actors, [
        'procedure_code' => 'PALAY-MILL-002-RN',
        'title' => 'Palay Milling Rescission Notice',
        'description' => 'Formal registry notice documenting the rescission of palay milling operations.',
        'category' => 'Operations',
        'owner_office' => 'NFA Region 3',
        'document_number' => 'PALAY-MILL-002-RN-2026',
        'summary_of_change' => 'Rescission notice records the withdrawal of the palay milling procedure.',
        'change_type' => 'RESCISSION',
        'relationship_type' => 'RESCINDS',
        'target_version_id' => $base['version_id'],
        'relationship_remarks' => 'Formal notice for the rescission of the palay milling procedure.',
        'registration_date' => '2026-02-08',
        'effective_date' => '2026-02-12',
        'file_path' => $files[1]
    ]);

    $service->rescindProcedure(
        $base['procedure_id'],
        'Palay milling was rescinded after the operation was transferred to an external service arrangement.',
        $actors['primary_id']
    );

    return [
        'family' => 'Palay Milling',
        'procedure_ids' => [$base['procedure_id'], $rescissionNotice['procedure_id']],
        'version_ids' => [$base['version_id'], $rescissionNotice['version_id']]
    ];
}

function seedDistribution(PDO $pdo, ProcedureAuthoringService $service, array $actors, array $files) {
    $legacy = createRegisteredProcedure($pdo, $service, $actors, [
        'procedure_code' => 'DIST-003-OLD',
        'title' => 'Procedure on Distribution',
        'description' => 'Legacy distribution procedure retained for supersession examples.',
        'category' => 'Distribution',
        'owner_office' => 'AO',
        'document_number' => 'DIST-003-2025',
        'summary_of_change' => 'Initial distribution registry record before replacement.',
        'registration_date' => '2025-12-10',
        'effective_date' => '2025-12-15',
        'file_path' => $files[0]
    ]);

    $replacement = createRegisteredProcedure($pdo, $service, $actors, [
        'procedure_code' => 'DIST-003',
        'title' => 'Procedure on Distribution',
        'description' => 'Current distribution procedure replacing the legacy distribution flow.',
        'category' => 'Distribution',
        'owner_office' => 'AO',
        'document_number' => 'DIST-003-2026',
        'summary_of_change' => 'Superseding procedure replaces the prior distribution record in full.',
        'change_type' => 'SUPERSEDING_PROCEDURE',
        'relationship_type' => 'SUPERSEDES',
        'target_version_id' => $legacy['version_id'],
        'relationship_remarks' => 'Current distribution procedure supersedes the legacy distribution document.',
        'registration_date' => '2026-02-20',
        'effective_date' => '2026-02-24',
        'file_path' => $files[1]
    ]);

    archiveVersionsWithStatuses($pdo, $service, $legacy['procedure_id'], $actors['secondary_id'], ['SUPERSEDED']);

    return [
        'family' => 'Procedure on Distribution',
        'procedure_ids' => [$legacy['procedure_id'], $replacement['procedure_id']],
        'version_ids' => [$legacy['version_id'], $replacement['version_id']]
    ];
}

function createFixtureData(PDO $pdo, ProcedureAuthoringService $service, array $actors) {
    $files = [
        '66e7abba8ee278.61953244.pdf',
        '66e7abf1bc3823.92893912.pdf',
        '66e7ac572d5c11.81833000.pdf',
        '66e7ac941e2fe9.90256384.pdf',
        '66e7acd29664e4.87659395.pdf',
        '66e7ad330119f2.21497981.pdf',
        '66e7ad787c67a3.83102008.pdf'
    ];

    return [
        seedPalayProcurement($pdo, $service, $actors, array_slice($files, 0, 4)),
        seedPalayMilling($pdo, $service, $actors, array_slice($files, 4, 2)),
        seedDistribution($pdo, $service, $actors, array_slice($files, 5, 2))
    ];
}

function printSummary(PDO $pdo, array $actors) {
    $queries = [
        'users_preserved' => 'SELECT COUNT(*) AS total FROM users',
        'procedures' => 'SELECT COUNT(*) AS total FROM procedures',
        'versions' => 'SELECT COUNT(*) AS total FROM procedure_versions',
        'relationships' => 'SELECT COUNT(*) AS total FROM document_relationships',
        'workflow_actions' => 'SELECT COUNT(*) AS total FROM workflow_actions',
        'sections' => 'SELECT COUNT(*) AS total FROM procedure_sections',
        'section_changes' => 'SELECT COUNT(*) AS total FROM section_change_log',
        'activity_logs' => 'SELECT COUNT(*) AS total FROM activity_logs'
    ];

    echo "Procedural data reset complete. Existing users were preserved.\n";

    foreach ($queries as $label => $sql) {
        $row = fetchOne($pdo, $sql);
        echo strtoupper($label) . ': ' . ($row['total'] ?? 0) . "\n";
    }

    echo "\nProcedure master statuses:\n";
    foreach (fetchAllRows(
        $pdo,
        'SELECT procedure_code, title, status
         FROM procedures
         ORDER BY id ASC'
    ) as $row) {
        echo $row['procedure_code'] . ' | ' . $row['title'] . ' | ' . $row['status'] . "\n";
    }

    echo "\nVersion status distribution:\n";
    foreach (fetchAllRows(
        $pdo,
        'SELECT status, COUNT(*) AS total
         FROM procedure_versions
         GROUP BY status
         ORDER BY status'
    ) as $row) {
        echo $row['status'] . ': ' . $row['total'] . "\n";
    }

    echo "\nChange type coverage:\n";
    foreach (fetchAllRows(
        $pdo,
        'SELECT change_type, COUNT(*) AS total
         FROM procedure_versions
         GROUP BY change_type
         ORDER BY change_type'
    ) as $row) {
        echo $row['change_type'] . ': ' . $row['total'] . "\n";
    }

    echo "\nLifecycle per procedure family:\n";
    foreach (fetchAllRows(
        $pdo,
        'SELECT
            p.procedure_code,
            p.title,
            pv.version_number,
            pv.change_type,
            pv.status
         FROM procedures p
         INNER JOIN procedure_versions pv ON pv.procedure_id = p.id
         ORDER BY p.id ASC, pv.id ASC'
    ) as $row) {
        echo $row['procedure_code'] . ' | ' . $row['title'] . ' | v' . $row['version_number'] . ' | ' . $row['change_type'] . ' | ' . $row['status'] . "\n";
    }

    echo "\nCurrent controlling procedures:\n";
    foreach (fetchAllRows(
        $pdo,
        'SELECT
            p.procedure_code,
            p.title,
            pv.version_number,
            pv.status,
            pv.effective_date
         FROM procedures p
         INNER JOIN procedure_versions pv ON pv.id = p.current_version_id
         ORDER BY p.id ASC'
    ) as $row) {
        echo $row['procedure_code'] . ' | ' . $row['title'] . ' | v' . $row['version_number'] . ' | ' . $row['status'] . ' | ' . $row['effective_date'] . "\n";
    }

    echo "\nSeed actors reused from existing users:\n";
    foreach ($actors['users'] as $user) {
        echo $user['role'] . ' -> ' . $user['email'] . "\n";
    }
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

$actors = resolveActorIds($pdo);
resetProceduralData($pdo);

$service = new ProcedureAuthoringService();
createFixtureData($pdo, $service, $actors);
printSummary($pdo, $actors);
