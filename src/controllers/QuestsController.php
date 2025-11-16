<?php
require_once 'AppController.php';

class QuestsController extends AppController {
    public function index(?string $id) {
        $user = ['id' => $id];
        return $this->render('quests', ['user' => $user]);
    }
}