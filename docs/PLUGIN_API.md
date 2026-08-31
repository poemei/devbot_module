# DevBot Plugin API

## 1. Overview

The DevBot Plugin API defines the execution contract between DevBot and development plugins.

DevBot plugins extend DevBot without requiring modifications to the DevBot runner or the ChAoS MVC Core.

A plugin is a PHP file loaded by DevBot during a DevBot execution cycle.

The plugin:

1. Returns a PHP callable.
2. Receives the DevBot runtime context.
3. Performs its development task.
4. Returns structured result data to DevBot.

The basic execution model is:

```text
DevBot Runner
     │
     ▼
Load Enabled Plugin
     │
     ▼
require plugin.php
     │
     ▼
Returned Callable
     │
     ▼
callable($context)
     │
     ▼
Structured Result
     │
     ▼
DevBot Signals / Report
```

The Plugin API intentionally uses a small contract so plugins can remain independent and focused.

---

## 2. Plugin Location

DevBot plugins are stored under:

```text
lib/plugins/
```

A plugin consists of a PHP file whose filename identifies the plugin.

Example:

```text
lib/plugins/mvc_triage.php
```

The plugin identifier is:

```text
mvc_triage
```

Additional examples:

```text
lib/plugins/filewatch.php
lib/plugins/ghostnote.php
lib/plugins/devclock.php
lib/plugins/my_plugin.php
```

Plugin filenames should use lowercase characters, numbers, and underscores.

---

## 3. Enabling Plugins

DevBot executes plugins that are enabled in its configuration.

An enabled plugin is referenced by its plugin identifier.

Conceptually:

```json
{
    "plugins": {
        "enabled": [
            "mvc_triage",
            "filewatch",
            "ghostnote",
            "devclock"
        ]
    }
}
```

The DevBot runner resolves each configured plugin identifier to:

```text
lib/plugins/{plugin_identifier}.php
```

For example:

```text
mvc_triage
```

resolves to:

```text
lib/plugins/mvc_triage.php
```

A plugin file existing in the plugin directory does not by itself require DevBot to execute it. The plugin must also be enabled through the DevBot configuration used by the runner.

---

## 4. Plugin Entry Contract

A DevBot plugin must return a callable when its PHP file is loaded.

Minimum example:

```php
<?php

declare(strict_types=1);

return static function (array $context): array {
    return [];
};
```

The required contract is:

```php
callable(array $context): array
```

DevBot loads the plugin file and validates that its return value is callable before execution.

Conceptually:

```php
$plugin = require $pluginPath;

if (!is_callable($plugin)) {
    throw new RuntimeException(
        'Plugin must return a callable.'
    );
}

$result = $plugin($context);
```

Plugins do not require a class, inheritance hierarchy, registration function, or separate manifest.

---

## 5. Runtime Context

DevBot supplies a runtime context array to each plugin.

The current DevBot context provides paths describing the active ChAoS MVC development environment and the DevBot installation.

Available context values include:

```php
[
    'project_root' => '...',
    'app_root' => '...',
    'user_root' => '...',
    'public_root' => '...',
    'module_root' => '...',
    'storage_root' => '...',
]
```

### `project_root`

Root directory of the current ChAoS MVC project.

Plugins can use this as the starting point for project-wide inspection.

### `app_root`

Path to the ChAoS MVC application directory.

Typically represents:

```text
/app
```

This may be used for inspection of application structure.

### `user_root`

Path to ChAoS MVC userland.

Typically represents:

```text
/user
```

This allows plugins to inspect user modules, themes, and other userland development resources where appropriate.

### `public_root`

Path to the public web root.

Typically represents:

```text
/public
```

Plugins may use this to inspect public entry points and public assets.

### `module_root`

Root directory of the DevBot module itself.

Plugins may use this when they need to reference DevBot-owned resources.

### `storage_root`

Path to DevBot's persistent storage area.

Plugins that require state across execution cycles should use DevBot-owned storage rather than creating arbitrary storage locations elsewhere in the ChAoS MVC project.

