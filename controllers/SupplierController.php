<?php

class SupplierController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new Partner();

        $editId = (int)$this->request('edit_id', 0);
        $editing = $editId > 0 ? $model->find('suppliers', $editId) : null;

        $this->view('partners/suppliers', [
            'suppliers' => $model->all('suppliers'),
            'editing' => $editing,
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('supplier/index');
        }

        $model = new Partner();
        $data = $this->validatedData();
        if ($data === null) {
            redirect('supplier/index');
        }

        $model->create('suppliers', $data);
        audit_log('create_supplier', 'supplier', 0, ['name' => $data['name']]);
        flash('success', 'Supplier added.');
        redirect('supplier/index');
    }

    public function update(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('supplier/index');
        }

        $id = (int)$this->request('id', 0);
        if ($id <= 0) {
            flash('error', 'Invalid supplier selected.');
            redirect('supplier/index');
        }

        $model = new Partner();
        $data = $this->validatedData();
        if ($data === null) {
            redirect('supplier/index&edit_id=' . $id);
        }

        $model->update('suppliers', $id, $data);
        audit_log('update_supplier', 'supplier', $id, ['name' => $data['name']]);
        flash('success', 'Supplier updated.');
        redirect('supplier/index');
    }

    public function delete(): void
    {
        $this->requireAuth();
        $id = (int)$this->request('id', 0);

        if ($id <= 0) {
            flash('error', 'Invalid supplier selected.');
            redirect('supplier/index');
        }

        try {
            (new Partner())->delete('suppliers', $id);
            audit_log('delete_supplier', 'supplier', $id);
            flash('success', 'Supplier deleted.');
        } catch (Throwable $e) {
            flash('error', 'Supplier cannot be deleted because it is linked to purchases.');
        }

        redirect('supplier/index');
    }

    private function validatedData(): ?array
    {
        $data = [
            'name' => trim((string)$this->request('name', '')),
            'gstin' => trim((string)$this->request('gstin', '')),
            'state' => trim((string)$this->request('state', '')),
            'phone' => trim((string)$this->request('phone', '')),
            'address' => trim((string)$this->request('address', '')),
        ];

        if ($data['name'] === '' || $data['state'] === '') {
            flash('error', 'Name and state are required.');
            return null;
        }

        return $data;
    }
}
