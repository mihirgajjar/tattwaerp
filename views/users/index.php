<div class="toolbar">
    <h2>User Management</h2>
</div>

<section class="card">
    <form method="get" class="grid four">
        <input type="hidden" name="route" value="user/index">
        <label>Search<input type="text" name="q" value="<?= e($q) ?>" placeholder="username or email"></label>
        <label>Status
            <select name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </label>
        <button type="submit">Filter</button>
    </form>
</section>

<div class="grid two">
    <section class="card">
        <h3>Add User</h3>
        <form method="post" action="index.php?route=user/create" class="form-grid">
        <?= csrf_field() ?>
            <label>Username<input type="text" name="username" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required></label>
            <label>Role
                <select name="role_id" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int)$role['id'] ?>"><?= e($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><input type="checkbox" name="must_change_password" value="1"> Force change on first login</label>
            <button type="submit">Create User</button>
        </form>
    </section>

    <section class="card">
        <h3>Users</h3>
        <table>
            <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role_name'] ?: $u['role']) ?></td>
                    <td><?= (int)$u['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <form method="post" action="index.php?route=user/update" style="display:inline;">
        <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="username" value="<?= e($u['username']) ?>">
                            <input type="hidden" name="email" value="<?= e($u['email']) ?>">
                            <input type="hidden" name="role_id" value="<?= (int)$u['role_id'] ?>">
                            <input type="hidden" name="is_active" value="<?= (int)$u['is_active'] === 1 ? 0 : 1 ?>">
                            <button type="submit"><?= (int)$u['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                        <form method="post" action="index.php?route=user/resetPassword" style="display:inline;">
        <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="new_password" value="Temp@1234">
                            <button class="secondary" type="submit">Reset Password</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<section class="card">
    <h3>Login History</h3>
    <table>
        <thead><tr><th>When</th><th>User</th><th>IP</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): ?>
            <tr>
                <td><?= e($h['created_at']) ?></td>
                <td><?= e($h['username']) ?></td>
                <td><?= e($h['ip_address']) ?></td>
                <td><?= (int)$h['success'] === 1 ? 'Success' : 'Failed' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
