<?php
declare(strict_types=1);

/**
 * Moving a booking to another day or time. Either side proposes a new slot (only times the pro
 * is free), the other side accepts or declines, and the proposer can withdraw. Nothing changes
 * until the other side accepts, and the slot is checked again at that moment.
 */
final class RescheduleController
{
    /** Bookings that can still move. */
    private const OPEN = ['requested', 'accepted'];

    private function load(string $id): array
    {
        $u = require_auth();
        $b = BookingController::find((int) $id, $u);
        $isClient = (int) $u['id'] === (int) $b['client_id'];
        $isPro = (int) $u['id'] === (int) $b['provider_id'];
        if (!$isClient && !$isPro) abort(403, 'Only the customer and the pro can change the schedule.');
        return [$u, $b, $isClient, $isPro];
    }

    public function form(string $id): void
    {
        [$u, $b, $isClient, $isPro] = $this->load($id);
        if (!in_array($b['status'], self::OPEN, true)) {
            flash('error', 'This booking can no longer be rescheduled.');
            redirect('/bookings/' . $id);
        }
        $p = BookingController::findProvider((int) $b['provider_id']);
        $dates = Availability::openDates($p);
        $busy = $dates ? Availability::busy((int) $p['id'], $dates[0], end($dates), (int) $b['id']) : [];
        view('bookings/reschedule', [
            'title' => 'Propose a new time', 'b' => $b, 'p' => $p, 'dates' => $dates, 'busy' => $busy,
            'isPro' => $isPro, 'active' => 'bookings',
        ], 'layouts/shell');
    }

    public function propose(string $id): void
    {
        [$u, $b, $isClient, $isPro] = $this->load($id);
        if (!in_array($b['status'], self::OPEN, true)) {
            flash('error', 'This booking can no longer be rescheduled.');
            redirect('/bookings/' . $id);
        }
        $p = BookingController::findProvider((int) $b['provider_id']);
        $back = '/bookings/' . $id . '/reschedule';
        $v = Validator::make($_POST, [
            'scheduled_date' => 'required|date|after_today',
            'scheduled_time' => 'required|in:' . implode(',', array_keys(BookingController::TIMES)),
            'note'           => 'nullable|max:255|string',
        ], ['scheduled_date' => 'Date', 'scheduled_time' => 'Time']);
        $errors = $v->fails() ? $v->errors() : [];
        $date = (string) ($_POST['scheduled_date'] ?? '');
        $time = (string) ($_POST['scheduled_time'] ?? '');
        if (!$errors && $date === $b['scheduled_date'] && $time === $b['scheduled_time']) {
            $errors['scheduled_time'] = 'That\'s the current time. Pick a different day or time.';
        }
        if (!$errors) {
            $why = self::slotProblem($p, $b, $date, $time);
            if ($why) $errors[$why[0]] = $why[1];
        }
        if ($errors) back_with_errors($errors, $back);
        $note = $v->validated()['note'] ?? null;

        DB::transaction(function () use ($b, $u, $date, $time, $note) {
            DB::update('bookings', ['proposed_date' => $date, 'proposed_time' => $time, 'proposed_by' => (int) $u['id'], 'proposed_note' => $note],
                'id = :id', ['id' => (int) $b['id']]);
            DB::insert('booking_events', ['booking_id' => (int) $b['id'], 'actor_id' => (int) $u['id'], 'event' => 'reschedule_proposed',
                'note' => self::slotText($date, $time, (int) $b['duration_hours']) . ($note ? ' · “' . $note . '”' : '')]);
        });
        $otherId = $isPro ? (int) $b['client_id'] : (int) $b['provider_id'];
        notify($otherId, 'booking', 'New time proposed',
            $u['first_name'] . ' asked to move “' . $b['title'] . '” to ' . self::slotText($date, $time, (int) $b['duration_hours']) . '.', '/bookings/' . $b['id']);
        flash('success', 'New time sent. ' . ($isPro ? $b['c_first'] : $b['p_first']) . ' will be asked to accept it.');
        redirect('/bookings/' . $b['id']);
    }

