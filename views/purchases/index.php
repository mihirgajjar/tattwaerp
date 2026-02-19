<div class="toolbar">
    <h2>Purchases</h2>
    <a class="btn" href="index.php?route=purchase/create">+ New Purchase</a>
</div>
<table>
    <thead><tr><th>Invoice No</th><th>Supplier</th><th>Date</th><th>Subtotal</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach ($purchases as $p): ?>
        <tr>
            <td><?= e($p['purchase_invoice_no']) ?></td>
            <td><?= e($p['supplier_name']) ?></td>
            <td><?= e($p['date']) ?></td>
            <td><?= number_format((float)$p['subtotal'], 2) ?></td>
            <td><?= number_format((float)$p['cgst'], 2) ?></td>
            <td><?= number_format((float)$p['sgst'], 2) ?></td>
            <td><?= number_format((float)$p['igst'], 2) ?></td>
            <td><?= number_format((float)$p['total_amount'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
