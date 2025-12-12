<?php
require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class ProfileController extends AppController {
    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function show(?string $username) {
        // If no username provided, check if user is logged in
        if (empty($username)) {
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                // Not logged in - redirect to login page
                header('Location: /login');
                exit();
            }
            // Show logged in user's profile
            $username = $_SESSION['username'];
        }

        // Get user by username
        $user = $this->userRepository->getUserByUsername($username);

        if (!$user) {
            // User not found - show 404
            http_response_code(404);
            return $this->render('404', ['message' => "User '$username' not found."]);
        }

        // TODO: Get user stats from user_stats table
        $userStats = [
            'level' => 12,
            'streak' => 5,
            'diamonds' => 1250
        ];

        // Check if viewing own profile
        $isOwnProfile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id'];

        return $this->render('profile', [
            'user' => $user,
            'stats' => $userStats,
            'isOwnProfile' => $isOwnProfile
        ]);
    }
}