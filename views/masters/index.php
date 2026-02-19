<h2>Master Data Module</h2>
<section class="card">
    <form method="get" class="grid four">
        <input type="hidden" name="route" value="master/index">
        <label>Master Table
            <select name="table">
                <?php foreach ($tables as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $table === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Search<input type="text" name="q" value="<?= e($q) ?>"></label>
        <label>Status
            <select name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </label>
        <button type="submit">Apply</button>
    </form>
</section>

<section class="card">
    <h3>Add / Update Record</h3>
    <form method="post" action="index.php?route=master/save" class="form-grid">
        <input type="hidden" name="table" value="<?= e($table) ?>">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <label>Name<input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></label>
        <?php if ($table === 'tax_settings'): ?>
            <label>GST Rate<input type="number" step="0.01" name="gst_rate" value="<?= e((string)($editing['gst_rate'] ?? '18')) ?>"></label>
        <?php endif; ?>
        <?php if ($table === 'product_subcategories'): ?>
            <label>Category
                <select name="category_id">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (int)($editing['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <?php if ($table === 'warehouses'): ?>
            <label>State<input type="text" name="state" value="<?= e($editing['state'] ?? '') ?>" required></label>
        <?php endif; ?>
        <?php if ($table !== 'warehouses'): ?>
            <label>Status
                <select name="is_active">
                    <option value="1" <?= (int)($editing['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (int)($editing['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </label>
        <?php endif; ?>
        <button type="submit"><?= !empty($editing) ? 'Update' : 'Save' ?></button>
        <?php if (!empty($editing)): ?>
            <a class="btn secondary" href="index.php?route=master/index&table=<?= e($table) ?>">Cancel</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h3>Records</h3>
    <table>
        <thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= e($r['name'] ?? '') ?></td>
                <td><?= array_key_exists('is_active', $r) ? ((int)$r['is_active'] === 1 ? 'Active' : 'Inactive') : '-' ?></td>
                <td>
                    <a href="index.php?route=master/index&table=<?= e($table) ?>&edit_id=<?= (int)$r['id'] ?>">Edit</a> |
                    <?php if (array_key_exists('is_active', $r)): ?>
                        <a href="index.php?route=master/deactivate&table=<?= e($table) ?>&id=<?= (int)$r['id'] ?>&active=<?= (int)$r['is_active'] === 1 ? 0 : 1 ?>">
                            <?= (int)$r['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                        </a> |
                    <?php endif; ?>
                    <a href="index.php?route=master/delete&table=<?= e($table) ?>&id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete record?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
