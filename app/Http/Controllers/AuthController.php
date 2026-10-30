<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;

require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Role.php';

class AuthController
{
    public function showLogin()
    {
        if (auth()->check()) {
            redirect('');
        }

        return view('auth.login', [
            'redirect' => $_GET['redirect'] ?? '',
            'error' => $_GET['error'] ?? null
        ]);
    }

    public function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirectUrl = $_POST['redirect'] ?? '';

        $user = User::findBy('email', $email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            unset($_SESSION['demo_override_role']);
            if ($redirectUrl) {
                redirect($redirectUrl);
            }
            redirect('');
        }

        return view('auth.login', [
            'redirect' => $redirectUrl,
            'error' => 'Invalid email credentials. Please check your email and password.',
            'oldEmail' => $email
        ]);
    }

    public function showRegister()
    {
        if (auth()->check()) {
            redirect('');
        }

        return view('auth.register', [
            'error' => null
        ]);
    }

    public function register()
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            return view('auth.register', ['error' => 'All fields are required.']);
        }

        $existing = User::findBy('email', $email);
        if ($existing) {
            return view('auth.register', ['error' => 'An account with this email already exists.']);
        }

        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role_id' => 5, // Reader (Registered)
            'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80',
            'bio' => 'Brighteen community reader & youth culture enthusiast.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['user_id'] = $userId;
        redirect('');
    }

    public function logout()
    {
        unset($_SESSION['user_id'], $_SESSION['demo_override_role']);
        session_destroy();
        redirect('');
    }

    /**
     * Per-account temporary role switcher for Management & Editor accounts
     * (Switches active role on the current logged-in account without changing user/login)
     */
    public function switchUser($roleName)
    {
        if (!auth()->canUseRoleSwitcher()) {
            redirect('');
        }

        $allowed = auth()->availableSwitchRoles();
        $originalRole = auth()->originalRole();

        if ($roleName === 'reset' || $roleName === $originalRole) {
            unset($_SESSION['demo_override_role']);
        } elseif (in_array($roleName, $allowed, true)) {
            $_SESSION['demo_override_role'] = $roleName;
        }

        $redirect = $_GET['redirect'] ?? '';
        if (in_array($roleName, ['guest', 'reader_registered'], true)) {
            redirect('');
        }
        redirect($redirect ?: '');
    }
}
