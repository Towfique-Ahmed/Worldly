<?php

declare(strict_types=1);

namespace Worldly;

/**
 * Pattern router.
 *
 * Handlers return either a string (rendered HTML) or an array shaped
 * ['status' => int, 'body' => mixed], which is emitted as JSON.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => 'GET',
            'regex' => $this->compile($pattern),
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path, callable $fallback): void
    {
        $path = '/' . trim(rawurldecode($path), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->emit(($route['handler'])($params));

            return;
        }

        http_response_code(404);
        $this->emit($fallback());
    }

    /**
     * Turn `/country/{iso3}` into a regex, with `{name:pattern}` for custom
     * segments such as `{zone:.+}` which must match slashes.
     */
    private function compile(string $pattern): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $m): string {
                $name = $m[1];
                $expression = $m[2] ?? '[^/]+';

                return '(?P<' . $name . '>' . $expression . ')';
            },
            $pattern,
        );

        return '#^' . $regex . '$#u';
    }

    private function emit(string|array $result): void
    {
        if (is_string($result)) {
            header('Content-Type: text/html; charset=utf-8');
            echo $result;

            return;
        }

        http_response_code($result['status'] ?? 200);
        header('Content-Type: application/json; charset=utf-8');
        // Static geometry is worth caching; everything else changes per request.
        header('Cache-Control: ' . (($result['cache'] ?? false) ? 'public, max-age=86400' : 'no-store'));
        echo json_encode($result['body'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
