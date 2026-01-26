<?php

require_once 'Repository.php';

class PostsRepository extends Repository {
    
    /**
     * Get a random post that the user hasn't voted on yet
     */
    public function getRandomUnvotedPost(int $userId): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT p.*, u.username, u.profile_photo as author_photo
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id NOT IN (
                SELECT post_id FROM user_votes WHERE user_id = :user_id
            )
            AND p.user_id != :user_id
            ORDER BY RANDOM()
            LIMIT 1
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get any random post (for non-logged users)
     */
    public function getRandomPost(): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT p.*, u.username, u.profile_photo as author_photo
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY RANDOM()
            LIMIT 1
        ');
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Check if user has already voted on a post
     */
    public function hasUserVoted(int $userId, int $postId): bool {
        $stmt = $this->database->connect()->prepare('
            SELECT COUNT(*) FROM user_votes 
            WHERE user_id = :user_id AND post_id = :post_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Add a vote record
     */
    public function addVote(int $userId, int $postId, string $voteType): bool {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO user_votes (user_id, post_id, vote_type)
            VALUES (:user_id, :post_id, :vote_type)
            ON CONFLICT (user_id, post_id) DO NOTHING
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':vote_type', $voteType, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    
    /**
     * Update post vote count
     */
    public function updateVoteCount(int $postId, string $voteType): bool {
        $column = $voteType === 'upvote' ? 'upvotes' : 'downvotes';
        
        $stmt = $this->database->connect()->prepare("
            UPDATE posts SET {$column} = {$column} + 1 WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Get post by ID
     */
    public function getPostById(int $postId): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT p.*, u.username, u.profile_photo as author_photo
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = :post_id
        ');
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Create a new post
     */
    public function createPost(int $userId, string $imageUrl): ?int {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO posts (user_id, image, upvotes, downvotes)
            VALUES (:user_id, :image, 0, 0)
            RETURNING id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':image', $imageUrl, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        }
        return null;
    }
    
    /**
     * Get total upvotes for a user's posts
     */
    public function getTotalUpvotesForUser(int $userId): int {
        $stmt = $this->database->connect()->prepare('
            SELECT COALESCE(SUM(upvotes), 0) as total_upvotes
            FROM posts WHERE user_id = :user_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total_upvotes'] ?? 0);
    }
    
    /**
     * Get posts by user ID
     */
    public function getPostsByUserId(int $userId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM posts WHERE user_id = :user_id ORDER BY timestamp DESC
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
