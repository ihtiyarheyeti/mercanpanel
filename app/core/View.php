<?php

namespace app\core;

class View {
    public $layout = 'main'; // Varsayılan layout

    public function render($view, $params = []) {
        $view_content = $this->renderView($view, $params);

        // Eğer bir layout belirtilmişse onu render et
        if ($this->layout) {
            $layout_content = $this->renderLayout($params);
            // View içeriğini layout'un içine yerleştir
            return str_replace('{{content}}', $view_content, $layout_content);
        } else {
            // Layout yoksa sadece view içeriğini döndür
            return $view_content;
        }
    }

    protected function renderLayout($params = []) {
        $layout_path = VIEW_PATH . '/layouts/' . $this->layout . '.php';

        if (!file_exists($layout_path)) {
            throw new \Exception("Layout file not found: $layout_path");
        }

        extract($params); // Layout için de parametreleri kullanılabilir yap
        ob_start();
        require $layout_path;
        return ob_get_clean();
    }

    protected function renderView($view, $params = []) {
        $file_path = VIEW_PATH . '/' . $view . '.php';

        if (!file_exists($file_path)) {
            throw new \Exception("View file not found: $file_path");
        }

        extract($params);
        ob_start();
        require $file_path;
        return ob_get_clean();
    }

    // İstenirse controller'dan layout değiştirmek için
    public function setLayout($layout) {
        $this->layout = $layout;
    }
} 