<?php
$purchase = $purchase ?? null;
$existingItems = $existingItems ?? [];
?>
<h2><?= e($title ?? 'Create Purchase') ?></h2>
<form method="post" action="index.php?route=<?= e($formAction ?? 'purchase/create') ?>" class="card">
    <?= csrf_field() ?>
    <div class="grid three">
        <label>Purchase Invoice No<input type="text" name="purchase_invoice_no" value="<?= e($invoiceNo) ?>" readonly></label>
        <label>Date<input type="date" name="date" value="<?= e((string)($purchase['date'] ?? date('Y-m-d'))) ?>" required></label>
        <label>Status
            <select name="status">
                <?php $statusVal = strtoupper((string)($purchase['status'] ?? 'FINAL')); ?>
                <option value="DRAFT" <?= $statusVal === 'DRAFT' ? 'selected' : '' ?>>Draft</option>
                <option value="FINAL" <?= $statusVal === 'FINAL' ? 'selected' : '' ?>>Final</option>
            </select>
        </label>
        <label>Supplier
            <select name="supplier_id" id="partySelect" data-party-kind="supplier" required>
                <option value="">Select</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" data-state="<?= e($s['state']) ?>" <?= (int)($purchase['supplier_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?> (<?= e($s['state']) ?>)
                    </option>
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
    <div class="grid two">
        <label>Transport Cost<input type="number" step="0.01" name="transport_cost" value="<?= e((string)($purchase['transport_cost'] ?? '0')) ?>"></label>
        <label>Other Charges<input type="number" step="0.01" name="other_charges" value="<?= e((string)($purchase['other_charges'] ?? '0')) ?>"></label>
    </div>
    <button type="submit"><?= e($submitLabel ?? 'Save Purchase') ?></button>
</form>

<script>
window.PRODUCTS = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
window.EXISTING_ITEMS = <?= json_encode($existingItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
window.EXISTING_PARTY_ID = <?= json_encode((int)($purchase['supplier_id'] ?? 0)) ?>;
window.EXISTING_PARTY_STATE = <?= json_encode((string)($purchase['party_state'] ?? '')) ?>;
</script>
