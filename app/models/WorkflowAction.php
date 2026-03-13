<?php
class WorkflowAction {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function record($data) {
        $this->db->query(
            'INSERT INTO workflow_actions
                (procedure_version_id, lifecycle_action_type, from_status, to_status, acted_by, remarks)
             VALUES
                (:procedure_version_id, :lifecycle_action_type, :from_status, :to_status, :acted_by, :remarks)'
        );

        $this->db->bind(':procedure_version_id', (int) $data['procedure_version_id'], PDO::PARAM_INT);
        $this->db->bind(':lifecycle_action_type', strtoupper($data['lifecycle_action_type']));
        $this->db->bind(':from_status', $data['from_status'] ?? null);
        $this->db->bind(':to_status', $data['to_status'] ?? null);
        $this->db->bind(':acted_by', $data['acted_by'] ?? null);
        $this->db->bind(':remarks', $data['remarks'] ?? null);

        return $this->db->execute();
    }

    public function getByVersionId($versionId) {
        $this->db->query(
            'SELECT *
             FROM workflow_actions
             WHERE procedure_version_id = :procedure_version_id
             ORDER BY acted_at DESC'
        );
        $this->db->bind(':procedure_version_id', (int) $versionId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
}
?>
