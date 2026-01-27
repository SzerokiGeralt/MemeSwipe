<?php
require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserStatsRepository.php';
require_once __DIR__ . '/../repository/ItemsRepository.php';
require_once __DIR__ . '/../repository/PostsRepository.php';
require_once __DIR__ . '/../repository/QuestsRepository.php';

class UploadController extends AppController {
    private $userStatsRepository;
    private $itemsRepository;
    private $postsRepository;
    private $questsRepository;
    
    const DEFAULT_COOLDOWN_HOURS = 24;
    const REDUCED_COOLDOWN_HOURS = 8;
    
    public function __construct() {
        $this->userStatsRepository = new UserStatsRepository();
        $this->itemsRepository = new ItemsRepository();
        $this->postsRepository = new PostsRepository();
        $this->questsRepository = new QuestsRepository();
    }
    
    public function upload(?string $id) {
        if (!isset($_SESSION['user_id'])) {
            // Not logged in - redirect to login page
            header('Location: /login');
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        
        // Get user stats for navbar
        $userStats = $this->userStatsRepository->getStatsByUserId($userId);
        
        // Check cooldown status
        $cooldownInfo = $this->getCooldownInfo($userId, $userStats);
        
        $user = ['id' => $userId];
        return $this->render('upload', [
            'user' => $user,
            'stats' => $userStats,
            'cooldown' => $cooldownInfo
        ]);
    }
    
    /**
     * Get cooldown info for user
     * Uses database timestamp for per-user cooldown tracking
     */
    private function getCooldownInfo(int $userId, array $userStats): array {
        // Check if user has cooldown reducer item
        $hasCooldownReducer = $this->itemsRepository->userOwnsItemByName($userId, 'Cooldown Reducer');
        $cooldownHours = $hasCooldownReducer ? self::REDUCED_COOLDOWN_HOURS : self::DEFAULT_COOLDOWN_HOURS;
        
        // Use database timestamp for user-specific cooldown
        $lastUploadDate = $userStats['last_upload_date'] ?? null;
        
        if (!$lastUploadDate) {
            // Never uploaded - can upload now (first upload is always available)
            return [
                'can_upload' => true,
                'cooldown_hours' => $cooldownHours,
                'has_reducer' => $hasCooldownReducer,
                'time_remaining' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0
            ];
        }
        
        // Parse the database timestamp
        $lastUploadTimestamp = strtotime($lastUploadDate);
        $now = time();
        $nextUploadTime = $lastUploadTimestamp + ($cooldownHours * 3600);
        
        if ($now >= $nextUploadTime) {
            // Cooldown expired - can upload
            return [
                'can_upload' => true,
                'cooldown_hours' => $cooldownHours,
                'has_reducer' => $hasCooldownReducer,
                'time_remaining' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0
            ];
        }
        
        // Still in cooldown
        $totalSeconds = $nextUploadTime - $now;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        
        return [
            'can_upload' => false,
            'cooldown_hours' => $cooldownHours,
            'has_reducer' => $hasCooldownReducer,
            'time_remaining' => $totalSeconds,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
            'next_upload_time' => date('Y-m-d H:i:s', $nextUploadTime)
        ];
    }
    
    /**
     * Handle file upload
     */
    public function uploadFile() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $userStats = $this->userStatsRepository->getStatsByUserId($userId);
        
        // Check cooldown
        $cooldownInfo = $this->getCooldownInfo($userId, $userStats);
        if (!$cooldownInfo['can_upload']) {
            echo json_encode([
                'success' => false, 
                'message' => "Please wait {$cooldownInfo['hours']}h {$cooldownInfo['minutes']}m before uploading again"
            ]);
            return;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
            return;
        }
        
        $file = $_FILES['image'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP']);
            return;
        }
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
            return;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('meme_', true) . '.' . $extension;
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../public/uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            return;
        }
        
        // Create post in database
        $imageUrl = '/public/uploads/' . $filename;
        $postId = $this->postsRepository->createPost($userId, $imageUrl);
        
        if (!$postId) {
            // Clean up file if database insert failed
            unlink($uploadPath);
            echo json_encode(['success' => false, 'message' => 'Failed to create post']);
            return;
        }
        
        // Update user stats
        $this->userStatsRepository->updateLastUploadDate($userId);
        $this->userStatsRepository->incrementPostsCount($userId);
        
        // Progress upload quests
        $completedQuests = $this->questsRepository->progressQuestsByAction($userId, 'upload', 1);
        
        // Calculate rewards from completed quests
        $totalReward = 0;
        foreach ($completedQuests as $completed) {
            $totalReward += $completed['reward'];
            $this->userStatsRepository->updateDiamonds($userId, $completed['reward']);
        }
        
        // Get updated stats
        $updatedStats = $this->userStatsRepository->getStatsByUserId($userId);
        $newCooldown = $this->getCooldownInfo($userId, $updatedStats);
        
        $response = [
            'success' => true,
            'message' => 'Meme uploaded successfully!',
            'post_id' => $postId,
            'new_posts_count' => $updatedStats['posts_count'],
            'cooldown' => $newCooldown
        ];
        
        if (!empty($completedQuests)) {
            $response['quests_completed'] = count($completedQuests);
            $response['quest_reward'] = $totalReward;
            $response['new_diamonds'] = $updatedStats['diamonds'];
        }
        
        echo json_encode($response);
    }
    
    /**
     * Get current cooldown status (AJAX)
     */
    public function getCooldown() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $userStats = $this->userStatsRepository->getStatsByUserId($userId);
        $cooldownInfo = $this->getCooldownInfo($userId, $userStats);
        
        echo json_encode([
            'success' => true,
            'cooldown' => $cooldownInfo
        ]);
    }
}