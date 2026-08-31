<?php
declare(strict_types=1);

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
return static function (array $context): array {
    $notes = [];

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

            $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES) ?: [];

            foreach ($lines as $number => $line) {
                if (preg_match('/\/\/@devbot:(.*)$/i', $line, $match)) {
                    $notes[] = [
                        'file' => str_replace('\\', '/', substr($file->getPathname(), strlen($context['project_root']) + 1)),
                        'line' => $number + 1,
                        'note' => trim($match[1]),
                    ];
                }
            }
        }
    }

    return [
        'summary' => ['critical' => 0, 'warning' => 0, 'notes' => count($notes)],
        'notes' => $notes,
    ];
};
/* [End AI:OpenAI Codex] */
