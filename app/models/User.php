<?php
class User {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function findUserByEmail($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);

        $row = $this->db->single();

        if ($this->db->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function login($email, $password) {
		$this->db->query('SELECT * FROM users WHERE email = :email');
		$this->db->bind(':email', $email);

		$row = $this->db->single();

		if ($row) {
			echo "User found in database"; // Debug echo
			$hashedPassword = $row->password;
			
			if (password_verify($password, $hashedPassword)) {
				echo "Password verification successful"; // Debug echo
				return $row;
			} else {
				echo "Password verification failed"; // Debug echo
				return false;
			}
		} else {
			echo "User not found in database"; // Debug echo
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

	public function isActive($email) {
        $this->db->query('SELECT status FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        $row = $this->db->single();

        if($row && $row->status == 'active') {
            return true;
        } else {
            return false;
        }
    }
	
	public function searchUsers($keyword) {
		$this->db->query("SELECT * FROM users WHERE firstname LIKE :keyword OR lastname LIKE :keyword OR email LIKE :keyword OR id_number LIKE :keyword");
		$this->db->bind(':keyword', '%' . $keyword . '%');
		return $this->db->resultSet();
	}

}
?>
