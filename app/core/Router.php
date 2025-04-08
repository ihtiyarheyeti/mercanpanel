<?php

namespace app\core;

class Router {
    private $routes = [];
    private $params = [];

    public function add($route, $params = []) {
        // Route'u düzenli ifadeye çevir
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z0-9-]+)', $route);
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = $params;
    }

    public function match($url) {
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }
                $this->params = $params;
                return true;
            }
        }
        return false;
    }

    public function dispatch($url) {
        if ($this->match($url)) {
            $controller = $this->params['controller'];
            $controller = "app\\controllers\\$controller";

            if (class_exists($controller)) {
                $controller_object = new $controller();

                $action = $this->params['action'];

                if (method_exists($controller_object, $action)) {
                    // Action parametrelerini kontrol et
                    $params = [];
                    
                    // URL'deki ID parametresini çıkar (users/edit/1 örneğindeki 1)
                    $url_parts = explode('/', $url);
                    if (count($url_parts) > 2) {
                        // İlk iki parça controller ve action, diğerleri parametre
                        for ($i = 2; $i < count($url_parts); $i++) {
                            $params[] = $url_parts[$i];
                        }
                    }

                    // Action'ı parametrelerle çağır
                    return call_user_func_array([$controller_object, $action], $params);
                } else {
                    throw new \Exception("Action method '$action' not found in controller '$controller'");
                }
            } else {
                throw new \Exception("Controller class '$controller' not found");
            }
        }

        // 404 sayfasına yönlendir
        header("HTTP/1.0 404 Not Found");
        require VIEW_PATH . '/error/404.php';
    }

    public function getRoutes() {
        return $this->routes;
    }

    public function getParams() {
        return $this->params;
    }
} 