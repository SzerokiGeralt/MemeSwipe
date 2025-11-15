<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/ProfileController.php';


class Routing {

    public static $routes = [
        'login' => ['controller' => 'SecurityController', 'action' => 'login'],
        'register' => ['controller' => 'SecurityController', 'action' => 'register'],
        'dashboard' => ['controller' => 'DashboardController', 'action' => 'index'], 
        'profile' => ['controller' => 'ProfileController', 'action' => 'show'],
    ];

    // Singleton
    private static $controllerInstances = [];

    private static function getController(string $controllerName) {
        if (!isset(self::$controllerInstances[$controllerName])) {
            self::$controllerInstances[$controllerName] = new $controllerName();
        }
        return self::$controllerInstances[$controllerName];
    }

    public static function run(string $path) {
        $path = trim($path, '/');
        $pathParts = explode('/', $path);
        $page = $pathParts[0] ?? ''; 
        $id = $pathParts[1] ?? '';   

        switch ($page) {
        case 'login':
        case 'register':
        case 'dashboard':
        case 'profile':
            $controllerName = self::$routes[$page]['controller'];
            $action = self::$routes[$page]['action'];

            $controllerObj = self::getController($controllerName);
            $controllerObj->$action($id);
            break;
        default:
            http_response_code(404);
            include 'public/views/404.html';
            break;
        }
    }
}