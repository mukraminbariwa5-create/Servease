<?php
declare(strict_types=1);

final class QuoteController
{
    public function store(string $jobId): void
    {
        $u = require_role('provider');
        $job = DB::one('SELECT * FROM jobs WHERE id = ?', [(int) $jobId]);
        if (!$job) abort(404);
        if ($job['status'] !== 'open') {
            flash('error', 'This job is no longer accepting quotes.');
            back('/jobs/' . $jobId);
        }
        if (DB::value('SELECT 1 FROM quotes WHERE job_id = ? AND provider_id = ?', [(int) $jobId, (int) $u['id']])) {
            flash('info', 'You already sent a quote for this job.');
            back('/jobs/' . $jobId);
        }
        $v = Validator::make($_POST, [
            'amount'      => 'required|numeric|min:50|max:10000000',
            'days_needed' => 'nullable|int|min:1|max:365',
            'message'     => 'required|min:20|max:2000|string',
        ], ['days_needed' => 'days needed']);
        if ($v->fails()) back_with_errors($v->errors(), '/jobs/' . $jobId);
        $d = $v->validated();

        DB::transaction(function () use ($job, $u, $d) {
            DB::insert('quotes', [
                'job_id' => (int) $job['id'], 'provider_id' => (int) $u['id'],
                'amount' => (float) $d['amount'], 'message' => $d['message'],
                'days_needed' => $d['days_needed'] !== null ? (int) $d['days_needed'] : null,
            ]);
            DB::query('UPDATE jobs SET quotes_count = quotes_count + 1 WHERE id = ?', [(int) $job['id']]);
        });
        notify((int) $job['client_id'], 'quote', 'New quote: ' . money($d['amount']),
            $u['first_name'] . ' sent a quote for “' . $job['title'] . '”.', '/jobs/' . $job['id']);
        flash('success', 'Quote sent! You\'ll be notified when the client responds.');
        redirect('/jobs/' . $job['id']);
    }

    public function mine(): void
    {
        $u = require_role('provider');
        $quotes = DB::all(
            "SELECT q.*, j.title, j.city, j.status AS job_status, j.client_id, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
                    (SELECT b.id FROM bookings b WHERE b.quote_id = q.id LIMIT 1) AS booking_id
             FROM quotes q JOIN jobs j ON j.id = q.job_id LEFT JOIN categories c ON c.id = j.category_id
             WHERE q.provider_id = ? ORDER BY q.created_at DESC",
            [(int) $u['id']]
        );
        view('quotes/mine', ['title' => 'My quotes', 'quotes' => $quotes, 'active' => 'quotes'], 'layouts/shell');
    }

