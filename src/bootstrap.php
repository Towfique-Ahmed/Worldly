<?php

declare(strict_types=1);

/**
 * Worldly bootstrap: PSR-4 style autoloading for the Worldly\ namespace,
 * without pulling in Composer. The app has no third-party dependencies.
 */

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

/** Cache-busting suffix for CSS and JS, derived from the newest asset mtime. */
define('ASSET_VERSION', (static function (): string {
    $newest = 0;
    $assets = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/../public/assets', FilesystemIterator::SKIP_DOTS),
    );

    foreach ($assets as $asset) {
        /** @var SplFileInfo $asset */
        $newest = max($newest, $asset->getMTime());
    }

    return substr(md5((string) $newest), 0, 8);
})());

spl_autoload_register(static function (string $class): void {
    $prefix = 'Worldly\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
