<?php
require_once __DIR__ . '/../helpers/pdms_authoring_options.php';

class ProcedureReadModel {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    private function sqlInList(array $values) {
        return '"' . implode('", "', $values) . '"';
    }

    private function tableExists($tableName) {
        try {
            $this->db->query(
                'SELECT COUNT(*) AS total
                 FROM information_schema.tables
                 WHERE table_schema = :table_schema
                   AND table_name = :table_name'
            );
            $this->db->bind(':table_schema', DB_NAME);
            $this->db->bind(':table_name', $tableName);

            $row = $this->db->single();
            return $row && (int) $row->total > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function columnExists($tableName, $columnName) {
        try {
            $this->db->query(
                'SELECT COUNT(*) AS total
                 FROM information_schema.columns
                 WHERE table_schema = :table_schema
                   AND table_name = :table_name
                   AND column_name = :column_name'
            );
            $this->db->bind(':table_schema', DB_NAME);
            $this->db->bind(':table_name', $tableName);
            $this->db->bind(':column_name', $columnName);

            $row = $this->db->single();
            return $row && (int) $row->total > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function hasPdmsFoundation() {
        return $this->tableExists('procedures')
            && $this->tableExists('procedure_versions')
            && $this->tableExists('document_relationships');
    }

    public function hasWorkflowTable() {
        return $this->tableExists('workflow_actions');
    }

    public function hasSectionHistoryFoundation() {
        return $this->tableExists('procedure_sections')
            && $this->tableExists('section_change_log');
    }

    private function normalizeStatusProperty($record, $property) {
        if ($record && isset($record->$property)) {
            $record->$property = PdmsAuthoringOptions::normalizeWorkflowStatus($record->$property, (string) $record->$property);
        }

        return $record;
    }

    private function normalizeStatusCollection(array $records, array $properties) {
        foreach ($records as $record) {
            foreach ($properties as $property) {
                $this->normalizeStatusProperty($record, $property);
            }
        }

        return $records;
    }

    public function getProcedureOverviewById($procedureId) {
        $this->db->query(
            'SELECT
                p.*,
                pv.id AS current_version_id,
                pv.version_number AS current_version_number,
                pv.document_number AS current_document_number,
                pv.change_type AS current_change_type,
                pv.summary_of_change AS current_summary_of_change,
                pv.legacy_post_id AS current_legacy_post_id,
                pv.status AS current_version_status,
                pv.effective_date AS current_effective_date,
                pv.file_path AS current_file_path
             FROM procedures p
             LEFT JOIN procedure_versions pv ON pv.id = p.current_version_id
             WHERE p.id = :procedure_id'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        $overview = $this->db->single();

        return $this->normalizeStatusProperty($this->applyHistoricalAnchorVersion($overview), 'current_version_status');
    }

    public function getProcedureDashboard($search = '') {
        if (!$this->hasPdmsFoundation()) {
            return [];
        }

        $sql = 'SELECT
                    p.id,
                    p.procedure_code,
                    p.title,
                    p.description,
                    p.status,
                    p.owner_office,
                    pv.id AS current_version_id,
                    pv.version_number AS current_version_number,
                    pv.document_number AS current_document_number,
                    pv.change_type AS current_change_type,
                    pv.status AS current_version_status,
                    pv.effective_date AS current_effective_date,
                    pv.file_path AS current_file_path
                FROM procedures p
                LEFT JOIN procedure_versions pv ON pv.id = p.current_version_id
                WHERE p.status NOT IN (' . $this->sqlInList(PdmsAuthoringOptions::terminalProcedureStatuses()) . ')';

        if ($search !== '') {
            $sql .= ' AND (
                        p.procedure_code LIKE :search
                        OR p.title LIKE :search
                        OR p.description LIKE :search
                        OR pv.document_number LIKE :search
                    )';
        }

        $sql .= ' ORDER BY
                    CASE WHEN pv.status = "EFFECTIVE" THEN 0 ELSE 1 END,
                    pv.effective_date DESC,
                    p.updated_at DESC';

        $this->db->query($sql);

        if ($search !== '') {
            $this->db->bind(':search', '%' . $search . '%');
        }

        return $this->normalizeStatusCollection($this->db->resultSet(), ['current_version_status']);
    }

    public function getBackfillStatus() {
        $status = [
            'total_posts' => 0,
            'mapped_versions' => 0,
            'unmapped_posts' => 0
        ];

        try {
            $this->db->query('SELECT COUNT(*) AS total FROM posts');
            $status['total_posts'] = (int) ($this->db->single()->total ?? 0);
        } catch (PDOException $e) {
            return $status;
        }

        if (!$this->hasPdmsFoundation()) {
            $status['unmapped_posts'] = $status['total_posts'];
            return $status;
        }

        $this->db->query('SELECT COUNT(*) AS total FROM procedure_versions WHERE legacy_post_id IS NOT NULL');
        $status['mapped_versions'] = (int) ($this->db->single()->total ?? 0);
        $status['unmapped_posts'] = max(0, $status['total_posts'] - $status['mapped_versions']);

        return $status;
    }

    public function getHistoryByProcedureId($procedureId) {
        $this->db->query(
            'SELECT *
             FROM procedure_versions
             WHERE procedure_id = :procedure_id
             ORDER BY effective_date DESC, created_at DESC'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        return $this->normalizeStatusCollection($this->db->resultSet(), ['status']);
    }

    public function getLatestVersionByProcedureId($procedureId) {
        $this->db->query(
            'SELECT *
             FROM procedure_versions
             WHERE procedure_id = :procedure_id
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function getRelationshipSummaryByVersionId($versionId) {
        $this->db->query(
            'SELECT
                dr.*,
                src.title AS source_title,
                src.version_number AS source_version_number,
                tgt.title AS target_title,
                tgt.version_number AS target_version_number
             FROM document_relationships dr
             INNER JOIN procedure_versions src ON src.id = dr.source_version_id
             INNER JOIN procedure_versions tgt ON tgt.id = dr.target_version_id
             WHERE dr.source_version_id = :version_id OR dr.target_version_id = :version_id
             ORDER BY dr.created_at DESC'
        );
        $this->db->bind(':version_id', (int) $versionId, PDO::PARAM_INT);
        return $this->normalizeStatusCollection($this->db->resultSet(), ['from_status', 'to_status']);
    }

    public function getWorkflowActionsByVersionId($versionId) {
        if (!$this->hasWorkflowTable()) {
            return [];
        }

        $this->db->query(
            'SELECT *
             FROM workflow_actions
             WHERE procedure_version_id = :version_id
             ORDER BY acted_at DESC, id DESC'
        );
        $this->db->bind(':version_id', (int) $versionId, PDO::PARAM_INT);
        return $this->normalizeStatusCollection($this->db->resultSet(), ['from_status', 'to_status']);
    }

    public function getSectionChangeLogByVersionId($versionId) {
        if (!$this->hasSectionHistoryFoundation()) {
            return [];
        }

        $this->db->query(
            'SELECT
                scl.*,
                ps.section_key,
                ps.section_title,
                pv.version_number,
                pv.title AS version_title
             FROM section_change_log scl
             INNER JOIN procedure_sections ps ON ps.id = scl.procedure_section_id
             INNER JOIN procedure_versions pv ON pv.id = scl.procedure_version_id
             WHERE scl.procedure_version_id = :version_id
             ORDER BY scl.created_at DESC, scl.id DESC'
        );
        $this->db->bind(':version_id', (int) $versionId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getSectionHistoryByProcedureId($procedureId, $limit = 25) {
        if (!$this->hasSectionHistoryFoundation()) {
            return [];
        }

        $this->db->query(
            'SELECT
                scl.*,
                ps.section_key,
                ps.section_title,
                pv.version_number,
                pv.title AS version_title
             FROM section_change_log scl
             INNER JOIN procedure_sections ps ON ps.id = scl.procedure_section_id
             INNER JOIN procedure_versions pv ON pv.id = scl.procedure_version_id
             WHERE ps.procedure_id = :procedure_id
             ORDER BY scl.created_at DESC, scl.id DESC
             LIMIT :limit'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        $this->db->bind(':limit', (int) $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getProcedureDashboardDetail($procedureId) {
        if (!$this->hasPdmsFoundation()) {
            return null;
        }

        $overview = $this->getProcedureOverviewById($procedureId);

        if (!$overview) {
            return null;
        }

        $history = $this->getHistoryByProcedureId($procedureId);
        $latestVersion = $this->getLatestVersionByProcedureId($procedureId);
        $relationships = [];
        $workflowActions = [];
        $sectionHistory = $this->getSectionHistoryByProcedureId($procedureId);

        if (!empty($overview->current_version_id)) {
            $relationships = $this->getRelationshipSummaryByVersionId($overview->current_version_id);
            $workflowActions = $this->getWorkflowActionsByVersionId($overview->current_version_id);
        }

        return [
            'overview' => $overview,
            'history' => $history,
            'latest_version' => $latestVersion,
            'relationships' => $relationships,
            'workflow_actions' => $workflowActions,
            'section_history' => $sectionHistory
        ];
    }

    public function getVersionDetailById($versionId) {
        if (!$this->hasPdmsFoundation()) {
            return null;
        }

        $this->db->query(
            'SELECT
                pv.*,
                p.procedure_code,
                p.status AS procedure_status,
                p.current_version_id,
                current_pv.version_number AS current_version_number
             FROM procedure_versions pv
             INNER JOIN procedures p ON p.id = pv.procedure_id
             LEFT JOIN procedure_versions current_pv ON current_pv.id = p.current_version_id
             WHERE pv.id = :version_id
             LIMIT 1'
        );
        $this->db->bind(':version_id', (int) $versionId, PDO::PARAM_INT);
        $version = $this->db->single();

        if (!$version) {
            return null;
        }

        $version = $this->normalizeStatusProperty($version, 'status');

        return [
            'version' => $version,
            'relationships' => $this->getRelationshipSummaryByVersionId((int) $versionId),
            'workflow_actions' => $this->getWorkflowActionsByVersionId((int) $versionId),
            'history' => $this->getHistoryByProcedureId((int) $version->procedure_id),
            'section_history' => $this->getSectionChangeLogByVersionId((int) $versionId)
        ];
    }

    public function getProcedureOverviewByLegacyPostId($legacyPostId) {
        if (!$this->hasPdmsFoundation()) {
            return null;
        }

        $this->db->query(
            'SELECT
                p.*,
                pv_current.id AS current_version_id,
                pv_current.version_number AS current_version_number,
                pv_current.document_number AS current_document_number,
                pv_current.change_type AS current_change_type,
                pv_current.status AS current_version_status,
                pv_current.effective_date AS current_effective_date,
                pv_current.file_path AS current_file_path,
                pv_lookup.id AS mapped_version_id,
                pv_lookup.version_number AS mapped_version_number,
                pv_lookup.change_type AS mapped_change_type,
                pv_lookup.status AS mapped_version_status,
                pv_lookup.effective_date AS mapped_effective_date
             FROM procedure_versions pv_lookup
             INNER JOIN procedures p ON p.id = pv_lookup.procedure_id
             LEFT JOIN procedure_versions pv_current ON pv_current.id = p.current_version_id
             WHERE pv_lookup.legacy_post_id = :legacy_post_id
             LIMIT 1'
        );
        $this->db->bind(':legacy_post_id', (int) $legacyPostId, PDO::PARAM_INT);
        $overview = $this->db->single();

        $overview = $this->applyHistoricalAnchorVersion($overview);
        $overview = $this->normalizeStatusProperty($overview, 'current_version_status');
        $overview = $this->normalizeStatusProperty($overview, 'mapped_version_status');

        return $overview;
    }

    public function getLegacyPostRelationshipSummary($legacyPostId) {
        $this->db->query(
            'SELECT
                p.id,
                p.title,
                p.reference_number,
                p.date_of_effectivity,
                CASE
                    WHEN p.id = current_post.id THEN "SELF"
                    WHEN p.id = current_post.amended_post_id THEN "AMENDS"
                    WHEN p.id = current_post.superseded_post_id THEN "SUPERSEDES"
                    WHEN p.amended_post_id = current_post.id THEN "AMENDED_BY"
                    WHEN p.superseded_post_id = current_post.id THEN "SUPERSEDED_BY"
                    ELSE "RELATED"
                END AS relationship_type
             FROM posts current_post
             INNER JOIN posts p
                ON p.id = current_post.id
                OR p.id = current_post.amended_post_id
                OR p.id = current_post.superseded_post_id
                OR p.amended_post_id = current_post.id
                OR p.superseded_post_id = current_post.id
             WHERE current_post.id = :legacy_post_id
             ORDER BY p.date_of_effectivity DESC, p.id DESC'
        );
        $this->db->bind(':legacy_post_id', (int) $legacyPostId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getNormalizedPostSnapshot($legacyPostId) {
        $this->db->query(
            'SELECT
                p.id AS legacy_post_id,
                p.reference_number AS procedure_code,
                p.reference_number AS document_number,
                p.title,
                p.description,
                p.date_of_effectivity AS effective_date,
                p.upload_date AS created_at,
                p.file AS file_path,
                p.amended_post_id,
                p.superseded_post_id
             FROM posts p
             WHERE p.id = :legacy_post_id'
        );
        $this->db->bind(':legacy_post_id', (int) $legacyPostId, PDO::PARAM_INT);
        $snapshot = $this->db->single();

        if ($snapshot) {
            $snapshot->inferred_change_type = PdmsAuthoringOptions::inferLegacyChangeTypeFromLinks(
                $snapshot->amended_post_id ?? null,
                $snapshot->superseded_post_id ?? null
            );
        }

        return $snapshot;
    }

    public function getPostDetailContext($legacyPostId) {
        $legacySnapshot = $this->getNormalizedPostSnapshot($legacyPostId);
        $legacyRelationships = $this->getLegacyPostRelationshipSummary($legacyPostId);

        $context = [
            'source' => 'legacy',
            'has_pdms_tables' => $this->hasPdmsFoundation(),
            'procedure_overview' => null,
            'history' => [],
            'relationships' => [],
            'workflow_actions' => [],
            'section_history' => [],
            'legacy_snapshot' => $legacySnapshot,
            'legacy_relationships' => $legacyRelationships,
            'legacy_timeline' => $this->buildLegacyTimeline($legacySnapshot, $legacyRelationships)
        ];

        if (!$context['has_pdms_tables']) {
            return $context;
        }

        $procedureOverview = $this->getProcedureOverviewByLegacyPostId($legacyPostId);

        if (!$procedureOverview) {
            return $context;
        }

        $history = $this->getHistoryByProcedureId($procedureOverview->id);
        $relationships = [];
        $workflowActions = [];
        $sectionHistory = $this->getSectionHistoryByProcedureId((int) $procedureOverview->id, 12);

        $contextVersionId = null;

        if (!empty($procedureOverview->mapped_version_id)) {
            $contextVersionId = (int) $procedureOverview->mapped_version_id;
        } elseif (!empty($procedureOverview->current_version_id)) {
            $contextVersionId = (int) $procedureOverview->current_version_id;
        }

        if (!empty($contextVersionId)) {
            $relationships = $this->getRelationshipSummaryByVersionId($contextVersionId);
            $workflowActions = $this->getWorkflowActionsByVersionId($contextVersionId);
        }

        $context['source'] = 'pdms';
        $context['procedure_overview'] = $procedureOverview;
        $context['history'] = $history;
        $context['relationships'] = $relationships;
        $context['workflow_actions'] = $workflowActions;
        $context['section_history'] = $sectionHistory;

        return $context;
    }

    public function getAuthoringMetadataByLegacyPostId($legacyPostId) {
        $metadata = [
            'is_mapped' => false,
            'change_type' => '',
            'workflow_status' => 'EFFECTIVE',
            'pdms_relationship_type' => '',
            'affected_sections' => '',
            'relationship_remarks' => ''
        ];

        if (!$this->hasPdmsFoundation()) {
            return $metadata;
        }

        $this->db->query(
            'SELECT pv.id, pv.change_type, pv.status
             FROM procedure_versions pv
             WHERE pv.legacy_post_id = :legacy_post_id
             LIMIT 1'
        );
        $this->db->bind(':legacy_post_id', (int) $legacyPostId, PDO::PARAM_INT);
        $version = $this->db->single();

        if (!$version) {
            return $metadata;
        }

        $metadata['is_mapped'] = true;
        $metadata['change_type'] = $version->change_type ?? '';
        $metadata['workflow_status'] = PdmsAuthoringOptions::normalizeWorkflowStatus($version->status ?? 'EFFECTIVE', 'EFFECTIVE');

        $this->db->query(
            'SELECT relationship_type, affected_sections, remarks
             FROM document_relationships
             WHERE source_version_id = :source_version_id
             ORDER BY id DESC
             LIMIT 1'
        );
        $this->db->bind(':source_version_id', (int) $version->id, PDO::PARAM_INT);
        $relationship = $this->db->single();

        if ($relationship) {
            $metadata['pdms_relationship_type'] = $relationship->relationship_type ?? '';
            $metadata['affected_sections'] = $relationship->affected_sections ?? '';
            $metadata['relationship_remarks'] = $relationship->remarks ?? '';
        }

        return $metadata;
    }

    private function buildLegacyTimeline($legacySnapshot, $legacyRelationships) {
        $timeline = [];

        if ($legacySnapshot) {
            $timeline[] = (object) [
                'label' => 'LEGACY_RECORD',
                'title' => $legacySnapshot->title,
                'date' => $legacySnapshot->effective_date,
                'note' => 'Current SOP detail is still sourced from the legacy posts table.'
            ];
        }

        foreach ($legacyRelationships as $relationship) {
            if ($relationship->relationship_type === 'SELF') {
                continue;
            }

            $timeline[] = (object) [
                'label' => $relationship->relationship_type,
                'title' => $relationship->title,
                'date' => $relationship->date_of_effectivity,
                'note' => $relationship->reference_number
            ];
        }

        usort($timeline, function ($left, $right) {
            return strcmp((string) ($right->date ?? ''), (string) ($left->date ?? ''));
        });

        return $timeline;
    }

    private function getLatestHistoricalAnchorVersion($procedureId) {
        $this->db->query(
            'SELECT
                pv.id,
                pv.version_number,
                pv.document_number,
                pv.change_type,
                pv.summary_of_change,
                pv.legacy_post_id,
                pv.status,
                pv.effective_date,
                pv.file_path
             FROM procedure_versions pv
             WHERE pv.procedure_id = :procedure_id
             ORDER BY
                pv.effective_date DESC,
                pv.created_at DESC,
                pv.id DESC
             LIMIT 1'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        return $this->db->single();
    }

    private function applyHistoricalAnchorVersion($overview) {
        if (!$overview || !empty($overview->current_version_id)) {
            return $overview;
        }

        if (!in_array((string) ($overview->status ?? ''), PdmsAuthoringOptions::terminalProcedureStatuses(), true)) {
            return $overview;
        }

        $anchorVersion = $this->getLatestHistoricalAnchorVersion((int) $overview->id);
        if (!$anchorVersion) {
            return $overview;
        }

        $overview->current_version_id = $anchorVersion->id;
        $overview->current_version_number = $anchorVersion->version_number;
        $overview->current_document_number = $anchorVersion->document_number;
        $overview->current_change_type = $anchorVersion->change_type;
        $overview->current_summary_of_change = $anchorVersion->summary_of_change;
        $overview->current_legacy_post_id = $anchorVersion->legacy_post_id;
        $overview->current_version_status = PdmsAuthoringOptions::normalizeWorkflowStatus($anchorVersion->status, $anchorVersion->status);
        $overview->current_effective_date = $anchorVersion->effective_date;
        $overview->current_file_path = $anchorVersion->file_path;
        $overview->historical_anchor_version = true;

        return $overview;
    }
}
?>
