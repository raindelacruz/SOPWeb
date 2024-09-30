<?php
class Post {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function getAllPosts() {
        try {
            $this->db->query('SELECT * FROM posts ORDER BY id DESC');
            $results = $this->db->resultSet();
            return $results;
        } catch (PDOException $e) {
            echo 'Query failed: ' . $e->getMessage();
            return [];
        }
    }

    public function searchPosts($search) {
        try {
            $this->db->query('SELECT * FROM posts WHERE title LIKE :search OR description LIKE :search ORDER BY date_of_effectivity DESC');
            $this->db->bind(':search', "%$search%");
            $results = $this->db->resultSet();
            return $results;
        } catch (PDOException $e) {
            echo 'Query failed: ' . $e->getMessage();
            return [];
        }
    }

    // Create a new post
	public function createPost($data) {
		$this->db->query('INSERT INTO posts (title, description, reference_number, date_of_effectivity, upload_date, file, amended_post_id, superseded_post_id) VALUES (:title, :description, :reference_number, :date_of_effectivity, :upload_date, :file, :amended_post_id, :superseded_post_id)');

		// Bind values
		$this->db->bind(':title', $data['title']);
		$this->db->bind(':description', $data['description']);
		$this->db->bind(':reference_number', $data['reference_number']);
		$this->db->bind(':date_of_effectivity', $data['date_of_effectivity']);
		$this->db->bind(':upload_date', $data['upload_date']);
		$this->db->bind(':file', $data['file']);
		$this->db->bind(':amended_post_id', !empty($data['amended_post_id']) ? $data['amended_post_id'] : null);
		$this->db->bind(':superseded_post_id', !empty($data['superseded_post_id']) ? $data['superseded_post_id'] : null);
		
		if($this->db->execute()) {
        // Log the activity
        $this->logActivity($data['user_id'], 'Add Post', 'Post titled "'.$data['title'].'" was added.');
			return true;
		} else {
			return false;
		}
		// Execute
		return $this->db->execute();
	}

    // Update an existing post
    public function updatePost($data) {
        $this->db->query('UPDATE posts SET title = :title, description = :description, reference_number = :reference_number, date_of_effectivity = :date_of_effectivity, upload_date = :upload_date, file = :file, amended_post_id = :amended_post_id, superseded_post_id = :superseded_post_id WHERE id = :id');
        // Bind values
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':reference_number', $data['reference_number']);
        $this->db->bind(':date_of_effectivity', $data['date_of_effectivity']);
        $this->db->bind(':upload_date', $data['upload_date']);
        $this->db->bind(':file', $data['file']);
        $this->db->bind(':amended_post_id', $data['amended_post_id']);
        $this->db->bind(':superseded_post_id', $data['superseded_post_id']);
        $this->db->bind(':id', $data['id']);
		
		if($this->db->execute()) {
        // Log the activity
        $this->logActivity($data['user_id'], 'Edit Post', 'Post titled "'.$data['title'].'" was Updated.');
			return true;
		} else {
			return false;
		}
        // Execute
        return $this->db->execute();
    }

    // Fetch a post by its ID
    public function getPostById($id) {
        $this->db->query('SELECT * FROM posts WHERE id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row;
    }

    // Fetch posts that supersede a given post
    public function getSupersedingPosts($id) {
        $this->db->query('SELECT * FROM posts WHERE superseded_post_id = :id');
        $this->db->bind(':id', $id);
        $results = $this->db->resultSet();
        return $results;
    }

    // Fetch posts that amend a given post
    public function getAmendingPosts($id) {
        $this->db->query('SELECT * FROM posts WHERE amended_post_id = :id');
        $this->db->bind(':id', $id);
        $results = $this->db->resultSet();
        return $results;
    }

    // Fetch the post that this post amends
    public function getAmendedPost($id) {
        $this->db->query('SELECT * FROM posts WHERE id = (SELECT amended_post_id FROM posts WHERE id = :id)');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row;
    }

    // Fetch the post that this post supersedes
    public function getSupersededPost($id) {
        $this->db->query('SELECT * FROM posts WHERE id = (SELECT superseded_post_id FROM posts WHERE id = :id)');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row;
    }

    public function deletePost($id, $user_id) {
		// Get the title of the post before deletion
		$this->db->query('SELECT title FROM posts WHERE id = :id');
		$this->db->bind(':id', $id);
		$row = $this->db->single();

		if ($row) {
			$title = $row->title;

			// Delete the post
			$this->db->query('DELETE FROM posts WHERE id = :id');
			$this->db->bind(':id', $id);

			if ($this->db->execute()) {
				// Log the delete action with the post title
				$this->logActivity($user_id, 'Delete Post', "Deleted SOP # $id entitled '$title'");
				return true;
			} else {
				return false;
			}
		} else {
			// Post not found, return false or handle the error as needed
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
	
	public function isPostReferenced($id) {
		// Check if any post is amending or superseding the current post
		$this->db->query('SELECT COUNT(*) as count FROM posts WHERE amended_post_id = :id OR superseded_post_id = :id');
		$this->db->bind(':id', $id);

		$row = $this->db->single();

		// If the count is greater than 0, the post is referenced
		return $row->count > 0;
	}

}

?>
