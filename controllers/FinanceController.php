<?php

class FinanceController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('financial_report_view', 'read');
        $model = new Finance();
        $from = (string)$this->request('from', date('Y-m-01'));
        $to = (string)$this->request('to', date('Y-m-t'));

        $this->view('finance/index', [
            'from' => $from,
            'to' => $to,
            'banks' => $model->banks(),
            'received' => $model->receivedPayments($from, $to),
            'paid' => $model->paidPayments($from, $to),
            'sales' => (new Sale())->all(),
            'suppliers' => (new Partner())->all('suppliers'),
            'purchases' => (new Purchase())->all(),
        ]);
    }

    public function addBank(): void
    {
        $this->requirePermission('financial_report_view', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('finance/index');
        }

        $qrPath = '';
        if (!empty($_FILES['qr_image']['tmp_name'])) {
            $destDir = __DIR__ . '/../assets/uploads';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0775, true);
            }
            $filename = 'qr_' . time() . '.png';
            $target = $destDir . '/' . $filename;
            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $target)) {
                $qrPath = 'assets/uploads/' . $filename;
            }
        }

        (new Finance())->addBank([
            'bank_name' => trim((string)$this->request('bank_name', '')),
            'account_name' => trim((string)$this->request('account_name', '')),
            'account_no' => trim((string)$this->request('account_no', '')),
            'ifsc' => trim((string)$this->request('ifsc', '')),
            'upi_id' => trim((string)$this->request('upi_id', '')),
            'qr_image_path' => $qrPath,
            'is_default' => (int)$this->request('is_default', 0) === 1 ? 1 : 0,
        ]);

        audit_log('add_bank_account', 'bank_accounts', 0);
        flash('success', 'Bank account added.');
        redirect('finance/index');
    }

    public function receive(): void
    {
        $this->requirePermission('financial_report_view', 'write');
        $this->requirePost('finance/index');
        try {
            (new Finance())->recordReceived((int)$this->request('sale_id', 0), (float)$this->request('amount', 0), (string)$this->request('payment_mode', 'UPI'));
            audit_log('record_payment_received', 'customer_payments', 0);
            flash('success', 'Payment received recorded.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('finance/index');
    }

    public function pay(): void
    {
        $this->requirePermission('financial_report_view', 'write');
        $this->requirePost('finance/index');
        try {
            (new Finance())->recordPaid((int)$this->request('supplier_id', 0), (int)$this->request('purchase_id', 0), (float)$this->request('amount', 0), (string)$this->request('payment_mode', 'Bank Transfer'));
            audit_log('record_payment_made', 'customer_payables', 0);
            flash('success', 'Payment made recorded.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('finance/index');
    }
}
