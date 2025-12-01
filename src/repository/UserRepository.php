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