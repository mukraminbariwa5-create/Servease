<?php
declare(strict_types=1);

/**
 * Small, idempotent schema upgrades for databases installed before a feature existed.
 * Fresh installs already get these from database/schema.sql; every step checks before it changes anything.
 * The applied version is cached in storage/cache so normal requests cost one file read.
 */
final class Migrator
{
    public const VERSION = 2;

    private static function cacheFile(): string { return ROOT . '/storage/cache/schema_version'; }

    public static function run(): void
    {
        $file = self::cacheFile();
        if (is_file($file) && (int) file_get_contents($file) >= self::VERSION) return;
        try {
            self::apply();
            @file_put_contents($file, (string) self::VERSION);
        } catch (Throwable $e) {
            error_log('Migration failed: ' . $e->getMessage());
        }
    }

    /** Marks the current schema as up to date (used right after a fresh install). */
    public static function markCurrent(): void
    {
        @file_put_contents(self::cacheFile(), (string) self::VERSION);
    }

    private static function hasColumn(string $table, string $column): bool
    {
        return (bool) DB::value(
            'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
    }

    private static function hasTable(string $table): bool
    {
        return (bool) DB::value('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]);
    }

    private static function apply(): void
    {
        $pdo = DB::pdo();

        // v1: reschedule proposals and day-before reminders on bookings
        $cols = [
            'proposed_date'    => 'DATE NULL AFTER cancel_reason',
            'proposed_time'    => 'VARCHAR(20) NULL AFTER proposed_date',
            'proposed_by'      => 'INT UNSIGNED NULL AFTER proposed_time',
            'proposed_note'    => 'VARCHAR(255) NULL AFTER proposed_by',
            'reminder_sent_at' => 'DATETIME NULL AFTER proposed_note',
        ];
        foreach ($cols as $col => $def) {
            if (!self::hasColumn('bookings', $col)) $pdo->exec("ALTER TABLE bookings ADD COLUMN `$col` $def");
        }

        // v2: problem reports on bookings, handled by admins
        if (!self::hasTable('booking_issues')) {
            $pdo->exec(self::issuesTableSql());
        }
    }

    public static function issuesTableSql(): string
    {
        return "CREATE TABLE booking_issues (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id   INT UNSIGNED NOT NULL,
            reporter_id  INT UNSIGNED NOT NULL,
            reason       ENUM('no_show','quality','payment','damage','safety','other') NOT NULL DEFAULT 'other',
            details      TEXT NOT NULL,
            status       ENUM('open','resolved') NOT NULL DEFAULT 'open',
            admin_note   VARCHAR(255) NULL,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at  DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_issues_status (status, created_at),
            KEY idx_issues_booking (booking_id),
            CONSTRAINT fk_issues_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
            CONSTRAINT fk_issues_reporter FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }
}
