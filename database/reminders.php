<?php
/**
 * Sends day-before booking reminders (notifications + email).
 *   php database/reminders.php [--url=http://localhost/Servease]
 *
 * Schedule it hourly so reminders go out on time, for example:
 *   Windows Task Scheduler:  C:\xampp\php\php.exe C:\xampp\htdocs\Servease\database\reminders.php
 *   Linux cron:              0 * * * * php /var/www/servease/database/reminders.php
 * Without a schedule, the site still sends them during normal visits (at most once an hour).
 */
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

// Links in emails need the site address; the command line has no web request to read it from
if ((string) config('app.url', '') === '') {
    $url = 'http://localhost/Servease';
    foreach ($argv as $arg) if (str_starts_with($arg, '--url=')) $url = substr($arg, 6);
    $GLOBALS['__config']['app']['url'] = rtrim($url, '/');
}
Migrator::run();
$n = Reminders::send();
@touch(ROOT . '/storage/cache/reminders_last_run');
echo "Sent reminders for $n booking" . ($n === 1 ? '' : 's') . ".\n";
