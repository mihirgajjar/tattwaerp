<h2>Reports</h2>
<section class="card">
    <form method="get" class="grid four">
        <input type="hidden" name="route" value="report/index">
        <label>From<input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To<input type="date" name="to" value="<?= e($to) ?>"></label>
        <label>GST Month<input type="month" name="month" value="<?= e($month) ?>"></label>
        <button type="submit">Apply Filters</button>
    </form>
</section>

<section class="card">
    <h3>Export CSV</h3>
    <div class="toolbar">
        <a class="btn" href="index.php?route=report/export&type=sales&from=<?= e($from) ?>&to=<?= e($to) ?>&month=<?= e($month) ?>">Sales CSV</a>
        <a class="btn" href="index.php?route=report/export&type=purchases&from=<?= e($from) ?>&to=<?= e($to) ?>&month=<?= e($month) ?>">Purchase CSV</a>
        <a class="btn" href="index.php?route=report/export&type=gst&from=<?= e($from) ?>&to=<?= e($to) ?>&month=<?= e($month) ?>">GST CSV</a>
        <a class="btn" href="index.php?route=report/export&type=profit&from=<?= e($from) ?>&to=<?= e($to) ?>&month=<?= e($month) ?>">Profit CSV</a>
    </div>
</section>

<section class="grid three">
    <div class="card metric"><h3>Sales</h3><p>Rs <?= number_format((float)$profit['sales'], 2) ?></p></div>
    <div class="card metric"><h3>Purchases</h3><p>Rs <?= number_format((float)$profit['purchases'], 2) ?></p></div>
    <div class="card metric"><h3>Profit</h3><p>Rs <?= number_format((float)$profit['profit'], 2) ?></p></div>
</section>

<section class="card">
    <h3>Sales Report</h3>
    <table>
        <thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($sales as $row): ?>
            <tr><td><?= e($row['date']) ?></td><td><?= e($row['invoice_no']) ?></td><td><?= e($row['customer_name']) ?></td><td><?= number_format((float)$row['total_amount'], 2) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h3>Purchase Report</h3>
    <table>
        <thead><tr><th>Date</th><th>Invoice</th><th>Supplier</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($purchases as $row): ?>
            <tr><td><?= e($row['date']) ?></td><td><?= e($row['purchase_invoice_no']) ?></td><td><?= e($row['supplier_name']) ?></td><td><?= number_format((float)$row['total_amount'], 2) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h3>GST Summary (<?= e($month) ?>)</h3>
    <table>
        <thead><tr><th>Type</th><th>CGST</th><th>SGST</th><th>IGST</th></tr></thead>
        <tbody>
        <?php foreach ($gst as $row): ?>
            <tr><td><?= e($row['type']) ?></td><td><?= number_format((float)$row['cgst'], 2) ?></td><td><?= number_format((float)$row['sgst'], 2) ?></td><td><?= number_format((float)$row['igst'], 2) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
