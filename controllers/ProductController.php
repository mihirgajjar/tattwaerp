<?php

class ProductController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('inventory', 'read');
        $model = new Product();
        $this->view('products/index', ['products' => $model->all()]);
    }

    public function create(): void
    {
        $this->requirePermission('inventory', 'write');
        $model = new Product();
        $categoryOptions = $this->categoryOptions();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->validatedData(0);
            if ($data) {
                try {
                    $model->create($data);
                    audit_log('create_product', 'product', 0, ['sku' => $data['sku']]);
                    flash('success', 'Product created.');
                    clear_old();
                    redirect('product/index');
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }
            }
            redirect('product/create');
        }

        $this->view('products/form', ['action' => 'create', 'product' => null, 'categoryOptions' => $categoryOptions]);
    }

    public function edit(): void
    {
        $this->requirePermission('inventory', 'write');
        $model = new Product();
        $categoryOptions = $this->categoryOptions();
        $id = (int)$this->request('id', 0);
        $product = $model->find($id);

        if (!$product) {
            flash('error', 'Product not found.');
            redirect('product/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->validatedData($id);
            if ($data) {
                try {
                    $model->update($id, $data);
                    audit_log('update_product', 'product', $id, ['sku' => $data['sku']]);
                    flash('success', 'Product updated.');
                    clear_old();
                    redirect('product/index');
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }
            }
            redirect('product/edit&id=' . $id);
        }

        $this->view('products/form', ['action' => 'edit&id=' . $id, 'product' => $product, 'categoryOptions' => $categoryOptions]);
    }

    public function delete(): void
    {
        $this->requirePermission('inventory', 'delete');
        $this->requirePost('product/index');
        $id = (int)$this->request('id', 0);
        if ($id > 0) {
            try {
                (new Product())->delete($id);
                audit_log('delete_product', 'product', $id);
                flash('success', 'Product deleted.');
            } catch (Throwable $e) {
                flash('error', 'Product cannot be deleted because it is used in transactions.');
            }
        }
        redirect('product/index');
    }

    private function validatedData(int $excludeId = 0): ?array
    {
        $productModel = new Product();
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
            'reserved_stock' => (int)$this->request('reserved_stock', 0),
            'min_stock_level' => (int)$this->request('min_stock_level', 0),
            'barcode' => trim((string)$this->request('barcode', '')),
            'image_path' => trim((string)$this->request('existing_image_path', '')),
            'is_active' => (int)$this->request('is_active', 1) === 1 ? 1 : 0,
        ];

        set_old($data);

        if ($data['product_name'] === '') {
            flash('error', 'Product name is required.');
            return null;
        }

        if ($data['sku'] === '') {
            $data['sku'] = $productModel->generateSku($data, $excludeId);
        }

        if ($productModel->skuExists($data['sku'], $excludeId)) {
            flash('error', 'SKU already exists. Please use a different SKU.');
            return null;
        }

        if (!in_array($data['gst_percent'], [5.0, 12.0, 18.0], true)) {
            flash('error', 'GST must be 5, 12 or 18.');
            return null;
        }

        if (!empty($_FILES['image']['tmp_name'])) {
            $destDir = __DIR__ . '/../assets/uploads';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0775, true);
            }
            $filename = 'product_' . time() . '_' . mt_rand(100, 999) . '.png';
            $target = $destDir . '/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $data['image_path'] = 'assets/uploads/' . $filename;
            }
        }

        return $data;
    }

    private function categoryOptions(): array
    {
        try {
            $rows = (new Master())->list('product_categories', '', 'active');
            $names = array_values(array_filter(array_map(static fn($r) => trim((string)($r['name'] ?? '')), $rows)));
            if (!empty($names)) {
                return $names;
            }
        } catch (Throwable $e) {
            // fallback below
        }

        return ['Single Oil', 'Blend', 'Diffuser Oil'];
    }
}
