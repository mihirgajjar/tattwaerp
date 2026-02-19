<?php

class SmartController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new Smart();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAction($model);
            redirect('smart/index');
        }

        $from = (string)$this->request('from', date('Y-m-01'));
        $to = (string)$this->request('to', date('Y-m-t'));
        $month = (string)$this->request('month', date('Y-m'));

        $this->view('smart/index', [
            'from' => $from,
            'to' => $to,
            'month' => $month,
            'data' => $model->data($from, $to, $month),
        ]);
    }

    public function complianceCsv(): void
    {
        $this->requireAuth();
        $month = (string)$this->request('month', date('Y-m'));
        $rows = (new Smart())->gstCompliance($month);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=gst_hsn_' . $month . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Month', 'HSN', 'Taxable', 'GST']);
        foreach ($rows as $r) {
            fputcsv($out, [$month, $r['hsn_code'], $r['taxable'], $r['gst']]);
        }
        fclose($out);
        exit;
    }

    public function einvoice(): void
    {
        $this->requireAuth();
        $id = (int)$this->request('sale_id', 0);
        $sale = (new Sale())->findWithItems($id);

        if (!$sale) {
            flash('error', 'Invoice not found.');
            redirect('smart/index');
        }

        header('Content-Type: application/json; charset=utf-8');
        $payload = [
            'Version' => '1.1',
            'TranDtls' => ['TaxSch' => 'GST', 'SupTyp' => 'B2B'],
            'DocDtls' => ['Typ' => 'INV', 'No' => $sale['invoice_no'], 'Dt' => $sale['date']],
            'SellerDtls' => [
                'Gstin' => config('app')['business_gstin'],
                'LglNm' => config('app')['business_name'],
                'Addr1' => config('app')['business_address'],
                'Loc' => config('app')['business_state'],
            ],
            'BuyerDtls' => [
                'Gstin' => $sale['gstin'] ?: 'URP',
                'LglNm' => $sale['customer_name'],
                'Addr1' => $sale['address'],
                'Loc' => $sale['state'],
            ],
            'ItemList' => array_map(function ($i) {
                return [
                    'PrdDesc' => $i['product_name'],
                    'HsnCd' => $i['hsn_code'],
                    'Qty' => (float)$i['quantity'],
                    'UnitPrice' => (float)$i['rate'],
                    'GstRt' => (float)$i['gst_percent'],
                    'TotItemVal' => (float)$i['total'],
                ];
            }, $sale['items']),
            'ValDtls' => [
                'AssVal' => (float)$sale['subtotal'],
                'CgstVal' => (float)$sale['cgst'],
                'SgstVal' => (float)$sale['sgst'],
                'IgstVal' => (float)$sale['igst'],
                'TotInvVal' => (float)$sale['total_amount'],
            ],
        ];

        echo json_encode($payload, JSON_PRETTY_PRINT);
        exit;
    }

    public function barcode(): void
    {
        $this->requireAuth();
        $productId = (int)$this->request('product_id', 0);
        $product = (new Product())->find($productId);

        if (!$product) {
            flash('error', 'Product not found.');
            redirect('smart/index');
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo "BARCODE LABEL\n";
        echo 'Product: ' . $product['product_name'] . "\n";
        echo 'SKU Code: ' . $product['sku'] . "\n";
        echo 'Suggested QR payload: SKU:' . $product['sku'] . '|GST:' . $product['gst_percent'] . "\n";
        exit;
    }

    private function handleAction(Smart $model): void
    {
        $action = (string)$this->request('action', '');

        try {
            if ($action === 'record_payment') {
                $saleId = (int)$this->request('sale_id', 0);
                $amount = (float)$this->request('amount', 0);
                $mode = trim((string)$this->request('payment_mode', 'UPI'));
                $model->recordPayment($saleId, $amount, $mode);
                audit_log('record_payment', 'sale', $saleId, ['amount' => $amount, 'mode' => $mode]);
                flash('success', 'Payment recorded.');
                return;
            }

            if ($action === 'add_batch') {
                $data = [
                    'product_id' => (int)$this->request('product_id', 0),
                    'warehouse_id' => (int)$this->request('warehouse_id', 0),
                    'batch_no' => trim((string)$this->request('batch_no', '')),
                    'mfg_date' => (string)$this->request('mfg_date', date('Y-m-d')),
                    'expiry_date' => (string)$this->request('expiry_date', date('Y-m-d')),
                    'quantity' => (int)$this->request('quantity', 0),
                ];
                $model->addBatch($data);
                audit_log('add_batch', 'product_batch', 0, $data);
                flash('success', 'Batch added.');
                return;
            }

            if ($action === 'save_supplier_rate') {
                $model->saveSupplierRate((int)$this->request('product_id', 0), (int)$this->request('supplier_id', 0), (float)$this->request('rate', 0));
                audit_log('save_supplier_rate', 'supplier_price', 0, ['product_id' => (int)$this->request('product_id', 0)]);
                flash('success', 'Supplier rate snapshot saved.');
                return;
            }

            if ($action === 'create_price_list') {
                $listId = $model->createPriceList(trim((string)$this->request('name', '')), trim((string)$this->request('channel', 'Retail')));
                audit_log('create_price_list', 'price_list', $listId);
                flash('success', 'Price list created. Add item prices below.');
                return;
            }

            if ($action === 'add_price_item') {
                $model->addPriceListItem((int)$this->request('price_list_id', 0), (int)$this->request('product_id', 0), (float)$this->request('price', 0));
                audit_log('add_price_item', 'price_list_item', 0);
                flash('success', 'Price list item saved.');
                return;
            }

            if ($action === 'add_warehouse') {
                $model->addWarehouse(trim((string)$this->request('name', '')), trim((string)$this->request('state', '')));
                audit_log('add_warehouse', 'warehouse', 0);
                flash('success', 'Warehouse added.');
                return;
            }

            if ($action === 'transfer_stock') {
                $model->transferStock([
                    'product_id' => (int)$this->request('product_id', 0),
                    'from_warehouse_id' => (int)$this->request('from_warehouse_id', 0),
                    'to_warehouse_id' => (int)$this->request('to_warehouse_id', 0),
                    'quantity' => (int)$this->request('quantity', 0),
                ]);
                audit_log('transfer_stock', 'stock_transfer', 0);
                flash('success', 'Stock transferred.');
                return;
            }

            if ($action === 'create_return') {
                $model->createReturn((int)$this->request('sale_id', 0), (int)$this->request('product_id', 0), (int)$this->request('quantity', 1), trim((string)$this->request('reason', '')));
                audit_log('create_return', 'credit_note', 0);
                flash('success', 'Credit note created and stock reversed.');
                return;
            }

            if ($action === 'create_approval') {
                $model->createApproval(trim((string)$this->request('approval_type', '')), trim((string)$this->request('reference_no', '')), trim((string)$this->request('notes', '')));
                audit_log('create_approval', 'approval', 0);
                flash('success', 'Approval request submitted.');
                return;
            }

            if ($action === 'review_approval') {
                $model->reviewApproval((int)$this->request('approval_id', 0), trim((string)$this->request('status', 'PENDING')));
                audit_log('review_approval', 'approval', (int)$this->request('approval_id', 0), ['status' => $this->request('status')]);
                flash('success', 'Approval updated.');
                return;
            }

            if ($action === 'run_notifications') {
                $model->runNotifications();
                audit_log('run_notifications', 'notification', 0);
                flash('success', 'Daily summary notification queued.');
                return;
            }

            flash('error', 'Invalid action.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
    }
}
