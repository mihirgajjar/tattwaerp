<?php

class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            redirect('dashboard/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim((string)$this->request('username', ''));
            $password = (string)$this->request('password', '');

            if (Auth::attempt($username, $password)) {
                redirect('dashboard/index');
            }

            flash('error', 'Invalid username or password.');
            set_old(['username' => $username]);
            redirect('auth/login');
        }

        $this->view('auth/login', [], false);
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('auth/login');
    }
}
