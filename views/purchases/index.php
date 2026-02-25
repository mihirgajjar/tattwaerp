<div class="toolbar">
    <h2>Purchases</h2>
    <a class="btn" href="index.php?route=purchase/create">+ New Purchase</a>
    <a class="btn secondary" href="index.php?route=purchase/import">Import from File</a>
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
                <?php if ($status === 'DRAFT'): ?>
                    <a href="index.php?route=purchase/edit&id=<?= (int)$p['id'] ?>">Edit</a> |
                <?php endif; ?>
                <?php if ($status !== 'FINAL'): ?>
                    <form method="post" action="index.php?route=purchase/status" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="status" value="FINAL">
                        <button type="submit">Finalize</button>
                    </form> |
                <?php endif; ?>
                <?php if ($status === 'FINAL' || $status === 'CANCELLED'): ?>
                    <form method="post" action="index.php?route=purchase/status" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="status" value="DRAFT">
                        <button type="submit">Draft</button>
                    </form> |
                <?php endif; ?>
                <?php if ($status !== 'CANCELLED'): ?>
                    <form method="post" action="index.php?route=purchase/status" style="display:inline;" onsubmit="return confirm('Cancel this purchase? Stock will be adjusted.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="status" value="CANCELLED">
                        <button type="submit">Cancel</button>
                    </form> |
                <?php endif; ?>
                <form method="post" action="index.php?route=purchase/delete" style="display:inline;" onsubmit="return confirm('Delete this purchase? Allowed only for Draft/Cancelled with no payment.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="danger-btn">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
