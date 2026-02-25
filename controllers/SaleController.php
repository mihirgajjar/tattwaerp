<?php

class SaleController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('billing', 'read');
        $model = new Sale();
        $this->view('sales/index', ['sales' => $model->all()]);
    }

    public function create(): void
    {
        $this->requirePermission('billing', 'write');
        $saleModel = new Sale();
        $partnerModel = new Partner();
        $productModel = new Product();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                [$header, $items] = $this->buildTransactionData();
                $saleId = $saleModel->create($header, $items);
                audit_log('create_sale', 'sale', $saleId, ['invoice_no' => $header['invoice_no']]);
                flash('success', 'Sale created successfully.');
                redirect('sale/invoice&id=' . $saleId);
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                redirect('sale/create');
            }
        }

        $this->view('sales/form', [
            'invoiceNo' => $saleModel->nextInvoiceNo(),
            'customers' => $partnerModel->all('customers'),
            'products' => $productModel->all(),
            'businessState' => config('app')['business_state'],
        ]);
    }

    public function status(): void
    {
        $this->requirePermission('billing', 'write');
        $this->requirePost('sale/index');
        $id = (int)$this->request('id', 0);
        $status = strtoupper(trim((string)$this->request('status', 'DRAFT')));
        try {
            if ($id > 0 && in_array($status, ['DRAFT', 'FINAL', 'CANCELLED', 'VOID'], true)) {
                (new Sale())->setStatus($id, $status);
                audit_log('sale_status', 'sale', $id, ['status' => $status]);
                flash('success', 'Sale status updated.');
            } else {
                flash('error', 'Invalid sale or status.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('sale/index');
    }

    public function delete(): void
    {
        $this->requirePermission('invoice_delete', 'delete');
        $this->requirePost('sale/index');
        $id = (int)$this->request('id', 0);
        if ($id > 0 && (new Sale())->deleteIfUnpaidAndAllowedStatus($id)) {
            audit_log('delete_sale', 'sale', $id);
            flash('success', 'Invoice deleted.');
        } else {
            flash('error', 'Delete allowed only for draft/cancelled/void invoices with no payment.');
        }
        redirect('sale/index');
    }

    public function invoice(): void
    {
        $this->requirePermission('billing', 'read');
        $id = (int)$this->request('id', 0);
        $sale = (new Sale())->findWithItems($id);

        if (!$sale) {
            flash('error', 'Invoice not found.');
            redirect('sale/index');
        }

        $this->view('sales/invoice', [
            'sale' => $sale,
            'app' => config('app'),
            'defaultBank' => $this->defaultBank(),
            'totalWords' => number_to_words_indian((float)$sale['total_amount']),
        ], false);
    }

    private function defaultBank(): ?array
    {
        try {
            $banks = (new Finance())->banks();
            foreach ($banks as $b) {
                if ((int)$b['is_default'] === 1) {
                    return $b;
                }
            }
            return $banks[0] ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function buildTransactionData(): array
    {
        $customerId = (int)$this->request('customer_id', 0);
        $date = (string)$this->request('date', date('Y-m-d'));
        $dueDate = (string)$this->request('due_date', date('Y-m-d', strtotime('+15 days')));
        $invoiceNo = trim((string)$this->request('invoice_no', ''));
        $status = strtoupper(trim((string)$this->request('status', 'FINAL')));
        $itemDiscount = (float)$this->request('item_discount', 0);
        $overallDiscount = (float)$this->request('overall_discount', 0);
        $roundOff = (float)$this->request('round_off', 0);
        $notes = trim((string)$this->request('notes', ''));
        $terms = trim((string)$this->request('terms', ''));

        $productIds = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $rates = $_POST['rate'] ?? [];
        $gstPercents = $_POST['gst_percent'] ?? [];

        if ($customerId <= 0 || $invoiceNo === '' || empty($productIds)) {
            throw new RuntimeException('Required fields are missing.');
        }
        if (!in_array($status, ['DRAFT', 'FINAL'], true)) {
            throw new RuntimeException('Invalid sale status.');
        }

        $items = [];
        $subtotal = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;

        $customerState = trim((string)$this->request('customer_state', ''));
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

            $lineDiscount = max(0.0, (float)$this->request('line_discount_' . $i, 0));
            $taxable = max(0.0, ($qty * $rate) - $lineDiscount);
            $taxAmount = $taxable * ($gstPercent / 100);
            $lineTotal = $taxable + $taxAmount;

            // Intrastate => CGST + SGST, Interstate => IGST.
            if (strcasecmp($customerState, $businessState) === 0) {
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
                'discount_amount' => $lineDiscount,
            ];
        }

        $discountedSubtotal = max(0.0, $subtotal - $itemDiscount - $overallDiscount);
        $grand = $discountedSubtotal + $cgst + $sgst + $igst + $roundOff;

        $header = [
            'invoice_no' => $invoiceNo,
            'customer_id' => $customerId,
            'date' => $date,
            'due_date' => $dueDate,
            'subtotal' => round($discountedSubtotal, 2),
            'cgst' => round($cgst, 2),
            'sgst' => round($sgst, 2),
            'igst' => round($igst, 2),
            'total_amount' => round($grand, 2),
            'status' => $status,
            'is_locked' => $status === 'FINAL' ? 1 : 0,
            'item_discount' => round($itemDiscount, 2),
            'overall_discount' => round($overallDiscount, 2),
            'round_off' => round($roundOff, 2),
            'notes' => $notes,
            'terms' => $terms,
            'payment_status' => 'UNPAID',
        ];

        return [$header, $items];
    }
}
