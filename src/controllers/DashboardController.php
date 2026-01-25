<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class DashboardController extends AppController {

    public function __construct() {

    }

    public function index(?string $id) {
        // Wyświeli wszystkie projekty z bazy danych
        $intID = (int)$id ?? 0;
        $userRepository = new UserRepository();
        $users = $userRepository->getUsers();

        // Pobierz dane zalogowanego użytkownika
        $userStats = null;
        if (isset($_SESSION['user_id'])) {
            $userStats = $userRepository->getUserById($_SESSION['user_id']);
        }

        return $this->render('dashboard', [
            'users' => $users,
            'stats' => $userStats
        ]);
    }

}