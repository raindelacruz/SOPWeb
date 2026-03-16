<?php
class Procedure {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function getAll($limit = null, $offset = null) {
        $sql = 'SELECT * FROM procedures ORDER BY updated_at DESC';

        if ($limit !== null && $offset !== null) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $this->db->query($sql);

        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int) $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', (int) $offset, PDO::PARAM_INT);
        }

        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM procedures WHERE id = :id');
        $this->db->bind(':id', (int) $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function getByCode($procedureCode) {
        $this->db->query('SELECT * FROM procedures WHERE procedure_code = :procedure_code');
        $this->db->bind(':procedure_code', $procedureCode);
        return $this->db->single();
    }

    public function getCurrentVersion($procedureId) {
        $this->db->query(
            'SELECT pv.*
             FROM procedures p
             LEFT JOIN procedure_versions pv ON pv.id = p.current_version_id
             WHERE p.id = :procedure_id'
        );
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function create($data) {
        $this->db->query(
            'INSERT INTO procedures
                (procedure_code, title, description, category, owner_office, status, current_version_id, created_by)
             VALUES
                (:procedure_code, :title, :description, :category, :owner_office, :status, :current_version_id, :created_by)'
        );

        $this->db->bind(':procedure_code', $data['procedure_code']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':category', $data['category'] ?? null);
        $this->db->bind(':owner_office', $data['owner_office'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'ACTIVE');
        $this->db->bind(':current_version_id', $data['current_version_id'] ?? null);
        $this->db->bind(':created_by', $data['created_by'] ?? null);

        return $this->db->execute();
    }

    public function setCurrentVersion($procedureId, $versionId) {
        $this->db->query(
            'UPDATE procedures
             SET current_version_id = :current_version_id
             WHERE id = :procedure_id'
        );
        $this->db->bind(':current_version_id', (int) $versionId, PDO::PARAM_INT);
        $this->db->bind(':procedure_id', (int) $procedureId, PDO::PARAM_INT);
        return $this->db->execute();
    }
}
?>
