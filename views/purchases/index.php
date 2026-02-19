<div class="toolbar">
    <h2>Purchases</h2>
    <a class="btn" href="index.php?route=purchase/create">+ New Purchase</a>
</div>
<table>
    <thead><tr><th>Invoice No</th><th>Supplier</th><th>Date</th><th>Status</th><th>Transport</th><th>Other</th><th>Total</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($purchases as $p): ?>
        <tr>
            <td><?= e($p['purchase_invoice_no']) ?></td>
            <td><?= e($p['supplier_name']) ?></td>
            <td><?= e($p['date']) ?></td>
            <td><?= e($p['status'] ?? 'FINAL') ?></td>
            <td><?= number_format((float)($p['transport_cost'] ?? 0), 2) ?></td>
            <td><?= number_format((float)($p['other_charges'] ?? 0), 2) ?></td>
            <td><?= number_format((float)$p['total_amount'], 2) ?></td>
            <td>
                <?php $status = strtoupper((string)($p['status'] ?? 'FINAL')); ?>
                <?php if ($status !== 'FINAL'): ?>
                    <a href="index.php?route=purchase/status&id=<?= (int)$p['id'] ?>&status=FINAL">Finalize</a> |
                <?php endif; ?>
                <?php if ($status === 'FINAL' || $status === 'CANCELLED'): ?>
                    <a href="index.php?route=purchase/status&id=<?= (int)$p['id'] ?>&status=DRAFT">Draft</a> |
                <?php endif; ?>
                <?php if ($status !== 'CANCELLED'): ?>
                    <a href="index.php?route=purchase/status&id=<?= (int)$p['id'] ?>&status=CANCELLED" onclick="return confirm('Cancel this purchase? Stock will be adjusted.')">Cancel</a> |
                <?php endif; ?>
                <a href="index.php?route=purchase/delete&id=<?= (int)$p['id'] ?>" onclick="return confirm('Delete this purchase? Allowed only for Draft/Cancelled with no payment.')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
