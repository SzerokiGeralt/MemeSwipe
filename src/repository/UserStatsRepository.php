<?php

require_once 'Repository.php';

class UserStatsRepository extends Repository {

    public function updateLastActiveDate(int $userId) {
        $date = date('Y-m-d');
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET last_active_date = :date WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':date', $date, PDO::PARAM_STR);
        $query->execute();
        $query = null;
    }
    
    /**
     * Check and process streak for a user
     * Returns: 'extended' | 'maintained' | 'lost' | 'new'
     */
    public function checkAndProcessStreak(int $userId): array {
        $stats = $this->getStatsByUserId($userId);
        if (!$stats) {
            return ['status' => 'error', 'message' => 'Stats not found'];
        }
        
        $lastActiveDate = $stats['last_active_date'];
        $currentStreak = $stats['streak'];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // First time user (streak is 0 or no last_active_date)
        if (!$lastActiveDate || $currentStreak == 0) {
            $this->updateLastActiveDate($userId);
            $this->setStreak($userId, 1);
            if ($stats['longest_streak'] < 1) {
                $this->updateLongestStreak($userId, 1);
            }
            return [
                'status' => 'new',
                'streak' => 1,
                'message' => 'Welcome! Your streak begins today!'
            ];
        }
        
        // Already logged in today - streak maintained
        if ($lastActiveDate === $today) {
            return [
                'status' => 'maintained',
                'streak' => $currentStreak,
                'message' => 'Streak already counted for today'
            ];
        }
        
        // Logged in yesterday - extend streak
        if ($lastActiveDate === $yesterday) {
            $newStreak = $currentStreak + 1;
            $this->updateLastActiveDate($userId);
            $this->setStreak($userId, $newStreak);
            
            // Update longest streak if needed
            if ($newStreak > $stats['longest_streak']) {
                $this->updateLongestStreak($userId, $newStreak);
            }
            
            return [
                'status' => 'extended',
                'streak' => $newStreak,
                'previousStreak' => $currentStreak,
                'message' => "Streak extended to {$newStreak} days!"
            ];
        }
        
        // More than one day missed - streak lost
        $lostStreak = $currentStreak;
        $this->updateLastActiveDate($userId);
        $this->setStreak($userId, 1);
        
        // Only show lost popup if there was actually a streak to lose
        if ($lostStreak > 1) {
            return [
                'status' => 'lost',
                'streak' => 1,
                'lostStreak' => $lostStreak,
                'daysMissed' => (int)((strtotime($today) - strtotime($lastActiveDate)) / 86400),
                'message' => "Streak lost! You missed {$lostStreak} day streak. Starting fresh with day 1."
            ];
        }
        
        // Had 1 day streak and missed - treat as new start
        return [
            'status' => 'new',
            'streak' => 1,
            'message' => 'Starting fresh with day 1!'
        ];
    }
    
    /**
     * Check if user's streak is currently active (logged in today or yesterday)
     */
    public function isStreakActive(int $userId): bool {
        $stats = $this->getStatsByUserId($userId);
        if (!$stats || !$stats['last_active_date']) {
            return false;
        }
        
        $lastActiveDate = $stats['last_active_date'];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        return ($lastActiveDate === $today || $lastActiveDate === $yesterday);
    }
    
    /**
     * Check streak status for any user (for viewing profiles)
     */
    public function getStreakStatus(int $userId): array {
        $stats = $this->getStatsByUserId($userId);
        if (!$stats) {
            return ['active' => false, 'streak' => 0];
        }
        
        $active = $this->isStreakActive($userId);
        return [
            'active' => $active,
            'streak' => $active ? $stats['streak'] : 0,
            'displayStreak' => $stats['streak'],
            'longestStreak' => $stats['longest_streak']
        ];
    }
    
    public function setStreak(int $userId, int $streak): bool {
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET streak = :streak WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':streak', $streak, PDO::PARAM_INT);
        return $query->execute();
    }
    
    public function updateLongestStreak(int $userId, int $streak): bool {
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET longest_streak = :streak WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':streak', $streak, PDO::PARAM_INT);
        return $query->execute();
    }
    
    public function getStatsByUserId(int $userId) {
        $query = $this->database->connect()->prepare('
            SELECT * FROM user_stats WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();
        $stats = $query->fetch(PDO::FETCH_ASSOC);
        $query = null;
        
        // Calculate XP needed for next level using polynomial model
        if ($stats) {
            $currentLevel = $stats['level'];
            $currentExp = $stats['experience'];
            
            // Polynomial model: Each level N requires N^2 * 100 XP to reach next level
            // Total XP needed to reach level L = sum of (i^2 * 100) for i from 1 to L-1
            
            // Calculate total XP at start of current level
            $totalXpForCurrentLevel = 0;
            for ($i = 1; $i < $currentLevel; $i++) {
                $totalXpForCurrentLevel += pow($i, 2) * 100;
            }
            
            // Calculate total XP needed to reach next level
            $totalXpForNextLevel = $totalXpForCurrentLevel + (pow($currentLevel, 2) * 100);
            
            // XP required to go from current level to next level
            $stats['expRequiredForNextLevel'] = pow($currentLevel, 2) * 100;
            
            // XP remaining to reach next level
            $stats['expToNextLevel'] = $totalXpForNextLevel - $currentExp;
            
            // Current progress within this level (for progress bar, 0-1)
            $expInCurrentLevel = $currentExp - $totalXpForCurrentLevel;
            if ($stats['expRequiredForNextLevel'] > 0) {
                $stats['expPercentage'] = $expInCurrentLevel / $stats['expRequiredForNextLevel'];
            } else {
                $stats['expPercentage'] = 0;
            }
            
            // Add current XP within level for display
            $stats['expInCurrentLevel'] = max(0, $expInCurrentLevel);
            
            // Add streak active status
            $lastActiveDate = $stats['last_active_date'];
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $stats['streakActive'] = ($lastActiveDate === $today || $lastActiveDate === $yesterday);
        }
        
        return $stats;
    }

    public function createStatsForUser(int $userId) {
        $query = $this->database->connect()->prepare('
            INSERT INTO user_stats (user_id, level, experience, diamonds, streak, longest_streak, posts_count)
            VALUES (:user_id, 1, 0, 0, 0, 0, 0)
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();
        return $this->getStatsByUserId($userId);
    }

    public function updateDiamonds(int $userId, int $amount): bool {
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET diamonds = diamonds + :amount WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':amount', $amount, PDO::PARAM_INT);
        return $query->execute();
    }

    public function updateLevel(int $userId, int $amount): bool {
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET level = level + :amount WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':amount', $amount, PDO::PARAM_INT);
        return $query->execute();
    }

    public function setLevel(int $userId, int $level): bool {
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET level = :level WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':level', $level, PDO::PARAM_INT);
        return $query->execute();
    }

    public function updateExperience(int $userId, int $amount): bool {
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET experience = experience + :amount WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':amount', $amount, PDO::PARAM_INT);
        return $query->execute();
    }

    public function updateStreak(int $userId, int $amount): bool {
        $query = $this->database->connect()->prepare('
            UPDATE user_stats 
            SET streak = streak + :amount,
                longest_streak = GREATEST(longest_streak, streak + :amount)
            WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':amount', $amount, PDO::PARAM_INT);
        return $query->execute();
    }
    
    /**
     * Update last upload date to now
     */
    public function updateLastUploadDate(int $userId): bool {
        $now = date('Y-m-d H:i:s');
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET last_upload_date = :date WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':date', $now, PDO::PARAM_STR);
        return $query->execute();
    }
    
    /**
     * Increment posts count by 1
     */
    public function incrementPostsCount(int $userId): bool {
        $query = $this->database->connect()->prepare('
            UPDATE user_stats SET posts_count = posts_count + 1 WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $query->execute();
    }
}
