<?php
require_once 'AppController.php';

class UploadController extends AppController {
    public function upload(?string $id) {
        $user = ['id' => $id];
        return $this->render('upload', ['user' => $user]);
    }
}