<?php
declare(strict_types=1);

/**
 * A pro's working schedule and how long each job takes.
 *
 * - Weekly pattern: working weekdays plus start and end of day.
 * - Days off: single dates the pro blocks (holidays, sick days).
 * - A booking holds the pro from its start time for its duration, so a 3-hour job
 *   at 10:00 also closes 11:00 and 12:00. A new booking must fit inside working
 *   hours without overlapping any open booking.
 */
final class Availability
{
    /** ISO weekday (1 = Monday) => label */
    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    /** Hours a pro can pick for the start and end of the day. */
    public const HOURS = ['06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00',
                          '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];

    /** Longest job a customer can request by the hour. */
    public const MAX_HOURS = 8;

    public static function days(array $p): array
    {
        $days = array_values(array_filter(array_map('intval', explode(',', (string) ($p['work_days'] ?? ''))), fn($d) => $d >= 1 && $d <= 7));
        return $days ?: [1, 2, 3, 4, 5, 6];
    }

    public static function start(array $p): string { return substr((string) ($p['work_start'] ?? '08:00') ?: '08:00', 0, 5); }
    public static function end(array $p): string   { return substr((string) ($p['work_end'] ?? '18:00') ?: '18:00', 0, 5); }

    public static function hour(string $hhmm): int { return (int) substr($hhmm, 0, 2); }

    /** Length of a full working day, used for per-day services. */
    public static function dayLength(array $p): int { return max(1, self::hour(self::end($p)) - self::hour(self::start($p))); }

    /** Start times inside the pro's hours for a job of $duration hours. */
    public static function times(array $p, int $duration = 1): array
    {
        $s = self::hour(self::start($p)); $e = self::hour(self::end($p));
        return array_filter(BookingController::TIMES, fn($k) => self::hour($k) >= $s && self::hour($k) + $duration <= $e, ARRAY_FILTER_USE_KEY);
    }

    private static function pid(array $p): int { return (int) ($p['user_id'] ?? $p['id']); }

    /** @return string[] upcoming blocked dates, Y-m-d */
    public static function daysOff(int $providerId, ?string $from = null, ?string $to = null): array
    {
        return array_column(DB::all(
            'SELECT day FROM provider_days_off WHERE provider_id = ? AND day BETWEEN ? AND ? ORDER BY day',
            [$providerId, $from ?? date('Y-m-d'), $to ?? date('Y-m-d', strtotime('+1 year'))]
        ), 'day');
    }

    public static function worksOn(array $p, string $date, ?array $daysOff = null): bool
    {
        $ts = strtotime($date);
        if ($ts === false || !in_array((int) date('N', $ts), self::days($p), true)) return false;
        $daysOff ??= self::daysOff(self::pid($p), $date, $date);
        return !in_array($date, $daysOff, true);
    }

    /** The next $count open dates, starting tomorrow, looking at most 60 days ahead. */
    public static function openDates(array $p, int $count = 14): array
    {
        $off = self::daysOff(self::pid($p), date('Y-m-d'), date('Y-m-d', strtotime('+60 days')));
        $out = [];
        for ($i = 1; $i <= 60 && count($out) < $count; $i++) {
            $d = date('Y-m-d', strtotime("+$i days"));
            if (self::worksOn($p, $d, $off)) $out[] = $d;
        }
        return $out;
    }

    /** date => [[startHour, endHour], …] held by requested or confirmed bookings. */
    public static function busy(int $providerId, ?string $from = null, ?string $to = null, ?int $ignoreBooking = null): array
    {
        $rows = DB::all(
            "SELECT scheduled_date, scheduled_time, duration_hours FROM bookings
             WHERE provider_id = ? AND status IN ('requested','accepted','in_progress')
               AND scheduled_date BETWEEN ? AND ? AND scheduled_time IS NOT NULL AND id <> ?",
            [$providerId, $from ?? date('Y-m-d'), $to ?? date('Y-m-d', strtotime('+90 days')), $ignoreBooking ?? 0]
        );
        $out = [];
        foreach ($rows as $r) {
            $h = self::hour((string) $r['scheduled_time']);
            $out[$r['scheduled_date']][] = [$h, $h + max(1, (int) $r['duration_hours'])];
        }
        return $out;
    }

    /** Does a job at $time for $duration hours fit the day's hours and avoid every busy range? */
    public static function fits(array $p, string $time, int $duration, array $busyRanges): bool
    {
        if (!isset(self::times($p, $duration)[$time])) return false;
        $a = self::hour($time); $b = $a + $duration;
        foreach ($busyRanges as [$s, $e]) if ($a < $e && $s < $b) return false;
        return true;
    }

    public static function isFree(array $p, string $date, string $time, int $duration): bool
    {
        return self::fits($p, $time, $duration, self::busy(self::pid($p), $date, $date)[$date] ?? []);
    }

    /**
     * How long a booking takes: fixed and starting-price services carry their own duration,
     * hourly work and custom jobs use the hours the customer picked, and per-day work takes the whole day.
     */
    public static function duration(?array $service, int $hours, array $p): int
    {
        $hours = max(1, min(self::MAX_HOURS, $hours));
        return match ($service['price_unit'] ?? null) {
            'fixed', 'starting' => max(1, (int) $service['duration_hours']),
            'day'               => self::dayLength($p),
            default             => $hours,
        };
    }

    /** "Mon–Sat, 8:00 AM – 6:00 PM" */
    public static function summary(array $p): string
    {
        $days = self::days($p);
        sort($days);
        $contiguous = count($days) > 2 && $days === range($days[0], end($days));
        $dayText = count($days) === 7 ? 'Every day'
            : ($contiguous ? self::DAYS[$days[0]] . '–' . self::DAYS[end($days)] : implode(', ', array_map(fn($d) => self::DAYS[$d], $days)));
        return $dayText . ', ' . self::label(self::start($p)) . ' – ' . self::label(self::end($p));
    }

    public static function label(string $hhmm): string
    {
        return date('g:i A', strtotime('2000-01-01 ' . $hhmm));
    }

    /** "2 hours", "1 hour" */
    public static function hoursText(int $h): string { return $h . ' hour' . ($h === 1 ? '' : 's'); }

    /** "10:00 AM – 12:00 PM" for a booking */
    public static function range(?string $time, int $duration): string
    {
        if (!$time) return '';
        $start = self::hour($time);
        return self::label(sprintf('%02d:00', $start)) . ' – ' . self::label(sprintf('%02d:00', min(23, $start + max(1, $duration))));
    }
}
