<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/CardsRepository.php';
require_once __DIR__.'/../repository/UserRepository.php';

class DashboardController extends AppController {

    private $cardsRepository;
    public function __construct() {
        $this->cardsRepository = new CardsRepository();
    }

    public function index(?string $id) {
        // Wyświeli wszystkie projekty z bazy danych
        $intID = (int)$id ?? 0;
        $userRepository = new UserRepository();
        $users = $userRepository->getUsers();
        return $this->render('dashboard', ['cards' => $cards[$intID] ?? '', 'users' => $users]);
    }

    public function search() {
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if ($contentType === "application/json") {
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);
            if(is_array($decoded)) {
                $searchString = $decoded['search'] ?? '';
                $cards = $this->cardsRepository->getCardsByTitle($searchString);
                header('Content-Type: application/json');
                echo json_encode($cards);
            } else {
                http_response_code(400);
            }
        } else {
            http_response_code(415);
        }
    }
}