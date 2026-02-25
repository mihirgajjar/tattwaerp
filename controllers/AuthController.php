<?php

class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            $u = Auth::user();
            if (!empty($u['must_change_password'])) {
                redirect('auth/changePassword');
            }
            redirect('dashboard/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = trim((string)$this->request('identifier', ''));
            $password = (string)$this->request('password', '');

            if (Auth::attempt($identifier, $password)) {
                $u = Auth::user();
                if (!empty($u['must_change_password'])) {
                    flash('success', 'Please change your password before continuing.');
                    redirect('auth/changePassword');
                }
                redirect('dashboard/index');
            }

            flash('error', 'Invalid username/email or password.');
            set_old(['identifier' => $identifier]);
            redirect('auth/login');
        }

        $this->view('auth/login', [], false);
    }

    public function logout(): void
    {
        $this->requirePost('auth/login');
        Auth::logout();
        redirect('auth/login');
    }

    public function changePassword(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $current = (string)$this->request('current_password', '');
            $new = (string)$this->request('new_password', '');
            $confirm = (string)$this->request('confirm_password', '');

            if ($new !== $confirm) {
                flash('error', 'New password and confirm password do not match.');
                redirect('auth/changePassword');
            }

            $policyError = validate_password_policy($new);
            if ($policyError !== null) {
                flash('error', $policyError);
                redirect('auth/changePassword');
            }

            $userModel = new User();
            $user = $userModel->find((int)Auth::user()['id']);
            if (!$user || !password_verify($current, $user['password'])) {
                flash('error', 'Current password is incorrect.');
                redirect('auth/changePassword');
            }

            $userModel->setPassword((int)$user['id'], password_hash($new, PASSWORD_DEFAULT), false);
            audit_log('change_password', 'user', (int)$user['id']);
            flash('success', 'Password changed successfully.');
            redirect('dashboard/index');
        }

        $this->view('auth/change_password', [], false);
    }

    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = trim((string)$this->request('identifier', ''));
            $userModel = new User();
            $user = $userModel->findByLogin($identifier);

            if ($user) {
                $token = $userModel->createResetToken((int)$user['id']);
                flash('success', 'Reset token generated: ' . $token . '. Use this token in the reset form.');
                audit_log('forgot_password_token', 'user', (int)$user['id']);
            } else {
                flash('success', 'If account exists, reset instructions have been generated.');
            }

            redirect('auth/forgotPassword');
        }

        $this->view('auth/forgot_password', [], false);
    }

    public function resetPassword(): void
    {
        $token = trim((string)$this->request('token', ''));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = trim((string)$this->request('token', ''));
            $password = (string)$this->request('new_password', '');
            $confirm = (string)$this->request('confirm_password', '');

            if ($password !== $confirm) {
                flash('error', 'Password and confirm password do not match.');
                redirect('auth/resetPassword&token=' . urlencode($token));
            }

            $policyError = validate_password_policy($password);
            if ($policyError !== null) {
                flash('error', $policyError);
                redirect('auth/resetPassword&token=' . urlencode($token));
            }

            $userModel = new User();
            $reset = $userModel->findResetToken($token);
            if (!$reset || $reset['used_at'] !== null || strtotime($reset['expires_at']) < time()) {
                flash('error', 'Invalid or expired reset token.');
                redirect('auth/forgotPassword');
            }

            $userModel->setPassword((int)$reset['user_id'], password_hash($password, PASSWORD_DEFAULT), false);
            $userModel->markResetUsed((int)$reset['id']);
            audit_log('reset_password', 'user', (int)$reset['user_id']);
            flash('success', 'Password reset successful. Please login.');
            redirect('auth/login');
        }

        $this->view('auth/reset_password', ['token' => $token], false);
    }
}
