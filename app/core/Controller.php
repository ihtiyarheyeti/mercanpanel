<?php

namespace app\core;

class Controller {
    protected $route_params = [];

    public function __construct($route_params = []) {
        $this->route_params = $route_params;
    }

    public function __call($name, $args) {
        $method = $name . 'Action';
        if (method_exists($this, $method)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $method], $args);
                $this->after();
            }
        } else {
            throw new \Exception("Method $method not found in controller " . get_class($this));
        }
    }

    protected function before() {
        // Override in child classes
        return true;
    }

    protected function after() {
        // Override in child classes
    }

    protected function render($view, $params = [], $layout = 'main') {
        try {
            $view_obj = new View();
            error_log("Rendering view: $view with layout: $layout");
            $view_obj->setLayout($layout);
            $content = $view_obj->render($view, $params);
            echo $content;
        } catch (\Exception $e) {
            // Hata durumunda hata sayfasına yönlendir
            header("HTTP/1.0 500 Internal Server Error");
            echo "<pre>Error: " . $e->getMessage() . "</pre>";
            exit;
        }
    }

    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
} 