    private function ownedQuote(string $id, array $u): array
    {
        $q = DB::one('SELECT q.*, j.client_id, j.title AS job_title, j.status AS job_status, j.description, j.city,
                             j.preferred_date, j.id AS job_id
                      FROM quotes q JOIN jobs j ON j.id = q.job_id WHERE q.id = ?', [(int) $id]);
        if (!$q || (int) $q['client_id'] !== (int) $u['id']) abort(404);
        return $q;
    }

    /** Step between "Accept quote" and the booking: pick a date and time the pro is free, and the address. */
    public function schedule(string $id): void
    {
        $u = require_role('client');
        $q = $this->ownedQuote($id, $u);
        if ($q['status'] !== 'pending' || $q['job_status'] !== 'open') {
            flash('error', 'This quote can no longer be accepted.');
            redirect('/jobs/' . $q['job_id']);
        }
        $p = BookingController::findProvider((int) $q['provider_id']);
        $dates = Availability::openDates($p);
        // Start on the customer's preferred date when the pro works that day
        $preferred = (string) ($q['preferred_date'] ?? '');
        view('quotes/accept', [
            'title' => 'Accept ' . $p['first_name'] . '\'s quote', 'q' => $q, 'p' => $p, 'dates' => $dates,
            'busy' => $dates ? Availability::busy((int) $p['id'], $dates[0], end($dates)) : [],
            'date' => (string) old('scheduled_date', in_array($preferred, $dates, true) ? $preferred : ($dates[0] ?? '')),
            'active' => 'jobs',
        ]);
    }

    public function accept(string $id): void
    {
        $u = require_role('client');
        $q = $this->ownedQuote($id, $u);
        if ($q['status'] !== 'pending' || $q['job_status'] !== 'open') {
            flash('error', 'This quote can no longer be accepted.');
            redirect('/jobs/' . $q['job_id']);
        }
        $p = BookingController::findProvider((int) $q['provider_id']);
        $back = '/quotes/' . $q['id'] . '/accept';
        $v = Validator::make($_POST, [
            'scheduled_date' => 'required|date|after_today',
            'scheduled_time' => 'required|in:' . implode(',', array_keys(BookingController::TIMES)),
            'hours'          => 'nullable|int|min:1|max:' . Availability::MAX_HOURS,
            'address'        => 'required|min:5|max:255|string',
            'city'           => 'required|max:80',
        ], ['scheduled_date' => 'Date', 'scheduled_time' => 'Time']);
        $errors = $v->fails() ? $v->errors() : [];
        $duration = max(1, min(Availability::MAX_HOURS, (int) ($_POST['hours'] ?? 2) ?: 2));
        $date = (string) ($_POST['scheduled_date'] ?? '');
        $time = (string) ($_POST['scheduled_time'] ?? '');
        if (!isset($errors['scheduled_date']) && !Availability::worksOn($p, $date)) {
            $errors['scheduled_date'] = $p['first_name'] . ' isn\'t working that day. Pick one of the open dates.';
        }
        if (!isset($errors['scheduled_date']) && !isset($errors['scheduled_time'])) {
            if (!isset(Availability::times($p, $duration)[$time])) {
                $errors['scheduled_time'] = 'A ' . Availability::hoursText($duration) . ' job at that time would run past ' . $p['first_name'] . '\'s working hours.';
            } elseif (!Availability::isFree($p, $date, $time, $duration)) {
                $errors['scheduled_time'] = $p['first_name'] . ' is already booked during part of that time. Please pick another.';
            }
        }
        if ($errors) back_with_errors($errors, $back);
        $d = $v->validated();

        $bookingId = DB::transaction(function () use ($q, $u, $d, $duration) {
            DB::update('quotes', ['status' => 'accepted'], 'id = :id', ['id' => (int) $q['id']]);
            $others = DB::all("SELECT provider_id FROM quotes WHERE job_id = ? AND id <> ? AND status = 'pending'", [(int) $q['job_id'], (int) $q['id']]);
            DB::query("UPDATE quotes SET status = 'declined' WHERE job_id = ? AND id <> ? AND status = 'pending'", [(int) $q['job_id'], (int) $q['id']]);
            DB::update('jobs', ['status' => 'assigned'], 'id = :id', ['id' => (int) $q['job_id']]);
            $now = date('Y-m-d H:i:s');
            $bid = DB::insert('bookings', [
                'client_id' => (int) $u['id'], 'provider_id' => (int) $q['provider_id'],
                'job_id' => (int) $q['job_id'], 'quote_id' => (int) $q['id'],
                'title' => $q['job_title'], 'details' => $q['description'],
                'scheduled_date' => $d['scheduled_date'], 'scheduled_time' => $d['scheduled_time'], 'duration_hours' => $duration,
                'address' => $d['address'], 'city' => $d['city'],
                'amount' => (float) $q['amount'], 'status' => 'accepted', 'accepted_at' => $now,
            ]);
            DB::insert('booking_events', ['booking_id' => $bid, 'actor_id' => (int) $u['id'], 'event' => 'requested', 'note' => 'Quote accepted']);
            DB::insert('booking_events', ['booking_id' => $bid, 'actor_id' => (int) $u['id'], 'event' => 'accepted']);
            foreach ($others as $o) {
                notify((int) $o['provider_id'], 'quote', 'Quote not selected', 'The client chose another pro for “' . $q['job_title'] . '”.', '/my/quotes');
            }
            return $bid;
        });
        notify((int) $q['provider_id'], 'booking', 'Your quote was accepted!',
            '“' . $q['job_title'] . '” is booked for ' . fmt_date($d['scheduled_date'], 'D, M j') . ' at ' . BookingController::TIMES[$d['scheduled_time']] . '.', '/bookings/' . $bookingId);
        flash('success', 'Quote accepted. ' . $p['first_name'] . ' is booked for ' . fmt_date($d['scheduled_date'], 'l, M j') . ' at ' . BookingController::TIMES[$d['scheduled_time']] . '.');
        redirect('/bookings/' . $bookingId);
    }

    public function decline(string $id): void
    {
        $u = require_role('client');
        $q = $this->ownedQuote($id, $u);
        if ($q['status'] === 'pending') {
            DB::update('quotes', ['status' => 'declined'], 'id = :id', ['id' => (int) $q['id']]);
            notify((int) $q['provider_id'], 'quote', 'Quote declined', 'The client declined your quote for “' . $q['job_title'] . '”.', '/my/quotes');
            flash('success', 'Quote declined.');
        }
        redirect('/jobs/' . $q['job_id']);
    }

    public function withdraw(string $id): void
    {
        $u = require_role('provider');
        $q = DB::one('SELECT * FROM quotes WHERE id = ? AND provider_id = ?', [(int) $id, (int) $u['id']]);
        if (!$q) abort(404);
        if ($q['status'] === 'pending') {
            DB::transaction(function () use ($q) {
                DB::update('quotes', ['status' => 'withdrawn'], 'id = :id', ['id' => (int) $q['id']]);
                DB::query('UPDATE jobs SET quotes_count = GREATEST(quotes_count - 1, 0) WHERE id = ?', [(int) $q['job_id']]);
            });
            flash('success', 'Your quote was withdrawn.');
        }
        back('/my/quotes');
    }
}
