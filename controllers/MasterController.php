<?php

class MasterController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('master_edit', 'read');
        $model = new Master();

        $table = (string)$this->request('table', 'product_categories');
        $q = trim((string)$this->request('q', ''));
        $status = (string)$this->request('status', 'all');
        $editId = (int)$this->request('edit_id', 0);

        try {
            $rows = $model->list($table, $q, $status);
        } catch (Throwable $e) {
            $table = 'product_categories';
            $rows = $model->list($table, $q, $status);
        }

        $editing = null;
        if ($editId > 0) {
            try {
                $editing = $model->find($table, $editId);
            } catch (Throwable $e) {
                $editing = null;
            }
        }

        $this->view('masters/index', [
            'table' => $table,
            'rows' => $rows,
            'tables' => $model->tables(),
            'q' => $q,
            'status' => $status,
            'editing' => $editing,
            'categories' => $model->list('product_categories', '', 'active'),
        ]);
    }

    public function save(): void
    {
        $this->requirePermission('master_edit', 'write');
        $this->requirePost('master/index');
        $table = (string)$this->request('table', '');
        $id = (int)$this->request('id', 0);
        $model = new Master();

        $data = [
            'name' => trim((string)$this->request('name', '')),
            'is_active' => (int)$this->request('is_active', 1),
            'gst_rate' => (float)$this->request('gst_rate', 0),
            'category_id' => (int)$this->request('category_id', 0),
            'state' => trim((string)$this->request('state', '')),
        ];

        try {
            if ($id > 0) {
                $model->update($table, $id, $data);
                audit_log('update_master', $table, $id);
                flash('success', 'Master record updated.');
            } else {
                $model->add($table, $data);
                audit_log('create_master', $table, 0);
                flash('success', 'Master record added.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('master/index&table=' . urlencode($table));
    }

    public function deactivate(): void
    {
        $this->requirePermission('master_edit', 'write');
        $this->requirePost('master/index');
        $table = (string)$this->request('table', '');
        $id = (int)$this->request('id', 0);
        $active = (int)$this->request('active', 1) === 1;

        try {
            (new Master())->deactivate($table, $id, $active);
            audit_log('deactivate_master', $table, $id, ['active' => $active]);
            flash('success', 'Record status updated.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('master/index&table=' . urlencode($table));
    }

    public function delete(): void
    {
        $this->requirePermission('master_edit', 'delete');
        $this->requirePost('master/index');
        $table = (string)$this->request('table', '');
        $id = (int)$this->request('id', 0);

        try {
            (new Master())->delete($table, $id);
            audit_log('delete_master', $table, $id);
            flash('success', 'Record deleted.');
        } catch (Throwable $e) {
            flash('error', 'Unable to delete. Record may be linked.');
        }

        redirect('master/index&table=' . urlencode($table));
    }
}
