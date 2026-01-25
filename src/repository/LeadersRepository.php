<?php
require_once 'Repository.php';

class LeadersRepository extends Repository {
    
    public function getTopUsersByUpvotes(string $period = 'all') {
        $dateCondition = $this->getDateCondition($period);
        
        $query = $this->database->connect()->prepare("
            SELECT 
                u.id,
                u.username,
                u.profile_photo,
                us.level,
                us.diamonds,
                us.streak,
                COALESCE(SUM(p.upvotes), 0) as total_upvotes,
                COUNT(p.id) as posts_count
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id $dateCondition
            LEFT JOIN user_stats us ON u.id = us.user_id
            WHERE u.enabled = TRUE
            GROUP BY u.id, u.username, u.profile_photo, us.level, us.diamonds, us.streak
            ORDER BY total_upvotes DESC
            LIMIT 100
        ");
        
        $query->execute();
        $leaders = $query->fetchAll(PDO::FETCH_ASSOC);
        $query = null;
        return $leaders;
    }
    
    private function getDateCondition(string $period) {
        switch($period) {
            case 'weekly':
                return "AND p.timestamp >= NOW() - INTERVAL '7 days'";
            case 'monthly':
                return "AND p.timestamp >= NOW() - INTERVAL '1 month'";
            case 'yearly':
                return "AND p.timestamp >= NOW() - INTERVAL '1 year'";
            case 'all':
            default:
                return "";
        }
    }
}
