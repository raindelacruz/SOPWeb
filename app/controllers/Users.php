<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class Users extends Controller {
	
    public function __construct() {
        $this->userModel = $this->model('User');
    }

    private function csrfFailure($url) {
        handle_csrf_failure($url);
    }

	public function index() {
		$usersPerPage = 10; // Number of users per page
		$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
		$offset = ($page - 1) * $usersPerPage;

		// Fetch users for current page
		$users = $this->userModel->getUsersPaginated($usersPerPage, $offset);

		// Get total number of users
		$totalUsers = $this->userModel->countUsers();
		$totalPages = ceil($totalUsers / $usersPerPage);

		$data = [
			'users' => $users,
			'current_page' => $page,
			'total_pages' => $totalPages
		];

		$this->view('users/index', $data);
	}
	
	public function login() {
		
		// If the user is logged in and reaches the login form (e.g., by pressing the back button)
		if(isset($_SESSION['user_id'])) {
			// Destroy the session
			session_unset();
			session_destroy();
			// Optionally, you can redirect to the login page again or another page
			redirect('users/login');
			return;
		}

		
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->csrfFailure('users/login');
            }

            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            // Init data
            $data = [
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'email_err' => '',
                'password_err' => ''
            ];

            // Validate email
            if(empty($data['email'])) {
                $data['email_err'] = 'Please enter email';
            }

            // Validate password
            if(empty($data['password'])) {
                $data['password_err'] = 'Please enter password';
            }

            // Make sure errors are empty
            if(empty($data['email_err']) && empty($data['password_err'])) {
                $user = $this->userModel->getUserByEmail($data['email']);

                if (!$user || $user->status !== 'active') {
                    $data['password_err'] = 'Invalid credentials or inactive account';
                    $this->view('users/login', $data);
                    return;
                }

                // Check and set logged in user
                $loggedInUser = $this->userModel->login($data['email'], $data['password']);

                if($loggedInUser) {
                    // Create Session
                    $this->createUserSession($loggedInUser);
                } else {
                    $data['password_err'] = 'Invalid credentials or inactive account';

                    $this->view('users/login', $data);
                }
            } else {
                // Load view with errors
                $this->view('users/login', $data);
            }
        } else {
            // Init data
            $data = [
                'email' => '',
                'password' => '',
                'email_err' => '',
                'password_err' => ''
            ];

            // Load view
            $this->view('users/login', $data);
        }
		
    }

	public function createUserSession($user) {
		$_SESSION['user_id'] = $user->id;
		$_SESSION['user_email'] = $user->email;
		$_SESSION['user_name'] = $user->name;
		$_SESSION['user_role'] = $user->role; // Ensure the role is set in the session
		redirect('procedures');
	}
	
	public function logout() {
        // Unset all of the session variables
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_role']);
        
        // Destroy the session
        session_destroy();
        
        // Redirect to the login page
        header('location:' . URLROOT . '/users/login');
    }

	public function register() {
        $offices = $this->userModel->getOffices();

		// Check for POST request
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->csrfFailure('users/register');
            }

			// Process form
			$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

			// Init data
			$data = [
				'id_number' => trim($_POST['id_number']),
				'firstname' => trim($_POST['firstname']),
				'lastname' => trim($_POST['lastname']),
				'middle_name' => trim($_POST['middle_name']),
				'office' => trim($_POST['office']),
				'email' => trim($_POST['email']),
				'password' => trim($_POST['password']),
				'confirm_password' => trim($_POST['confirm_password']),
				'id_number_err' => '',
				'firstname_err' => '',
				'lastname_err' => '',
				'email_err' => '',
				'password_err' => '',
				'confirm_password_err' => '',
				'office_err' => '',
                'offices' => $offices
			];

			// Validate Email
			if (empty($data['email'])) {
				$data['email_err'] = 'Please enter email';
			} else {
				// Check if email is already taken
				if ($this->userModel->findUserByEmail($data['email'])) {
					$data['email_err'] = 'Email is already taken';
				}
			}

			// Validate ID Number
			if (empty($data['id_number'])) {
				$data['id_number_err'] = 'Please enter ID number';
			} else {
				// Check if ID number is already taken
				if ($this->userModel->findUserByIdNumber($data['id_number'])) {
					$data['id_number_err'] = 'ID number is already taken';
				}
			}

			// Validate First Name
			if (empty($data['firstname'])) {
				$data['firstname_err'] = 'Please enter first name';
			}

			// Validate Last Name
			if (empty($data['lastname'])) {
				$data['lastname_err'] = 'Please enter last name';
			}

			// Validate Office
			if (empty($data['office'])) {
				$data['office_err'] = 'Please enter office';
			}

			// Validate Password
			if (empty($data['password'])) {
				$data['password_err'] = 'Please enter password';
			} elseif (strlen($data['password']) < 6) {
				$data['password_err'] = 'Password must be at least 6 characters';
			}

			// Validate Confirm Password
			if (empty($data['confirm_password'])) {
				$data['confirm_password_err'] = 'Please confirm password';
			} else {
				if ($data['password'] != $data['confirm_password']) {
					$data['confirm_password_err'] = 'Passwords do not match';
				}
			}

			// Make sure no validation errors
			if (
				empty($data['email_err']) &&
				empty($data['id_number_err']) &&
				empty($data['firstname_err']) &&
				empty($data['lastname_err']) &&
				empty($data['password_err']) &&
				empty($data['confirm_password_err']) &&
				empty($data['office_err'])
			) {
				// Hash Password
				$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

				// Register User
			if ($this->userModel->register($data)) {
					// Send email notification after successful registration
					if ($this->sendRegistrationNotification($data['email'], $data['firstname'], $data['lastname'], $data['middle_name'])) {
						// Redirect to login page after successful registration
                        flash('register_success', 'Registration successful. You may now log in.');
						redirect('users/login');
					} else {
						die('Registration successful, but failed to send email');
					}
				} else {
					die('Something went wrong');
				}
			} else {
				// Load view with errors
				$this->view('users/register', $data);
			}
		} else {
			// Init data
			$data = [
				'id_number' => '',
				'firstname' => '',
				'lastname' => '',
				'middle_name' => '',				
				'office' => '',
				'email' => '',
				'password' => '',
				'confirm_password' => '',
				'id_number_err' => '',
				'firstname_err' => '',
				'lastname_err' => '',
				'email_err' => '',
				'password_err' => '',
				'confirm_password_err' => '',
				'office_err' => '',
                'offices' => $offices
			];

			// Load view
			$this->view('users/register', $data);
		}
	}

	private function sendRegistrationNotification($email, $firstname, $lastname, $middle_name) {
		// Load PHPMailer classes
		require_once '../libs/PHPMailer/PHPMailer.php';
		require_once '../libs/PHPMailer/SMTP.php';
		require_once '../libs/PHPMailer/Exception.php';
		
		$mail = new PHPMailer(true);

		// Concatenate lastname, firstname, and the first letter of middle name
		$full_name = $lastname . ', ' . $firstname . ' ' . substr($middle_name, 0, 1) . '.';


		try {
            if (
                MAIL_HOST === '' ||
                MAIL_USERNAME === '' ||
                MAIL_PASSWORD === '' ||
                MAIL_FROM_ADDRESS === '' ||
                MAIL_NOTIFY_ADDRESS === ''
            ) {
                throw new Exception('Mail configuration is incomplete.');
            }

			// Server settings
			$mail->isSMTP();
			$mail->Host = MAIL_HOST;
			$mail->SMTPAuth = true;
			$mail->Username = MAIL_USERNAME;
			$mail->Password = MAIL_PASSWORD;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = MAIL_PORT;

			// Recipients
			$mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
			$mail->addAddress($email, $firstname);

			// Content for the registrant
			$mail->isHTML(true);
			$mail->Subject = 'Registration Successful';
			$mail->Body    = 'Hi ' . $firstname . ',<br>Thank you for registering on our website. Your account is subject for approval. Thank you!';
			$mail->send();

			// Notify the sender (yourself)
			$mail->clearAddresses();  // Clear previous recipients
			$mail->addAddress(MAIL_NOTIFY_ADDRESS);
			$mail->Subject = 'New Registration Alert';
			$mail->Body    = 'A new user has registered on the site:<br>Name: ' . $full_name . '<br>Email: ' . $email;
			$mail->send();
			
			return true;
		} catch (Exception $e) {
			echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
			return false;
		}
	}

	public function isAdmin() {
        return $this->role === 'admin';
    }

    public function isUser() {
        return $this->role === 'user';
    }
	
	public function manage() {
		Middleware::checkSuperAdmin();
        $offices = $this->userModel->getOffices();

		// Get filter values from query parameters
		$office = isset($_GET['office']) ? trim($_GET['office']) : '';
		$status = isset($_GET['status']) ? trim($_GET['status']) : '';
		$role = isset($_GET['role']) ? trim($_GET['role']) : '';

		// Pagination
		$usersPerPage = 10;
		$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
		$offset = ($page - 1) * $usersPerPage;

		// Fetch users with limit & offset
		$users = $this->userModel->getFilteredUsers($office, $status, $role, $usersPerPage, $offset);

		// Count total filtered users
		$totalUsers = $this->userModel->countFilteredUsers($office, $status, $role);
		$totalPages = ceil($totalUsers / $usersPerPage);

		$data = [
			'users' => $users,
			'office' => $office,
			'status' => $status,
			'role' => $role,
			'current_page' => $page,
			'total_pages' => $totalPages,
            'offices' => $offices
		];

		$this->view('users/manage', $data);
	}


	public function activate($id) {
		Middleware::checkSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->csrfFailure('users/manage');
        }

		$user_id = $_SESSION['user_id']; // Get the current admin's user_id from the session

		if ($this->userModel->updateStatus($id, 'active', $user_id)) {
			$this->redirect('users/manage');
		} else {
			die('Something went wrong');
		}
	}

	public function deactivate($id) {
		Middleware::checkSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->csrfFailure('users/manage');
        }

		$user_id = $_SESSION['user_id']; // Get the current admin's user_id from the session

		if ($this->userModel->updateStatus($id, 'inactive', $user_id)) {
			$this->redirect('users/manage');
		} else {
			die('Something went wrong');
		}
	}

	public function changeRole($id, $role) {
		Middleware::checkSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->csrfFailure('users/manage');
        }

		$user_id = $_SESSION['user_id']; // Get the current admin's user_id from the session

		if ($this->userModel->updateRole($id, $role, $user_id)) {
			$this->redirect('users/manage');
		} else {
			die('Something went wrong');
		}
	}

    private function redirect($url) {
        header('Location: ' . URLROOT . '/' . $url);
        exit();
    }
	
	public function search() {
		Middleware::checkSuperAdmin();
        $offices = $this->userModel->getOffices();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->csrfFailure('users/manage');
        }

		$keyword = trim($_POST['keyword']);
		$users = $this->userModel->searchUsers($keyword);
		$data = [
			'users' => $users,
			'keyword' => $keyword,
			'office' => '',
			'status' => '',
			'role' => '',
			'current_page' => 1,
			'total_pages' => 1,
            'offices' => $offices
		];
		$this->view('users/manage', $data);
	}
	
	public function profile() {
		// Check if user is logged in
		if (!isLoggedIn()) {
			redirect('users/login');
		}

		// Fetch user data based on session user ID
		$user = $this->userModel->getUserById($_SESSION['user_id']);

		// Fetch available offices for the dropdown
		$offices = $this->userModel->getOffices();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->csrfFailure('users/profile');
            }

			// Sanitize POST data
			$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

			$data = [
				'id_number' => $user->id_number, // ID number is displayed but not editable
				'firstname' => trim($_POST['firstname'] ?? $user->firstname),
				'lastname' => trim($_POST['lastname'] ?? $user->lastname),
				'middle_name' => trim($_POST['middle_name'] ?? $user->middle_name),
				'email' => trim($_POST['email'] ?? $user->email),
				'office' => trim($_POST['office']),
				'password' => trim($_POST['password']),
				'confirm_password' => trim($_POST['confirm_password']),
				'role' => $user->role, // Role cannot be changed
				'status' => $user->status, // Status cannot be changed
				'offices' => $offices,
				'firstname_err' => '',
				'lastname_err' => '',
				'email_err' => '',
				'office_err' => '',
				'password_err' => '',
				'confirm_password_err' => ''
			];

			// Validation checks
			if (empty($data['firstname'])) {
				$data['firstname_err'] = 'Please enter your first name';
			}

			if (empty($data['lastname'])) {
				$data['lastname_err'] = 'Please enter your last name';
			}

			if (empty($data['email'])) {
				$data['email_err'] = 'Please enter your email address';
			} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
				$data['email_err'] = 'Please enter a valid email address';
			}

			if (empty($data['office'])) {
				$data['office_err'] = 'Please select an office';
			}

			// Validate password only if it is being changed
			if (!empty($data['password'])) {
				if (strlen($data['password']) < 6) {
					$data['password_err'] = 'Password must be at least 6 characters';
				}

				if ($data['password'] !== $data['confirm_password']) {
					$data['confirm_password_err'] = 'Passwords do not match';
				}
			}

			// If there are no validation errors, update the user
			if (empty($data['firstname_err']) && empty($data['lastname_err']) && empty($data['email_err']) && empty($data['office_err']) && empty($data['password_err']) && empty($data['confirm_password_err'])) {
				$data['password'] = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : $user->password; // Use hashed password if changed
				if ($this->userModel->updateUser($data)) {
					flash('profile_message', 'Profile updated successfully');
					redirect('users/profile');
				} else {
					die('Something went wrong while updating the profile');
				}
			} else {
				// Load the profile view with errors
				$this->view('users/profile', $data);
			}
		} else {
			// Load user data for the profile form
			$data = [
				'id_number' => $user->id_number, // Display but not editable
				'firstname' => $user->firstname,
				'lastname' => $user->lastname,
				'middle_name' => $user->middle_name,
				'email' => $user->email,
				'office' => $user->office,
				'role' => $user->role,
				'status' => $user->status,
				'offices' => $offices,
				'password' => '', // Password is blank initially
				'confirm_password' => '', // Confirm password is blank initially
			];

			// Load the profile view with user data
			$this->view('users/profile', $data);
		}
	}

	

}
?>
