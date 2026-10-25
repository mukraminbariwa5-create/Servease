<?php
declare(strict_types=1);

final class AccountController
{
    /** Profile tab on phones: who you are plus every account link in one list. */
    public function menu(): void
    {
        $u = require_auth();
        $stats = is_role('provider')
            ? DB::one("SELECT rating_avg, rating_count, jobs_completed FROM provider_profiles WHERE user_id = ?", [(int) $u['id']])
            : null;
        view('account/menu', ['title' => 'Profile', 'u' => $u, 'stats' => $stats, 'active' => 'account'], 'layouts/shell');
    }

    public function edit(): void
    {
        $u = require_auth();
        view('account/settings', ['title' => 'Settings', 'u' => $u, 'active' => 'settings'], 'layouts/shell');
    }

    public function update(): void
    {
        $u = require_auth();
        $v = Validator::make($_POST, [
            'first_name' => 'required|max:80',
            'last_name'  => 'required|max:80',
            'email'      => 'required|email|max:190',
            'phone'      => 'nullable|phone',
            'city'       => 'nullable|max:80',
        ]);
        if ($v->fails()) back_with_errors($v->errors(), '/settings');
        $d = $v->validated();
        $d['email'] = strtolower($d['email']);

        if ($d['email'] !== $u['email'] && DB::value('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$d['email'], (int) $u['id']])) {
            back_with_errors(['email' => 'That email is already used by another account.'], '/settings');
        }
        $update = [
            'first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'email' => $d['email'],
            'phone' => $d['phone'], 'city' => $d['city'],
        ];
        if ($d['email'] !== $u['email']) {
            $token = bin2hex(random_bytes(32));
            $update['email_verified_at'] = null;
            $update['verify_token_hash'] = hash('sha256', $token);
            $link = absolute_url('/verify-email', ['token' => $token]);
            Mailer::send($d['email'], 'Confirm your new email', "Hi {$d['first_name']},\n\nConfirm your new Servease email address:\n$link\n");
            Mailer::devLink('Verify your new email', $link);
        }
        DB::update('users', $update, 'id = :id', ['id' => (int) $u['id']]);
        flash('success', 'Your profile has been updated.');
        redirect('/settings');
    }

    public function password(): void
    {
        $u = require_auth();
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $u['password_hash'])) {
            back_with_errors(['current_password' => 'Your current password is incorrect.'], '/settings#password');
        }
        $v = Validator::make($_POST, ['password' => 'required|password|confirmed'], ['password' => 'New password']);
        if ($v->fails()) back_with_errors($v->errors(), '/settings#password');
        DB::update('users', ['password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT)], 'id = :id', ['id' => (int) $u['id']]);
        session_regenerate_id(true);
        flash('success', 'Password changed.');
        redirect('/settings');
    }

    public function avatar(): void
    {
        $u = require_auth();
        $back = ($_POST['return'] ?? '') === '/provider/profile' ? '/provider/profile' : '/settings';
        if (!empty($_POST['remove'])) {
            Upload::delete($u['avatar']);
            DB::update('users', ['avatar' => null], 'id = :id', ['id' => (int) $u['id']]);
            flash('success', 'Profile photo removed.');
            redirect($back);
        }
        $res = Upload::store($_FILES['avatar'] ?? null, 'avatars', Upload::IMAGES, 3);
        if ($res['error']) back_with_errors(['avatar' => $res['error']], $back);
        Upload::delete($u['avatar']);
        DB::update('users', ['avatar' => $res['path']], 'id = :id', ['id' => (int) $u['id']]);
        flash('success', 'Profile photo updated.');
        redirect($back);
    }

    /** Switch the interface between English and Filipino (works signed in or out). */
    public function language(): void
    {
        Lang::set((string) ($_POST['lang'] ?? 'en'));
        back('/');
    }
}
