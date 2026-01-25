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
        ');
        $query->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT)]);
        return;
    }
}