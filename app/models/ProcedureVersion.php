<?php
require_once __DIR__ . '/../helpers/pdms_authoring_options.php';
class ProcedureVersion {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM procedure_versions WHERE id = :id');
        $this->db->bind(':id', (int) $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function getByLegacyPostId($legacyPostId) {
        $this->db->query('SELECT * FROM procedure_versions WHERE legacy_post_id = :legacy_post_id');
        $this->db->bind(':legacy_post_id', (int) $legacyPostId, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function getByProcedureId($procedureId) {
        $this->db->query(
            'SELECT *
             FROM procedure_versions
             WHERE procedure_id = :procedure_id
             ORDER BY effective_date DESC, created_at DESC'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getControllingCandidates($procedureId) {
        $statuses = "'" . implode("','", PdmsAuthoringOptions::controllingWorkflowStatuses()) . "'";
        $this->db->query(
            "SELECT *
             FROM procedure_versions
             WHERE procedure_id = :procedure_id
               AND status IN ($statuses)
             ORDER BY effective_date DESC, created_at DESC"
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function create($data) {
        $this->db->query(
            'INSERT INTO procedure_versions
                (procedure_id, legacy_post_id, version_number, document_number, title, summary_of_change, change_type, effective_date,
                 registration_date, status, file_path, based_on_version_id, created_by, registered_by)
             VALUES
                (:procedure_id, :legacy_post_id, :version_number, :document_number, :title, :summary_of_change, :change_type, :effective_date,
                 :registration_date, :status, :file_path, :based_on_version_id, :created_by, :registered_by)'
        );
        $this->db->bind(':registration_date', $data['registration_date'] ?? date('Y-m-d'));
        $this->db->bind(':registered_by', $data['registered_by'] ?? $data['created_by'] ?? null);

        $this->db->bind(':procedure_id', (int) $data['procedure_id'], PDO::PARAM_INT);
        $this->db->bind(':legacy_post_id', $data['legacy_post_id'] ?? null);
        $this->db->bind(':version_number', $data['version_number']);
        $this->db->bind(':document_number', $data['document_number'] ?? null);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':summary_of_change', $data['summary_of_change'] ?? null);
        $this->db->bind(':change_type', $data['change_type'] ?? 'NEW');
        $this->db->bind(':effective_date', $data['effective_date'] ?? null);
        $this->db->bind(':status', PdmsAuthoringOptions::normalizeWorkflowStatus($data['status'] ?? 'REGISTERED', 'REGISTERED'));
        $this->db->bind(':file_path', $data['file_path'] ?? null);
        $this->db->bind(':based_on_version_id', $data['based_on_version_id'] ?? null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);
        $this->db->bind(':registered_by', $data['registered_by'] ?? $data['created_by'] ?? null);

        return $this->db->execute();
    }

    public function updateStatus($versionId, $status) {
        $this->db->query(
            'UPDATE procedure_versions
             SET status = :status
             WHERE id = :id'
        );
        $this->db->bind(':status', $status);
        $this->db->bind(':id', (int) $versionId, PDO::PARAM_INT);
        return $this->db->execute();
    }
}
?>
