<?php

class Dashboard
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function metrics(): array
    {
        $todaySales = $this->db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM sales WHERE date = CURDATE() AND status <> 'CANCELLED'")->fetch()['total'];
        $sales = $this->db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM sales WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') AND status = 'FINAL'")->fetch()['total'];
        $purchases = $this->db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM purchases WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') AND status = 'FINAL'")->fetch()['total'];
        $profit = $sales - $purchases;

        $lowStockStmt = $this->db->query('SELECT product_name, stock_quantity, reorder_level FROM products WHERE stock_quantity < reorder_level ORDER BY stock_quantity ASC LIMIT 10');

        $outstandingRec = $this->db->query("SELECT COALESCE(SUM(s.total_amount - IFNULL(p.paid,0)),0) AS v
            FROM sales s
            LEFT JOIN (SELECT sale_id, SUM(amount) AS paid FROM customer_payments GROUP BY sale_id) p ON p.sale_id = s.id")->fetch()['v'];

        $payables = $this->db->query("SELECT COALESCE(SUM(p.total_amount - IFNULL(pp.paid,0)),0) AS v
            FROM purchases p
            LEFT JOIN (SELECT purchase_id, SUM(amount) AS paid FROM customer_payables GROUP BY purchase_id) pp ON pp.purchase_id = p.id
            WHERE p.status = 'FINAL'")->fetch()['v'];

        $topProducts = $this->db->query("SELECT pr.product_name, COALESCE(SUM(si.quantity),0) qty
            FROM products pr LEFT JOIN sale_items si ON si.product_id = pr.id
            GROUP BY pr.id ORDER BY qty DESC LIMIT 5")->fetchAll();

        return [
            'today_sales' => (float)$todaySales,
            'sales' => (float)$sales,
            'purchases' => (float)$purchases,
            'profit' => (float)$profit,
            'outstanding_receivables' => (float)$outstandingRec,
            'payables' => (float)$payables,
            'low_stock' => $lowStockStmt->fetchAll(),
            'top_products' => $topProducts,
        ];
    }

    public function monthlySalesChart(): array
    {
        $stmt = $this->db->query("SELECT DATE_FORMAT(date, '%b') AS month_name, MONTH(date) AS month_no, COALESCE(SUM(total_amount),0) AS total FROM sales WHERE YEAR(date) = YEAR(CURDATE()) GROUP BY MONTH(date), DATE_FORMAT(date, '%b') ORDER BY month_no");
        $rows = $stmt->fetchAll();

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = $row['month_name'];
            $values[] = (float)$row['total'];
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
