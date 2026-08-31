<?php
declare(strict_types=1);

/**
 * DevBot reusable scanner service.
 */

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
class devbot_runner
{
    private string $projectRoot;
    private string $moduleRoot;
    private string $storageRoot;
    private array $config;

    public function __construct(
        string $projectRoot,
        string $moduleRoot,
        array $config = []
    ) {
        $this->projectRoot = rtrim($projectRoot, '/\\');
        $this->moduleRoot = rtrim($moduleRoot, '/\\');
        $this->storageRoot = $this->moduleRoot . '/storage';
        $this->config = $config;
    }

    /**
     * Run enabled plugins and write an atomic report.
     *
     * @return array
     */
    public function run(string $trigger = 'manual'): array
    {
        if (($this->config['enabled'] ?? true) !== true) {
            throw new RuntimeException('DevBot is disabled by configuration.');
        }

        $started = microtime(true);
        $this->ensure_directory($this->storageRoot . '/reports');
        $this->ensure_directory($this->storageRoot . '/state');
        $this->ensure_directory($this->storageRoot . '/logs');

        $enabled = $this->config['plugins']['enabled'] ?? [];
        $signals = [];
        $errors = [];

        foreach ($enabled as $pluginName) {
            if (!is_string($pluginName) || !preg_match('/^[a-z][a-z0-9_]*$/', $pluginName)) {
                $errors[] = 'Invalid plugin name in configuration.';
                continue;
            }

            $pluginPath = $this->moduleRoot . '/lib/plugins/' . $pluginName . '.php';

            if (!is_file($pluginPath)) {
                $errors[] = "Plugin not found: {$pluginName}";
                continue;
            }

            try {
                $plugin = require $pluginPath;

                if (!is_callable($plugin)) {
                    throw new RuntimeException('Plugin must return a callable.');
                }

                $result = $plugin($this->context());
                $signals[$pluginName] = is_array($result) ? $result : [];
            } catch (Throwable $exception) {
                $errors[] = $pluginName . ': ' . $exception->getMessage();
            }
        }

        $critical = 0;
        $warning = count($errors);

        foreach ($signals as $signal) {
            $critical += (int) ($signal['summary']['critical'] ?? 0);
            $warning += (int) ($signal['summary']['warning'] ?? 0);
        }

        $report = [
            'schema_version' => '1.0.0',
            'generated_at' => gmdate('c'),
            'trigger' => $trigger,
            'project_root' => $this->relative_path($this->projectRoot),
            'summary' => [
                'status' => $critical > 0 ? 'failed' : ($warning > 0 ? 'warning' : 'clean'),
                'critical' => $critical,
                'warning' => $warning,
                'plugins' => count($signals),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ],
            'signals' => $signals,
            'errors' => $errors,
        ];

        $stamp = gmdate('Ymd-His') . '-' . substr(sha1((string) microtime(true)), 0, 6);
        $this->write_json($this->storageRoot . '/reports/' . $stamp . '.json', $report);
        $this->write_json($this->storageRoot . '/reports/latest.json', $report);
        $this->purge_old_reports();

        return $report;
    }

    private function context(): array
    {
        return [
            'project_root' => $this->projectRoot,
            'app_root' => $this->projectRoot . '/app',
            'user_root' => $this->projectRoot . '/user',
            'public_root' => $this->projectRoot . '/public',
            'module_root' => $this->moduleRoot,
            'storage_root' => $this->storageRoot,
        ];
    }

    private function ensure_directory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('DevBot could not create: ' . $path);
        }
    }

    private function write_json(string $path, array $payload): void
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!is_string($json)) {
            throw new RuntimeException('DevBot could not encode its report.');
        }

        $temporary = $path . '.tmp';

        if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('DevBot could not write its report.');
        }

        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('DevBot could not publish its report.');
        }
    }

    private function purge_old_reports(): void
    {
        $days = max(1, (int) ($this->config['retention_days'] ?? 30));
        $cutoff = time() - ($days * 86400);

        foreach (glob($this->storageRoot . '/reports/*.json') ?: [] as $path) {
            if (basename($path) !== 'latest.json' && filemtime($path) < $cutoff) {
                @unlink($path);
            }
        }
    }

    private function relative_path(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
/* [End AI:OpenAI Codex] */
