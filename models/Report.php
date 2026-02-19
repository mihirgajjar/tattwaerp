<?php

class Report
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function sales(string $from, string $to): array
    {
        $stmt = $this->db->prepare('SELECT s.*, c.name AS customer_name FROM sales s JOIN customers c ON c.id = s.customer_id WHERE s.date BETWEEN :from AND :to ORDER BY s.date DESC');
        $stmt->execute(['from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }

    public function purchases(string $from, string $to): array
    {
        $stmt = $this->db->prepare('SELECT p.*, s.name AS supplier_name FROM purchases p JOIN suppliers s ON s.id = p.supplier_id WHERE p.date BETWEEN :from AND :to AND p.status = \'FINAL\' ORDER BY p.date DESC');
        $stmt->execute(['from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }

    public function gstMonthly(string $yearMonth): array
    {
        $stmt = $this->db->prepare("SELECT
                'Sales' AS type,
                COALESCE(SUM(cgst),0) AS cgst,
                COALESCE(SUM(sgst),0) AS sgst,
                COALESCE(SUM(igst),0) AS igst
            FROM sales
            WHERE DATE_FORMAT(date, '%Y-%m') = :ym
            UNION ALL
            SELECT
                'Purchases' AS type,
                COALESCE(SUM(cgst),0) AS cgst,
                COALESCE(SUM(sgst),0) AS sgst,
                COALESCE(SUM(igst),0) AS igst
            FROM purchases
            WHERE DATE_FORMAT(date, '%Y-%m') = :ym AND status = 'FINAL'");
        $stmt->execute(['ym' => $yearMonth]);
        return $stmt->fetchAll();
    }

    public function profit(string $from, string $to): array
    {
        $salesStmt = $this->db->prepare('SELECT COALESCE(SUM(total_amount),0) AS total FROM sales WHERE date BETWEEN :from AND :to');
        $salesStmt->execute(['from' => $from, 'to' => $to]);
        $sales = (float)$salesStmt->fetch()['total'];

        $purchaseStmt = $this->db->prepare('SELECT COALESCE(SUM(total_amount),0) AS total FROM purchases WHERE date BETWEEN :from AND :to AND status = \'FINAL\'');
        $purchaseStmt->execute(['from' => $from, 'to' => $to]);
        $purchases = (float)$purchaseStmt->fetch()['total'];

        return [
            'sales' => $sales,
            'purchases' => $purchases,
            'profit' => $sales - $purchases,
        ];
    }
}
