<?php


require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SecurityController extends AppController {

    private $userRepository;
    
    public function __construct() {
        $this->userRepository = new UserRepository();
    }


    public function login() {
        // TODO: Get data from login form
        // check if user exists in database


        //TODO: Check for existing email
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userRepository->getUserByEmail($email);

            if (!$user) {
            return $this->render('login', ['messages' => 'User not found']);
            }

            if (!password_verify($password, $user['password'])) {
                return $this->render('login', ['messages' => 'Wrong password']);
            }
            
            // Successful login - store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/dashboard");
            exit();

        }

        return $this->render('login');
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Simple validation
            if (empty($username) || empty($email) || empty($password)) {
                return $this->render('register', ['messages' => 'All fields are required']);
            }

            // Check if email already exists
            if ($this->userRepository->getUserByEmail($email)) {
                return $this->render('register', ['messages' => 'Email already registered']);
            }

            // Create user in the database
            $this->userRepository->createUser($username, $email, $password);

            // Redirect to login page after successful registration
            header('Location: /login');
            exit();
        }
        return $this->render('register');
    }

    public function logout() {
        // Destroy session and redirect to login
        session_unset();
        session_destroy();
        header('Location: /login');
        exit();
    }
}

