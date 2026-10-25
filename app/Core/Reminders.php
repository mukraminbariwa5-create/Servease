<?php
declare(strict_types=1);

/**
 * Day-before reminders for confirmed bookings: an in-app notification and an email to both
 * the customer and the pro. Each booking is reminded once (reminder_sent_at); moving it to a
 * new time resets that, so the new day gets its own reminder.
 *
 * Runs from the command line (php database/reminders.php, e.g. from Windows Task Scheduler or cron)
 * and, so it also works with no setup, at most once an hour during normal page loads.
 */
final class Reminders
{
    private static function stampFile(): string { return ROOT . '/storage/cache/reminders_last_run'; }

    /** Cheap check on page loads: runs the reminders when the last run was over an hour ago. */
    public static function runIfDue(): void
    {
        $file = self::stampFile();
        if (is_file($file) && time() - (int) @filemtime($file) < 3600) return;
        @touch($file);
        try { self::send(); } catch (Throwable $e) { error_log('Reminders failed: ' . $e->getMessage()); }
    }

    /** @return int number of bookings reminded */
    public static function send(): int
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $rows = DB::all(
            "SELECT b.*, c.first_name AS c_first, c.email AS c_email, p.first_name AS p_first, p.last_name AS p_last, p.email AS p_email, p.phone AS p_phone
             FROM bookings b JOIN users c ON c.id = b.client_id JOIN users p ON p.id = b.provider_id
             WHERE b.status = 'accepted' AND b.scheduled_date = ? AND b.reminder_sent_at IS NULL",
            [$tomorrow]
        );
        foreach ($rows as $b) {
            $when = fmt_date($b['scheduled_date'], 'l, M j') . ($b['scheduled_time'] ? ', ' . Availability::range($b['scheduled_time'], (int) $b['duration_hours']) : '');
            $where = trim(($b['address'] ?? '') . ', ' . ($b['city'] ?? ''), ', ');
            $link = absolute_url('/bookings/' . $b['id']);

            notify((int) $b['client_id'], 'booking', 'Reminder: ' . $b['p_first'] . ' comes tomorrow',
                '“' . $b['title'] . '” · ' . $when, '/bookings/' . $b['id']);
            notify((int) $b['provider_id'], 'booking', 'Reminder: job tomorrow',
                '“' . $b['title'] . '” for ' . $b['c_first'] . ' · ' . $when, '/bookings/' . $b['id']);

            Mailer::send((string) $b['c_email'], 'Reminder: ' . $b['p_first'] . ' is coming tomorrow',
                "Hi {$b['c_first']},\n\nA quick reminder about your booking:\n\n  {$b['title']}\n  {$when}\n  " . ($where ?: 'Address to be confirmed')
                . "\n  Pro: {$b['p_first']} {$b['p_last']}" . ($b['p_phone'] ? " ({$b['p_phone']})" : '')
                . "\n\nNeed to change something? Open the booking to message {$b['p_first']} or propose a new time:\n$link\n\nServease");
            Mailer::send((string) $b['p_email'], 'Reminder: job tomorrow for ' . $b['c_first'],
                "Hi {$b['p_first']},\n\nYou have a job tomorrow:\n\n  {$b['title']}\n  {$when}\n  " . ($where ?: 'Address to be confirmed')
                . "\n\nOpen the booking for the details and to chat with {$b['c_first']}:\n$link\n\nServease");

            DB::update('bookings', ['reminder_sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $b['id']]);
        }
        return count($rows);
    }
}
