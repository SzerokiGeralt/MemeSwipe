<?php

require_once 'Repository.php';

class QuestsRepository extends Repository {
    
    /**
     * Get all available quests from database
     */
    public function getAllQuests(): array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM quests ORDER BY action_type, count ASC
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get weekly quests for user - creates them if not exist or expired
     */
    public function getWeeklyQuestsForUser(int $userId): array {
        // First, check if user has active weekly quests
        $currentQuests = $this->getActiveWeeklyQuests($userId);
        
        // If no quests or all expired, generate new ones
        if (empty($currentQuests) || $this->areQuestsExpired($currentQuests)) {
            $this->generateWeeklyQuests($userId);
            $currentQuests = $this->getActiveWeeklyQuests($userId);
        }
        
        return $currentQuests;
    }
    
    /**
     * Get active weekly quests for user
     */
    private function getActiveWeeklyQuests(int $userId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT q.*, uq.progress, uq.completed, uq.expiration_date
            FROM quests q
            INNER JOIN user_quests uq ON q.id = uq.quest_id
            WHERE uq.user_id = :user_id
            ORDER BY uq.completed ASC, q.reward DESC
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if quests are expired (past expiration date)
     */
    private function areQuestsExpired(array $quests): bool {
        if (empty($quests)) return true;
        
        $now = new DateTime();
        foreach ($quests as $quest) {
            if ($quest['expiration_date']) {
                $expDate = new DateTime($quest['expiration_date']);
                if ($expDate > $now) {
                    return false; // At least one quest is still valid
                }
            }
        }
        return true;
    }
    
    /**
     * Generate 4 random weekly quests for user
     */
    private function generateWeeklyQuests(int $userId): void {
        // First, delete old quests for this user
        $this->deleteUserQuests($userId);
        
        // Get all available quests
        $allQuests = $this->getAllQuests();
        
        if (count($allQuests) < 4) {
            return; // Not enough quests in database
        }
        
        // Shuffle and pick 4 random quests (try to get variety by action_type)
        $questsByType = [];
        foreach ($allQuests as $quest) {
            $type = $quest['action_type'];
            if (!isset($questsByType[$type])) {
                $questsByType[$type] = [];
            }
            $questsByType[$type][] = $quest;
        }
        
        $selectedQuests = [];
        $types = array_keys($questsByType);
        shuffle($types);
        
        // Try to select one quest from each type first
        foreach ($types as $type) {
            if (count($selectedQuests) >= 4) break;
            if (!empty($questsByType[$type])) {
                $randomIndex = array_rand($questsByType[$type]);
                $selectedQuests[] = $questsByType[$type][$randomIndex];
                unset($questsByType[$type][$randomIndex]);
            }
        }
        
        // If we still need more quests, pick randomly from remaining
        if (count($selectedQuests) < 4) {
            $remaining = [];
            foreach ($questsByType as $quests) {
                $remaining = array_merge($remaining, $quests);
            }
            shuffle($remaining);
            
            while (count($selectedQuests) < 4 && !empty($remaining)) {
                $selectedQuests[] = array_shift($remaining);
            }
        }
        
        // Calculate expiration date (end of current week - Sunday 23:59:59)
        $expirationDate = $this->getWeekEndDate();
        
        // Insert selected quests for user
        foreach ($selectedQuests as $quest) {
            $this->assignQuestToUser($userId, $quest['id'], $expirationDate);
        }
    }
    
    /**
     * Get the end of the current week (Sunday 23:59:59)
     */
    private function getWeekEndDate(): string {
        $date = new DateTime();
        $dayOfWeek = (int)$date->format('N'); // 1 = Monday, 7 = Sunday
        $daysUntilSunday = 7 - $dayOfWeek;
        $date->modify("+{$daysUntilSunday} days");
        $date->setTime(23, 59, 59);
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * Delete all quests for user
     */
    private function deleteUserQuests(int $userId): void {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM user_quests WHERE user_id = :user_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    /**
     * Assign a quest to user
     */
    private function assignQuestToUser(int $userId, int $questId, string $expirationDate): bool {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO user_quests (user_id, quest_id, progress, completed, expiration_date)
            VALUES (:user_id, :quest_id, 0, false, :expiration_date)
            ON CONFLICT (user_id, quest_id) DO NOTHING
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':quest_id', $questId, PDO::PARAM_INT);
        $stmt->bindParam(':expiration_date', $expirationDate, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    
    public function getUserQuestProgress(int $userId, int $questId): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM user_quests 
            WHERE user_id = :user_id AND quest_id = :quest_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':quest_id', $questId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Update quest progress (increments by amount)
     */
    public function incrementQuestProgress(int $userId, int $questId, int $amount = 1): bool {
        $stmt = $this->database->connect()->prepare('
            UPDATE user_quests 
            SET progress = progress + :amount
            WHERE user_id = :user_id AND quest_id = :quest_id AND completed = false
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':quest_id', $questId, PDO::PARAM_INT);
        $stmt->bindParam(':amount', $amount, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Set quest progress to specific value
     */
    public function setQuestProgress(int $userId, int $questId, int $progress): bool {
        $stmt = $this->database->connect()->prepare('
            UPDATE user_quests 
            SET progress = :progress
            WHERE user_id = :user_id AND quest_id = :quest_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':quest_id', $questId, PDO::PARAM_INT);
        $stmt->bindParam(':progress', $progress, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Complete quest and mark as done
     */
    public function completeQuest(int $userId, int $questId): bool {
        $stmt = $this->database->connect()->prepare('
            UPDATE user_quests 
            SET completed = true 
            WHERE user_id = :user_id AND quest_id = :quest_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':quest_id', $questId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Get all quests by action type for a user (for tracking progress)
     */
    public function getUserQuestsByActionType(int $userId, string $actionType): array {
        $stmt = $this->database->connect()->prepare('
            SELECT q.*, uq.progress, uq.completed
            FROM quests q
            INNER JOIN user_quests uq ON q.id = uq.quest_id
            WHERE uq.user_id = :user_id AND q.action_type = :action_type AND uq.completed = false
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':action_type', $actionType, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Progress quests by action type (call when user does action)
     * Returns completed quests with rewards
     */
    public function progressQuestsByAction(int $userId, string $actionType, int $currentValue): array {
        $completedQuests = [];
        $quests = $this->getUserQuestsByActionType($userId, $actionType);
        
        foreach ($quests as $quest) {
            // For streak type, set progress to current value
            // For other types, use current value directly
            if ($actionType === 'streak') {
                $this->setQuestProgress($userId, $quest['id'], $currentValue);
            } else {
                $this->incrementQuestProgress($userId, $quest['id'], 1);
            }
            
            // Check if quest is now complete
            $updatedQuest = $this->getUserQuestProgress($userId, $quest['id']);
            if ($updatedQuest && $updatedQuest['progress'] >= $quest['count'] && !$updatedQuest['completed']) {
                $this->completeQuest($userId, $quest['id']);
                $completedQuests[] = [
                    'quest' => $quest,
                    'reward' => $quest['reward']
                ];
            }
        }
        
        return $completedQuests;
    }
    
    /**
     * Get time until quests reset
     */
    public function getTimeUntilReset(): array {
        $now = new DateTime();
        $endOfWeek = new DateTime();
        $dayOfWeek = (int)$endOfWeek->format('N');
        $daysUntilSunday = 7 - $dayOfWeek;
        $endOfWeek->modify("+{$daysUntilSunday} days");
        $endOfWeek->setTime(23, 59, 59);
        
        $interval = $now->diff($endOfWeek);
        
        return [
            'days' => $interval->d,
            'hours' => $interval->h,
            'minutes' => $interval->i,
            'seconds' => $interval->s,
            'total_seconds' => ($interval->days * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s,
            'reset_date' => $endOfWeek->format('Y-m-d H:i:s')
        ];
    }
}
