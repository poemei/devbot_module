<?php
declare(strict_types=1);

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
return static function (array $context): array {
    $statePath = $context['storage_root'] . '/state/filewatch.json';
    $previous = is_file($statePath)
        ? json_decode((string) file_get_contents($statePath), true)
        : [];
    $previous = is_array($previous) ? $previous : [];
    $current = [];

    foreach ([$context['app_root'], $context['user_root'] . '/modules'] as $root) {
        if (!is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            if (str_starts_with($path, $context['storage_root'] . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($path, strlen($context['project_root']) + 1));
            $current[$relative] = sha1_file($path) ?: '';
        }
    }

    ksort($current, SORT_STRING);
    $added = array_values(array_diff(array_keys($current), array_keys($previous)));
    $removed = array_values(array_diff(array_keys($previous), array_keys($current)));
    $modified = [];

    foreach ($current as $path => $hash) {
        if (isset($previous[$path]) && $previous[$path] !== $hash) {
            $modified[] = $path;
        }
    }

    file_put_contents(
        $statePath,
        json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        LOCK_EX
    );

    return [
        'summary' => ['critical' => 0, 'warning' => 0, 'changes' => count($added) + count($modified) + count($removed)],
        'added' => $added,
        'modified' => $modified,
        'removed' => $removed,
    ];
};
/* [End AI:OpenAI Codex] */
