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
        $sales = $this->db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM sales WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetch()['total'];
        $purchases = $this->db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM purchases WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetch()['total'];
        $profit = $sales - $purchases;

        $lowStockStmt = $this->db->query('SELECT product_name, stock_quantity, reorder_level FROM products WHERE stock_quantity < reorder_level ORDER BY stock_quantity ASC LIMIT 10');

        return [
            'sales' => (float)$sales,
            'purchases' => (float)$purchases,
            'profit' => (float)$profit,
            'low_stock' => $lowStockStmt->fetchAll(),
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
