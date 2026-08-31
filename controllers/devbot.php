<?php
declare(strict_types=1);

/**
 * DevBot administration controller.
 */

/* [AI:OpenAI Codex | 2026-08-31 06:45:00 UTC] */
class devbot extends controller
{
    /**
     * Display DevBot status and optionally run an authorized scan.
     *
     * @param array $params Route parameters.
     *
     * @return void
     */
    public function admin($params = [])
    {
        $this->require_admin(7);
        $model = $this->model('devbot_model');
        $notice = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->require_csrf();

            if (($_POST['action'] ?? '') === 'scan') {
                try {
                    $report = $model->run_scan('admin');
                    $notice = sprintf(
                        'DevBot scan completed with %d critical finding(s) and %d warning(s).',
                        (int) ($report['summary']['critical'] ?? 0),
                        (int) ($report['summary']['warning'] ?? 0)
                    );
                } catch (Throwable $exception) {
                    $error = $exception->getMessage();
                }
            }
        }

        $data = $model->dashboard();
        $data['title'] = 'DevBot Admin';
        $data['notice'] = $notice;
        $data['error'] = $error;
        $data['csrf_token'] = $this->csrf_token();

        $this->view('admin/devbot', $data);
    }
}
/* [End AI:OpenAI Codex] */
