<?php

require_once 'src/controllers/SecurityController.php';


//TODO: Controller singleton
//TODO: przechwytywanie regeg w index
class Routing {

    public static $routes = [
        'login' => ['controller' => 'SecurityController', 'action' => 'login'],
        'register' => ['controller' => 'SecurityController', 'action' => 'register'],
        'dashboard' => ['view' => 'dashboard.html'],
    ];

    public static function run(string $path) {
        switch ($path) {
        case 'login':
        case 'register':
            $controller = self::$routes[$path]['controller'];
            $controllerObj = new $controller();
            $action = self::$routes[$path]['action'];
            $controllerObj->$action();
            break;
        case 'dashboard':
            include 'public/views/dashboard.html';
            break;
        default:
            http_response_code(404);
            include 'public/views/404.html';
            break;
        }
    }
}