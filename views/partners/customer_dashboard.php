<h2>Customer Dashboard - <?= e($customer['name']) ?></h2>
<section class="grid four">
    <div class="card metric"><h3>Total Sales</h3><p>Rs <?= number_format((float)$summary['total_sales'], 2) ?></p></div>
    <div class="card metric"><h3>Outstanding</h3><p>Rs <?= number_format((float)$outstanding, 2) ?></p></div>
    <div class="card metric"><h3>Last Purchase</h3><p><?= e($summary['last_purchase_date'] ?: '-') ?></p></div>
    <div class="card metric"><h3>Credit Limit</h3><p>Rs <?= number_format((float)($customer['credit_limit'] ?? 0), 2) ?></p></div>
</section>
<section class="card">
    <h3>Customer Transactions / Ledger</h3>
    <table>
        <thead><tr><th>Date</th><th>Invoice</th><th>Status</th><th>Total</th><th>Payment</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr><td><?= e($t['date']) ?></td><td><?= e($t['invoice_no']) ?></td><td><?= e($t['status'] ?? 'FINAL') ?></td><td><?= number_format((float)$t['total_amount'], 2) ?></td><td><?= e($t['payment_status'] ?? 'UNPAID') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
