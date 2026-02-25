<?php

class UserController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('user_manage', 'read');
        $search = trim((string)$this->request('q', ''));
        $status = trim((string)$this->request('status', 'all'));

        $userModel = new User();
        $this->view('users/index', [
            'users' => $userModel->all($search, $status),
            'roles' => $userModel->roles(),
            'q' => $search,
            'status' => $status,
            'history' => $userModel->loginHistory(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('user_manage', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('user/index');
        }

        $username = trim((string)$this->request('username', ''));
        $email = trim((string)$this->request('email', ''));
        $password = (string)$this->request('password', '');
        $roleId = (int)$this->request('role_id', 0);

        if ($username === '' || $email === '' || $roleId <= 0 || $password === '') {
            flash('error', 'Username, email, role and password are required.');
            redirect('user/index');
        }

        $policyError = validate_password_policy($password);
        if ($policyError) {
            flash('error', $policyError);
            redirect('user/index');
        }

        $roleName = $this->roleName($roleId);
        $id = (new User())->create([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $roleName,
            'role_id' => $roleId,
            'is_active' => 1,
            'must_change_password' => (int)$this->request('must_change_password', 0) === 1 ? 1 : 0,
        ]);

        audit_log('create_user', 'user', $id, ['username' => $username]);
        flash('success', 'User created.');
        redirect('user/index');
    }

    public function update(): void
    {
        $this->requirePermission('user_manage', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('user/index');
        }

        $id = (int)$this->request('id', 0);
        $roleId = (int)$this->request('role_id', 0);
        if ($id <= 0 || $roleId <= 0) {
            flash('error', 'Invalid user.');
            redirect('user/index');
        }

        (new User())->update($id, [
            'username' => trim((string)$this->request('username', '')),
            'email' => trim((string)$this->request('email', '')),
            'role' => $this->roleName($roleId),
            'role_id' => $roleId,
            'is_active' => (int)$this->request('is_active', 0) === 1 ? 1 : 0,
            'must_change_password' => (int)$this->request('must_change_password', 0) === 1 ? 1 : 0,
        ]);

        audit_log('update_user', 'user', $id);
        flash('success', 'User updated.');
        redirect('user/index');
    }

    public function toggleActive(): void
    {
        $this->requirePermission('user_manage', 'write');
        $this->requirePost('user/index');
        $id = (int)$this->request('id', 0);
        $active = (int)$this->request('active', 1) === 1;
        if ($id > 0) {
            (new User())->setActive($id, $active);
            audit_log('toggle_user_status', 'user', $id, ['active' => $active]);
            flash('success', 'User status updated.');
        }
        redirect('user/index');
    }

    public function resetPassword(): void
    {
        $this->requirePermission('user_manage', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('user/index');
        }

        $id = (int)$this->request('id', 0);
        $password = (string)$this->request('new_password', '');
        if ($id <= 0) {
            flash('error', 'Invalid user.');
            redirect('user/index');
        }
        $policyError = validate_password_policy($password);
        if ($policyError) {
            flash('error', $policyError);
            redirect('user/index');
        }

        (new User())->setPassword($id, password_hash($password, PASSWORD_DEFAULT), true);
        audit_log('admin_reset_password', 'user', $id);
        flash('success', 'Password reset. User must change on next login.');
        redirect('user/index');
    }

    private function roleName(int $roleId): string
    {
        $roles = (new User())->roles();
        foreach ($roles as $role) {
            if ((int)$role['id'] === $roleId) {
                return $role['name'];
            }
        }
        return 'Viewer';
    }
}
