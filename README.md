# DevBot

DevBot is a developer-focused module for **ChAoS MVC** that inspects a development environment, gathers structured development signals, and provides those signals through a centralized administrative interface.

DevBot is designed to help developers understand what is happening inside a ChAoS MVC project without modifying the ChAoS MVC Core.

It operates from userland and can inspect the application, public entry points, user modules, and other development resources exposed through its runtime context.

## Purpose

DevBot exists to provide developers with a common place for development-time inspection, diagnostics, monitoring, and project intelligence.

Rather than building every development tool directly into DevBot, DevBot provides a plugin system that allows additional development capabilities to be added independently.

The basic architecture is:

```text
ChAoS MVC
└── DevBot
    ├── Runner
    ├── Admin Interface
    ├── Storage
    └── Plugins
        ├── mvc_triage
        ├── filewatch
        ├── ghostnote
        ├── devclock
        └── your_plugin
```

DevBot provides the execution environment.

Plugins provide the capabilities.

## Features

DevBot currently provides:

- ChAoS MVC development environment inspection
- Core and userland structure awareness
- Plugin-based development tooling
- Structured plugin execution
- Persistent DevBot storage
- Administrative reporting
- Manual/on-demand execution
- Scheduled execution support
- Isolated plugin failure reporting
- Extensible developer tooling without modifying DevBot itself

## Plugin System

DevBot includes a lightweight PHP plugin system.

Plugins live under:

```text
lib/plugins/
```

Each enabled plugin is loaded by the DevBot runner and receives a structured runtime context describing the current ChAoS MVC environment.

A plugin is a PHP file that returns a callable:

```php
<?php

declare(strict_types=1);

return static function (array $context): array {
    return [
        'summary' => [
            'status' => 'clean',
        ],
    ];
};
```

The callable receives the DevBot context and returns structured data to DevBot.

This intentionally keeps the plugin boundary small:

```text
Plugin
  ↓
Callable
  ↓
DevBot Context
  ↓
Plugin Execution
  ↓
Structured Result
  ↓
DevBot Report
```

Developers can create their own plugins without modifying the DevBot runner.

See:

- `docs/PLUGIN_API.md` — DevBot Plugin API reference
- `docs/PLUGIN_DEVELOPMENT.md` — guide to building DevBot plugins

## Included Plugins

DevBot currently ships with several plugins that also serve as working examples of the plugin system.

### MVC Triage

`mvc_triage.php`

Inspects the ChAoS MVC project structure and reports structural problems and development warnings.

It demonstrates how a plugin can inspect the ChAoS MVC environment using the context supplied by DevBot.

### FileWatch

`filewatch.php`

Provides file monitoring capabilities and demonstrates how a DevBot plugin can maintain persistent state between executions.

### Ghostnote

`ghostnote.php`

Provides another example of structured plugin output and development-time reporting.

### DevClock

`devclock.php`

Provides runtime/environment information and demonstrates a lightweight DevBot plugin.

These plugins are not the limit of DevBot's capabilities.

They are examples of what can be built using the Plugin API.

## Building Plugins

A DevBot plugin can provide virtually any development-time capability that can operate within the plugin execution environment.

Possible plugins include:

- module validation
- theme validation
- PHP inspection
- documentation analysis
- certification checks
- project health checks
- development metrics
- security inspection
- dependency inspection
- release validation
- custom project-specific development tools

Plugins should remain focused on a specific development responsibility.

DevBot handles execution and context.

The plugin handles its task.

## Project Structure

A DevBot installation follows the ChAoS MVC user module structure.

```text
devbot/
├── controllers/
├── models/
├── views/
├── lib/
│   ├── devbot_runner.php
│   └── plugins/
├── storage/
├── docs/
└── module.json
```

The exact contents may evolve as DevBot develops.

## ChAoS MVC Integration

DevBot is a **userland ChAoS MVC module**.

It does not become part of the ChAoS MVC Core and does not require DevBot-specific modifications to the Core.

DevBot may inspect the ChAoS MVC environment made available to it, including Core structure where appropriate, while remaining outside the protected Core.

This follows the ChAoS MVC architectural principle:

> **Protect the core. Grow outward.**

DevBot grows development capabilities outward through plugins.

## Plugin Failure Isolation

Plugins execute independently through the DevBot runner.

A plugin failure is reported by DevBot rather than intentionally terminating execution of the remaining plugins.

This allows developers to experiment with custom development tooling without making every plugin a dependency of the complete DevBot run.

Plugins are still PHP code executed inside the development environment and should therefore only be installed from sources the developer trusts.

## Development Status

DevBot is under active development.

The existing plugin system is functional and is being formalized as the **DevBot Plugin API** so developers can build their own DevBot capabilities against a documented interface.

Existing DevBot plugins provide reference implementations of that API.

## Repository

This repository is the development home of the DevBot ChAoS MVC module.

It contains the DevBot module, included plugins, developer documentation, and future DevBot releases.

## Documentation

Developer documentation is maintained under:

```text
docs/
```

The primary developer documents are:

```text
docs/PLUGIN_API.md
docs/PLUGIN_DEVELOPMENT.md
```

`PLUGIN_API.md` defines the technical contract between DevBot and a plugin.
[PLUGIN API](docs/PLUGIN_API.md)

`PLUGIN_DEVELOPMENT.md` explains how to use that contract to create and test a plugin.
[PLUGININ DEVELOPMENT](docs/PLUGIN_DEVELOPMENT.md)

[CHANGELOG](docs/CHANGELOG.md)

## License

See the repository `LICENSE` file for licensing terms.

---

**DevBot**

Developer tooling for ChAoS MVC.

Build the tools your development environment needs.
