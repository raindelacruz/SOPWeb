<?php
class Posts extends Controller {
	
    public function __construct() {
		Middleware::checkLoggedIn();
        if (!isset($_SESSION['user_id'])) {
            header('location: ' . URLROOT . '/users/login');
        }

        $this->postModel = $this->model('Post');
    }

    public function index() {
        // Get posts
        $posts = $this->postModel->getAllPosts();

        $data = [
            'posts' => $posts
        ];

        $this->view('posts/index', $data);
    }

    public function search() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $search = trim($_POST['search']);
            $posts = $this->postModel->searchPosts($search);

            $data = [
                'posts' => $posts
            ];

            $this->view('posts/index', $data);
        } else {
            $this->index();
        }
    }

    public function create() {
		// Ensure the user is logged in
		Middleware::checkLoggedIn();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Sanitize POST data
			$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		
			// Handle file upload
			$file = $_FILES['file'];
			$fileName = $_FILES['file']['name'];
			$fileTmpName = $_FILES['file']['tmp_name'];
			$fileSize = $_FILES['file']['size'];
			$fileError = $_FILES['file']['error'];
			$fileType = $_FILES['file']['type'];

			$fileExt = explode('.', $fileName);
			$fileActualExt = strtolower(end($fileExt));

			$allowed = array('pdf');
			
			// Validate amended_post_id
			$amended_post_id = !empty($_POST['amended_post_id']) ? trim($_POST['amended_post_id']) : NULL;
			if (!empty($amended_post_id)) {
				// Check if amended post exists
				$amendedPost = $this->postModel->getPostById($amended_post_id);
				if (!$amendedPost) {
					$amended_post_id = NULL; // Set to NULL if invalid
				}
			}
			
			// Get the user_id from session
			$user_id = $_SESSION['user_id'];

			if (in_array($fileActualExt, $allowed)) {
				if ($fileError === 0) {
					if ($fileSize < 1000000) { // Limit to 1MB
						$fileNameNew = uniqid('', true) . "." . $fileActualExt;
						$fileDestination = APPROOT . '/uploads/' . $fileNameNew;

						// Ensure the upload directory exists
						if (!file_exists(APPROOT . '/uploads')) {
							mkdir(APPROOT . '/uploads', 0777, true);
						}

						if (move_uploaded_file($fileTmpName, $fileDestination)) {
							$fileUploaded = true;
						} else {
							die('There was an error uploading your file!');
						}
					} else {
						die('Your file is too big!');
					}
				} else {
					die('There was an error uploading your file!');
				}
			} else {
				die('You cannot upload files of this type!');
			}

			// Add user_id to the data array
			$data = [
				'user_id' => $user_id, // Include the user_id from session
				'title' => trim($_POST['title']),
				'description' => trim($_POST['description']),
				'reference_number' => trim($_POST['reference_number']),
				'date_of_effectivity' => trim($_POST['date_of_effectivity']),
				'upload_date' => date('Y-m-d'),
				'file' => $fileNameNew,
				'amended_post_id' => !empty($_POST['amended_post_id']) ? trim($_POST['amended_post_id']) : null,
				'superseded_post_id' => !empty($_POST['superseded_post_id']) ? trim($_POST['superseded_post_id']) : null
			];

			if ($this->postModel->createPost($data)) {
				header('location: ' . URLROOT . '/posts');
			} else {
				die('Something went wrong');
			}
		} else {
			$posts = $this->postModel->getAllPosts();
			$data = [
				'posts' => $posts,
				'title' => '',
				'description' => '',
				'reference_number' => '',
				'date_of_effectivity' => '',
				'file' => '',
				'amended_post_id' => '',
				'superseded_post_id' => ''
			];

			$this->view('posts/create', $data);
		}
	}

    public function edit($id) {
		// Ensure user is logged in
		Middleware::checkLoggedIn();
				
		// Get the user_id from session (move this line outside the file upload block)
		$user_id = $_SESSION['user_id'] ?? null;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Sanitize POST data
			$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

			// Initialize file name
			$fileNameNew = '';

			// Handle file upload if necessary
			if (!empty($_FILES['file']['name'])) {
				$file = $_FILES['file'];
				$fileName = $_FILES['file']['name'];
				$fileTmpName = $_FILES['file']['tmp_name'];
				$fileSize = $_FILES['file']['size'];
				$fileError = $_FILES['file']['error'];
				$fileType = $_FILES['file']['type'];

				$fileExt = explode('.', $fileName);
				$fileActualExt = strtolower(end($fileExt));

				$allowed = array('pdf');

				if (in_array($fileActualExt, $allowed)) {
					if ($fileError === 0) {
						if ($fileSize < 1000000) {
							$fileNameNew = uniqid('', true) . "." . $fileActualExt;
							$fileDestination = APPROOT . '/../uploads/' . $fileNameNew;
							move_uploaded_file($fileTmpName, $fileDestination);
						} else {
							die('Your file is too big!');
						}
					} else {
						die('There was an error uploading your file!');
					}
				} else {
					die('You cannot upload files of this type!');
				}
			}
			
			// Validate amended_post_id
			$amended_post_id = !empty($_POST['amended_post_id']) ? trim($_POST['amended_post_id']) : NULL;
			if (!empty($amended_post_id)) {
				// Check if amended post exists
				$amendedPost = $this->postModel->getPostById($amended_post_id);
				if (!$amendedPost) {
					$amended_post_id = NULL; // Set to NULL if invalid
				}
			}

			// Prepare data to update the post
			$data = [
				'user_id' => $user_id, // Include the user_id from session
				'id' => $id,
				'title' => trim($_POST['title']),
				'description' => trim($_POST['description']),
				'reference_number' => trim($_POST['reference_number']),
				'date_of_effectivity' => trim($_POST['date_of_effectivity']),
				'upload_date' => date('Y-m-d'),
				'file' => $fileNameNew ? $fileNameNew : trim($_POST['existing_file']),
				'amended_post_id' => trim($_POST['amended_post_id']),
				'superseded_post_id' => !empty($_POST['superseded_post_id']) ? trim($_POST['superseded_post_id']) : null
			];
			
			// Debugging: Check what is being passed for superseded_post_id
			if (!empty($_POST['superseded_post_id'])) {
				echo "Superseded Post ID: " . $_POST['superseded_post_id'];
			} else {
				echo "No Superseded Post ID";
			}
		
			// Update the post and redirect if successful
			if ($this->postModel->updatePost($data)) {
				header('location: ' . URLROOT . '/posts');
			} else {
				die('Something went wrong');
			}
		} else {
			// Fetch post data and display edit form
			$post = $this->postModel->getPostById($id);
			$posts = $this->postModel->getAllPosts();

			$data = [
				'id' => $id,
				'posts' => $posts,
				'title' => $post->title,
				'description' => $post->description,
				'reference_number' => $post->reference_number,
				'date_of_effectivity' => $post->date_of_effectivity,
				'file' => $post->file,
				'amended_post_id' => $post->amended_post_id,
				'superseded_post_id' => $post->superseded_post_id
			];

			// Load the edit view with the data
			$this->view('posts/edit', $data);
		}
	}

    public function show($id) {
		$post = $this->postModel->getPostById($id);
		$amendedPost = $this->postModel->getAmendedPost($id);
		$supersededPost = $this->postModel->getSupersededPost($id);
		$amendingPosts = $this->postModel->getAmendingPosts($id);
		$supersedingPosts = $this->postModel->getSupersedingPosts($id);
		
		// Check if the post is referenced by other posts (amended or superseded)
		$isReferenced = $this->postModel->isPostReferenced($id);

		if ($post) {
			$data = [
				'post' => $post,
				'amendedPost' => $amendedPost,
				'supersededPost' => $supersededPost,
				'amendingPosts' => $amendingPosts,
				'supersedingPosts' => $supersedingPosts,
				'isReferenced' => $isReferenced // Pass the reference check result to the view
			];

			$this->view('posts/show', $data);
		} else {
			die('Post not found');
		}
	}


    public function delete($id) {
    // Ensure the user is logged in
		Middleware::checkLoggedIn();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Get the user_id from session
			$user_id = $_SESSION['user_id'];

			// Pass user_id to the deletePost method
			if ($this->postModel->deletePost($id, $user_id)) {
				header('location: ' . URLROOT . '/posts');
			} else {
				die('Something went wrong');
			}
		} else {
			header('location: ' . URLROOT . '/posts');
		}
	}

}
?>
