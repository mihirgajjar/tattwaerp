<?php $isEdit = !empty($editing); ?>
<div class="toolbar">
    <h2>Supplier Master</h2>
</div>
<section class="card">
    <form method="get" class="grid four">
        <input type="hidden" name="route" value="supplier/index">
        <label>Search<input type="text" name="q" value="<?= e($q ?? '') ?>"></label>
        <label>Status
            <select name="status">
                <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>All</option>
                <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </label>
        <button type="submit">Filter</button>
    </form>
</section>

<div class="grid two">
    <section class="card">
        <h3><?= $isEdit ? 'Edit Supplier' : 'Add Supplier' ?></h3>
        <form method="post" action="index.php?route=supplier/<?= $isEdit ? 'update' : 'create' ?>" class="form-grid">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
            <label>Name<input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></label>
            <label>Supplier Type<input type="text" name="supplier_type" value="<?= e($editing['supplier_type'] ?? '') ?>"></label>
            <label>GSTIN<input type="text" name="gstin" value="<?= e($editing['gstin'] ?? '') ?>"></label>
            <label>State<input type="text" name="state" value="<?= e($editing['state'] ?? '') ?>" required></label>
            <label>Phone<input type="text" name="phone" value="<?= e($editing['phone'] ?? '') ?>"></label>
            <label>Payment Terms<input type="text" name="payment_terms" value="<?= e($editing['payment_terms'] ?? '') ?>"></label>
            <label>Status
                <select name="is_active">
                    <option value="1" <?= (int)($editing['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (int)($editing['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </label>
            <label>Bank Details<textarea name="bank_details"><?= e($editing['bank_details'] ?? '') ?></textarea></label>
            <label>Address<textarea name="address"><?= e($editing['address'] ?? '') ?></textarea></label>
            <div>
                <button type="submit"><?= $isEdit ? 'Update Supplier' : 'Save Supplier' ?></button>
                <?php if ($isEdit): ?><a class="btn secondary" href="index.php?route=supplier/index">Cancel</a><?php endif; ?>
            </div>
        </form>
    </section>
    <section class="card">
        <h3>Suppliers</h3>
        <table>
            <thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['supplier_type'] ?? '') ?></td>
                    <td><?= e($s['phone']) ?></td>
                    <td><?= (int)($s['is_active'] ?? 1) === 1 ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <a href="index.php?route=supplier/dashboard&id=<?= (int)$s['id'] ?>">Dashboard</a> |
                        <a href="index.php?route=supplier/index&edit_id=<?= (int)$s['id'] ?>">Edit</a> |
                        <a href="index.php?route=supplier/delete&id=<?= (int)$s['id'] ?>" onclick="return confirm('Delete this supplier?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
