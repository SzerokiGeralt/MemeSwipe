<?php
require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/ItemsRepository.php';
require_once __DIR__ . '/../repository/QuestsRepository.php';
require_once __DIR__ . '/../repository/UserStatsRepository.php';

class QuestsController extends AppController {
    private $userRepository;
    private $itemsRepository;
    private $questsRepository;
    private $userStatsRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->itemsRepository = new ItemsRepository();
        $this->questsRepository = new QuestsRepository();
        $this->userStatsRepository = new UserStatsRepository();
    }

    public function index(?string $id) {
        if (!isset($_SESSION['user_id'])) {
            // Not logged in - redirect to login page
            header('Location: /login');
            exit();
        }

        $userId = $_SESSION['user_id'];

        // Get current user stats for navbar (with expPercentage calculated)
        $userStats = $this->userStatsRepository->getStatsByUserId($userId);

        // Get available items for the user
        $items = $this->itemsRepository->getAvailableItemsForUser($userId);

        // Get weekly quests for the user (generates if needed)
        $quests = $this->questsRepository->getWeeklyQuestsForUser($userId);
        
        // Get time until reset
        $resetInfo = $this->questsRepository->getTimeUntilReset();

        return $this->render('quests', [
            'stats' => $userStats,
            'items' => $items,
            'quests' => $quests,
            'resetInfo' => $resetInfo
        ]);
    }
    
    /**
     * Claim reward for completed quest
     */
    public function claimReward() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $questId = $data['quest_id'] ?? null;

        if (!$questId) {
            echo json_encode(['success' => false, 'message' => 'Invalid quest ID']);
            return;
        }

        // Get quest progress
        $questProgress = $this->questsRepository->getUserQuestProgress($userId, $questId);
        if (!$questProgress) {
            echo json_encode(['success' => false, 'message' => 'Quest not found']);
            return;
        }
        
        // Check if already completed and claimed
        if ($questProgress['completed']) {
            echo json_encode(['success' => false, 'message' => 'Quest already claimed']);
            return;
        }
        
        // Get quest details
        $quests = $this->questsRepository->getWeeklyQuestsForUser($userId);
        $quest = null;
        foreach ($quests as $q) {
            if ($q['id'] == $questId) {
                $quest = $q;
                break;
            }
        }
        
        if (!$quest) {
            echo json_encode(['success' => false, 'message' => 'Quest not found']);
            return;
        }
        
        // Check if progress meets requirement
        if ($questProgress['progress'] < $quest['count']) {
            echo json_encode(['success' => false, 'message' => 'Quest not completed yet']);
            return;
        }
        
        // Mark as completed and give reward
        $this->questsRepository->completeQuest($userId, $questId);
        $this->userStatsRepository->updateDiamonds($userId, $quest['reward']);
        
        // Get updated stats
        $updatedStats = $this->userStatsRepository->getStatsByUserId($userId);
        
        echo json_encode([
            'success' => true,
            'message' => "Quest completed! You earned {$quest['reward']} 💎",
            'reward' => $quest['reward'],
            'new_diamonds' => $updatedStats['diamonds']
        ]);
    }

    public function purchase() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $itemId = $data['item_id'] ?? null;

        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
            return;
        }

        // Get item details
        $item = $this->itemsRepository->getItemById($itemId);
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            return;
        }

        // Get user stats
        $userStats = $this->userStatsRepository->getStatsByUserId($userId);
        if (!$userStats) {
            echo json_encode(['success' => false, 'message' => 'User stats not found']);
            return;
        }

        // Check if user can afford
        if ($userStats['diamonds'] < $item['cost']) {
            echo json_encode(['success' => false, 'message' => 'Not enough diamonds']);
            return;
        }

        // Check if item is limited and already owned
        if ($item['max_quantity'] > 0 && $this->itemsRepository->userOwnsItem($userId, $itemId)) {
            echo json_encode(['success' => false, 'message' => 'You already own this item']);
            return;
        }

        // Deduct diamonds
        $this->userStatsRepository->updateDiamonds($userId, -$item['cost']);

        // Apply item effect based on item name/id
        $result = $this->applyItemEffect($userId, $item);

        echo json_encode($result);
    }

    private function applyItemEffect(int $userId, array $item): array {
        $response = ['success' => true, 'message' => 'Purchase successful!'];

        switch ($item['name']) {
            case 'Instant Level Up':
                $this->userStatsRepository->updateLevel($userId, 1);
                $response['message'] = 'Level up! You gained 1 level! 🎉';
                $response['effect'] = 'level_up';
                break;

            case 'Streak Booster':
                $this->userStatsRepository->updateStreak($userId, 1);
                $response['message'] = 'Streak increased by 1 day! 🔥';
                $response['effect'] = 'streak_boost';
                break;

            case 'Mystery Box':
                // Random rewards
                $rewards = [
                    ['type' => 'diamonds', 'amount' => rand(50, 200)],
                    ['type' => 'experience', 'amount' => rand(100, 500)],
                    ['type' => 'both', 'diamonds' => rand(25, 100), 'experience' => rand(50, 250)]
                ];
                $reward = $rewards[array_rand($rewards)];
                
                if ($reward['type'] === 'diamonds') {
                    $this->userStatsRepository->updateDiamonds($userId, $reward['amount']);
                    $response['message'] = "Mystery Box opened! You got {$reward['amount']} diamonds! 💎";
                } elseif ($reward['type'] === 'experience') {
                    $this->userStatsRepository->updateExperience($userId, $reward['amount']);
                    $response['message'] = "Mystery Box opened! You got {$reward['amount']} XP! ⭐";
                } else {
                    $this->userStatsRepository->updateDiamonds($userId, $reward['diamonds']);
                    $this->userStatsRepository->updateExperience($userId, $reward['experience']);
                    $response['message'] = "Mystery Box opened! You got {$reward['diamonds']} diamonds and {$reward['experience']} XP! 🎁";
                }
                $response['effect'] = 'mystery_box';
                break;

            case 'Avatar Frame':
                $this->itemsRepository->addItemToUser($userId, $item['id']);
                $response['message'] = "Avatar Frame unlocked! Your profile photo now has a special frame! ✨";
                $response['effect'] = 'avatar_frame';
                break;

            case 'VIP Badge':
                $this->itemsRepository->addItemToUser($userId, $item['id']);
                $response['message'] = "VIP Badge unlocked! You now have a crown badge on your profile! 👑";
                $response['effect'] = 'vip_badge';
                break;

            case 'Golden Page':
                $this->itemsRepository->addItemToUser($userId, $item['id']);
                $response['message'] = "Golden Profile unlocked! Your profile now has a golden theme! 🌟";
                $response['effect'] = 'golden_profile';
                break;

            case 'Cooldown Reducer':
                $this->itemsRepository->addItemToUser($userId, $item['id']);
                $response['message'] = "Cooldown Reducer activated! Upload cooldown reduced to 8 hours! ⏰";
                $response['effect'] = 'cooldown_reducer';
                break;

            default:
                $response['message'] = 'Item purchased successfully!';
                break;
        }

        // Get updated stats
        $updatedStats = $this->userStatsRepository->getStatsByUserId($userId);
        $response['new_diamonds'] = $updatedStats['diamonds'];
        $response['new_level'] = $updatedStats['level'];
        $response['new_streak'] = $updatedStats['streak'];
        $response['new_experience'] = $updatedStats['experience'];

        return $response;
    }
}