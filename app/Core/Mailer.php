<?php
declare(strict_types=1);

final class Mailer
{
    /** Sends an email (or logs it to storage/logs/mail.log when driver = log). */
    public static function send(string $to, string $subject, string $text): bool
    {
        $driver = config('mail.driver', 'log');
        $from = config('mail.from_name', 'Servease') . ' <' . config('mail.from_email', 'no-reply@servease.test') . '>';

        if ($driver === 'mail') {
            $headers = "From: $from\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            $ok = @mail($to, $subject, $text, $headers);
            if ($ok) return true;
        }

        $entry = sprintf("[%s]\nTo: %s\nFrom: %s\nSubject: %s\n\n%s\n%s\n",
            date('c'), $to, $from, $subject, $text, str_repeat('-', 60));
        @file_put_contents(ROOT . '/storage/logs/mail.log', $entry, FILE_APPEND | LOCK_EX);
        return true;
    }

    /** In local mode we also show the link on screen so the flow can be tested without SMTP. */
    public static function devLink(string $label, string $link): void
    {
        if (config('app.env') === 'local') {
            $_SESSION['_flash'][] = [
                'type' => 'info',
                'html' => true,
                'message' => '<strong>Local mode:</strong> ' . e($label) . ' — <a href="' . e($link) . '">open link</a>',
            ];
        }
    }
}
