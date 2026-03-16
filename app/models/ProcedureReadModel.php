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

    private function normalizeDashboardFilters($search = '', array $filters = []) {
        return [
            'search' => trim((string) $search),
            'responsibility_center' => trim((string) ($filters['responsibility_center'] ?? '')),
            'date_from' => trim((string) ($filters['date_from'] ?? '')),
            'date_to' => trim((string) ($filters['date_to'] ?? ''))
        ];
    }

    private function applyDashboardFiltersToSql($sql, array $filters, $includeDocumentNumber = true) {
        if ($filters['search'] !== '') {
            $sql .= ' AND (
                        p.procedure_code LIKE :search
                        OR p.title LIKE :search
                        OR p.description LIKE :search';

            if ($includeDocumentNumber) {
                $sql .= '
                        OR pv.document_number LIKE :search';
            }

            $sql .= '
                    )';
        }

        if ($filters['responsibility_center'] !== '') {
            $sql .= ' AND p.owner_office = :responsibility_center';
        }

        if ($filters['date_from'] !== '') {
            $sql .= ' AND pv.effective_date >= :date_from';
        }

        if ($filters['date_to'] !== '') {
            $sql .= ' AND pv.effective_date <= :date_to';
        }

        return $sql;
    }

    private function bindDashboardFilters(array $filters) {
        if ($filters['search'] !== '') {
            $this->db->bind(':search', '%' . $filters['search'] . '%');
        }

        if ($filters['responsibility_center'] !== '') {
            $this->db->bind(':responsibility_center', $filters['responsibility_center']);
        }

        if ($filters['date_from'] !== '') {
            $this->db->bind(':date_from', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $this->db->bind(':date_to', $filters['date_to']);
        }
    }

    public function getDashboardResponsibilityCenters() {
        if (!$this->hasPdmsFoundation()) {
            return [];
        }

        $this->db->query(
            'SELECT DISTINCT owner_office
             FROM procedures
             WHERE owner_office IS NOT NULL
               AND owner_office <> ""
             ORDER BY owner_office ASC'
        );

        $rows = $this->db->resultSet();

        return array_values(array_filter(array_map(function ($row) {
            return trim((string) ($row->owner_office ?? ''));
        }, $rows)));
    }

    public function getProcedureDashboard($search = '', array $filters = []) {
        if (!$this->hasPdmsFoundation()) {
            return [];
        }

        $filters = $this->normalizeDashboardFilters($search, $filters);

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
        $sql = $this->applyDashboardFiltersToSql($sql, $filters, true);

        $sql .= ' ORDER BY
                    CASE WHEN pv.status = "EFFECTIVE" THEN 0 ELSE 1 END,
                    pv.effective_date DESC,
                    p.updated_at DESC';

        $this->db->query($sql);
        $this->bindDashboardFilters($filters);

        return $this->normalizeStatusCollection($this->db->resultSet(), ['current_version_status']);
    }

    public function getHistoricalProcedureDashboard($search = '', array $filters = []) {
        if (!$this->hasPdmsFoundation()) {
            return [];
        }

        $filters = $this->normalizeDashboardFilters($search, $filters);

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
                WHERE p.status IN (' . $this->sqlInList(PdmsAuthoringOptions::terminalProcedureStatuses()) . ')';
        $sql = $this->applyDashboardFiltersToSql($sql, $filters, true);

        $sql .= ' ORDER BY p.updated_at DESC, p.id DESC';

        $this->db->query($sql);
        $this->bindDashboardFilters($filters);

        return $this->normalizeStatusCollection($this->db->resultSet(), ['current_version_status']);
    }

    public function getProcedureDashboardCounts($search = '', array $filters = []) {
        return [
            'active_total' => count($this->getProcedureDashboard($search, $filters)),
            'historical_total' => count($this->getHistoricalProcedureDashboard($search, $filters))
        ];
    }

    public function documentNumberExists($documentNumber, $excludeVersionId = null) {
        $sql = 'SELECT id
                FROM procedure_versions
                WHERE document_number = :document_number';

        if ($excludeVersionId !== null) {
            $sql .= ' AND id <> :exclude_version_id';
        }

        $sql .= ' LIMIT 1';

        $this->db->query($sql);
        $this->db->bind(':document_number', $documentNumber);

        if ($excludeVersionId !== null) {
            $this->db->bind(':exclude_version_id', (int) $excludeVersionId, PDO::PARAM_INT);
        }

        return (bool) $this->db->single();
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

    private function getLatestHistoricalAnchorVersion($procedureId) {
        $this->db->query(
            'SELECT
                pv.id,
                pv.version_number,
                pv.document_number,
                pv.change_type,
                pv.summary_of_change,
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
        $overview->current_version_status = PdmsAuthoringOptions::normalizeWorkflowStatus($anchorVersion->status, $anchorVersion->status);
        $overview->current_effective_date = $anchorVersion->effective_date;
        $overview->current_file_path = $anchorVersion->file_path;
        $overview->historical_anchor_version = true;

        return $overview;
    }
}
?>
