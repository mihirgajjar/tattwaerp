<?php

class CustomerController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new Partner();

        $editId = (int)$this->request('edit_id', 0);
        $editing = $editId > 0 ? $model->find('customers', $editId) : null;

        $this->view('partners/customers', [
            'customers' => $model->all('customers'),
            'editing' => $editing,
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
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
        $this->requireAuth();
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
        $this->requireAuth();
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
