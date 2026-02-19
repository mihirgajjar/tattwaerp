<h2>Create Purchase</h2>
<form method="post" action="index.php?route=purchase/create" class="card">
    <div class="grid three">
        <label>Purchase Invoice No<input type="text" name="purchase_invoice_no" value="<?= e($invoiceNo) ?>" readonly></label>
        <label>Date<input type="date" name="date" value="<?= date('Y-m-d') ?>" required></label>
        <label>Supplier
            <select name="supplier_id" id="partySelect" data-party-kind="supplier" required>
                <option value="">Select</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" data-state="<?= e($s['state']) ?>"><?= e($s['name']) ?> (<?= e($s['state']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <input type="hidden" name="party_state" id="partyState">
    <input type="hidden" id="businessState" value="<?= e($businessState) ?>">

    <table id="lineItems" class="line-items">
        <thead><tr><th>Product</th><th>Qty</th><th>Rate</th><th>GST%</th><th>Taxable</th><th>Tax</th><th>Total</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
    <button type="button" class="btn secondary" id="addRowBtn">+ Add Item</button>

    <div class="totals">
        <p>Subtotal: <strong id="subtotal">0.00</strong></p>
        <p>CGST: <strong id="cgst">0.00</strong></p>
        <p>SGST: <strong id="sgst">0.00</strong></p>
        <p>IGST: <strong id="igst">0.00</strong></p>
        <p>Grand Total: <strong id="grandTotal">0.00</strong></p>
    </div>
    <button type="submit">Save Purchase</button>
</form>

<script>
window.PRODUCTS = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
</script>
