<?php


require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SecurityController extends AppController {

    private static $instance = null;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }
    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private $userRepository;

    

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
            
            // Successful login
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
}

