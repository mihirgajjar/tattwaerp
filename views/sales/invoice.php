<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= e($sale['invoice_no']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php
$invoiceTheme = app_setting('invoice_theme', 'classic');
$invoiceAccent = app_setting('invoice_accent_color', '#1d6f5f');
?>
<body class="invoice-body invoice-theme-<?= e($invoiceTheme) ?>" style="--invoice-accent: <?= e($invoiceAccent) ?>;">
<div class="invoice-actions no-print">
    <a class="btn" href="index.php?route=sale/index">Back</a>
    <button class="btn" onclick="window.print()">Export / Print PDF</button>
</div>
<div class="invoice-box">
    <?php $invoiceLogo = app_setting('invoice_logo', ''); ?>
    <?php if ($invoiceLogo !== ''): ?>
        <img src="<?= e($invoiceLogo) ?>" alt="Business Logo" class="invoice-logo">
    <?php endif; ?>
    <h1><?= e($app['business_name']) ?></h1>
    <p><?= e($app['business_address']) ?> | <?= e($app['business_phone']) ?></p>
    <p><strong>GSTIN:</strong> <?= e($app['business_gstin']) ?></p>

    <hr>

    <div class="grid two">
        <div>
            <h3>Bill To</h3>
            <p><?= e($sale['customer_name']) ?></p>
            <p><?= e($sale['address']) ?></p>
            <p><strong>State:</strong> <?= e($sale['state']) ?></p>
            <p><strong>GSTIN:</strong> <?= e($sale['gstin']) ?></p>
        </div>
        <div>
            <p><strong>Invoice No:</strong> <?= e($sale['invoice_no']) ?></p>
            <p><strong>Date:</strong> <?= e($sale['date']) ?></p>
        </div>
    </div>

    <table>
        <thead><tr><th>#</th><th>Product</th><th>HSN</th><th>Qty</th><th>Rate</th><th>GST%</th><th>Tax</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($sale['items'] as $idx => $item): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= e($item['product_name']) ?></td>
                <td><?= e($item['hsn_code']) ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td><?= number_format((float)$item['rate'], 2) ?></td>
                <td><?= number_format((float)$item['gst_percent'], 2) ?></td>
                <td><?= number_format((float)$item['tax_amount'], 2) ?></td>
                <td><?= number_format((float)$item['total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="tax-breakup">
        <tr><th>Taxable Value</th><td><?= number_format((float)$sale['subtotal'], 2) ?></td></tr>
        <tr><th>Item Discount</th><td><?= number_format((float)($sale['item_discount'] ?? 0), 2) ?></td></tr>
        <tr><th>Overall Discount</th><td><?= number_format((float)($sale['overall_discount'] ?? 0), 2) ?></td></tr>
        <tr><th>CGST</th><td><?= number_format((float)$sale['cgst'], 2) ?></td></tr>
        <tr><th>SGST</th><td><?= number_format((float)$sale['sgst'], 2) ?></td></tr>
        <tr><th>IGST</th><td><?= number_format((float)$sale['igst'], 2) ?></td></tr>
        <tr><th>Round Off</th><td><?= number_format((float)($sale['round_off'] ?? 0), 2) ?></td></tr>
        <tr><th>Grand Total</th><td><strong><?= number_format((float)$sale['total_amount'], 2) ?></strong></td></tr>
        <tr><th>Payment Status</th><td><?= e($sale['payment_status'] ?? 'UNPAID') ?></td></tr>
    </table>

    <p><strong>Total in words:</strong> <?= e($totalWords) ?></p>
    <?php if (!empty($sale['notes'])): ?><p><strong>Notes:</strong> <?= e($sale['notes']) ?></p><?php endif; ?>
    <?php if (!empty($sale['terms'])): ?><p><strong>Terms:</strong> <?= e($sale['terms']) ?></p><?php endif; ?>

    <?php if (!empty($defaultBank)): ?>
        <hr>
        <div class="grid two">
            <div>
                <h3>Bank Details</h3>
                <p><strong>Bank:</strong> <?= e($defaultBank['bank_name']) ?></p>
                <p><strong>A/C Name:</strong> <?= e($defaultBank['account_name']) ?></p>
                <p><strong>A/C No:</strong> <?= e($defaultBank['account_no']) ?></p>
                <p><strong>IFSC:</strong> <?= e($defaultBank['ifsc']) ?></p>
                <p><strong>UPI:</strong> <?= e($defaultBank['upi_id']) ?></p>
            </div>
            <div>
                <h3>Pay via QR</h3>
                <?php if (!empty($defaultBank['qr_image_path'])): ?>
                    <img src="<?= e($defaultBank['qr_image_path']) ?>" alt="UPI QR" class="invoice-logo-preview">
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
