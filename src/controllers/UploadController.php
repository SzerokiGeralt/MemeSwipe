<?php
require_once 'AppController.php';

class UploadController extends AppController {
    public function upload(?string $id) {
        if (!isset($_SESSION['user_id'])) {
                // Not logged in - redirect to login page
                header('Location: /login');
                exit();
            }
        $user = ['id' => $id];
        return $this->render('upload', ['user' => $user]);
    }
}