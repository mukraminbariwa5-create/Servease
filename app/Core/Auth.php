<?php
declare(strict_types=1);

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) return self::$user;
        self::$loaded = true;
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) return null;
        $u = DB::one('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$u || $u['status'] !== 'active') {
            self::logout();
            return null;
        }
        return self::$user = $u;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = $user;
        self::$loaded = true;
        DB::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $user['id']]);
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        self::$user = null;
        session_regenerate_id(true);
    }

    public static function refresh(): void
    {
        self::$loaded = false;
        self::$user = null;
    }

    /* ---- Brute-force protection: 5 failed attempts per email+IP per 15 minutes ---- */

    public static function tooManyAttempts(string $email): bool
    {
        $count = (int) DB::value(
            'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip = ? AND attempted_at > ?',
            [strtolower($email), self::ip(), date('Y-m-d H:i:s', time() - 900)]
        );
        return $count >= 5;
    }

    public static function recordFailure(string $email): void
    {
        DB::insert('login_attempts', ['email' => strtolower($email), 'ip' => self::ip()]);
    }

    public static function clearFailures(string $email): void
    {
        DB::query('DELETE FROM login_attempts WHERE email = ? AND ip = ?', [strtolower($email), self::ip()]);
    }

    private static function ip(): string
    {
        return substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 45);
    }
}
