# DevBot Plugin Development

## 1. Introduction

DevBot plugins allow developers to add development tools to DevBot without modifying the DevBot runner or the ChAoS MVC Core.

A DevBot plugin is intentionally simple:

```text
Create PHP File
      │
      ▼
Return Callable
      │
      ▼
Receive DevBot Context
      │
      ▼
Perform Development Task
      │
      ▼
Return Structured Result
```

If you can write a PHP function that accepts an array and returns an array, you can build a DevBot plugin.

This guide walks through the process.

For the complete technical contract, see:

```text
docs/PLUGIN_API.md
```

---

## 2. Before You Start

A DevBot plugin should have a clear development responsibility.

Examples include:

```text
module inspection
theme inspection
project diagnostics
file monitoring
PHP inspection
documentation analysis
release validation
development metrics
security inspection
certification checks
```

Before writing the plugin, define what question it answers.

For example:

```text
Are my user modules structurally valid?
```

or:

```text
What files changed since the last DevBot run?
```

or:

```text
Do my themes contain the expected development resources?
```

A focused plugin is easier to understand, test, maintain, and remove.

---

## 3. Plugin Location

DevBot plugins live in:

```text
lib/plugins/
```

For example:

```text
lib/plugins/my_plugin.php
```

The filename becomes the plugin identifier:

```text
my_plugin
```

Use simple lowercase filenames containing letters, numbers, and underscores.

Examples:

```text
module_check.php
theme_check.php
php_inspector.php
release_check.php
documentation_check.php
```

---

## 4. Create Your First Plugin

Create:

```text
lib/plugins/hello_devbot.php
```

Add:

```php
<?php

declare(strict_types=1);

return static function (array $context): array {
    return [
        'message' => 'Hello from DevBot.',
    ];
};
```

That is a valid DevBot plugin.

The important part is:

```php
return static function (array $context): array {
```

The plugin file returns a callable.

DevBot executes that callable and supplies the runtime context.

The callable returns its results to DevBot.

---

## 5. Enable the Plugin

Adding the PHP file does not automatically require DevBot to execute it.

Add the plugin identifier to the enabled plugin configuration.

For example:

```json
{
    "plugins": {
        "enabled": [
            "mvc_triage",
            "filewatch",
            "ghostnote",
            "devclock",
            "hello_devbot"
        ]
    }
}
```

DevBot resolves:

```text
hello_devbot
```

to:

```text
lib/plugins/hello_devbot.php
```

The next DevBot run can then execute the plugin.

---

## 6. Understanding the Context

Every plugin receives a DevBot context array.

The current context includes:

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

These values allow your plugin to locate resources without knowing where the developer installed ChAoS MVC.

### Example

Instead of:

```php
$modules = '/var/www/example/user/modules';
```

use:

```php
$modules = $context['user_root'] . '/modules';
```

This makes the plugin portable between development environments.

---

## 7. Validate the Context You Need

A plugin does not necessarily need every context value.

Validate the values required for your task.

Example:

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

    return [
        'user_root' => $userRoot,
    ];
};
```

If the plugin cannot safely perform its job, throwing an exception allows DevBot to report the failure without intentionally terminating the complete DevBot run.

---

## 8. Inspecting ChAoS MVC

One of the primary uses for DevBot plugins is inspecting the development environment.

For example, a plugin can inspect installed user modules.

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

    $modulesRoot = $userRoot . '/modules';
    $modules = [];

    if (is_dir($modulesRoot)) {
        $entries = scandir($modulesRoot);

        if (is_array($entries)) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $modulesRoot . '/' . $entry;

                if (is_dir($path)) {
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

DevBot handles execution.

Your plugin handles the inspection.

---

## 9. Returning Results

Plugins return arrays.

There is no requirement that every plugin return identical information because different development tools perform different jobs.

A useful result should still be structured and predictable.

For example:

```php
return [
    'summary' => [
        'status' => 'warning',
        'critical' => 0,
        'warning' => 2,
    ],

    'warnings' => [
        [
            'type' => 'example_warning',
            'message' => 'Something requires developer attention.',
        ],
        [
            'type' => 'another_warning',
            'message' => 'Another condition was detected.',
        ],
    ],
];
```

Prefer structured arrays over preformatted HTML.

DevBot or another consumer can decide how the information should be presented.

---

## 10. Findings

Inspection plugins often need to report findings.

A useful finding can contain:

```php
[
    'type' => 'missing_resource',
    'message' => 'Expected development resource was not found.',
]
```

Additional information can be included when useful:

```php
[
    'type' => 'module_problem',
    'module' => 'example',
    'file' => 'module.json',
    'message' => 'The module manifest could not be read.',
]
```

Keep findings factual.

Report what the plugin observed.

Avoid presenting assumptions as established conditions.

---

## 11. Plugin State

Some plugins need to remember information between runs.

For example, a file monitoring plugin needs a previous snapshot to determine what changed.

DevBot provides:

```php
$context['storage_root']
```

Use DevBot-owned storage for persistent plugin state.

Example:

```php
$stateFile = $context['storage_root']
    . '/state/my_plugin.json';
