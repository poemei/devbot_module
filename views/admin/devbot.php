<?php
declare(strict_types=1);

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
require APPROOT . '/views/inc/head.php';

$report = is_array($data['report'] ?? null) ? $data['report'] : [];
$summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
$signals = is_array($report['signals'] ?? null) ? $report['signals'] : [];
$triage = is_array($signals['mvc_triage'] ?? null) ? $signals['mvc_triage'] : [];
$filewatch = is_array($signals['filewatch'] ?? null) ? $signals['filewatch'] : [];
$ghostnote = is_array($signals['ghostnote'] ?? null) ? $signals['ghostnote'] : [];
?>

<p>
    <small><a href="/admin">Admin</a> &gt;&gt; <strong>DevBot</strong></small>
</p>

<main class="container my-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2">DevBot</h1>
            <p class="text-muted mb-0">
                Scheduled developer diagnostics and Chaos MVC structure reports.
            </p>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($data['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="scan">
            <button type="submit" class="btn btn-primary">Run scan now</button>
        </form>
    </div>

    <?php if (!empty($data['notice'])) : ?>
        <div class="alert alert-success" role="status">
            <?= htmlspecialchars((string) $data['notice'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($data['error'])) : ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars((string) $data['error'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($report)) : ?>
        <div class="alert alert-info">
            DevBot has not generated a report yet. Run the first scan or configure the cron command below.
        </div>
    <?php endif; ?>

    <section class="row g-3 mb-4" aria-label="DevBot status">
        <?php foreach ([
            'Status' => (string) ($summary['status'] ?? 'Not run'),
            'Critical' => (int) ($summary['critical'] ?? 0),
            'Warnings' => (int) ($summary['warning'] ?? 0),
            'Duration' => isset($summary['duration_ms']) ? ((int) $summary['duration_ms'] . ' ms') : '—',
        ] as $label => $value) : ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="h4 mb-0"><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?></p>
                </div></div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="card mb-4">
        <div class="card-body">
            <h2 class="h5">Latest scan</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3">Generated</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string) ($report['generated_at'] ?? 'Not generated'), ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt class="col-sm-3">Trigger</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string) ($report['trigger'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt class="col-sm-3">Controllers</dt>
                <dd class="col-sm-9"><?= (int) ($triage['summary']['controllers_total'] ?? 0); ?></dd>
                <dt class="col-sm-3">Views</dt>
                <dd class="col-sm-9"><?= (int) ($triage['summary']['views_total'] ?? 0); ?></dd>
                <dt class="col-sm-3">File changes</dt>
                <dd class="col-sm-9"><?= (int) ($filewatch['summary']['changes'] ?? 0); ?></dd>
                <dt class="col-sm-3">Ghostnotes</dt>
                <dd class="col-sm-9"><?= (int) ($ghostnote['summary']['notes'] ?? 0); ?></dd>
            </dl>
        </div>
    </section>

    <?php foreach (['criticals' => 'Critical findings', 'warnings' => 'Warnings'] as $bucket => $heading) : ?>
        <?php if (!empty($triage[$bucket])) : ?>
            <section class="card mb-4<?= $bucket === 'criticals' ? ' border-danger' : ''; ?>">
                <div class="card-body">
                    <h2 class="h5"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <ul class="mb-0">
                        <?php foreach ($triage[$bucket] as $finding) : ?>
                            <li>
                                <?= htmlspecialchars((string) ($finding['message'] ?? 'Unknown finding'), ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($finding['module'])) : ?>
                                    <code><?= htmlspecialchars((string) $finding['module'], ENT_QUOTES, 'UTF-8'); ?></code>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <section class="card mb-4">
        <div class="card-body">
            <h2 class="h5">Cron command</h2>
            <p class="text-muted">Run at the interval appropriate for the development environment.</p>
            <code class="d-block text-break"><?= htmlspecialchars((string) ($data['cron_command'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
        </div>
    </section>

    <?php if (!empty($data['history'])) : ?>
        <section>
            <h2 class="h5">Recent runs</h2>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Generated</th><th>Trigger</th><th>Status</th><th>Critical</th><th>Warnings</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['history'] as $run) : ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($run['generated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($run['trigger'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($run['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= (int) ($run['critical'] ?? 0); ?></td>
                                <td><?= (int) ($run['warning'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require APPROOT . '/views/inc/foot.php';
/* [End AI:OpenAI Codex] */
