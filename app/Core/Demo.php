<?php
declare(strict_types=1);

/**
 * Public demo mode, for running Servease as an open portfolio demo.
 *
 * Turn it on with 'app' => ['public_demo' => true] in config/config.php on the demo server:
 *   - the seeded demo accounts (emails ending in @servease.test) are protected: nobody can change
 *     their details, password or photo, reset their password, or suspend them, so one visitor
 *     can't lock out the next
 *   - file uploads are off, so strangers can't publish images or documents on the site
 *   - the first visit of each day puts the demo data back, so whatever visitors changed is gone
 * Off by default, so local development and the test suites behave normally.
 */
final class Demo
{
    public const DOMAIN = '@servease.test';

    public static function on(): bool
    {
        return (bool) config('app.public_demo', false);
    }

    /** True for a protected demo account (a user row with an email). */
    public static function isDemoAccount(?array $user): bool
    {
        return self::on() && $user && str_ends_with(strtolower((string) ($user['email'] ?? '')), self::DOMAIN);
    }

    /** Sends the visitor back with a friendly message when $user is a protected demo account. */
    public static function guard(?array $user, string $what, string $back): void
    {
        if (self::isDemoAccount($user)) {
            flash('error', "This is a shared demo account, so its $what can't be changed. Create your own account to try that.");
            redirect($back);
        }
    }

    public static function uploadsBlocked(): bool
    {
        return self::on();
    }

    /** Runs the daily reset once per day: the first request wins, others wait on the lock and skip. */
    public static function dailyReset(): void
    {
        if (!self::on() || !config('app.demo_reset_daily', true) || PHP_SAPI === 'cli') return;
        $stamp = ROOT . '/storage/cache/demo-reset.txt';
        $today = date('Y-m-d');
        if (is_file($stamp) && trim((string) @file_get_contents($stamp)) === $today) return;
        if (!DB::isInstalled()) return;   // the installer sets up the first copy
        if (!is_dir(dirname($stamp))) @mkdir(dirname($stamp), 0755, true);
        $fh = @fopen($stamp, 'c+');
        if (!$fh) return;
        try {
            if (!flock($fh, LOCK_EX)) return;
            rewind($fh);
            if (trim((string) stream_get_contents($fh)) === $today) return;   // another request just did it
            @set_time_limit(60);
            InstallController::install(true);
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, $today);
        } catch (Throwable $e) {
            error_log('Servease demo reset failed: ' . $e->getMessage());
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}
