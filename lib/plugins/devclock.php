<?php
declare(strict_types=1);

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
return static function (array $context): array {
    return [
        'summary' => ['critical' => 0, 'warning' => 0],
        'timestamp_utc' => gmdate('c'),
        'php_version' => PHP_VERSION,
        'sapi' => PHP_SAPI,
    ];
};
/* [End AI:OpenAI Codex] */
