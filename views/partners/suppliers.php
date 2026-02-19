<?php
$isEdit = !empty($editing);
?>
<div class="grid two">
    <section class="card">
        <h3><?= $isEdit ? 'Edit Supplier' : 'Add Supplier' ?></h3>
        <form method="post" action="index.php?route=supplier/<?= $isEdit ? 'update' : 'create' ?>" class="form-grid">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
            <?php endif; ?>
            <label>Name<input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></label>
            <label>GSTIN<input type="text" name="gstin" value="<?= e($editing['gstin'] ?? '') ?>"></label>
            <label>State<input type="text" name="state" value="<?= e($editing['state'] ?? '') ?>" required></label>
            <label>Phone<input type="text" name="phone" value="<?= e($editing['phone'] ?? '') ?>"></label>
            <label>Address<textarea name="address"><?= e($editing['address'] ?? '') ?></textarea></label>
            <div>
                <button type="submit"><?= $isEdit ? 'Update Supplier' : 'Save Supplier' ?></button>
                <?php if ($isEdit): ?>
                    <a class="btn secondary" href="index.php?route=supplier/index">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <section class="card">
        <h3>Suppliers</h3>
        <table>
            <thead><tr><th>Name</th><th>GSTIN</th><th>State</th><th>Phone</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['gstin']) ?></td>
                    <td><?= e($s['state']) ?></td>
                    <td><?= e($s['phone']) ?></td>
                    <td>
                        <a href="index.php?route=supplier/index&edit_id=<?= (int)$s['id'] ?>">Edit</a> |
                        <a href="index.php?route=supplier/delete&id=<?= (int)$s['id'] ?>" onclick="return confirm('Delete this supplier?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
