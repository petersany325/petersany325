<?php

declare(strict_types=1);

final class View
{
    private ?App $app = null;

    public function __construct(private string $root)
    {
    }

    public function setApp(App $app): void
    {
        $this->app = $app;
    }

    public function render(string $template, array $data = [], string $layout = 'layouts/site'): void
    {
        $data['app'] = $this->app;
        $data['content'] = $this->capture($this->root . '/app/views/' . $template . '.php', $data);
        echo $this->capture($this->root . '/app/views/' . $layout . '.php', $data);
    }

    public function plugin(string $plugin, string $template, array $data = [], string $layout = 'layouts/admin'): void
    {
        $data['app'] = $this->app;
        $data['content'] = $this->capture($this->root . '/plugins/' . $plugin . '/views/' . $template . '.php', $data);
        echo $this->capture($this->root . '/app/views/' . $layout . '.php', $data);
    }

    public function capture(string $file, array $data): string
    {
        if (!is_file($file)) {
            return '';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}
