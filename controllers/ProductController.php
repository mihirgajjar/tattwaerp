<?php

class ProductController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new Product();
        $this->view('products/index', ['products' => $model->all()]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $model = new Product();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->validatedData();
            if ($data) {
                $model->create($data);
                audit_log('create_product', 'product', 0, ['sku' => $data['sku']]);
                flash('success', 'Product created.');
                clear_old();
                redirect('product/index');
            }
            redirect('product/create');
        }

        $this->view('products/form', ['action' => 'create', 'product' => null]);
    }

    public function edit(): void
    {
        $this->requireAuth();
        $model = new Product();
        $id = (int)$this->request('id', 0);
        $product = $model->find($id);

        if (!$product) {
            flash('error', 'Product not found.');
            redirect('product/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->validatedData();
            if ($data) {
                $model->update($id, $data);
                audit_log('update_product', 'product', $id, ['sku' => $data['sku']]);
                flash('success', 'Product updated.');
                clear_old();
                redirect('product/index');
            }
            redirect('product/edit&id=' . $id);
        }

        $this->view('products/form', ['action' => 'edit&id=' . $id, 'product' => $product]);
    }

    public function delete(): void
    {
        $this->requireAuth();
        $id = (int)$this->request('id', 0);
        if ($id > 0) {
            (new Product())->delete($id);
            audit_log('delete_product', 'product', $id);
            flash('success', 'Product deleted.');
        }
        redirect('product/index');
    }

    private function validatedData(): ?array
    {
        $data = [
            'product_name' => trim((string)$this->request('product_name', '')),
            'sku' => trim((string)$this->request('sku', '')),
            'category' => trim((string)$this->request('category', '')),
            'variant' => trim((string)$this->request('variant', '')),
            'size' => trim((string)$this->request('size', '')),
            'hsn_code' => trim((string)$this->request('hsn_code', '')),
            'gst_percent' => (float)$this->request('gst_percent', 0),
            'purchase_price' => (float)$this->request('purchase_price', 0),
            'selling_price' => (float)$this->request('selling_price', 0),
            'stock_quantity' => (int)$this->request('stock_quantity', 0),
            'reorder_level' => (int)$this->request('reorder_level', 0),
        ];

        set_old($data);

        if ($data['product_name'] === '' || $data['sku'] === '') {
            flash('error', 'Product name and SKU are required.');
            return null;
        }

        if (!in_array($data['gst_percent'], [5.0, 12.0, 18.0], true)) {
            flash('error', 'GST must be 5, 12 or 18.');
            return null;
        }

        return $data;
    }
}
