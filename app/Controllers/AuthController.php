<?php
declare(strict_types=1);

final class AuthController
{
    private function guestOnly(): void
    {
        if (user()) redirect(home_for(user()));
    }

    /* ---------------- Sign in ---------------- */

    public function showLogin(): void
    {
        $this->guestOnly();
        view('auth/login', ['title' => 'Log in', 'art' => 'verified', 'back' => '/welcome'], 'layouts/auth');
    }

    public function login(): void
    {
        $this->guestOnly();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            back_with_errors(['email' => 'Enter your email and password.'], '/login');
        }
        if (Auth::tooManyAttempts($email)) {
            back_with_errors(['email' => 'Too many failed attempts. Please wait 15 minutes and try again.'], '/login');
        }

        $u = DB::one('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$u || !password_verify($password, $u['password_hash'])) {
            Auth::recordFailure($email);
            back_with_errors(['email' => 'That email and password combination is incorrect.'], '/login');
        }
        if ($u['status'] !== 'active') {
            back_with_errors(['email' => 'This account has been suspended. Contact support if you think this is a mistake.'], '/login');
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => (int) $u['id']]);
        }

        Auth::clearFailures($email);
        Auth::login($u);
        flash('success', 'Welcome back, ' . $u['first_name'] . '!');

        $intended = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);
        if ($intended && str_starts_with($intended, base_path() . '/') && !str_contains($intended, '//')) {
            header('Location: ' . $intended, true, 303);
            exit;
        }
        redirect(home_for($u));
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been signed out.');
        redirect('/login');
    }

    /* ---------------- Welcome (splash + intro slides + log in / create account) ---------------- */

    public function welcome(): void
    {
        $this->guestOnly();
        // Remember the intro was seen: next time a signed-out visitor goes straight to the login screen
        setcookie(Router::INTRO_COOKIE, '1', [
            'expires' => time() + 365 * 86400, 'path' => base_path() ?: '/', 'samesite' => 'Lax', 'httponly' => true,
        ]);
        view('auth/welcome', ['title' => 'Welcome'], 'layouts/blank');
    }

    /* ---------------- Sign up ---------------- */

    public function showRegister(): void
    {
        $this->guestOnly();
        $role = (string) old('role', (string) ($_GET['role'] ?? ''));
        if (!in_array($role, ['client', 'provider'], true)) {
            // Step 1: choose how you'll use Servease
            view('auth/role', ['title' => 'Create your account', 'art' => 'services', 'back' => '/welcome'], 'layouts/auth');
            return;
        }
        view('auth/register', ['title' => 'Create your account', 'role' => $role, 'art' => $role === 'provider' ? 'booking' : 'services', 'back' => '/register'], 'layouts/auth');
    }

    public function register(): void
    {
        $this->guestOnly();
        $v = Validator::make($_POST, [
            'role'        => 'required|in:client,provider',
            'first_name'  => 'required|max:80',
            'last_name'   => 'required|max:80',
            'email'       => 'required|email|max:190',
            'phone'       => 'nullable|phone',
            'city'        => 'required|max:80',
            'category_id' => ($_POST['role'] ?? '') === 'provider' ? 'required|int|exists:categories,id' : 'nullable',
            'password'    => 'required|password|confirmed',
        ], ['category_id' => 'service category']);
        $errors = $v->errors();
        $data = $v->validated();

        if (!$errors && DB::value('SELECT 1 FROM users WHERE email = ?', [strtolower($data['email'])])) {
            $errors['email'] = 'An account with this email already exists. Try signing in instead.';
        }
        if (empty($_POST['terms'])) $errors['terms'] = 'Please accept the terms to continue.';
        if ($errors) back_with_errors($errors, '/register');

        $token = bin2hex(random_bytes(32));
        $id = DB::transaction(function () use ($data, $token) {
            $id = DB::insert('users', [
                'role' => $data['role'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'],
                'city' => $data['city'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'verify_token_hash' => hash('sha256', $token),
            ]);
            if ($data['role'] === 'provider') {
                DB::insert('provider_profiles', [
                    'user_id' => $id, 'category_id' => (int) $data['category_id'], 'city' => $data['city'],
                    'service_area' => $data['city'],
                ]);
            }
            return $id;
        });

        $u = DB::one('SELECT * FROM users WHERE id = ?', [$id]);
        $this->sendVerification($u, $token);
        Auth::login($u);
        notify($id, 'welcome', 'Welcome to Servease, ' . $u['first_name'] . '!',
            $u['role'] === 'provider' ? 'Complete your pro profile so clients can find you.' : 'Find a pro or post your first job.',
            $u['role'] === 'provider' ? '/provider/profile' : '/');

        if ($u['role'] === 'provider') {
            flash('success', 'Your account is ready! Start with a clear photo of your face, then add your services and working hours.');
            redirect('/provider/profile#photo');
        }
        flash('success', 'Welcome to Servease, ' . $u['first_name'] . '! Search for a service or post a job to get started.');
        redirect('/');
    }

    /* ---------------- Email verification ---------------- */

    private function sendVerification(array $u, string $token): void
    {
        $link = absolute_url('/verify-email', ['token' => $token]);
        Mailer::send($u['email'], 'Confirm your Servease email',
            "Hi {$u['first_name']},\n\nPlease confirm your email address by opening this link:\n$link\n\nThanks,\nThe Servease team");
        Mailer::devLink('confirm your email', $link);
    }

    public function verify(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $u = $token ? DB::one('SELECT * FROM users WHERE verify_token_hash = ?', [hash('sha256', $token)]) : null;
        if (!$u) {
            flash('error', 'That verification link is invalid or has already been used.');
            redirect(user() ? home_for(user()) : '/login');
        }
        DB::update('users', ['email_verified_at' => date('Y-m-d H:i:s'), 'verify_token_hash' => null], 'id = :id', ['id' => (int) $u['id']]);
        flash('success', 'Your email is confirmed. Thank you!');
        redirect(user() ? home_for(user()) : '/login');
    }

    public function resendVerification(): void
    {
        $u = require_auth();
        if ($u['email_verified_at']) back(home_for(user()));
        $token = bin2hex(random_bytes(32));
        DB::update('users', ['verify_token_hash' => hash('sha256', $token)], 'id = :id', ['id' => (int) $u['id']]);
        $this->sendVerification($u, $token);
        flash('success', 'We sent a new confirmation link to ' . $u['email'] . '.');
        back(home_for(user()));
    }

    /* ---------------- Password reset ---------------- */

    public function showForgot(): void
    {
        view('auth/forgot', ['title' => 'Reset your password', 'back' => '/login'], 'layouts/auth');
    }

    public function forgot(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back_with_errors(['email' => 'Enter a valid email address.'], '/forgot-password');

        $u = DB::one("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        if ($u) {
            $token = bin2hex(random_bytes(32));
            DB::update('users', [
                'reset_token_hash' => hash('sha256', $token),
                'reset_expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ], 'id = :id', ['id' => (int) $u['id']]);
            $link = absolute_url('/reset-password', ['token' => $token]);
            Mailer::send($u['email'], 'Reset your Servease password',
                "Hi {$u['first_name']},\n\nUse this link to choose a new password (valid for 1 hour):\n$link\n\nIf you didn't ask for this, you can ignore this email.");
            Mailer::devLink('reset your password', $link);
        }
        // Same message either way so we don't reveal which emails are registered
        flash('success', 'If an account exists for that email, we sent a link to reset the password.');
        redirect('/forgot-password');
    }

    public function showReset(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $u = $this->userForResetToken($token);
        if (!$u) {
            flash('error', 'That reset link is invalid or has expired. Please request a new one.');
            redirect('/forgot-password');
        }
        view('auth/reset', ['title' => 'Choose a new password', 'token' => $token, 'back' => '/login'], 'layouts/auth');
    }

    public function reset(): void
    {
        $token = (string) ($_POST['token'] ?? '');
        $u = $this->userForResetToken($token);
        if (!$u) {
            flash('error', 'That reset link is invalid or has expired. Please request a new one.');
            redirect('/forgot-password');
        }
        $v = Validator::make($_POST, ['password' => 'required|password|confirmed']);
        if ($v->fails()) back_with_errors($v->errors(), '/reset-password?token=' . urlencode($token));

        DB::update('users', [
            'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'reset_token_hash' => null, 'reset_expires_at' => null,
        ], 'id = :id', ['id' => (int) $u['id']]);
        Auth::clearFailures($u['email']);
        flash('success', 'Your password was updated. You can sign in now.');
        redirect('/login');
    }

    private function userForResetToken(string $token): ?array
    {
        if (strlen($token) !== 64) return null;
        return DB::one('SELECT * FROM users WHERE reset_token_hash = ? AND reset_expires_at > ?', [hash('sha256', $token), date('Y-m-d H:i:s')]);
    }
}
