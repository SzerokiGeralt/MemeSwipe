<?php
require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UserStatsRepository.php';
require_once __DIR__ . '/../repository/ItemsRepository.php';

class ProfileController extends AppController {
    private $userRepository;
    private $userStatsRepository;
    private $itemsRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->userStatsRepository = new UserStatsRepository();
        $this->itemsRepository = new ItemsRepository();
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
            $username = $_SESSION['username'];

        }
        if (isset($_SESSION['user_id'])) {
            $ownUserStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
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
        
        // Get VIP status from purchased items
        $vipStatus = $this->itemsRepository->getUserVipStatus($user['id']);
        $userStats = array_merge($userStats, $vipStatus);
        
        // Check and award new badges based on user stats
        $this->checkAndAwardBadges($user['id'], $userStats);
        
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
            'isOwnProfile' => $isOwnProfile,
            'ownStats' => $ownUserStats ?? null
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
            
            // Handle profile photo upload
            $profilePhoto = null;
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleProfilePhotoUpload($_FILES['profile_photo']);
                if ($uploadResult['success']) {
                    $profilePhoto = $uploadResult['path'];
                } else {
                    $user = $this->userRepository->getUserByUsername($_SESSION['username']);
                    $userStats = $this->userStatsRepository->getStatsByUserId($_SESSION['user_id']);
                    return $this->render('profile_edit', [
                        'user' => $user,
                        'stats' => $userStats,
                        'messages' => $uploadResult['message']
                    ]);
                }
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

    /**
     * Check user's stats and award badges they've earned
     */
    private function checkAndAwardBadges(int $userId, array $stats): void {
        // Get user's current badges to avoid checking ones they already have
        $currentBadges = $this->userRepository->getUserBadges($userId);
        $badgeNames = array_column($currentBadges, 'name');
        
        // Check "First Post" badge
        if (!in_array('First Post', $badgeNames) && $stats['posts_count'] >= 1) {
            $this->userRepository->assignBadgeToUser($userId, 'First Post');
        }
        
        // Check "Streak Master" badge (7-day streak)
        if (!in_array('Streak Master', $badgeNames) && $stats['longest_streak'] >= 7) {
            $this->userRepository->assignBadgeToUser($userId, 'Streak Master');
        }
        
        // Check "Consistent" badge (50 posts)
        if (!in_array('Consistent', $badgeNames) && $stats['posts_count'] >= 50) {
            $this->userRepository->assignBadgeToUser($userId, 'Consistent');
        }
        
        // Check "Diamond Collector" badge (1000 diamonds)
        if (!in_array('Diamond Collector', $badgeNames) && $stats['diamonds'] >= 1000) {
            $this->userRepository->assignBadgeToUser($userId, 'Diamond Collector');
        }
        
        // Check "Level 10" badge
        if (!in_array('Level 10', $badgeNames) && $stats['level'] >= 10) {
            $this->userRepository->assignBadgeToUser($userId, 'Level 10');
        }
        
        // Check "Popular" badge (100 upvotes on a single post)
        $posts = $this->userRepository->getUserPosts($userId);
        $hasPopular = false;
        $hasViral = false;
        foreach ($posts as $post) {
            if ($post['upvotes'] >= 100) {
                $hasPopular = true;
            }
            if ($post['upvotes'] >= 1000) {
                $hasViral = true;
            }
        }
        
        if (!in_array('Popular', $badgeNames) && $hasPopular) {
            $this->userRepository->assignBadgeToUser($userId, 'Popular');
        }
        
        // Check "Viral" badge (1000 upvotes on a single post)
        if (!in_array('Viral', $badgeNames) && $hasViral) {
            $this->userRepository->assignBadgeToUser($userId, 'Viral');
        }
        
        // Check "VIP" badge (if user owns VIP Badge item)
        if (!in_array('VIP', $badgeNames) && ($stats['has_vip_badge'] ?? false)) {
            $this->userRepository->assignBadgeToUser($userId, 'VIP');
        }
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
}
