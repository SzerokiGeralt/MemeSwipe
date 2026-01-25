<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/ProfileController.php';
require_once 'src/controllers/UploadController.php';
require_once 'src/controllers/QuestsController.php';
require_once 'src/controllers/LeadersController.php';


class Routing {
    //TODO:
    //Regex
    //Singleton
    //Inarray of routes
    //User session

    public static $routes = [
        'login' => ['controller' => 'SecurityController', 'action' => 'login'],
        'register' => ['controller' => 'SecurityController', 'action' => 'register'],
        'logout' => ['controller' => 'SecurityController', 'action' => 'logout'],
        'dashboard' => ['controller' => 'DashboardController', 'action' => 'index'], 
        'profile' => ['controller' => 'ProfileController', 'action' => 'show'],
        'upload' => ['controller' => 'UploadController', 'action' => 'upload'],
        'quests' => ['controller' => 'QuestsController', 'action' => 'index'],
        'leaders' => ['controller' => 'LeadersController', 'action' => 'index']
    ];

    // Special routes that require exact path matching
    public static $specialRoutes = [
        'profile/edit' => ['controller' => 'ProfileController', 'action' => 'edit'],
        'profile/update' => ['controller' => 'ProfileController', 'action' => 'update']
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
        
        // Check special routes first (exact match)
        if (array_key_exists($path, self::$specialRoutes)) {
            $controllerName = self::$specialRoutes[$path]['controller'];
            $action = self::$specialRoutes[$path]['action'];
            $controllerObj = self::getController($controllerName);
            $controllerObj->$action();
            return;
        }
        
        // Regular routes with parameters
        $pathParts = explode('/', $path);
        $page = $pathParts[0] ?? ''; 
        $param = $pathParts[1] ?? '';   

        if (!array_key_exists($page, self::$routes)) {
            http_response_code(404);
            include 'public/views/404.html';
        } else {
            $controllerName = self::$routes[$page]['controller'];
            $action = self::$routes[$page]['action'];
            $controllerObj = self::getController($controllerName);
            $controllerObj->$action($param);
        }
    }
}