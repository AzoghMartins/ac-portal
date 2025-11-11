<?php
declare(strict_types=1);

namespace App;

final class View {
    public static function render(string $template, array $data = []): void {
        $templateFile = __DIR__ . '/../views/' . $template . '.php';
        if (!is_file($templateFile)) {
            throw new \RuntimeException("View not found: $templateFile");
        }
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../views/layout.php';
    }
}
