<h2>Invoice Settings</h2>
<section class="card">
    <h3>Database Upgrade</h3>
    <p class="muted">Use Migration Center for safe, incremental live upgrades without data loss.</p>
    <a class="btn" href="index.php?route=migration/index">Open Migration Center</a>
</section>

<section class="card">
    <h3>Invoice Theme</h3>
    <form method="post" action="index.php?route=setting/index" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_invoice_theme">
        <label>Theme
            <select name="invoice_theme" required>
                <option value="classic" <?= ($invoiceTheme ?? 'classic') === 'classic' ? 'selected' : '' ?>>Classic</option>
                <option value="minimal" <?= ($invoiceTheme ?? 'classic') === 'minimal' ? 'selected' : '' ?>>Minimal</option>
                <option value="premium" <?= ($invoiceTheme ?? 'classic') === 'premium' ? 'selected' : '' ?>>Premium</option>
            </select>
        </label>
        <label>Accent Color
            <input type="color" name="invoice_accent_color" value="<?= e($invoiceAccentColor ?? '#1d6f5f') ?>" required>
        </label>
        <div>
            <button type="submit">Save Theme</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Upload Invoice Logo</h3>
    <p class="muted">Allowed: PNG, JPG, WEBP. Max size: 2 MB.</p>
    <form method="post" action="index.php?route=setting/index" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_logo">
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
