<?php
class ActivityLog {
    private $db;

    public function __construct() {
        $this->db = new Database; // Assuming you have a Database class for handling DB operations
    }

    public function getLogs() {
		$this->db->query("SELECT activity_logs.*, users.firstname, users.lastname 
						  FROM activity_logs 
						  JOIN users ON activity_logs.user_id = users.id 
						  ORDER BY activity_logs.created_at DESC");
		return $this->db->resultSet();
	}
	
	public function searchLogs($keyword) {
		$this->db->query("
			SELECT activity_logs.*, users.firstname, users.lastname 
			FROM activity_logs 
			JOIN users ON activity_logs.user_id = users.id 
			WHERE activity_logs.action LIKE :keyword 
			OR activity_logs.user_id LIKE :keyword 
			OR activity_logs.description LIKE :keyword 
			OR activity_logs.created_at LIKE :keyword
		");
		$this->db->bind(':keyword', '%' . $keyword . '%');
		return $this->db->resultSet();
	}

	
}
