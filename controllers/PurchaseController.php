<?php

class PurchaseController extends Controller
{
    private const IMPORT_SESSION_KEY = '_purchase_import_payload';

    public function index(): void
    {
        $this->requirePermission('inventory', 'read');
        audit_log('view_purchase_index', 'purchase', 0, ['route' => 'purchase/index']);
        $model = new Purchase();
        $this->view('purchases/index', ['purchases' => $model->all()]);
    }

    public function create(): void
    {
        $this->requirePermission('inventory', 'write');
        $purchaseModel = new Purchase();
        $partnerModel = new Partner();
        $productModel = new Product();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                [$header, $items] = $this->buildTransactionData('supplier_id', 'purchase_invoice_no');
                $purchaseId = $purchaseModel->create($header, $items);
                audit_log('create_purchase', 'purchase', $purchaseId, [
                    'purchase_invoice_no' => $header['purchase_invoice_no'],
                    'status' => $header['status'],
                ]);
                flash('success', 'Purchase recorded successfully.');
                redirect('purchase/index');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                redirect('purchase/create');
            }
        }

        $this->view('purchases/form', [
            'formAction' => 'purchase/create',
            'title' => 'Create Purchase',
            'submitLabel' => 'Save Purchase',
            'purchase' => null,
            'invoiceNo' => $purchaseModel->nextInvoiceNo(),
            'suppliers' => $partnerModel->all('suppliers'),
            'products' => $productModel->all(),
            'existingItems' => [],
            'businessState' => config('app')['business_state'],
        ]);
    }

    public function edit(): void
    {
        $this->requirePermission('inventory', 'write');
        $id = (int)$this->request('id', 0);
        $purchaseModel = new Purchase();
        $partnerModel = new Partner();
        $productModel = new Product();

        $purchase = $purchaseModel->find($id);
        if (!$purchase) {
            flash('error', 'Purchase not found.');
            redirect('purchase/index');
        }

        if (strtoupper((string)$purchase['status']) !== 'DRAFT') {
            flash('error', 'Only draft purchases can be edited.');
            redirect('purchase/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                [$header, $items] = $this->buildTransactionData('supplier_id', 'purchase_invoice_no');
                $purchaseModel->updateDraft($id, $header, $items);
                audit_log('update_purchase', 'purchase', $id, [
                    'purchase_invoice_no' => $header['purchase_invoice_no'],
                    'status' => $header['status'],
                ]);
                flash('success', 'Purchase updated successfully.');
                redirect('purchase/index');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                redirect('purchase/edit&id=' . $id);
            }
        }

        $this->view('purchases/form', [
            'formAction' => 'purchase/edit&id=' . $id,
            'title' => 'Edit Purchase',
            'submitLabel' => 'Update Purchase',
            'purchase' => $purchase,
            'invoiceNo' => (string)$purchase['purchase_invoice_no'],
            'suppliers' => $partnerModel->all('suppliers'),
            'products' => $productModel->all(),
            'existingItems' => $purchaseModel->items($id),
            'businessState' => config('app')['business_state'],
        ]);
    }

    public function import(): void
    {
        $this->requirePermission('inventory', 'write');
        $partnerModel = new Partner();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $supplierId = (int)$this->request('supplier_id', 0);
                $status = strtoupper(trim((string)$this->request('status', 'DRAFT')));
                $date = (string)$this->request('date', date('Y-m-d'));
                $invoiceNo = trim((string)$this->request('purchase_invoice_no', ''));
                $transportCost = (float)$this->request('transport_cost', 0);
                $otherCharges = (float)$this->request('other_charges', 0);

                if ($supplierId <= 0 || !isset($_FILES['source_file'])) {
                    throw new RuntimeException('Supplier and source file are required.');
                }
                if (!in_array($status, ['DRAFT', 'FINAL'], true)) {
                    throw new RuntimeException('Invalid purchase status.');
                }

                $purchaseModel = new Purchase();
                if ($invoiceNo === '') {
                    $invoiceNo = $purchaseModel->nextInvoiceNo();
                }

                $supplier = $partnerModel->find('suppliers', $supplierId);
                if (!$supplier) {
                    throw new RuntimeException('Supplier not found.');
                }

                $rows = (new PurchaseImport())->parseUploadedFile($_FILES['source_file']);
                $mappedRows = $this->autoMapRows($rows);

                if (count($mappedRows) === 0) {
                    throw new RuntimeException('No valid rows found in uploaded file.');
                }

                $_SESSION[self::IMPORT_SESSION_KEY] = [
                    'header' => [
                        'purchase_invoice_no' => $invoiceNo,
                        'supplier_id' => $supplierId,
                        'supplier_state' => (string)($supplier['state'] ?? ''),
                        'date' => $date,
                        'status' => $status,
                        'transport_cost' => $transportCost,
                        'other_charges' => $otherCharges,
                        'source_file' => $_FILES['source_file']['name'] ?? '',
                    ],
                    'rows' => $mappedRows,
                ];

                audit_log('purchase_import_uploaded', 'purchase_import', 0, [
                    'source_file' => $_FILES['source_file']['name'] ?? '',
                    'rows' => count($mappedRows),
                ]);

                redirect('purchase/previewImport');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                redirect('purchase/import');
            }
        }

        $this->view('purchases/import', [
            'invoiceNo' => (new Purchase())->nextInvoiceNo(),
            'suppliers' => $partnerModel->all('suppliers'),
        ]);
    }

    public function previewImport(): void
    {
        $this->requirePermission('inventory', 'write');
        $payload = $_SESSION[self::IMPORT_SESSION_KEY] ?? null;
        if (!$payload || empty($payload['rows'])) {
            flash('error', 'No import data found. Please upload file again.');
            redirect('purchase/import');
        }

        $this->view('purchases/import_preview', [
            'header' => $payload['header'],
            'rows' => $payload['rows'],
            'products' => (new Product())->all(),
            'supplier' => (new Partner())->find('suppliers', (int)$payload['header']['supplier_id']),
        ]);
    }

    public function finalizeImport(): void
    {
        $this->requirePermission('inventory', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('purchase/previewImport');
        }

        $payload = $_SESSION[self::IMPORT_SESSION_KEY] ?? null;
        if (!$payload || empty($payload['rows'])) {
            flash('error', 'No import data found. Please upload again.');
            redirect('purchase/import');
        }

        try {
            $productIds = $_POST['product_id'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $rates = $_POST['rate'] ?? [];
            $gstPercents = $_POST['gst_percent'] ?? [];

            $resolvedRows = [];
            foreach ($payload['rows'] as $i => $row) {
                $pid = (int)($productIds[$i] ?? 0);
                $qty = max(1, (int)($quantities[$i] ?? 0));
                $rate = (float)($rates[$i] ?? 0);
                $gst = (float)($gstPercents[$i] ?? 0);

                if ($pid <= 0) {
                    throw new RuntimeException('Please map all rows to a product before finalizing import.');
                }
                if ($qty <= 0 || $rate <= 0) {
                    throw new RuntimeException('Quantity and rate must be greater than 0 for all rows.');
                }
                if (!in_array($gst, [5.0, 12.0, 18.0], true)) {
                    throw new RuntimeException('Invalid GST rate found in preview rows.');
                }

                $resolvedRows[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'rate' => $rate,
                    'gst_percent' => $gst,
                ];
            }

            [$header, $items] = $this->buildFromPreviewRows($payload['header'], $resolvedRows);
            $purchaseId = (new Purchase())->create($header, $items);

            audit_log('import_purchase_from_file', 'purchase', $purchaseId, [
                'source_file' => $payload['header']['source_file'] ?? '',
                'status' => $header['status'],
            ]);

            unset($_SESSION[self::IMPORT_SESSION_KEY]);
            flash('success', 'Purchase imported and created successfully.');
            redirect('purchase/index');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('purchase/previewImport');
        }
    }

    public function clearImport(): void
    {
        $this->requirePermission('inventory', 'write');
        $this->requirePost('purchase/import');
        unset($_SESSION[self::IMPORT_SESSION_KEY]);
        flash('success', 'Import draft cleared.');
        redirect('purchase/import');
    }

    public function status(): void
    {
        $this->requirePermission('inventory', 'write');
        $this->requirePost('purchase/index');
        $id = (int)$this->request('id', 0);
        $status = strtoupper(trim((string)$this->request('status', 'DRAFT')));

        try {
            if ($id > 0) {
                (new Purchase())->setStatus($id, $status);
                audit_log('purchase_status_change', 'purchase', $id, ['status' => $status]);
                flash('success', 'Purchase status updated to ' . $status . '.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('purchase/index');
    }

    public function delete(): void
    {
        $this->requirePermission('inventory', 'delete');
        $this->requirePost('purchase/index');
        $id = (int)$this->request('id', 0);

        try {
            $ok = $id > 0 ? (new Purchase())->deleteSafe($id) : false;
            if ($ok) {
                audit_log('delete_purchase', 'purchase', $id);
                flash('success', 'Purchase deleted successfully.');
            } else {
                flash('error', 'Delete allowed only for Draft/Cancelled purchase with no payment.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('purchase/index');
    }

    private function buildTransactionData(string $partyField, string $invoiceField): array
    {
        $partyId = (int)$this->request($partyField, 0);
        $date = (string)$this->request('date', date('Y-m-d'));
        $invoiceNo = trim((string)$this->request($invoiceField, ''));
        $status = strtoupper(trim((string)$this->request('status', 'FINAL')));
        $transportCost = (float)$this->request('transport_cost', 0);
        $otherCharges = (float)$this->request('other_charges', 0);

        $productIds = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $rates = $_POST['rate'] ?? [];
        $gstPercents = $_POST['gst_percent'] ?? [];

        if ($partyId <= 0 || $invoiceNo === '' || empty($productIds)) {
            throw new RuntimeException('Required fields are missing.');
        }

        if (!in_array($status, ['DRAFT', 'FINAL'], true)) {
            throw new RuntimeException('Invalid purchase status.');
        }

        $items = [];
        $subtotal = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;

        $partyState = trim((string)$this->request('party_state', ''));
        $businessState = config('app')['business_state'];

        foreach ($productIds as $i => $productId) {
            $qty = max(1, (int)($quantities[$i] ?? 0));
            $rate = (float)($rates[$i] ?? 0);
            $gstPercent = (float)($gstPercents[$i] ?? 0);
            if ($rate <= 0) {
                throw new RuntimeException('Rate must be greater than 0 for all line items.');
            }

            if (!in_array($gstPercent, [5.0, 12.0, 18.0], true)) {
                throw new RuntimeException('Invalid GST rate selected.');
            }

            $taxable = $qty * $rate;
            $taxAmount = $taxable * ($gstPercent / 100);
            $lineTotal = $taxable + $taxAmount;

            if (strcasecmp($partyState, $businessState) === 0) {
                $cgst += $taxAmount / 2;
                $sgst += $taxAmount / 2;
            } else {
                $igst += $taxAmount;
            }

            $subtotal += $taxable;

            $items[] = [
                'product_id' => (int)$productId,
                'quantity' => $qty,
                'rate' => $rate,
                'gst_percent' => $gstPercent,
                'tax_amount' => $taxAmount,
                'total' => $lineTotal,
            ];
        }

        $header = [
            $invoiceField => $invoiceNo,
            $partyField => $partyId,
            'date' => $date,
            'subtotal' => round($subtotal, 2),
            'cgst' => round($cgst, 2),
            'sgst' => round($sgst, 2),
            'igst' => round($igst, 2),
            'total_amount' => round($subtotal + $cgst + $sgst + $igst + $transportCost + $otherCharges, 2),
            'status' => $status,
            'transport_cost' => round($transportCost, 2),
            'other_charges' => round($otherCharges, 2),
        ];

        return [$header, $items];
    }

    private function autoMapRows(array $rows): array
    {
        $productModel = new Product();
        $result = [];

        foreach ($rows as $r) {
            $sku = trim((string)($r['sku'] ?? ''));
            $name = trim((string)($r['product_name'] ?? ''));

            $product = null;
            if ($sku !== '') {
                $product = $productModel->findBySku($sku);
            }
            if (!$product && $name !== '') {
                $product = $productModel->findByName($name);
            }

            $result[] = [
                'product_name' => $name,
                'sku' => $sku,
                'product_id' => (int)($product['id'] ?? 0),
                'quantity' => max(1, (int)round((float)($r['quantity'] ?? 0))),
                'rate' => (float)($r['rate'] ?? 0),
                'gst_percent' => (float)($r['gst_percent'] ?? ($product['gst_percent'] ?? 18)),
            ];
        }

        return $result;
    }

    private function buildFromPreviewRows(array $headerIn, array $rows): array
    {
        $supplierState = (string)($headerIn['supplier_state'] ?? '');
        $businessState = config('app')['business_state'];

        $items = [];
        $subtotal = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;

        foreach ($rows as $r) {
            $qty = max(1, (int)$r['quantity']);
            $rate = (float)$r['rate'];
            $gst = (float)$r['gst_percent'];

            $taxable = $qty * $rate;
            $taxAmount = $taxable * ($gst / 100);
            $lineTotal = $taxable + $taxAmount;

            if (strcasecmp($supplierState, $businessState) === 0) {
                $cgst += $taxAmount / 2;
                $sgst += $taxAmount / 2;
            } else {
                $igst += $taxAmount;
            }

            $subtotal += $taxable;

            $items[] = [
                'product_id' => (int)$r['product_id'],
                'quantity' => $qty,
                'rate' => $rate,
                'gst_percent' => $gst,
                'tax_amount' => round($taxAmount, 2),
                'total' => round($lineTotal, 2),
            ];
        }

        $transport = (float)($headerIn['transport_cost'] ?? 0);
        $other = (float)($headerIn['other_charges'] ?? 0);

        $header = [
            'purchase_invoice_no' => (string)$headerIn['purchase_invoice_no'],
            'supplier_id' => (int)$headerIn['supplier_id'],
            'date' => (string)$headerIn['date'],
            'subtotal' => round($subtotal, 2),
            'cgst' => round($cgst, 2),
            'sgst' => round($sgst, 2),
            'igst' => round($igst, 2),
            'total_amount' => round($subtotal + $cgst + $sgst + $igst + $transport + $other, 2),
            'status' => strtoupper((string)$headerIn['status']),
            'transport_cost' => round($transport, 2),
            'other_charges' => round($other, 2),
        ];

        return [$header, $items];
    }
}
