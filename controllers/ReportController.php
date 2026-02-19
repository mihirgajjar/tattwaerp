<?php

class ReportController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new Report();

        $from = (string)$this->request('from', date('Y-m-01'));
        $to = (string)$this->request('to', date('Y-m-t'));
        $month = (string)$this->request('month', date('Y-m'));

        $this->view('reports/index', [
            'from' => $from,
            'to' => $to,
            'month' => $month,
            'sales' => $model->sales($from, $to),
            'purchases' => $model->purchases($from, $to),
            'gst' => $model->gstMonthly($month),
            'profit' => $model->profit($from, $to),
        ]);
    }

    public function export(): void
    {
        $this->requireAuth();
        $type = (string)$this->request('type', '');
        $from = (string)$this->request('from', date('Y-m-01'));
        $to = (string)$this->request('to', date('Y-m-t'));
        $month = (string)$this->request('month', date('Y-m'));
        $model = new Report();

        $filename = 'report_' . $type . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        switch ($type) {
            case 'sales':
                fputcsv($out, ['Date', 'Invoice No', 'Customer', 'Subtotal', 'CGST', 'SGST', 'IGST', 'Total']);
                foreach ($model->sales($from, $to) as $row) {
                    fputcsv($out, [
                        $row['date'],
                        $row['invoice_no'],
                        $row['customer_name'],
                        $row['subtotal'],
                        $row['cgst'],
                        $row['sgst'],
                        $row['igst'],
                        $row['total_amount'],
                    ]);
                }
                break;
            case 'purchases':
                fputcsv($out, ['Date', 'Purchase No', 'Supplier', 'Subtotal', 'CGST', 'SGST', 'IGST', 'Total']);
                foreach ($model->purchases($from, $to) as $row) {
                    fputcsv($out, [
                        $row['date'],
                        $row['purchase_invoice_no'],
                        $row['supplier_name'],
                        $row['subtotal'],
                        $row['cgst'],
                        $row['sgst'],
                        $row['igst'],
                        $row['total_amount'],
                    ]);
                }
                break;
            case 'gst':
                fputcsv($out, ['Type', 'CGST', 'SGST', 'IGST', 'Month']);
                foreach ($model->gstMonthly($month) as $row) {
                    fputcsv($out, [$row['type'], $row['cgst'], $row['sgst'], $row['igst'], $month]);
                }
                break;
            case 'profit':
                $p = $model->profit($from, $to);
                fputcsv($out, ['From', 'To', 'Sales', 'Purchases', 'Profit']);
                fputcsv($out, [$from, $to, $p['sales'], $p['purchases'], $p['profit']]);
                break;
            default:
                fputcsv($out, ['Invalid report type']);
                break;
        }

        fclose($out);
        exit;
    }
}
