<section class="grid four">
    <div class="card metric"><h3>Total Sales Today</h3><p>Rs <?= number_format($metrics['today_sales'], 2) ?></p></div>
    <div class="card metric"><h3>Total Sales (This Month)</h3><p>Rs <?= number_format($metrics['sales'], 2) ?></p></div>
    <div class="card metric"><h3>Total Purchases (This Month)</h3><p>Rs <?= number_format($metrics['purchases'], 2) ?></p></div>
    <div class="card metric"><h3>Profit</h3><p>Rs <?= number_format($metrics['profit'], 2) ?></p></div>
    <div class="card metric"><h3>Outstanding Receivables</h3><p>Rs <?= number_format($metrics['outstanding_receivables'], 2) ?></p></div>
    <div class="card metric"><h3>Payables</h3><p>Rs <?= number_format($metrics['payables'], 2) ?></p></div>
    <div class="card metric"><h3>Low Stock Items</h3><p><?= count($metrics['low_stock']) ?></p></div>
</section>

<section class="card">
    <h3>Monthly Sales Chart</h3>
    <canvas id="salesChart" height="120" data-labels='<?= e(json_encode($chart['labels'])) ?>' data-values='<?= e(json_encode($chart['values'])) ?>'></canvas>
</section>

<section class="card">
    <h3>Top Selling Products</h3>
    <table>
        <thead><tr><th>Product</th><th>Quantity Sold</th></tr></thead>
        <tbody>
        <?php foreach (($metrics['top_products'] ?? []) as $tp): ?>
            <tr><td><?= e($tp['product_name']) ?></td><td><?= (int)$tp['qty'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h3>Low Stock Items</h3>
    <table>
        <thead><tr><th>Product</th><th>Stock</th><th>Reorder Level</th></tr></thead>
        <tbody>
        <?php foreach ($metrics['low_stock'] as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
                <td><?= (int)$item['stock_quantity'] ?></td>
                <td><?= (int)$item['reorder_level'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
