<?php
declare(strict_types=1);

/**
 * DevBot CLI and cron entry point.
 */

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$moduleRoot = dirname(__DIR__);
$projectRoot = dirname(__DIR__, 4);
$configPath = $moduleRoot . '/config/devbot_config.json';
$config = is_file($configPath)
    ? json_decode((string) file_get_contents($configPath), true)
    : [];

require_once $moduleRoot . '/lib/devbot_runner.php';

try {
    $runner = new devbot_runner(
        $projectRoot,
        $moduleRoot,
        is_array($config) ? $config : []
    );
    $report = $runner->run('cron');
    $summary = $report['summary'] ?? [];

    fwrite(
        STDOUT,
        sprintf(
            "DevBot: %s; critical=%d; warning=%d; duration=%dms\n",
            (string) ($summary['status'] ?? 'unknown'),
            (int) ($summary['critical'] ?? 0),
            (int) ($summary['warning'] ?? 0),
            (int) ($summary['duration_ms'] ?? 0)
        )
    );

    exit(((int) ($summary['critical'] ?? 0)) > 0 ? 1 : 0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'DevBot failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
/* [End AI:OpenAI Codex] */
