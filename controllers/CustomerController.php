<?php

class CustomerController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('master_edit', 'read');
        $model = new Partner();

        $editId = (int)$this->request('edit_id', 0);
        $editing = $editId > 0 ? $model->find('customers', $editId) : null;
        $q = trim((string)$this->request('q', ''));
        $status = trim((string)$this->request('status', 'all'));

        $this->view('partners/customers', [
            'customers' => $model->all('customers', $q, $status),
            'editing' => $editing,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('master_edit', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('customer/index');
        }

        $model = new Partner();
        $data = $this->validatedData();
        if ($data === null) {
            redirect('customer/index');
        }

        $model->create('customers', $data);
        audit_log('create_customer', 'customer', 0, ['name' => $data['name']]);
        flash('success', 'Customer added.');
        redirect('customer/index');
    }

    public function update(): void
    {
        $this->requirePermission('master_edit', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('customer/index');
        }

        $id = (int)$this->request('id', 0);
        if ($id <= 0) {
            flash('error', 'Invalid customer selected.');
            redirect('customer/index');
        }

        $model = new Partner();
        $data = $this->validatedData();
        if ($data === null) {
            redirect('customer/index&edit_id=' . $id);
        }

        $model->update('customers', $id, $data);
        audit_log('update_customer', 'customer', $id, ['name' => $data['name']]);
        flash('success', 'Customer updated.');
        redirect('customer/index');
    }

    public function delete(): void
    {
        $this->requirePermission('master_edit', 'delete');
        $id = (int)$this->request('id', 0);

        if ($id <= 0) {
            flash('error', 'Invalid customer selected.');
            redirect('customer/index');
        }

        try {
            (new Partner())->delete('customers', $id);
            audit_log('delete_customer', 'customer', $id);
            flash('success', 'Customer deleted.');
        } catch (Throwable $e) {
            flash('error', 'Customer cannot be deleted because it is linked to sales.');
        }

        redirect('customer/index');
    }

    public function dashboard(): void
    {
        $this->requirePermission('reports', 'read');
        $id = (int)$this->request('id', 0);
        $model = new Partner();
        $customer = $model->find('customers', $id);
        if (!$customer) {
            flash('error', 'Customer not found.');
            redirect('customer/index');
        }

        $data = $model->customerDashboard($id);
        $this->view('partners/customer_dashboard', [
            'customer' => $customer,
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
            'customer_type' => trim((string)$this->request('customer_type', 'Retail')),
            'area_region' => trim((string)$this->request('area_region', '')),
            'payment_terms' => trim((string)$this->request('payment_terms', '')),
            'credit_limit' => (float)$this->request('credit_limit', 0),
            'pan_no' => trim((string)$this->request('pan_no', '')),
            'shipping_address' => trim((string)$this->request('shipping_address', '')),
            'is_active' => (int)$this->request('is_active', 1) === 1 ? 1 : 0,
        ];

        if ($data['name'] === '' || $data['state'] === '') {
            flash('error', 'Name and state are required.');
            return null;
        }

        return $data;
    }
}