---

## 6. Context Usage

Plugins should derive project paths from the supplied context rather than assuming an absolute server path.

Preferred:

```php
$appRoot = $context['app_root'];
$controllerPath = $appRoot . '/controllers';
```

Avoid:

```php
$controllerPath = '/var/www/example/app/controllers';
```

Using the supplied context allows the same plugin to operate across different ChAoS MVC development environments.

Plugins should verify that required context values exist before using them.

Example:

```php
$projectRoot = $context['project_root'] ?? null;

if (!is_string($projectRoot) || $projectRoot === '') {
    throw new RuntimeException(
        'DevBot project_root context is unavailable.'
    );
}
```

---

## 7. Plugin Results

A plugin returns an array containing the results of its execution.

The contents of the result are determined by the plugin's responsibility.

Example:

```php
return static function (array $context): array {
    return [
        'summary' => [
            'status' => 'clean',
            'critical' => 0,
            'warning' => 0,
        ],
        'criticals' => [],
        'warnings' => [],
    ];
};
```

Plugins may return additional structured information required by their task.

For example, an inspection plugin may return:

```php
[
    'summary' => [...],
    'inventory' => [...],
    'criticals' => [...],
    'warnings' => [...],
]
```

A runtime-information plugin may return an entirely different set of plugin-specific data.

The Plugin API does not require every plugin to perform the same type of work.

---

## 8. Signals

Successful plugin results are collected by DevBot as plugin signals.

Conceptually:

```php
$signals[$pluginName] = $result;
```

This means a plugin named:

```text
mvc_triage
```

produces a signal available as:

```php
$signals['mvc_triage']
```

Plugin identifiers therefore also act as result namespaces.

Plugins should not attempt to write directly into another plugin's signal namespace.

---

## 9. Failure Isolation

Plugin execution is isolated by the DevBot runner.

A failure in one plugin should not prevent subsequent enabled plugins from executing.

Plugin failures are captured and reported by DevBot.

Conceptually:

```php
try {
    $result = $plugin($context);
    $signals[$pluginName] = $result;
} catch (Throwable $exception) {
    $errors[] = $pluginName
        . ': '
        . $exception->getMessage();
}
```

Plugins may throw an exception when they cannot safely complete their task.

Example:

```php
if (!is_dir($context['user_root'])) {
    throw new RuntimeException(
        'ChAoS MVC userland could not be located.'
    );
}
```

A plugin should not intentionally terminate the PHP process using mechanisms such as:

```php
exit;
die;
```

Throw an exception instead so DevBot can report the failure and continue processing other plugins.

---

## 10. Persistent Plugin State

Plugins may require state that survives between DevBot execution cycles.

The DevBot runtime context provides:

```php
$context['storage_root']
```

Plugins should keep persistent DevBot-owned state beneath this location.

For example:

```text
storage/
└── state/
    └── filewatch.json
```

A plugin should use a unique state filename or directory to prevent collisions with other plugins.

Example:

```text
storage/state/my_plugin.json
```

or:

```text
storage/state/my_plugin/
```

Plugins should not use another plugin's state files as their own storage.

The included `filewatch` plugin provides a working example of persistent plugin state.

---

## 11. Read and Write Boundaries

DevBot plugins execute as PHP code within the developer's ChAoS MVC environment.

Plugins may inspect resources available through the supplied DevBot context when required by their development responsibility.

Inspection of ChAoS MVC Core files does not grant authority to modify ChAoS MVC Core.

DevBot remains a userland module.

Plugins should treat Core resources as inspection targets unless a separate, explicit development operation establishes authority for another action.

Plugin-owned persistent data should normally remain within DevBot's storage area.

A diagnostic or inspection plugin should not silently modify the resources it is inspecting.

---

## 12. Plugin Scope

A plugin should perform a focused development responsibility.

Examples include:

```text
MVC structure inspection
module validation
theme validation
file monitoring
PHP inspection
documentation inspection
release validation
development metrics
security inspection
certification checks
project-specific diagnostics
```

