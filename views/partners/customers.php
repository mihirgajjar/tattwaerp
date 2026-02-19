<?php
$isEdit = !empty($editing);
?>
<div class="grid two">
    <section class="card">
        <h3><?= $isEdit ? 'Edit Customer' : 'Add Customer' ?></h3>
        <form method="post" action="index.php?route=customer/<?= $isEdit ? 'update' : 'create' ?>" class="form-grid">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
            <?php endif; ?>
            <label>Name<input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></label>
            <label>GSTIN<input type="text" name="gstin" value="<?= e($editing['gstin'] ?? '') ?>"></label>
            <label>State<input type="text" name="state" value="<?= e($editing['state'] ?? '') ?>" required></label>
            <label>Phone<input type="text" name="phone" value="<?= e($editing['phone'] ?? '') ?>"></label>
            <label>Address<textarea name="address"><?= e($editing['address'] ?? '') ?></textarea></label>
            <div>
                <button type="submit"><?= $isEdit ? 'Update Customer' : 'Save Customer' ?></button>
                <?php if ($isEdit): ?>
                    <a class="btn secondary" href="index.php?route=customer/index">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <section class="card">
        <h3>Customers</h3>
        <table>
            <thead><tr><th>Name</th><th>GSTIN</th><th>State</th><th>Phone</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($customers as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['gstin']) ?></td>
                    <td><?= e($s['state']) ?></td>
                    <td><?= e($s['phone']) ?></td>
                    <td>
                        <a href="index.php?route=customer/index&edit_id=<?= (int)$s['id'] ?>">Edit</a> |
                        <a href="index.php?route=customer/delete&id=<?= (int)$s['id'] ?>" onclick="return confirm('Delete this customer?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
