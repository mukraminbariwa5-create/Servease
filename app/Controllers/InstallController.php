<?php
declare(strict_types=1);

/**
 * One-click installer: creates the database, tables and demo data.
 * Available while the app is not installed, or in local mode from localhost.
 */
final class InstallController
{
    private function allowed(): bool
    {
        if (!DB::isInstalled()) return true;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return config('app.env') === 'local' && in_array($ip, ['127.0.0.1', '::1'], true);
    }

    public function show(): void
    {
        if (!$this->allowed()) abort(404);
        $installed = DB::isInstalled();
        $dbError = null;
        try { DB::pdo(false); } catch (Throwable $e) { $dbError = $e->getMessage(); }
        view('install/index', ['installed' => $installed, 'dbError' => $dbError], 'layouts/blank');
    }

    public function run(): void
    {
        if (!$this->allowed()) abort(404);
        $seed = !empty($_POST['seed']);
        try {
            $result = self::install($seed);
        } catch (Throwable $e) {
            flash('error', 'Installation failed: ' . $e->getMessage());
            redirect('/install');
        }
        session_regenerate_id(true);
        unset($_SESSION['user_id']);
        flash('success', $seed
            ? "Fixoria is installed with demo data. Sign in with any demo account (password: {$result['password']})."
            : 'Fixoria is installed. Create your account to get started.');
        redirect($seed ? '/login' : '/register');
    }

    /** Also used by the CLI script database/install.php */
    public static function install(bool $seed = true): array
    {
        $name = config('db.name');
        if (!preg_match('/^\w+$/', (string) $name)) throw new RuntimeException('Invalid database name in config.');
        DB::pdo(false)->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $sql = file_get_contents(ROOT . '/database/schema.sql');
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            DB::pdo()->exec($statement);
        }

        foreach (['uploads/avatars', 'uploads/portfolio', 'storage/private/verification', 'storage/logs'] as $dir) {
            if (!is_dir(ROOT . "/$dir")) mkdir(ROOT . "/$dir", 0755, true);
        }

        if (!$seed) return ['password' => null];
        require_once ROOT . '/database/Seeder.php';
        return (new Seeder())->run();
    }
}
