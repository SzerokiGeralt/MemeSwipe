<?php

require_once 'Routing.php';
require_once 'Repository.php';

class CardsRepository extends Repository{

    // Example method to get a card by its ID
    public function getCardsByTitle(string $searchString)
    {
        $searchString = '%' . strtolower($searchString) . '%';

        $stmt = $this->database->connect()->prepare('
            SELECT * FROM cards
            WHERE LOWER(title) LIKE :search OR LOWER(description) LIKE :search
        ');
        $stmt->bindParam(':search', $searchString, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search() {
        // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //     $searchString = $_POST['search'] ?? '';

        //     if (empty($searchString)) {
        //         return [];
        //     }

        //     return $this->getCardsByTitle($searchString);
        // }

        // return [];
        die("cards");
    }
}