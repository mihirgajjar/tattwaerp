<?php $isEdit = !empty($editing); ?>
<div class="toolbar">
    <h2>Customer Master</h2>
</div>
<section class="card">
    <form method="get" class="grid four">
        <input type="hidden" name="route" value="customer/index">
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
        <h3><?= $isEdit ? 'Edit Customer' : 'Add Customer' ?></h3>
        <form method="post" action="index.php?route=customer/<?= $isEdit ? 'update' : 'create' ?>" class="form-grid">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
            <label>Name<input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></label>
            <label>Customer Type
                <select name="customer_type">
                    <?php foreach (['Retail', 'Wholesale', 'Distributor'] as $t): ?>
                        <option <?= ($editing['customer_type'] ?? 'Retail') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>GSTIN<input type="text" name="gstin" value="<?= e($editing['gstin'] ?? '') ?>"></label>
            <label>PAN<input type="text" name="pan_no" value="<?= e($editing['pan_no'] ?? '') ?>"></label>
            <label>State<input type="text" name="state" value="<?= e($editing['state'] ?? '') ?>" required></label>
            <label>Area/Region<input type="text" name="area_region" value="<?= e($editing['area_region'] ?? '') ?>"></label>
            <label>Phone<input type="text" name="phone" value="<?= e($editing['phone'] ?? '') ?>"></label>
            <label>Payment Terms<input type="text" name="payment_terms" value="<?= e($editing['payment_terms'] ?? '') ?>"></label>
            <label>Credit Limit<input type="number" step="0.01" name="credit_limit" value="<?= e((string)($editing['credit_limit'] ?? '0')) ?>"></label>
            <label>Status
                <select name="is_active">
                    <option value="1" <?= (int)($editing['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (int)($editing['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </label>
            <label>Billing Address<textarea name="address"><?= e($editing['address'] ?? '') ?></textarea></label>
            <label>Shipping Address<textarea name="shipping_address"><?= e($editing['shipping_address'] ?? '') ?></textarea></label>
            <div>
                <button type="submit"><?= $isEdit ? 'Update Customer' : 'Save Customer' ?></button>
                <?php if ($isEdit): ?><a class="btn secondary" href="index.php?route=customer/index">Cancel</a><?php endif; ?>
            </div>
        </form>
    </section>
    <section class="card">
        <h3>Customers</h3>
        <table>
            <thead><tr><th>Name</th><th>Type</th><th>Region</th><th>Credit</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($customers as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['customer_type'] ?? '') ?></td>
                    <td><?= e($s['area_region'] ?? '') ?></td>
                    <td><?= number_format((float)($s['credit_limit'] ?? 0), 2) ?></td>
                    <td><?= (int)($s['is_active'] ?? 1) === 1 ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <a href="index.php?route=customer/dashboard&id=<?= (int)$s['id'] ?>">Dashboard</a> |
                        <a href="index.php?route=customer/index&edit_id=<?= (int)$s['id'] ?>">Edit</a> |
                        <form method="post" action="index.php?route=customer/delete" style="display:inline;" onsubmit="return confirm('Delete this customer?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <button type="submit" class="danger-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
