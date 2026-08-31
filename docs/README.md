# DevBot

DevBot is a Chaos MVC userland module that provides an authenticated administration dashboard and scheduled developer diagnostics.

## Administration

After refreshing the Chaos MVC module indices, open `/admin/devbot`. Administrators at level 7 or above can view reports and run an immediate scan.

## Cron

Run the module-owned CLI entry point with PHP:

```text
php /absolute/path/to/user/modules/devbot/cron/devbot.php
```

The command exits with status `1` when critical structural findings exist or the scan cannot complete. Generated state and reports are stored beneath `storage/`.

## Architecture

- `controllers/devbot.php` owns the admin route.
- `models/devbot_model.php` reads reports and invokes the scanner.
- `views/admin/devbot.php` renders the dashboard.
- `lib/devbot_runner.php` coordinates configured plugins.
- `lib/plugins/` contains diagnostics that return structured arrays.
- `cron/devbot.php` is the CLI-only scheduled entry point.

DevBot is not loaded from Core bootstrap and never runs on ordinary public requests.