```

A possible DevBot state layout is:

```text
storage/
└── state/
    ├── filewatch.json
    ├── my_plugin.json
    └── another_plugin/
        └── state.json
```

Keep your plugin's state separate from other plugins.

Do not assume ownership of another plugin's state.

---

## 12. Writing JSON State

A plugin that needs persistent state can use JSON.

Example:

```php
<?php

declare(strict_types=1);

return static function (array $context): array {
    $storageRoot = $context['storage_root'] ?? null;

    if (!is_string($storageRoot) || $storageRoot === '') {
        throw new RuntimeException(
            'DevBot storage_root context is unavailable.'
        );
    }

    $stateDirectory = $storageRoot . '/state';

    if (!is_dir($stateDirectory)) {
        if (!mkdir($stateDirectory, 0775, true)
            && !is_dir($stateDirectory)) {
            throw new RuntimeException(
                'Unable to create plugin state directory.'
            );
        }
    }

    $stateFile = $stateDirectory . '/my_plugin.json';

    $state = [
        'last_run' => gmdate('c'),
    ];

    $json = json_encode(
        $state,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

    if (!is_string($json)) {
        throw new RuntimeException(
            'Unable to encode plugin state.'
        );
    }

    if (file_put_contents($stateFile, $json) === false) {
        throw new RuntimeException(
            'Unable to write plugin state.'
        );
    }

    return [
        'summary' => [
            'status' => 'clean',
        ],
        'state' => $state,
    ];
};
```

For a working persistent-state implementation, inspect:

```text
lib/plugins/filewatch.php
```

---

## 13. Handling Errors

Do not terminate DevBot from inside a plugin.

Avoid:

```php
die('Something broke.');
```

and:

```php
exit;
```

Instead:

```php
throw new RuntimeException(
    'The plugin could not complete its inspection.'
);
```

DevBot can capture the exception, associate it with the plugin, and continue executing other enabled plugins.

This is important because plugins are independent development tools.

One broken plugin should not intentionally prevent unrelated plugins from running.

---

## 14. Do Not Modify Core During Inspection

A DevBot plugin may inspect ChAoS MVC Core resources when that inspection is required for its development task.

Inspection does not grant modification authority.

For example:

```php
$router = $context['app_root']
    . '/core/router.php';
```

A plugin may inspect that file.

A normal inspection plugin should not silently rewrite it.

DevBot is a userland module.

Its plugin system does not move DevBot into the protected ChAoS MVC Core.

---

## 15. Keep Plugin Data in DevBot

If your plugin creates persistent operational state, prefer:

```text
DevBot storage
```

rather than scattering plugin files around the ChAoS MVC installation.

Good:

```text
storage/state/my_plugin.json
```

Avoid unnecessary files such as:

```text
/app/my_plugin_state.json
/public/my_plugin_state.json
/user/random_state.json
```

The development environment being inspected should not become the plugin's storage system.

---

## 16. Do Not Depend on Plugin Execution Order

Do not assume:

```text
plugin_a
```

always runs before:

```text
plugin_b
```

A plugin should perform its own responsibility using the context DevBot provides.

Avoid:

```php
require __DIR__ . '/another_plugin.php';
```

and avoid designing a plugin around another plugin's internal implementation.

Plugins should remain independently installable and removable whenever practical.

---

## 17. Building a Module Inspector

Suppose you want to build:

```text
module_inspector.php
```

Its responsibility might be:

> Inspect installed ChAoS MVC user modules and report useful development information.

Start with:

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

    $modulesRoot = $userRoot . '/modules';
    $modules = [];

    if (is_dir($modulesRoot)) {
        foreach (scandir($modulesRoot) ?: [] as $slug) {
            if ($slug === '.' || $slug === '..') {
                continue;
            }

            $moduleRoot = $modulesRoot . '/' . $slug;

            if (!is_dir($moduleRoot)) {
                continue;
            }

            $modules[] = [
                'slug' => $slug,
                'manifest' => is_file(
                    $moduleRoot . '/module.json'
                ),
                'controller' => is_file(
                    $moduleRoot
                    . '/controllers/'
                    . $slug
                    . '.php'
                ),
            ];
        }
    }

    return [
        'summary' => [
            'modules' => count($modules),
        ],
        'modules' => $modules,
    ];
};
```

That is already useful.

You can then extend it gradually instead of attempting to build every possible module check at once.

---

## 18. Building a Theme Inspector

The same API can inspect ChAoS MVC user themes.

For example:

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

    $themesRoot = $userRoot . '/themes';
    $themes = [];

    if (is_dir($themesRoot)) {
        foreach (scandir($themesRoot) ?: [] as $slug) {
            if ($slug === '.' || $slug === '..') {
                continue;
            }

            $themeRoot = $themesRoot . '/' . $slug;

            if (!is_dir($themeRoot)) {
                continue;
            }

            $themes[] = [
                'slug' => $slug,
                'manifest' => is_file(
                    $themeRoot . '/theme.json'
                ),
            ];
        }
    }

    return [
        'summary' => [
            'themes' => count($themes),
        ],
        'themes' => $themes,
    ];
};
```

Again, DevBot supplies the environment.

The plugin supplies the development capability.

---

## 19. Testing Your Plugin

During development, test at least:

### Normal execution

The plugin receives valid context and returns the expected result.

### Missing resource

A directory or file the plugin expects does not exist.

The plugin should report the condition or fail cleanly depending on whether the missing resource prevents the plugin from doing its job.

### Empty environment

The target directory exists but contains no relevant resources.

### Invalid state

If the plugin reads persistent state, test malformed or incomplete state.

### Write failure

If the plugin writes state, test what happens when its state location cannot be written.

### Repeated execution

Run the plugin multiple times.

Stateful plugins should behave predictably across executions.

### Other plugin failure

A different DevBot plugin failing should not require your plugin to fail.

---

## 20. What Not to Do

Avoid turning a plugin into another framework.

A DevBot plugin normally does not need:

```text
its own plugin loader
its own dependency container
its own routing system
its own plugin API
its own copy of DevBot environment discovery
```

Start with:

```text
input
→ task
→ result
```

Add complexity only when the plugin's actual responsibility requires it.

---

## 21. Use the Included Plugins

DevBot already contains working plugins.

Read them.

```text
lib/plugins/mvc_triage.php
lib/plugins/filewatch.php
lib/plugins/ghostnote.php
lib/plugins/devclock.php
```

They demonstrate different ways of using the same plugin execution mechanism.

### MVC Triage

Study this plugin when you want to understand:

```text
project inspection
filesystem discovery
structured warnings
structured critical findings
inventory reporting
```

### FileWatch

Study this plugin when you want to understand:

```text
persistent state
filesystem monitoring
comparison between runs
```

### Ghostnote

Study this plugin when you want to understand:

```text
structured development signals
plugin-specific reporting
```

### DevClock

Study this plugin when you want to understand:

```text
small plugins
runtime information
simple result generation
```

You do not need to copy an entire plugin.

Use the parts that demonstrate the capability your plugin needs.

---

## 22. Plugin Development Checklist

Before considering a plugin ready, verify:

- The plugin is located under `lib/plugins/`.
- The filename provides a clear plugin identifier.
- The PHP file returns a callable.
- The callable accepts `array $context`.
- The callable returns an array.
- Required context values are validated.
- Project paths are derived from DevBot context.
- Results are structured rather than preformatted HTML.
- Exceptions are used instead of `exit` or `die`.
- Persistent state remains within DevBot storage.
- Plugin state does not collide with another plugin.
- The plugin does not silently modify ChAoS MVC Core.
- The plugin does not depend on undocumented execution order.
- The plugin has a focused development responsibility.
- Normal and failure conditions have been tested.

---

## 23. Where to Go Next

Once your plugin works:

1. Run it repeatedly in a development environment.
2. Verify its results against the resources it inspects.
3. Test failure conditions.
4. Keep its output structured.
5. Document what the plugin detects or provides.
6. Maintain compatibility with the documented DevBot Plugin API.

The technical API contract is documented in:

```text
docs/PLUGIN_API.md
```

Working reference implementations are available in:

```text
lib/plugins/
```

---

## Build What You Need

DevBot does not need to contain every development tool.

That is what the Plugin API is for.

```text
DevBot provides the environment.
Your plugin provides the capability.
```

Build the tool your development environment needs.