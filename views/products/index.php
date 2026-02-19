<div class="toolbar">
    <h2>Products</h2>
    <a class="btn" href="index.php?route=product/create">+ Add Product</a>
</div>
<table>
    <thead>
    <tr>
        <th>SKU</th><th>Name</th><th>Category</th><th>Variant</th><th>Size</th><th>GST%</th><th>Purchase</th><th>Selling</th><th>Stock</th><th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $row): ?>
        <tr>
            <td><?= e($row['sku']) ?></td>
            <td><?= e($row['product_name']) ?></td>
            <td><?= e($row['category']) ?></td>
            <td><?= e($row['variant']) ?></td>
            <td><?= e($row['size']) ?></td>
            <td><?= e((string)$row['gst_percent']) ?></td>
            <td><?= number_format((float)$row['purchase_price'], 2) ?></td>
            <td><?= number_format((float)$row['selling_price'], 2) ?></td>
            <td><?= (int)$row['stock_quantity'] ?></td>
            <td>
                <a href="index.php?route=product/edit&id=<?= (int)$row['id'] ?>">Edit</a> |
                <a href="index.php?route=product/delete&id=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this product?')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
