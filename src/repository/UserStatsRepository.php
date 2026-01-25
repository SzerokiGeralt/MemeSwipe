<?php

require_once 'Repository.php';

class UserStatsRepository extends Repository {
    
    public function getStatsByUserId(int $userId) {
        $query = $this->database->connect()->prepare('
            SELECT * FROM user_stats WHERE user_id = :user_id
        ');
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();
        $stats = $query->fetch(PDO::FETCH_ASSOC);
        $query = null;
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
}
