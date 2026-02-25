<?php
$categories = $categoryOptions ?? ['Single Oil', 'Blend', 'Diffuser Oil'];
$product = $product ?? [];
?>
<h2><?= $product ? 'Edit Product' : 'Add Product' ?></h2>
<form method="post" enctype="multipart/form-data" class="card form-grid" action="index.php?route=product/<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>Product Name<input type="text" name="product_name" value="<?= e(old('product_name', $product['product_name'] ?? '')) ?>" required></label>
    <label>SKU (optional, auto-generated if blank)<input type="text" name="sku" placeholder="Auto generated if left blank" value="<?= e(old('sku', $product['sku'] ?? '')) ?>"></label>
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
    <label>Reserved Stock<input type="number" name="reserved_stock" value="<?= e(old('reserved_stock', (string)($product['reserved_stock'] ?? '0'))) ?>"></label>
    <label>Minimum Stock Level<input type="number" name="min_stock_level" value="<?= e(old('min_stock_level', (string)($product['min_stock_level'] ?? '0'))) ?>"></label>
    <label>Barcode<input type="text" name="barcode" value="<?= e(old('barcode', (string)($product['barcode'] ?? ''))) ?>"></label>
    <label>Status
        <select name="is_active">
            <option value="1" <?= (int)old('is_active', (string)($product['is_active'] ?? 1)) === 1 ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= (int)old('is_active', (string)($product['is_active'] ?? 1)) === 0 ? 'selected' : '' ?>>Inactive</option>
        </select>
    </label>
    <input type="hidden" name="existing_image_path" value="<?= e($product['image_path'] ?? '') ?>">
    <label>Product Image<input type="file" name="image" accept="image/*"></label>
    <?php if (!empty($product['image_path'])): ?>
        <div><img src="<?= e($product['image_path']) ?>" alt="Product" class="invoice-logo-preview"></div>
    <?php endif; ?>
    <button type="submit">Save Product</button>
</form>
