<?php

require_once 'Repository.php';

class UserRepository extends Repository {
    public function getUsers() {
        $query = $this->database->connect()->prepare('
            SELECT * FROM users
        ');
        $query->execute();
        $users = $query->fetchAll(PDO::FETCH_ASSOC);
        //closing connection
        $query = null;
        return $users;
    }

    public function getUserByEmail(string $email) {
        $query = $this->database->connect()->prepare('
            SELECT * FROM users WHERE email = :email
        ');
        $query->bindParam(':email', $email);
        $query->execute();
        $user = $query->fetch(PDO::FETCH_ASSOC);
        $query = null;
        return $user;
    }

    public function getUserByUsername(string $username) {
        $query = $this->database->connect()->prepare('
            SELECT * FROM users WHERE username = :username
        ');
        $query->bindParam(':username', $username);
        $query->execute();
        $user = $query->fetch(PDO::FETCH_ASSOC);
        $query = null;
        return $user;
    }

    public function getUserById(int $userId) {
        $query = $this->database->connect()->prepare('
            SELECT * FROM user_stats WHERE user_id = :userId
        ');
        $query->bindParam(':userId', $userId, PDO::PARAM_INT);
        $query->execute();
        $userStats = $query->fetch(PDO::FETCH_ASSOC);
        $query = null;
        return $userStats;
    }

    public function getUserBadges(int $userId) {
        $query = $this->database->connect()->prepare('
            SELECT b.* FROM badges b
            JOIN users_badges ub ON b.id = ub.badge_id
            WHERE ub.user_id = :userId
        ');
        $query->bindParam(':userId', $userId, PDO::PARAM_INT);
        $query->execute();
        $badges = $query->fetchAll(PDO::FETCH_ASSOC);
        $query = null;
        return $badges;
    }

    public function getUserPosts(int $userId) {
        $query = $this->database->connect()->prepare('
            SELECT * FROM posts WHERE user_id = :userId
        ');
        $query->bindParam(':userId', $userId);
        $query->execute();
        $posts = $query->fetchAll(PDO::FETCH_ASSOC);
        $query = null;
        return $posts;
    }

    public function createUser(
        string $username, 
        string $email, 
        string $password) {
        $query = $this->database->connect()->prepare('
            INSERT INTO users (username, email, password)
            VALUES (?, ?, ?)
            RETURNING id
        ');
        $query->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT)]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['id'] ?? null;
    }
    
    public function createUserWithPhoto(
        string $username, 
        string $email, 
        string $password,
        ?string $profilePhoto = null) {
        $query = $this->database->connect()->prepare('
            INSERT INTO users (username, email, password, profile_photo)
            VALUES (?, ?, ?, ?)
            RETURNING id
        ');
        $query->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT), $profilePhoto]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['id'] ?? null;
    }

    public function updateUser(int $userId, string $username, string $email, string $profilePhoto = null) {
        if ($profilePhoto) {
            $query = $this->database->connect()->prepare('
                UPDATE users 
                SET username = :username, email = :email, profile_photo = :profile_photo
                WHERE id = :id
            ');
            $query->bindParam(':profile_photo', $profilePhoto);
        } else {
            $query = $this->database->connect()->prepare('
                UPDATE users 
                SET username = :username, email = :email
                WHERE id = :id
            ');
        }
        
        $query->bindParam(':username', $username);
        $query->bindParam(':email', $email);
        $query->bindParam(':id', $userId, PDO::PARAM_INT);
        $query->execute();
        $query = null;
        return;
    }

    public function updatePassword(int $userId, string $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $query = $this->database->connect()->prepare('
            UPDATE users 
            SET password = :password
            WHERE id = :id
        ');
        $query->bindParam(':password', $hashedPassword);
        $query->bindParam(':id', $userId, PDO::PARAM_INT);
        $query->execute();
        $query = null;
        return;
    }

    public function assignBadgeToUser(int $userId, string $badgeName): bool {
        // First, get the badge ID by name
        $stmt = $this->database->connect()->prepare('
            SELECT id FROM badges WHERE name = :badge_name
        ');
        $stmt->bindParam(':badge_name', $badgeName);
        $stmt->execute();
        $badge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$badge) {
            return false; // Badge not found
        }
        
        $badgeId = $badge['id'];
        
        // Now, assign the badge to the user
        $stmt = $this->database->connect()->prepare('
            INSERT INTO users_badges (user_id, badge_id)
            VALUES (:user_id, :badge_id)
            ON CONFLICT (user_id, badge_id) DO NOTHING
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':badge_id', $badgeId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}