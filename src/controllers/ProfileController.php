<?php
require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UserStatsRepository.php';

class ProfileController extends AppController {
    private $userRepository;
    private $userStatsRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->userStatsRepository = new UserStatsRepository();
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

        // Get user stats from user_stats table
        $userStats = $this->userStatsRepository->getStatsByUserId($user['id']);
        
        // If no stats exist, create default stats
        if (!$userStats) {
            $userStats = $this->userStatsRepository->createStatsForUser($user['id']);
        }
        
        // Update last active date if viewing own profile
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) {
            $this->userStatsRepository->updateLastActiveDate($user['id']);
        }

        $userPosts = $this->userRepository->getUserPosts($user['id']);

        $userBadges = $this->userRepository->getUserBadges($user['id']);

        // Check if viewing own profile
        $isOwnProfile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id'];

        return $this->render('profile', [
            'user' => $user,
            'stats' => $userStats,
            'posts' => $userPosts,
            'badges' => $userBadges,
            'isOwnProfile' => $isOwnProfile
        ]);
    }
}