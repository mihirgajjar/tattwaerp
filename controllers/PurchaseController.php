<?php

class PurchaseController extends Controller
{
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
            'invoiceNo' => $purchaseModel->nextInvoiceNo(),
            'suppliers' => $partnerModel->all('suppliers'),
            'products' => $productModel->all(),
            'businessState' => config('app')['business_state'],
        ]);
    }

    public function status(): void
    {
        $this->requirePermission('inventory', 'write');
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
}
