<?php

class SaleController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new Sale();
        $this->view('sales/index', ['sales' => $model->all()]);
    }

    public function create(): void
    {
        $this->requireAuth();
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

    public function invoice(): void
    {
        $this->requireAuth();
        $id = (int)$this->request('id', 0);
        $sale = (new Sale())->findWithItems($id);

        if (!$sale) {
            flash('error', 'Invoice not found.');
            redirect('sale/index');
        }

        $this->view('sales/invoice', [
            'sale' => $sale,
            'app' => config('app'),
            'totalWords' => number_to_words_indian((float)$sale['total_amount']),
        ], false);
    }

    private function buildTransactionData(): array
    {
        $customerId = (int)$this->request('customer_id', 0);
        $date = (string)$this->request('date', date('Y-m-d'));
        $dueDate = (string)$this->request('due_date', date('Y-m-d', strtotime('+15 days')));
        $invoiceNo = trim((string)$this->request('invoice_no', ''));

        $productIds = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $rates = $_POST['rate'] ?? [];
        $gstPercents = $_POST['gst_percent'] ?? [];

        if ($customerId <= 0 || $invoiceNo === '' || empty($productIds)) {
            throw new RuntimeException('Required fields are missing.');
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

            if (!in_array($gstPercent, [5.0, 12.0, 18.0], true)) {
                throw new RuntimeException('Invalid GST rate selected.');
            }

            $taxable = $qty * $rate;
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
            ];
        }

        $header = [
            'invoice_no' => $invoiceNo,
            'customer_id' => $customerId,
            'date' => $date,
            'due_date' => $dueDate,
            'subtotal' => round($subtotal, 2),
            'cgst' => round($cgst, 2),
            'sgst' => round($sgst, 2),
            'igst' => round($igst, 2),
            'total_amount' => round($subtotal + $cgst + $sgst + $igst, 2),
        ];

        return [$header, $items];
    }
}
