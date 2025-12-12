<?php
require_once 'AppController.php';

class QuestsController extends AppController {
    public function index(?string $id) {
        if (!isset($_SESSION['user_id'])) {
                // Not logged in - redirect to login page
                header('Location: /login');
                exit();
            }
        $user = ['id' => $id];
        return $this->render('quests', ['user' => $user]);
    }
}