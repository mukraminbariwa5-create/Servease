<?php
declare(strict_types=1);

final class View
{
    /** Render a template inside a layout. Templates are plain PHP files in app/Views. */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::partial($template, $data);
        if ($layout === null) return $content;
        return self::partial($layout, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $template, array $data = []): string
    {
        $file = APP . '/Views/' . $template . '.php';
        if (!is_file($file)) throw new RuntimeException("View not found: $template");
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}

/** Include a partial from inside a template. */
function partial(string $template, array $data = []): string
{
    return View::partial('partials/' . $template, $data);
}
