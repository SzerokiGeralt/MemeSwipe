<?php


require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UserStatsRepository.php';

class SecurityController extends AppController {

    private $userRepository;
    private $userStatsRepository;
    
    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->userStatsRepository = new UserStatsRepository();
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
            
            // Update last active date
            $this->userStatsRepository->updateLastActiveDate($user['id']);
            
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
            
            // Check if username already exists
            if ($this->userRepository->getUserByUsername($username)) {
                return $this->render('register', ['messages' => 'Username already taken']);
            }

            // Handle profile photo upload
            $profilePhotoPath = null;
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleProfilePhotoUpload($_FILES['profile_photo']);
                if ($uploadResult['success']) {
                    $profilePhotoPath = $uploadResult['path'];
                } else {
                    return $this->render('register', [
                        'messages' => $uploadResult['message'],
                        'username' => $username,
                        'email' => $email
                    ]);
                }
            } else {
                return $this->render('register', [
                    'messages' => 'Profile photo is required',
                    'username' => $username,
                    'email' => $email
                ]);
            }

            // Create user in the database with profile photo
            $userId = $this->userRepository->createUserWithPhoto($username, $email, $password, $profilePhotoPath);
            
            // Create initial stats for the user
            $this->userStatsRepository->createStatsForUser($userId);

            // Redirect to login page after successful registration
            header('Location: /login');
            exit();
        }
        return $this->render('register');
    }
    
    /**
     * Handle profile photo upload
     */
    private function handleProfilePhotoUpload(array $file): array {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and WebP are allowed.'];
        }
        
        // Validate file size (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . uniqid() . '_' . time() . '.' . $extension;
        
        // Create uploads/profiles directory if it doesn't exist
        $uploadDir = 'public/uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $filename;
        
        // Move the file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => false, 'message' => 'Failed to save the file.'];
        }
        
        // Return the web-accessible path
        return ['success' => true, 'path' => '/' . $uploadPath];
    }

    public function logout() {
        // Destroy session and redirect to login
        session_unset();
        session_destroy();
        header('Location: /login');
        exit();
    }
}

