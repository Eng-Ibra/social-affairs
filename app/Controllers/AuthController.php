<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Validator;

class AuthController extends Controller
{
    protected function guestOnlyRoutes(): bool
    {
        return true;
    }

    public function showLogin(): void
    {
        if (is_logged_in()) {
            redirect('/dashboard');
        }
        $this->view('auth/login', ['title' => 'Login'], 'layout/guest');
    }

    public function login(): void
    {
        $this->verifyCsrf();
        $email = trim((string) $this->input('email'));
        $password = (string) $this->input('password');

        [$ok, $message] = Auth::attempt($email, $password);
        if (!$ok) {
            flash('error', $message);
            $_SESSION['_old_input'] = ['email' => $email];
            redirect('/login');
        }
        flash('success', 'Welcome back, ' . current_user()['name'] . '!');
        redirect('/dashboard');
    }

    public function showSignup(): void
    {
        if (is_logged_in()) {
            redirect('/dashboard');
        }
        $pdo = Database::connection();
        $roles = $pdo->query("SELECT id, name FROM roles WHERE slug != 'super_admin' ORDER BY name")->fetchAll();
        $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
        $this->view('auth/signup', compact('roles', 'departments') + ['title' => 'Create Account'], 'layout/guest');
    }

    public function signup(): void
    {
        $this->verifyCsrf();
        $data = [
            'name' => trim((string) $this->input('name')),
            'email' => trim(strtolower((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'password' => (string) $this->input('password'),
            'password_confirmation' => (string) $this->input('password_confirmation'),
            'department_id' => $this->input('department_id') ?: null,
            'position' => trim((string) $this->input('position')),
            'role_id' => $this->input('role_id'),
        ];

        $errors = $this->validate($data, [
            'name' => 'required|max:150',
            'email' => 'required|email|unique:users,email',
            'phone' => 'max:30',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required',
        ]);

        if ($errors) {
            redirect('/signup');
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, phone, password_hash, department_id, position, role_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?: null,
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['department_id'],
            $data['position'] ?: null,
            $data['role_id'],
        ]);
        $newUserId = (int) $pdo->lastInsertId();

        Audit::log('created', 'users', $newUserId, "{$data['name']} signed up (pending approval)");

        // Notify all Admins & Super Admins
        $admins = $pdo->query("SELECT id FROM roles WHERE slug IN ('admin','super_admin')")->fetchAll();
        foreach ($admins as $role) {
            \App\Core\Notification::toRole(
                (int) $role['id'],
                'New account pending approval',
                "{$data['name']} ({$data['email']}) has registered and is awaiting approval.",
                'user_pending',
                '/users?status=pending'
            );
        }

        flash('success', 'Your account has been created and is pending administrator approval. You will be able to log in once approved.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }

    public function showForgot(): void
    {
        $this->view('auth/forgot', ['title' => 'Forgot Password'], 'layout/guest');
    }

    public function forgot(): void
    {
        $this->verifyCsrf();
        $email = trim(strtolower((string) $this->input('email')));
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "approved"');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same message to avoid user enumeration.
        flash('success', 'If that email exists in our system, a password reset link has been sent.');

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $stmt = $pdo->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))'
            );
            $stmt->execute([$user['id'], $hash]);

            $link = url('/reset-password?token=' . $token . '&email=' . urlencode($email));
            Mailer::send(
                $email,
                'Password Reset — ' . config('app.name'),
                "Hello {$user['name']},\n\nA password reset was requested for your account.\n"
                . "Click the link below to set a new password (valid for 60 minutes):\n\n{$link}\n\n"
                . "If you did not request this, you can safely ignore this email."
            );
            Audit::log('password_reset_requested', 'users', (int) $user['id'], 'Password reset link generated');

            if (config('app.debug')) {
                $_SESSION['_debug_reset_link'] = $link;
            }
        }

        redirect('/forgot-password');
    }

    public function showReset(): void
    {
        $token = (string) $this->input('token');
        $email = (string) $this->input('email');
        $this->view('auth/reset', ['title' => 'Reset Password', 'token' => $token, 'email' => $email], 'layout/guest');
    }

    public function reset(): void
    {
        $this->verifyCsrf();
        $token = (string) $this->input('token');
        $email = trim(strtolower((string) $this->input('email')));
        $password = (string) $this->input('password');

        $errors = $this->validate(['password' => $password, 'password_confirmation' => $this->input('password_confirmation')], [
            'password' => 'required|min:8|confirmed',
        ]);
        if ($errors) {
            redirect('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email));
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $hash = hash('sha256', $token);
        $stmt = $pdo->prepare(
            'SELECT * FROM password_resets WHERE token_hash = ? AND user_id = ? AND used_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$hash, $user['id'] ?? 0]);
        $reset = $stmt->fetch();

        if (!$user || !$reset) {
            flash('error', 'This password reset link is invalid or has expired.');
            redirect('/forgot-password');
        }

        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_BCRYPT), $user['id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$reset['id']]);

        Audit::log('password_reset', 'users', (int) $user['id'], 'Password reset completed');
        flash('success', 'Your password has been reset. You can now log in.');
        redirect('/login');
    }

    public function profile(): void
    {
        $user = current_user();
        $this->view('auth/profile', ['title' => 'My Profile', 'user' => $user]);
    }

    public function updateProfile(): void
    {
        $this->verifyCsrf();
        $user = current_user();
        $data = [
            'name' => trim((string) $this->input('name')),
            'phone' => trim((string) $this->input('phone')),
            'position' => trim((string) $this->input('position')),
        ];
        $errors = $this->validate($data, [
            'name' => 'required|max:150',
        ]);
        if ($errors) {
            redirect('/profile');
        }

        $pdo = Database::connection();

        $avatarPath = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $avatarPath = $this->storeAvatar($_FILES['avatar'], $user['id']);
        }

        $pdo->prepare('UPDATE users SET name = ?, phone = ?, position = ?, avatar = ? WHERE id = ?')
            ->execute([$data['name'], $data['phone'] ?: null, $data['position'] ?: null, $avatarPath, $user['id']]);

        Audit::log('updated', 'users', (int) $user['id'], 'Profile updated');
        Auth::refreshSessionUser();
        flash('success', 'Profile updated successfully.');
        redirect('/profile');
    }

    private function storeAvatar(array $file, int $userId): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            flash('error', 'Avatar must be a JPG, PNG or WEBP image.');
            return null;
        }
        $dir = base_path('public/uploads/avatars');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'user_' . $userId . '_' . time() . '.' . $allowed[$mime];
        move_uploaded_file($file['tmp_name'], $dir . '/' . $filename);
        return 'uploads/avatars/' . $filename;
    }

    public function changePassword(): void
    {
        $this->verifyCsrf();
        $user = current_user();
        $current = (string) $this->input('current_password');
        $new = (string) $this->input('new_password');

        $errors = $this->validate(['new_password' => $new, 'new_password_confirmation' => $this->input('new_password_confirmation')], [
            'new_password' => 'required|min:8|confirmed',
        ]);
        if ($errors) {
            redirect('/profile');
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            flash('error', 'Your current password is incorrect.');
            redirect('/profile');
        }

        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_BCRYPT), $user['id']]);
        Audit::log('password_changed', 'users', (int) $user['id'], 'Password changed by user');
        flash('success', 'Password changed successfully.');
        redirect('/profile');
    }
}
