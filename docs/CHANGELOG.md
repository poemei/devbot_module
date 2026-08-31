# DevBot Changelog

## 1.0.2 - 2026-080-31

### Added

- created `docs/PLUGIN_API.md` to define the useable API
- created `docs/PLUGIN_DEVELOPMENT.md` to assist developers build plugins
- determined that plugins do not get auto registered, and must be entered into `config/devbot_config.json`

## 1.0.2 — 2026-08-30

### Added

- Converted DevBot into a conventional Chaos MVC userland module.
- Added an authenticated administration dashboard and manual scan action.
- Added a CLI-only cron entry point.
- Added MVC triage for Core and userland controllers and views.
- Added file-change inventory, ghostnote discovery, and runtime metadata.
- Added atomic JSON reports, recent run history, and report retention.
