<?php
require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class ProfileController extends AppController {
    public function show(?string $id) {
        if ($id === null || !is_numeric($id)) {
            $id = '1';
        }
        $user = ['id' => $id, 
                'imageUrlPath' => 'https://randomuser.me/api/portraits/men/' . $id . '.jpg', 
                'nickname' => 'User' . $id,
                'level' => 5 + (int)$id,
                'streak' => 3 + (int)$id];
        return $this->render('profile', ['user' => $user]);
    }
}