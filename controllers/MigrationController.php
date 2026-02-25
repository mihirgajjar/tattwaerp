<?php

class MigrationController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('user_manage', 'write');
        $model = new Migration();

        $this->view('settings/migrations', [
            'pending' => $model->pending(),
            'applied' => $model->applied(),
        ]);
    }

    public function run(): void
    {
        $this->requirePermission('user_manage', 'write');
        $this->requirePost('migration/index');

        try {
            $applied = (new Migration())->applyAll();
            audit_log('run_migrations', 'schema_migrations', 0, ['applied' => $applied]);
            if (count($applied) === 0) {
                flash('success', 'No pending migrations.');
            } else {
                flash('success', 'Applied migrations: ' . implode(', ', $applied));
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('migration/index');
    }

    public function baseline(): void
    {
        $this->requirePermission('user_manage', 'write');
        $this->requirePost('migration/index');

        try {
            $marked = (new Migration())->baselineMarkAll();
            audit_log('baseline_migrations', 'schema_migrations', 0, ['marked' => $marked]);
            if (count($marked) === 0) {
                flash('success', 'Baseline already up to date.');
            } else {
                flash('success', 'Marked as applied: ' . implode(', ', $marked));
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('migration/index');
    }
}
