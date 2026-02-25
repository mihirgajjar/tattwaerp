<div class="toolbar">
    <h2>Products</h2>
    <a class="btn" href="index.php?route=product/create">+ Add Product</a>
</div>
<table>
    <thead>
    <tr>
        <th>SKU</th><th>Name</th><th>Category</th><th>GST%</th><th>Selling</th><th>Stock</th><th>Reserved</th><th>Min</th><th>Status</th><th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $row): ?>
        <tr>
            <td><?= e($row['sku']) ?></td>
            <td><?= e($row['product_name']) ?></td>
            <td><?= e($row['category']) ?></td>
            <td><?= e((string)$row['gst_percent']) ?></td>
            <td><?= number_format((float)$row['selling_price'], 2) ?></td>
            <td><?= (int)$row['stock_quantity'] ?></td>
            <td><?= (int)($row['reserved_stock'] ?? 0) ?></td>
            <td><?= (int)($row['min_stock_level'] ?? 0) ?></td>
            <td><?= (int)($row['is_active'] ?? 1) === 1 ? 'Active' : 'Inactive' ?></td>
            <td>
                <a href="index.php?route=product/edit&id=<?= (int)$row['id'] ?>">Edit</a> |
                <form method="post" action="index.php?route=product/delete" style="display:inline;" onsubmit="return confirm('Delete this product?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button type="submit" class="danger-btn">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
