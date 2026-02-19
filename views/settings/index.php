<h2>Invoice Settings</h2>
<section class="card">
    <h3>Upload Invoice Logo</h3>
    <p class="muted">Allowed: PNG, JPG, WEBP. Max size: 2 MB.</p>
    <form method="post" action="index.php?route=setting/index" enctype="multipart/form-data" class="form-grid">
        <label>Invoice Logo<input type="file" name="invoice_logo" accept="image/png,image/jpeg,image/webp" required></label>
        <div>
            <button type="submit">Upload Logo</button>
        </div>
    </form>

    <?php if (!empty($logoPath)): ?>
        <div class="logo-preview">
            <p><strong>Current Logo:</strong></p>
            <img src="<?= e($logoPath) ?>" alt="Invoice Logo" class="invoice-logo-preview">
        </div>
    <?php endif; ?>
</section>
