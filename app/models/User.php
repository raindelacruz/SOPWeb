<?php
class User {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

	// Check if ID number exists
	public function findUserByIdNumber($id_number) {
		$this->db->query("SELECT * FROM users WHERE id_number = :id_number");
		$this->db->bind(':id_number', $id_number);
		
		return $this->db->single() ? true : false;
	}

	// Check if email exists
	public function findUserByEmail($email) {
		$this->db->query("SELECT * FROM users WHERE email = :email");
		$this->db->bind(':email', $email);
		
		return $this->db->single() ? true : false;
	}

	public function getUserById($id) {
		$this->db->query('SELECT * FROM users WHERE id = :id');
		$this->db->bind(':id', $id);
		
		$row = $this->db->single();
		return $row;
	}

    public function getUserByEmail($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);

        return $this->db->single();
    }

    public function login($email, $password) {
		$this->db->query('SELECT * FROM users WHERE email = :email');
		$this->db->bind(':email', $email);

		$row = $this->db->single();

		if ($row) {
			//echo "User found in database"; // Debug echo
			$hashedPassword = $row->password;
			
			if (password_verify($password, $hashedPassword)) {
				//echo "Password verification successful"; // Debug echo
				return $row;
			} else {
				//echo "Password verification failed"; // Debug echo
				return false;
			}
		} else {
			//echo "User not found in database"; // Debug echo
			return false;
		}
	}
	
	public function register($data) {
		// Prepare the SQL query
		$this->db->query('INSERT INTO users (id_number, firstname, lastname, middle_name, office, email, password, status) 
						  VALUES (:id_number, :firstname, :lastname, :middle_name, :office, :email, :password, :status)');
		
		// Bind values
		$this->db->bind(':id_number', $data['id_number']);
		$this->db->bind(':firstname', $data['firstname']);
		$this->db->bind(':lastname', $data['lastname']);
		$this->db->bind(':middle_name', $data['middle_name']);
		$this->db->bind(':office', $data['office']);
		$this->db->bind(':email', $data['email']);
		$this->db->bind(':password', $data['password']);
		$this->db->bind(':status', 'inactive');  // Default status is 'inactive'

		// Execute the query and check for success
		if ($this->db->execute()) {
			return true;
		} else {
			return false;
		}
	}

	public function getUsers() {
        $this->db->query("SELECT * FROM users");
        return $this->db->resultSet();
    }

    // Update user status
    public function updateStatus($id, $status, $user_id) {
		// Update user status
		$this->db->query("UPDATE users SET status = :status WHERE id = :id");
		$this->db->bind(':status', $status);
		$this->db->bind(':id', $id);
		
		if ($this->db->execute()) {
			// Log the status change action
			$this->logActivity($user_id, 'Change Status', "User ID #$id status changed to '$status'");
			return true;
		} else {
			return false;
		}
	}

    // Update user role
    public function updateRole($id, $role, $user_id) {
		// Update user role
		$this->db->query("UPDATE users SET role = :role WHERE id = :id");
		$this->db->bind(':role', $role);
		$this->db->bind(':id', $id);
		
		if ($this->db->execute()) {
			// Log the role change action
			$this->logActivity($user_id, 'Change Role', "User ID #$id role changed to '$role'");
			return true;
		} else {
			return false;
		}
	}

	public function logActivity($user_id, $action, $description) {
		$this->db->query('INSERT INTO activity_logs (user_id, action, description) VALUES (:user_id, :action, :description)');
		$this->db->bind(':user_id', $user_id);
		$this->db->bind(':action', $action);
		$this->db->bind(':description', $description);
		$this->db->execute();
	}

	public function searchUsers($keyword) {
		$this->db->query("SELECT * FROM users WHERE firstname LIKE :keyword OR lastname LIKE :keyword OR email LIKE :keyword OR id_number LIKE :keyword");
		$this->db->bind(':keyword', '%' . $keyword . '%');
		return $this->db->resultSet();
	}

	public function updateUser($data) {
		$this->db->query('UPDATE users SET firstname = :firstname, lastname = :lastname, middle_name = :middle_name, office = :office, email = :email, password = :password WHERE id = :id');
		
		// Bind values
		$this->db->bind(':id', $_SESSION['user_id']);
		$this->db->bind(':firstname', $data['firstname']);
		$this->db->bind(':lastname', $data['lastname']);
		$this->db->bind(':middle_name', $data['middle_name']);
		$this->db->bind(':office', $data['office']);
		$this->db->bind(':email', $data['email']);
		$this->db->bind(':password', $data['password']);
		
		// Execute and check
		return $this->db->execute();
	}

	public function getOffices() {
		return [
			"AO" => "Office of the Administrator",
			"ODA" => "Office of the Deputy Administrator",
			"OAAO" => "Office of the Assistant Administrator for Finance and Administration",
			"OAAFA" => "Office of the Assistant Administrator for Finance and Administration",
			"OCS" => "Office of the Council Secretariat",
			"OCD" => "Operations and Coordination Department",
			"LAD" => "Legal Affairs Department",
			"AGSD" => "Administrative and General Services Department",
			"FD" => "Finance Department",
			"IAD" => "Internal Audit Department",
			"CPMSD" => "Corporate Planning and Management Services Department",
			"NFA Region 1" => "NFA Region 1",
			"NFA Region 2" => "NFA Region 2",
			"NFA Region 3" => "NFA Region 3",
			"NFA Region 4" => "NFA Region 4",
			"NFA Region 5" => "NFA Region 5",
			"NFA Region 6" => "NFA Region 6",
			"NFA Region 7" => "NFA Region 7",
			"NFA Region 8" => "NFA Region 8",
			"NFA Region 9" => "NFA Region 9",
			"NFA Region 10" => "NFA Region 10",
			"NFA Region 11" => "NFA Region 11",
			"NFA Region 12" => "NFA Region 12",
			"NFA Region NCR" => "NFA Region NCR",
			"NFA Region ARMM" => "NFA Region ARMM",
			"NFA Region CARAGA" => "NFA Region CARAGA"
		];
	}

	public function getFilteredUsers($office, $status, $role, $limit = null, $offset = null) {
		$query = "SELECT * FROM users WHERE 1=1";
		
		if (!empty($office)) {
			$query .= " AND office = :office";
		}
		if (!empty($status)) {
			$query .= " AND status = :status";
		}
		if (!empty($role)) {
			$query .= " AND role = :role";
		}

        $query .= " ORDER BY id DESC";

        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
		
		$this->db->query($query);

		if (!empty($office)) {
			$this->db->bind(':office', $office);
		}
		if (!empty($status)) {
			$this->db->bind(':status', $status);
		}
		if (!empty($role)) {
			$this->db->bind(':role', $role);
		}
        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int) $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', (int) $offset, PDO::PARAM_INT);
        }

		return $this->db->resultSet();
	}

	// Get users for pagination
	public function getUsersPaginated($limit, $offset) {
		$this->db->query("SELECT * FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset");
		$this->db->bind(':limit', $limit);
		$this->db->bind(':offset', $offset);
		return $this->db->resultSet();
	}

	// Count all users
	public function countUsers() {
		$this->db->query("SELECT COUNT(*) AS total FROM users");
		return $this->db->single()->total;
	}

	public function countFilteredUsers($office = '', $status = '', $role = '') {
		$sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
		$params = [];

		if (!empty($office)) {
			$sql .= " AND office = :office";
			$params[':office'] = $office;
		}
		if (!empty($status)) {
			$sql .= " AND status = :status";
			$params[':status'] = $status;
		}
		if (!empty($role)) {
			$sql .= " AND role = :role";
			$params[':role'] = $role;
		}

		$this->db->query($sql);

		foreach ($params as $key => $value) {
			$this->db->bind($key, $value);
		}

		$row = $this->db->single();
		return $row ? $row->total : 0;
	}


}
?>
