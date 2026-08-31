<?php
declare(strict_types=1);

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
return static function (array $context): array {
    $critical = [];
    $warnings = [];
    $controllers = [];
    $views = [];

    $scanPhp = static function (string $root, bool $recursive = true): array {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS))
            : new IteratorIterator(new DirectoryIterator($root));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files, SORT_STRING);
        return $files;
    };

    foreach ($scanPhp($context['app_root'] . '/controllers', false) as $path) {
        $controllers[] = str_replace('\\', '/', substr($path, strlen($context['project_root']) + 1));
    }

    foreach ($scanPhp($context['app_root'] . '/views') as $path) {
        $views[] = str_replace('\\', '/', substr($path, strlen($context['project_root']) + 1));
    }

    $modulesRoot = $context['user_root'] . '/modules';

    foreach (glob($modulesRoot . '/*', GLOB_ONLYDIR) ?: [] as $moduleRoot) {
        $slug = basename($moduleRoot);
        $required = [
            'controller' => $moduleRoot . '/controllers/' . $slug . '.php',
            'model directory' => $moduleRoot . '/models',
            'view directory' => $moduleRoot . '/views',
            'manifest' => $moduleRoot . '/module.json',
        ];

        foreach ($required as $kind => $path) {
            $present = $kind === 'model directory' || $kind === 'view directory'
                ? is_dir($path)
                : is_file($path);

            if (!$present) {
                $warnings[] = [
                    'type' => 'module_contract_missing',
                    'module' => $slug,
                    'message' => "Module is missing its {$kind}.",
                ];
            }
        }

        foreach ($scanPhp($moduleRoot . '/controllers', false) as $path) {
            $controllers[] = str_replace('\\', '/', substr($path, strlen($context['project_root']) + 1));
        }

        foreach ($scanPhp($moduleRoot . '/views') as $path) {
            $views[] = str_replace('\\', '/', substr($path, strlen($context['project_root']) + 1));
        }
    }

    $entryPath = $context['public_root'] . '/index.php';
    $bootstrapPath = $context['app_root'] . '/bootstrap.php';
    $routerPath = $context['app_root'] . '/core/router.php';

    if (!is_file($entryPath)) {
        $critical[] = ['type' => 'entry_missing', 'message' => 'public/index.php is missing.'];
    } else {
        $entry = (string) file_get_contents($entryPath);
        if (!str_contains($entry, 'app/bootstrap.php')) {
            $critical[] = ['type' => 'bootstrap_missing', 'message' => 'The public entry does not load app/bootstrap.php.'];
        }
        if (!preg_match('/new\s+router\s*\(/i', $entry)) {
            $critical[] = ['type' => 'router_activation_missing', 'message' => 'The public entry does not activate the router.'];
        }
    }

    foreach (['bootstrap' => $bootstrapPath, 'router' => $routerPath] as $kind => $path) {
        if (!is_file($path)) {
            $critical[] = ['type' => $kind . '_missing', 'message' => "The {$kind} file is missing."];
        }
    }

    return [
        'summary' => [
            'status' => $critical ? 'failed' : ($warnings ? 'warning' : 'clean'),
            'critical' => count($critical),
            'warning' => count($warnings),
            'controllers_total' => count($controllers),
            'views_total' => count($views),
        ],
        'inventory' => ['controllers' => $controllers, 'views' => $views],
        'criticals' => $critical,
        'warnings' => $warnings,
    ];
};
/* [End AI:OpenAI Codex] */