    public function respond(string $id): void
    {
        [$u, $b, $isClient, $isPro] = $this->load($id);
        $answer = (string) ($_POST['answer'] ?? '');
        if (!$b['proposed_date'] || !in_array($b['status'], self::OPEN, true)) {
            flash('error', 'There is no new time waiting for an answer.');
            redirect('/bookings/' . $id);
        }
        $mine = (int) $b['proposed_by'] === (int) $u['id'];
        // The proposer can only withdraw; the other side accepts or declines
        if (($mine && $answer !== 'withdraw') || (!$mine && !in_array($answer, ['accept', 'decline'], true))) abort(400);

        $slot = self::slotText($b['proposed_date'], $b['proposed_time'], (int) $b['duration_hours']);
        $clear = ['proposed_date' => null, 'proposed_time' => null, 'proposed_by' => null, 'proposed_note' => null];
        $otherId = $isPro ? (int) $b['client_id'] : (int) $b['provider_id'];

        if ($answer === 'accept') {
            $p = BookingController::findProvider((int) $b['provider_id']);
            $why = self::slotProblem($p, $b, $b['proposed_date'], $b['proposed_time']);
            if ($why) {
                DB::update('bookings', $clear, 'id = :id', ['id' => (int) $b['id']]);
                flash('error', 'That time is no longer free: ' . $why[1] . ' Propose another time instead.');
                redirect('/bookings/' . $id);
            }
            DB::transaction(function () use ($b, $u, $clear, $slot) {
                DB::update('bookings', $clear + ['scheduled_date' => $b['proposed_date'], 'scheduled_time' => $b['proposed_time'], 'reminder_sent_at' => null],
                    'id = :id', ['id' => (int) $b['id']]);
                DB::insert('booking_events', ['booking_id' => (int) $b['id'], 'actor_id' => (int) $u['id'], 'event' => 'rescheduled', 'note' => $slot]);
            });
            notify($otherId, 'booking', 'New time accepted', '“' . $b['title'] . '” is now on ' . $slot . '.', '/bookings/' . $b['id']);
            flash('success', 'Done. The booking is now on ' . $slot . '.');
        } else {
            DB::transaction(function () use ($b, $u, $clear, $answer) {
                DB::update('bookings', $clear, 'id = :id', ['id' => (int) $b['id']]);
                DB::insert('booking_events', ['booking_id' => (int) $b['id'], 'actor_id' => (int) $u['id'],
                    'event' => $answer === 'withdraw' ? 'reschedule_withdrawn' : 'reschedule_declined']);
            });
            if ($answer === 'decline') {
                notify($otherId, 'booking', 'New time declined', $u['first_name'] . ' kept the original time for “' . $b['title'] . '”.', '/bookings/' . $b['id']);
            }
            flash('success', $answer === 'withdraw' ? 'Your proposal was withdrawn.' : 'You kept the original time.');
        }
        redirect('/bookings/' . $id);
    }

    /** [field, message] when a slot can't be used, or null when it's free. Ignores the booking itself. */
    private static function slotProblem(array $p, array $b, string $date, string $time): ?array
    {
        $duration = max(1, (int) $b['duration_hours']);
        if ($date < date('Y-m-d', strtotime('+1 day'))) return ['scheduled_date', 'Pick a day from tomorrow on.'];
        if (!Availability::worksOn($p, $date)) return ['scheduled_date', $p['first_name'] . ' isn\'t working that day.'];
        if (!isset(Availability::times($p, $duration)[$time])) {
            return ['scheduled_time', 'A ' . Availability::hoursText($duration) . ' job at that time would run past ' . $p['first_name'] . '\'s working hours.'];
        }
        $busy = Availability::busy((int) $p['id'], $date, $date, (int) $b['id'])[$date] ?? [];
        if (!Availability::fits($p, $time, $duration, $busy)) return ['scheduled_time', $p['first_name'] . ' is already booked during part of that time.'];
        return null;
    }

    /** "Tue, Oct 20 · 1:00 PM – 3:00 PM" */
    public static function slotText(?string $date, ?string $time, int $duration): string
    {
        return fmt_date($date, 'D, M j') . ($time ? ' · ' . Availability::range($time, $duration) : '');
    }
}
