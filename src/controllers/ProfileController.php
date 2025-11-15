<?php
require_once 'AppController.php';

class ProfileController extends AppController {
    public function show(?string $id) {
        $user = ['id' => $id];
        return $this->render('profile', ['user' => $user]);
    }
}