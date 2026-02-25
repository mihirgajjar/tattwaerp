<div class="toolbar">
    <h2>Sales</h2>
    <a class="btn" href="index.php?route=sale/create">+ New Sale</a>
</div>
<table>
    <thead><tr><th>Invoice No</th><th>Customer</th><th>Date</th><th>Status</th><th>Payment</th><th>Total</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($sales as $s): ?>
        <tr>
            <td><?= e($s['invoice_no']) ?></td>
            <td><?= e($s['customer_name']) ?></td>
            <td><?= e($s['date']) ?></td>
            <td><?= e($s['status'] ?? 'FINAL') ?></td>
            <td><?= e($s['payment_status'] ?? 'UNPAID') ?></td>
            <td><?= number_format((float)$s['total_amount'], 2) ?></td>
            <td>
                <a href="index.php?route=sale/invoice&id=<?= (int)$s['id'] ?>" target="_blank">Print</a>
                <?php if (($s['status'] ?? 'FINAL') !== 'FINAL'): ?> |
                    <form method="post" action="index.php?route=sale/status" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <input type="hidden" name="status" value="FINAL">
                        <button type="submit">Finalize</button>
                    </form>
                <?php endif; ?> |
                <form method="post" action="index.php?route=sale/status" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <input type="hidden" name="status" value="CANCELLED">
                    <button type="submit">Cancel</button>
                </form> |
                <form method="post" action="index.php?route=sale/delete" style="display:inline;" onsubmit="return confirm('Delete this draft/cancelled/void invoice (only if no payment)?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="danger-btn">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
