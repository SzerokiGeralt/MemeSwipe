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

    public function edit() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }

        // Get user data
        $user = $this->userRepository->getUserByUsername($_SESSION['username']);
        $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);

        return $this->render('profile_edit', [
            'user' => $user,
            'stats' => $userStats
        ]);
    }

    public function update() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $profilePhoto = $_POST['profile_photo'] ?? '';
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validation
            if (empty($username) || empty($email)) {
                $user = $this->userRepository->getUserByUsername($_SESSION['username']);
                $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
                return $this->render('profile_edit', [
                    'user' => $user,
                    'stats' => $userStats,
                    'messages' => 'Username and email are required'
                ]);
            }

            // Check password change
            if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
                // All password fields must be filled
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $user = $this->userRepository->getUserByUsername($_SESSION['username']);
                    $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
                    return $this->render('profile_edit', [
                        'user' => $user,
                        'stats' => $userStats,
                        'messages' => 'All password fields are required to change password'
                    ]);
                }

                // Check if new passwords match
                if ($newPassword !== $confirmPassword) {
                    $user = $this->userRepository->getUserByUsername($_SESSION['username']);
                    $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
                    return $this->render('profile_edit', [
                        'user' => $user,
                        'stats' => $userStats,
                        'messages' => 'New passwords do not match'
                    ]);
                }

                // Check password length
                if (strlen($newPassword) < 6) {
                    $user = $this->userRepository->getUserByUsername($_SESSION['username']);
                    $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
                    return $this->render('profile_edit', [
                        'user' => $user,
                        'stats' => $userStats,
                        'messages' => 'New password must be at least 6 characters long'
                    ]);
                }

                // Verify current password
                $user = $this->userRepository->getUserByUsername($_SESSION['username']);
                if (!password_verify($currentPassword, $user['password'])) {
                    $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
                    return $this->render('profile_edit', [
                        'user' => $user,
                        'stats' => $userStats,
                        'messages' => 'Current password is incorrect'
                    ]);
                }

                // Update password
                $this->userRepository->updatePassword($_SESSION['user_id'], $newPassword);
            }

            // Check if username is taken by another user
            $existingUser = $this->userRepository->getUserByUsername($username);
            if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
                $user = $this->userRepository->getUserByUsername($_SESSION['username']);
                $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
                return $this->render('profile_edit', [
                    'user' => $user,
                    'stats' => $userStats,
                    'messages' => 'Username is already taken'
                ]);
            }

            // Check if email is taken by another user
            $existingEmail = $this->userRepository->getUserByEmail($email);
            if ($existingEmail && $existingEmail['id'] != $_SESSION['user_id']) {
                $user = $this->userRepository->getUserByUsername($_SESSION['username']);
                $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
                return $this->render('profile_edit', [
                    'user' => $user,
                    'stats' => $userStats,
                    'messages' => 'Email is already taken'
                ]);
            }

            // Update user data
            $this->userRepository->updateUser($_SESSION['user_id'], $username, $email, $profilePhoto);

            // Update session username
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            // Redirect to profile
            header('Location: /profile');
            exit();
        }

        header('Location: /profile/edit');
        exit();
    }
}