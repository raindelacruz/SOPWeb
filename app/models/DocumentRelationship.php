<?php
class DocumentRelationship {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function create($data) {
        $this->db->query(
            'INSERT INTO document_relationships
                (source_version_id, target_version_id, relationship_type, affected_sections, remarks, created_by)
             VALUES
                (:source_version_id, :target_version_id, :relationship_type, :affected_sections, :remarks, :created_by)'
        );

        $this->db->bind(':source_version_id', (int) $data['source_version_id'], PDO::PARAM_INT);
        $this->db->bind(':target_version_id', (int) $data['target_version_id'], PDO::PARAM_INT);
        $this->db->bind(':relationship_type', strtoupper($data['relationship_type']));
        $this->db->bind(':affected_sections', $data['affected_sections'] ?? null);
        $this->db->bind(':remarks', $data['remarks'] ?? null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);

        return $this->db->execute();
    }

    public function getOutgoingForVersion($versionId) {
        $this->db->query(
            'SELECT dr.*, pv.title AS target_title, pv.version_number AS target_version_number
             FROM document_relationships dr
             INNER JOIN procedure_versions pv ON pv.id = dr.target_version_id
             WHERE dr.source_version_id = :version_id
             ORDER BY dr.created_at DESC'
        );
        $this->db->bind(':version_id', (int) $versionId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getIncomingForVersion($versionId) {
        $this->db->query(
            'SELECT dr.*, pv.title AS source_title, pv.version_number AS source_version_number
             FROM document_relationships dr
             INNER JOIN procedure_versions pv ON pv.id = dr.source_version_id
             WHERE dr.target_version_id = :version_id
             ORDER BY dr.created_at DESC'
        );
        $this->db->bind(':version_id', (int) $versionId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getByTypeForVersion($versionId, $relationshipType) {
        $this->db->query(
            'SELECT *
             FROM document_relationships
             WHERE (source_version_id = :version_id OR target_version_id = :version_id)
               AND relationship_type = :relationship_type
             ORDER BY created_at DESC'
        );
        $this->db->bind(':version_id', (int) $versionId, PDO::PARAM_INT);
        $this->db->bind(':relationship_type', strtoupper($relationshipType));
        return $this->db->resultSet();
    }
}
?>
