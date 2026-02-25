<h2>Auto Generate Purchase from File</h2>
<section class="card">
    <p class="muted">
        Supported: CSV, XLSX, PDF, PNG, JPG, JPEG, WEBP.<br>
        Flow: Upload -> Preview/Edit -> Finalize Purchase.<br>
        Best accuracy: CSV/XLSX with columns: <code>product_name</code> or <code>sku</code>, <code>quantity</code>, <code>rate</code>, <code>gst_percent</code>.
    </p>

    <form method="post" action="index.php?route=purchase/import" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <label>Purchase Invoice No
            <input type="text" name="purchase_invoice_no" value="<?= e($invoiceNo) ?>" placeholder="Auto if blank">
        </label>
        <label>Date
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
        </label>
        <label>Status
            <select name="status">
                <option value="DRAFT" selected>Draft</option>
                <option value="FINAL">Final</option>
            </select>
        </label>
        <label>Supplier
            <select name="supplier_id" required>
                <option value="">Select</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['state']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Transport Cost
            <input type="number" step="0.01" name="transport_cost" value="0">
        </label>
        <label>Other Charges
            <input type="number" step="0.01" name="other_charges" value="0">
        </label>
        <label>Upload File
            <input type="file" name="source_file" accept=".csv,.txt,.xlsx,.pdf,.png,.jpg,.jpeg,.webp" required>
        </label>
        <div>
            <button type="submit">Upload and Preview</button>
            <a class="btn secondary" href="index.php?route=purchase/index">Back</a>
        </div>
    </form>
</section>
