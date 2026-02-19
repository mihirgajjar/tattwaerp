<section class="grid three">
    <div class="card metric"><h3>Stock Valuation</h3><p>Rs <?= number_format($valuation, 2) ?></p></div>
    <div class="card metric"><h3>Total Products</h3><p><?= count($products) ?></p></div>
    <div class="card metric"><h3>Low Stock Alerts</h3><p><?= count($lowStock) ?></p></div>
</section>

<section class="card">
    <h3>Live Stock Tracking</h3>
    <table>
        <thead><tr><th>SKU</th><th>Product</th><th>Stock</th><th>Reorder Level</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= e($p['sku']) ?></td>
                <td><?= e($p['product_name']) ?></td>
                <td><?= (int)$p['stock_quantity'] ?></td>
                <td><?= (int)$p['reorder_level'] ?></td>
                <td><?= (int)$p['stock_quantity'] < (int)$p['reorder_level'] ? '<span class="badge danger">Low</span>' : '<span class="badge">OK</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h3>Product-wise Sales Summary</h3>
    <table>
        <thead><tr><th>SKU</th><th>Product</th><th>Qty Sold</th><th>Total Sales</th></tr></thead>
        <tbody>
        <?php foreach ($salesSummary as $s): ?>
            <tr>
                <td><?= e($s['sku']) ?></td>
                <td><?= e($s['product_name']) ?></td>
                <td><?= (int)$s['total_qty'] ?></td>
                <td><?= number_format((float)$s['total_sales'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
