<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class Users extends Controller {
	
    public function __construct() {
        $this->userModel = $this->model('User');
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

            // Check if user is active
            if($this->userModel->isActive($data['email'])) {
                // Check for user/email
                if($this->userModel->findUserByEmail($data['email'])) {
                    // User found
                } else {
                    $data['email_err'] = 'No user found';
                }
            } else {
                $data['email_err'] = 'User is inactive';
            }

            // Make sure errors are empty
            if(empty($data['email_err']) && empty($data['password_err'])) {
                // Check and set logged in user
                $loggedInUser = $this->userModel->login($data['email'], $data['password']);

                if($loggedInUser) {
                    // Create Session
                    $this->createUserSession($loggedInUser);
                } else {
                    $data['password_err'] = 'Password incorrect';

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
		redirect('posts');
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
		// Check for POST request
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
				'office_err' => ''
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
				'office_err' => ''
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
			// Server settings
			$mail->isSMTP();
			$mail->Host = 'smtp.gmail.com';  // Replace with your SMTP server
			$mail->SMTPAuth = true;
			$mail->Username = 'rainier.delacruz@nfa.gov.ph';  // Your email
			$mail->Password = 'vlrr dkmu rvib emma';          // Your email password
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 587;

			// Recipients
			$mail->setFrom('rainier.delacruz@nfa.gov.ph', 'Rainier Dela Cruz');  // Your email and name
			$mail->addAddress($email, $firstname);  // The recipient's email and name

			// Content for the registrant
			$mail->isHTML(true);
			$mail->Subject = 'Registration Successful';
			$mail->Body    = 'Hi ' . $firstname . ',<br>Thank you for registering on our website. Your account is subject for approval. Thank you!';
			$mail->send();

			// Notify the sender (yourself)
			$mail->clearAddresses();  // Clear previous recipients
			$mail->addAddress('rainier.delacruz@nfa.gov.ph');  // Sender email
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
        $users = $this->userModel->getUsers();
        $data = ['users' => $users];
        $this->view('users/manage', $data);
    }

    public function activate($id) {
		Middleware::checkLoggedIn();
		$user_id = $_SESSION['user_id']; // Get the current admin's user_id from the session

		if ($this->userModel->updateStatus($id, 'active', $user_id)) {
			$this->redirect('users/manage');
		} else {
			die('Something went wrong');
		}
	}

	public function deactivate($id) {
		Middleware::checkLoggedIn();
		$user_id = $_SESSION['user_id']; // Get the current admin's user_id from the session

		if ($this->userModel->updateStatus($id, 'inactive', $user_id)) {
			$this->redirect('users/manage');
		} else {
			die('Something went wrong');
		}
	}

	public function changeRole($id, $role) {
		Middleware::checkLoggedIn();
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
		$keyword = trim($_POST['keyword']);
		$users = $this->userModel->searchUsers($keyword);
		$data = [
			'users' => $users,
			'keyword' => $keyword
		];
		$this->view('users/manage', $data);
	}

}
?>
