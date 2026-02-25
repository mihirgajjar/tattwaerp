<h2>Finance & Payment Module</h2>
<section class="card">
    <form method="get" class="grid three">
        <input type="hidden" name="route" value="finance/index">
        <label>From<input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To<input type="date" name="to" value="<?= e($to) ?>"></label>
        <button type="submit">Filter</button>
    </form>
</section>

<div class="grid two">
    <section class="card">
        <h3>Record Payment Received</h3>
        <form method="post" action="index.php?route=finance/receive" class="form-grid">
        <?= csrf_field() ?>
            <label>Invoice
                <select name="sale_id" required>
                    <?php foreach ($sales as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['invoice_no']) ?> (<?= e($s['customer_name']) ?>)</option><?php endforeach; ?>
                </select>
            </label>
            <label>Amount<input type="number" step="0.01" name="amount" required></label>
            <label>Mode<input type="text" name="payment_mode" value="UPI"></label>
            <button type="submit">Record</button>
        </form>
    </section>

    <section class="card">
        <h3>Record Payment Made</h3>
        <form method="post" action="index.php?route=finance/pay" class="form-grid">
        <?= csrf_field() ?>
            <label>Supplier
                <select name="supplier_id" required>
                    <?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Purchase
                <select name="purchase_id">
                    <?php foreach ($purchases as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['purchase_invoice_no']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Amount<input type="number" step="0.01" name="amount" required></label>
            <label>Mode<input type="text" name="payment_mode" value="Bank Transfer"></label>
            <button type="submit">Record</button>
        </form>
    </section>
</div>

<section class="card">
    <h3>Bank Details + UPI/QR</h3>
    <form method="post" action="index.php?route=finance/addBank" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <label>Bank Name<input type="text" name="bank_name" required></label>
        <label>Account Name<input type="text" name="account_name" required></label>
        <label>Account No<input type="text" name="account_no" required></label>
        <label>IFSC<input type="text" name="ifsc" required></label>
        <label>UPI ID<input type="text" name="upi_id"></label>
        <label>Static QR Image<input type="file" name="qr_image" accept="image/*"></label>
        <label><input type="checkbox" name="is_default" value="1"> Default Bank</label>
        <button type="submit">Save Bank</button>
    </form>

    <table>
        <thead><tr><th>Bank</th><th>Account</th><th>IFSC</th><th>UPI</th><th>Default</th></tr></thead>
        <tbody>
        <?php foreach ($banks as $b): ?>
            <tr>
                <td><?= e($b['bank_name']) ?></td>
                <td><?= e($b['account_no']) ?></td>
                <td><?= e($b['ifsc']) ?></td>
                <td><?= e($b['upi_id']) ?></td>
                <td><?= (int)$b['is_default'] === 1 ? 'Yes' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<div class="grid two">
    <section class="card">
        <h3>Payments Received</h3>
        <table>
            <thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Mode</th><th>Amount</th></tr></thead>
            <tbody>
            <?php foreach ($received as $r): ?>
                <tr><td><?= e($r['payment_date']) ?></td><td><?= e($r['invoice_no']) ?></td><td><?= e($r['customer_name']) ?></td><td><?= e($r['payment_mode']) ?></td><td><?= number_format((float)$r['amount'], 2) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h3>Payments Made</h3>
        <table>
            <thead><tr><th>Date</th><th>Purchase</th><th>Supplier</th><th>Mode</th><th>Amount</th></tr></thead>
            <tbody>
            <?php foreach ($paid as $r): ?>
                <tr><td><?= e($r['payment_date']) ?></td><td><?= e($r['purchase_invoice_no']) ?></td><td><?= e($r['supplier_name']) ?></td><td><?= e($r['payment_mode']) ?></td><td><?= number_format((float)$r['amount'], 2) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
