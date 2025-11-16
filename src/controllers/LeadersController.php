<?php
require_once 'AppController.php';

class LeadersController extends AppController {
    public function index(?string $id) {
        $user = ['id' => $id];
        return $this->render('leaders', ['user' => $user]);
    }
}