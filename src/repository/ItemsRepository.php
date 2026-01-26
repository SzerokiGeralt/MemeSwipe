<?php

require_once 'Repository.php';

class ItemsRepository extends Repository {
    
    /**
     * Get all items from the store
     */
    public function getAllItems(): array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM items ORDER BY cost ASC
        ');
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get items that user can buy (considering max_quantity)
     * Returns items with a flag indicating if user already owns them
     */
    public function getAvailableItemsForUser(int $userId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT i.*, 
                   CASE WHEN ui.id IS NOT NULL THEN true ELSE false END as owned_by_user
            FROM items i
            LEFT JOIN user_items ui ON i.id = ui.item_id AND ui.user_id = :user_id
            ORDER BY i.cost ASC
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if user owns a specific item
     */
    public function userOwnsItem(int $userId, int $itemId): bool {
        $stmt = $this->database->connect()->prepare('
            SELECT COUNT(*) FROM user_items 
            WHERE user_id = :user_id AND item_id = :item_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Add item to user's inventory
     */
    public function addItemToUser(int $userId, int $itemId): bool {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO user_items (user_id, item_id)
            VALUES (:user_id, :item_id)
            ON CONFLICT (user_id, item_id) DO NOTHING
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':item_id', $itemId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Get item by ID
     */
    public function getItemById(int $itemId): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM items WHERE id = :item_id
        ');
        $stmt->bindParam(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get all items owned by user
     */
    public function getUserItems(int $userId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT i.* 
            FROM items i
            INNER JOIN user_items ui ON i.id = ui.item_id
            WHERE ui.user_id = :user_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if user owns item by name
     */
    public function userOwnsItemByName(int $userId, string $itemName): bool {
        $stmt = $this->database->connect()->prepare('
            SELECT COUNT(*) FROM user_items ui
            INNER JOIN items i ON ui.item_id = i.id
            WHERE ui.user_id = :user_id AND i.name = :item_name
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':item_name', $itemName, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get VIP status for a user (checks owned items)
     */
    public function getUserVipStatus(int $userId): array {
        $ownedItems = $this->getUserItems($userId);
        $itemNames = array_column($ownedItems, 'name');
        
        return [
            'has_vip_badge' => in_array('VIP Badge', $itemNames),
            'has_golden_profile' => in_array('Golden Page', $itemNames),
            'has_avatar_frame' => in_array('Avatar Frame', $itemNames),
            'has_cooldown_reducer' => in_array('Cooldown Reducer', $itemNames)
        ];
    }
}
