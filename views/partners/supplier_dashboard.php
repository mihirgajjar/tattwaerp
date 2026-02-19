<h2>Supplier Dashboard - <?= e($supplier['name']) ?></h2>
<section class="grid three">
    <div class="card metric"><h3>Total Purchase Value</h3><p>Rs <?= number_format((float)$summary['total_purchase'], 2) ?></p></div>
    <div class="card metric"><h3>Outstanding Payable</h3><p>Rs <?= number_format((float)$outstanding, 2) ?></p></div>
    <div class="card metric"><h3>Last Purchase</h3><p><?= e($summary['last_purchase_date'] ?: '-') ?></p></div>
</section>
<section class="card">
    <h3>Supplier Ledger</h3>
    <table>
        <thead><tr><th>Date</th><th>Invoice</th><th>Status</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr><td><?= e($t['date']) ?></td><td><?= e($t['purchase_invoice_no']) ?></td><td><?= e($t['status'] ?? 'FINAL') ?></td><td><?= number_format((float)$t['total_amount'], 2) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
