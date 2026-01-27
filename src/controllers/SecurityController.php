<?php


require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UserStatsRepository.php';

class SecurityController extends AppController {

    private $userRepository;
    private $userStatsRepository;
    
    // Generic error message for login failures (security: don't reveal if email exists)
    private const LOGIN_ERROR_MESSAGE = 'Invalid email or password';
    
    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->userStatsRepository = new UserStatsRepository();
    }
    
    /**
     * Enforce HTTPS for security-sensitive pages
     */
    private function enforceHttps(): void {
        // Skip HTTPS enforcement in development/localhost
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            return;
        }
        
        // Check if request is over HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                   || ($_SERVER['SERVER_PORT'] ?? 80) == 443
                   || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        if (!$isHttps) {
            $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $httpsUrl, true, 301);
            exit();
        }
    }
    
    /**
     * Validate email format
     */
    private function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize input string
     */
    private function sanitizeInput(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }


    public function login() {
        // Enforce HTTPS for login
        $this->enforceHttps();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $this->sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Validate email format
            if (!$this->isValidEmail($email)) {
                return $this->render('login', ['messages' => self::LOGIN_ERROR_MESSAGE]);
            }

            $user = $this->userRepository->getUserByEmail($email);

            // Use generic error message - don't reveal if email exists
            if (!$user) {
                // Add small delay to prevent timing attacks
                usleep(random_int(100000, 300000)); // 100-300ms
                return $this->render('login', ['messages' => self::LOGIN_ERROR_MESSAGE]);
            }

            if (!password_verify($password, $user['password'])) {
                // Add small delay to prevent timing attacks
                usleep(random_int(100000, 300000)); // 100-300ms
                return $this->render('login', ['messages' => self::LOGIN_ERROR_MESSAGE]);
            }
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Successful login - store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            // Update last active date
            $this->userStatsRepository->updateLastActiveDate($user['id']);
            
            // Use relative redirect for security
            header("Location: /dashboard");
            exit();

        }

        return $this->render('login');
    }

    public function register() {
        // Enforce HTTPS for registration
        $this->enforceHttps();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $this->sanitizeInput($_POST['username'] ?? '');
            $email = $this->sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Simple validation
            if (empty($username) || empty($email) || empty($password)) {
                return $this->render('register', ['messages' => 'All fields are required']);
            }
            
            // Validate email format
            if (!$this->isValidEmail($email)) {
                return $this->render('register', [
                    'messages' => 'Invalid email format',
                    'username' => $username
                ]);
            }
            
            // Validate password length
            if (strlen($password) < 8) {
                return $this->render('register', [
                    'messages' => 'Password must be at least 8 characters long',
                    'username' => $username,
                    'email' => $email
                ]);
            }
            
            // Validate password confirmation
            if ($password !== $confirmPassword) {
                return $this->render('register', [
                    'messages' => 'Passwords do not match',
                    'username' => $username,
                    'email' => $email
                ]);
            }
            
            // Validate username format (alphanumeric, 3-20 chars)
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
                return $this->render('register', [
                    'messages' => 'Username must be 3-20 characters (letters, numbers, underscore only)',
                    'email' => $email
                ]);
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

