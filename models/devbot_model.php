<?php
declare(strict_types=1);

/**
 * DevBot report and runner model.
 */

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
class devbot_model
{
    private const MODULE_PATH = '/modules/devbot';

    /**
     * Return the information required by the administration dashboard.
     *
     * @return array
     */
    public function dashboard(): array
    {
        $report = $this->latest_report();

        return [
            'report' => $report,
            'history' => $this->report_history(),
            'cron_command' => PHP_BINARY
                . ' '
                . $this->module_root()
                . DIRECTORY_SEPARATOR
                . 'cron'
                . DIRECTORY_SEPARATOR
                . 'devbot.php',
            'storage_writable' => is_writable($this->storage_root()),
        ];
    }

    /**
     * Execute the scanner and persist its report.
     *
     * @param string $trigger Invocation source.
     *
     * @return array
     */
    public function run_scan(string $trigger = 'manual'): array
    {
        require_once $this->module_root() . '/lib/devbot_runner.php';

        $runner = new devbot_runner(
            dirname(USERROOT),
            $this->module_root(),
            $this->config()
        );

        return $runner->run($trigger);
    }

    /**
     * Return the latest report, or an empty report when never run.
     *
     * @return array
     */
    public function latest_report(): array
    {
        $path = $this->storage_root() . '/reports/latest.json';

        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Return recent report summaries.
     *
     * @return array
     */
    public function report_history(): array
    {
        $files = glob($this->storage_root() . '/reports/*.json') ?: [];
        $files = array_values(array_filter(
            $files,
            static fn(string $path): bool => basename($path) !== 'latest.json'
        ));
        rsort($files, SORT_STRING);

        $history = [];

        foreach (array_slice($files, 0, 10) as $path) {
            $decoded = json_decode((string) file_get_contents($path), true);

            if (is_array($decoded)) {
                $history[] = [
                    'generated_at' => $decoded['generated_at'] ?? null,
                    'trigger' => $decoded['trigger'] ?? null,
                    'status' => $decoded['summary']['status'] ?? 'unknown',
                    'critical' => (int) ($decoded['summary']['critical'] ?? 0),
                    'warning' => (int) ($decoded['summary']['warning'] ?? 0),
                ];
            }
        }

        return $history;
    }

    /** @return array */
    private function config(): array
    {
        $path = $this->module_root() . '/config/devbot_config.json';
        $decoded = is_file($path)
            ? json_decode((string) file_get_contents($path), true)
            : [];

        return is_array($decoded) ? $decoded : [];
    }

    /** @return string */
    private function module_root(): string
    {
        return USERROOT . self::MODULE_PATH;
    }

    /** @return string */
    private function storage_root(): string
    {
        return $this->module_root() . '/storage';
    }
}
/* [End AI:OpenAI Codex] */
