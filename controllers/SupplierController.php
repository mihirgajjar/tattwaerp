<?php

class SupplierController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('master_edit', 'read');
        $model = new Partner();

        $editId = (int)$this->request('edit_id', 0);
        $editing = $editId > 0 ? $model->find('suppliers', $editId) : null;
        $q = trim((string)$this->request('q', ''));
        $status = trim((string)$this->request('status', 'all'));

        $this->view('partners/suppliers', [
            'suppliers' => $model->all('suppliers', $q, $status),
            'editing' => $editing,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('master_edit', 'write');
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
        $this->requirePermission('master_edit', 'write');
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
        $this->requirePermission('master_edit', 'delete');
        $this->requirePost('supplier/index');
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

    public function dashboard(): void
    {
        $this->requirePermission('reports', 'read');
        $id = (int)$this->request('id', 0);
        $model = new Partner();
        $supplier = $model->find('suppliers', $id);
        if (!$supplier) {
            flash('error', 'Supplier not found.');
            redirect('supplier/index');
        }

        $data = $model->supplierDashboard($id);
        $this->view('partners/supplier_dashboard', [
            'supplier' => $supplier,
            'summary' => $data['summary'],
            'outstanding' => $data['outstanding'],
            'transactions' => $data['transactions'],
        ]);
    }

    private function validatedData(): ?array
    {
        $data = [
            'name' => trim((string)$this->request('name', '')),
            'gstin' => trim((string)$this->request('gstin', '')),
            'state' => trim((string)$this->request('state', '')),
            'phone' => trim((string)$this->request('phone', '')),
            'address' => trim((string)$this->request('address', '')),
            'supplier_type' => trim((string)$this->request('supplier_type', 'General')),
            'bank_details' => trim((string)$this->request('bank_details', '')),
            'payment_terms' => trim((string)$this->request('payment_terms', '')),
            'is_active' => (int)$this->request('is_active', 1) === 1 ? 1 : 0,
        ];

        if ($data['name'] === '' || $data['state'] === '') {
            flash('error', 'Name and state are required.');
            return null;
        }

        return $data;
    }
}
