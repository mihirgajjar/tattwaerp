<?php
$categories = ['Single Oil', 'Blend', 'Diffuser Oil'];
$product = $product ?? [];
?>
<h2><?= $product ? 'Edit Product' : 'Add Product' ?></h2>
<form method="post" class="card form-grid" action="index.php?route=product/<?= e($action) ?>">
    <label>Product Name<input type="text" name="product_name" value="<?= e(old('product_name', $product['product_name'] ?? '')) ?>" required></label>
    <label>SKU<input type="text" name="sku" value="<?= e(old('sku', $product['sku'] ?? '')) ?>" required></label>
    <label>Category
        <select name="category" required>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c) ?>" <?= old('category', $product['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Variant<input type="text" name="variant" value="<?= e(old('variant', $product['variant'] ?? '')) ?>" required></label>
    <label>Size<input type="text" name="size" value="<?= e(old('size', $product['size'] ?? '')) ?>" required></label>
    <label>HSN Code<input type="text" name="hsn_code" value="<?= e(old('hsn_code', $product['hsn_code'] ?? '')) ?>" required></label>
    <label>GST %
        <select name="gst_percent" required>
            <?php foreach ([5,12,18] as $gst): ?>
                <option value="<?= $gst ?>" <?= (float)old('gst_percent', (string)($product['gst_percent'] ?? 5)) === (float)$gst ? 'selected' : '' ?>><?= $gst ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Purchase Price<input type="number" step="0.01" name="purchase_price" value="<?= e(old('purchase_price', (string)($product['purchase_price'] ?? '0'))) ?>" required></label>
    <label>Selling Price<input type="number" step="0.01" name="selling_price" value="<?= e(old('selling_price', (string)($product['selling_price'] ?? '0'))) ?>" required></label>
    <label>Stock Quantity<input type="number" name="stock_quantity" value="<?= e(old('stock_quantity', (string)($product['stock_quantity'] ?? '0'))) ?>" required></label>
    <label>Reorder Level<input type="number" name="reorder_level" value="<?= e(old('reorder_level', (string)($product['reorder_level'] ?? '0'))) ?>" required></label>
    <button type="submit">Save Product</button>
</form>
