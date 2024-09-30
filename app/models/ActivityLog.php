<?php
class ActivityLog {
    private $db;

    public function __construct() {
        $this->db = new Database; // Assuming you have a Database class for handling DB operations
    }

    public function getLogs() {
        $this->db->query("SELECT * FROM activity_logs ORDER BY created_at DESC");
        return $this->db->resultSet();
    }
	
	public function searchLogs($keyword) {
		$this->db->query("SELECT * FROM activity_logs WHERE action LIKE :keyword OR user_id LIKE :keyword OR description LIKE :keyword OR created_at LIKE :keyword");
		$this->db->bind(':keyword', '%' . $keyword . '%');
		return $this->db->resultSet();
	}

}