A plugin does not need to reproduce DevBot's environment discovery.

DevBot provides the environment context.

The plugin uses that context to perform its task.

---

## 13. Plugin Independence

Plugins should remain independent whenever practical.

A plugin should not require another plugin's internal PHP implementation.

For example:

```text
plugin_a.php
```

should not directly:

```php
require 'plugin_b.php';
```

DevBot owns plugin discovery and execution.

Each plugin should receive what it needs through the DevBot context or through resources explicitly owned by that plugin.

This keeps plugins removable and prevents plugin loading order from becoming an undocumented dependency system.

---

## 14. Included Reference Plugins

DevBot ships with plugins that demonstrate different uses of the Plugin API.

### `mvc_triage`

Demonstrates project and ChAoS MVC structure inspection.

It examines application infrastructure and userland module structure and returns structured findings.

### `filewatch`

Demonstrates filesystem monitoring and persistent state between DevBot runs.

### `ghostnote`

Demonstrates structured development-time plugin output.

### `devclock`

Demonstrates lightweight runtime and environment reporting.

Developers building new plugins should inspect these implementations as working examples of the API.

---

## 15. Minimal Plugin

A minimal DevBot plugin can contain only:

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

Save it as:

```text
lib/plugins/example.php
```

and enable:

```text
example
```

in DevBot's plugin configuration.

On the next DevBot execution, the runner loads:

```text
lib/plugins/example.php
```

and stores its returned data under:

```php
$signals['example']
```

---

## 16. Example Inspection Plugin

The following demonstrates using DevBot context to inspect the current project:

```php
<?php

declare(strict_types=1);

return static function (array $context): array {
    $userRoot = $context['user_root'] ?? null;

    if (!is_string($userRoot) || $userRoot === '') {
        throw new RuntimeException(
            'DevBot user_root context is unavailable.'
        );
    }

    $modulesPath = $userRoot . '/modules';

    $modules = [];

    if (is_dir($modulesPath)) {
        $entries = scandir($modulesPath);

        if (is_array($entries)) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                if (is_dir($modulesPath . '/' . $entry)) {
                    $modules[] = $entry;
                }
            }
        }
    }

    sort($modules);

    return [
        'summary' => [
            'modules' => count($modules),
        ],
        'modules' => $modules,
    ];
};
```

This plugin does not need to know the absolute location of the ChAoS MVC installation.

DevBot supplies that information through:

```php
$context['user_root']
```

---

## 17. Compatibility

Developers should build plugins against the documented DevBot Plugin API rather than undocumented DevBot runner internals.

The API boundary is:

```text
Plugin file
     +
Returned callable
     +
DevBot context
     +
Structured result
```

Internal DevBot implementation details outside that boundary may change as DevBot develops.

Changes to the documented Plugin API that break existing plugins should require an API compatibility decision and corresponding documentation.

---

## 18. Plugin API Version

This document defines the first formalized DevBot Plugin API contract.

```text
DevBot Plugin API v1
```

The API is based on the plugin execution mechanism already used by DevBot's included plugins.

Future API versions may add capabilities while preserving the principle that DevBot plugins remain lightweight, independent development tools.

---

## 19. Security

A DevBot plugin is executable PHP code.

Installing a plugin therefore grants that plugin the same operating-system and PHP-level access available to the DevBot execution process.

Only plugins from trusted sources should be installed.

DevBot's plugin failure isolation protects the execution cycle from ordinary plugin exceptions. It is not a security sandbox for untrusted PHP code.

Developers should review third-party plugin source before enabling it.

---

## 20. Development Guide

For a step-by-step guide to creating a plugin, see:

```text
docs/PLUGIN_DEVELOPMENT.md
```

For working implementations, see:

```text
lib/plugins/
```

The included DevBot plugins are the reference implementations for the Plugin API.

---

**DevBot Plugin API v1**

DevBot provides the environment.

Plugins provide the capability.