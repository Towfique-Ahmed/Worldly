<?php

declare(strict_types=1);

namespace Worldly;

/**
 * Minimal template renderer: pages render into a buffer, the buffer is handed
 * to the layout. No dependencies, no compilation step.
 */
final class View
{
    /** @var array<string, mixed> */
    private array $shared = [];

    public function __construct(private readonly string $viewDir)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public function render(string $page, array $data = []): string
    {
        $data += $this->shared;
        $content = $this->capture("{$this->viewDir}/pages/{$page}.php", $data);

        return $this->capture("{$this->viewDir}/layout.php", $data + [
            'content' => $content,
            'page' => $page,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function partial(string $name, array $data = []): string
    {
        return $this->capture("{$this->viewDir}/partials/{$name}.php", $data + $this->shared);
    }

    /** @param array<string, mixed> $data */
    private function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();

        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
