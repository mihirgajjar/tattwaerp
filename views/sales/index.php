<div class="toolbar">
    <h2>Sales</h2>
    <a class="btn" href="index.php?route=sale/create">+ New Sale</a>
</div>
<table>
    <thead><tr><th>Invoice No</th><th>Customer</th><th>Date</th><th>Subtotal</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($sales as $s): ?>
        <tr>
            <td><?= e($s['invoice_no']) ?></td>
            <td><?= e($s['customer_name']) ?></td>
            <td><?= e($s['date']) ?></td>
            <td><?= number_format((float)$s['subtotal'], 2) ?></td>
            <td><?= number_format((float)$s['cgst'], 2) ?></td>
            <td><?= number_format((float)$s['sgst'], 2) ?></td>
            <td><?= number_format((float)$s['igst'], 2) ?></td>
            <td><?= number_format((float)$s['total_amount'], 2) ?></td>
            <td><a href="index.php?route=sale/invoice&id=<?= (int)$s['id'] ?>" target="_blank">Print</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
