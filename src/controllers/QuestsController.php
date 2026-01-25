<?php
require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class QuestsController extends AppController {
    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function index(?string $id) {
        if (!isset($_SESSION['user_id'])) {
            // Not logged in - redirect to login page
            header('Location: /login');
            exit();
        }

        // Get current user stats for navbar
        $userStats = $this->userRepository->getUserById($_SESSION['user_id']);

        return $this->render('quests', [
            'stats' => $userStats
        ]);
    }
}