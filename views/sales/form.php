<h2>Create Sales Invoice</h2>
<form method="post" action="index.php?route=sale/create" class="card">
    <div class="grid three">
        <label>Invoice No<input type="text" name="invoice_no" value="<?= e($invoiceNo) ?>" readonly></label>
        <label>Date<input type="date" name="date" value="<?= date('Y-m-d') ?>" required></label>
        <label>Due Date<input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required></label>
        <label>Status
            <select name="status">
                <option value="DRAFT">Draft</option>
                <option value="FINAL" selected>Final</option>
            </select>
        </label>
        <label>Customer
            <select name="customer_id" id="partySelect" data-party-kind="customer" required>
                <option value="">Select</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" data-state="<?= e($c['state']) ?>"><?= e($c['name']) ?> (<?= e($c['state']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <input type="hidden" name="customer_state" id="partyState">
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
    <div class="grid three">
        <label>Item Discount<input type="number" step="0.01" name="item_discount" value="0"></label>
        <label>Overall Discount<input type="number" step="0.01" name="overall_discount" value="0"></label>
        <label>Round Off<input type="number" step="0.01" name="round_off" value="0"></label>
    </div>
    <label>Notes<textarea name="notes"></textarea></label>
    <label>Terms<textarea name="terms">Goods once sold will not be taken back unless quality issue.</textarea></label>
    <button type="submit">Save Sale</button>
</form>

<script>
window.PRODUCTS = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
</script>
