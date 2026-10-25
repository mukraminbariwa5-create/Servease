<?php
declare(strict_types=1);

final class BookingController
{
    /** Which role may move a booking from one status to another. */
    private const TRANSITIONS = [
        'accept'   => ['from' => ['requested'], 'to' => 'accepted', 'by' => 'provider'],
        'decline'  => ['from' => ['requested'], 'to' => 'declined', 'by' => 'provider'],
        'start'    => ['from' => ['accepted'], 'to' => 'in_progress', 'by' => 'provider'],
        'complete' => ['from' => ['accepted', 'in_progress'], 'to' => 'completed', 'by' => 'provider'],
        'cancel'   => ['from' => ['requested', 'accepted'], 'to' => 'cancelled', 'by' => 'either'],
    ];

    /** Why a booking is cancelled or declined. Asked every time so both sides (and admins) know what happened. */
    public const CANCEL_REASONS = [
        'client' => ['plans' => 'My plans changed', 'found' => 'I found another pro', 'price' => 'The price doesn\'t work for me',
                     'asked' => 'The pro asked me to cancel', 'other' => 'Something else'],
        'provider' => ['unavailable' => 'I can\'t make that time anymore', 'area' => 'It\'s outside my service area',
                       'scope' => 'The job is outside what I do', 'asked' => 'The customer asked me to cancel',
                       'emergency' => 'Personal emergency', 'other' => 'Something else'],
        'decline' => ['booked' => 'I\'m fully booked then', 'area' => 'It\'s outside my service area',
                      'scope' => 'The job is outside what I do', 'other' => 'Something else'],
    ];

    public const TIMES = ['08:00' => '8:00 AM', '09:00' => '9:00 AM', '10:00' => '10:00 AM', '11:00' => '11:00 AM',
                          '13:00' => '1:00 PM', '14:00' => '2:00 PM', '15:00' => '3:00 PM', '16:00' => '4:00 PM', '17:00' => '5:00 PM'];

    /** Load a booking the current user is part of (or any booking for admins). */
    public static function find(int $id, array $u): array
    {
        $b = DB::one(
            "SELECT b.*, c.first_name AS c_first, c.last_name AS c_last, c.avatar AS c_avatar, c.phone AS c_phone, c.email AS c_email,
                    p.first_name AS p_first, p.last_name AS p_last, p.avatar AS p_avatar, p.phone AS p_phone, p.email AS p_email,
                    pp.headline AS p_headline, pp.verification_status AS p_verified, cat.name AS category_name
             FROM bookings b
             JOIN users c ON c.id = b.client_id
             JOIN users p ON p.id = b.provider_id
             LEFT JOIN provider_profiles pp ON pp.user_id = p.id
             LEFT JOIN categories cat ON cat.id = pp.category_id
             WHERE b.id = ?",
            [$id]
        );
        if (!$b) abort(404, 'Booking not found.');
        $uid = (int) $u['id'];
        if ($u['role'] !== 'admin' && $uid !== (int) $b['client_id'] && $uid !== (int) $b['provider_id']) abort(404, 'Booking not found.');
        return $b;
    }

    /** True once the booked day has arrived (or when no date was set). */
    public static function dayHasCome(array $b): bool
    {
        return empty($b['scheduled_date']) || $b['scheduled_date'] <= date('Y-m-d');
    }

    public function create(string $providerId): void
    {
        $u = user();
        if (!$u) {
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? url('/providers/' . $providerId);
            flash('info', 'Sign in or create a free account to book this pro.');
            redirect('/login');
        }
        if ($u['role'] !== 'client') abort(403, 'Only client accounts can book pros.');
        $p = $this->provider((int) $providerId);
        $services = DB::all('SELECT * FROM provider_services WHERE provider_id = ? ORDER BY price', [(int) $providerId]);
        $dates = Availability::openDates($p);
        view('bookings/create', [
            'title' => 'Book ' . $p['first_name'], 'p' => $p, 'services' => $services,
            'dates' => $dates,
            'busy' => $dates ? Availability::busy((int) $providerId, $dates[0], end($dates)) : [],
            'serviceId' => (int) ($_GET['service'] ?? 0),
        ]);
    }

    private function provider(int $id): array
    {
        return self::findProvider($id);
    }

    /** An active pro with their profile and category, or a 404. */
    public static function findProvider(int $id): array
    {
        $p = DB::one(
            "SELECT u.*, pp.*, c.name AS category_name, c.icon AS category_icon, c.slug AS category_slug FROM users u JOIN provider_profiles pp ON pp.user_id = u.id
             LEFT JOIN categories c ON c.id = pp.category_id WHERE u.id = ? AND u.role = 'provider' AND u.status = 'active'",
            [$id]
        );
        if (!$p) abort(404, 'This pro could not be found.');
        $p['id'] = $id;
        return $p;
    }

    public function store(string $providerId): void
    {
        $u = require_role('client');
        $p = $this->provider((int) $providerId);
        $v = Validator::make($_POST, [
            'service_id'     => 'nullable|int',
            'title'          => 'required|min:5|max:140|string',
            'details'        => 'required|min:10|max:3000|string',
            'scheduled_date' => 'required|date|after_today',
            'scheduled_time' => 'required|in:' . implode(',', array_keys(self::TIMES)),
            'hours'          => 'nullable|int|min:1|max:' . Availability::MAX_HOURS,
            'address'        => 'required|min:5|max:255|string',
            'city'           => 'required|max:80',
            'amount'         => 'nullable|numeric|min:50|max:10000000',
        ], ['scheduled_date' => 'date', 'scheduled_time' => 'time', 'amount' => 'budget']);
        $errors = $v->fails() ? $v->errors() : [];

        $service = null;
        if (!empty($_POST['service_id'])) {
            $service = DB::one('SELECT * FROM provider_services WHERE id = ? AND provider_id = ?', [(int) $_POST['service_id'], (int) $providerId]) ?: null;
        }
        $hours = (int) ($_POST['hours'] ?? 2) ?: 2;
        $duration = Availability::duration($service, $hours, $p);

        // The price follows the service's price type; only open-ended jobs take the customer's number
        $posted = is_numeric($_POST['amount'] ?? null) ? (float) $_POST['amount'] : 0.0;
        $unit = $service['price_unit'] ?? null;
        $amount = match ($unit) {
            'fixed', 'day' => (float) $service['price'],
            'hour'         => (float) $service['price'] * $duration,
            default        => $posted,
        };
        if (!isset($errors['amount'])) {
            if ($unit === 'starting' && $amount < (float) $service['price']) {
                $errors['amount'] = 'This service starts at ' . money($service['price']) . '. Enter at least that.';
            } elseif ($unit === null && $amount < 50) {
                $errors['amount'] = 'Enter your budget for this job.';
            }
        }

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
        if ($errors) back_with_errors($errors, '/providers/' . $providerId . '/book');
        $d = $v->validated();
        $d['amount'] = $amount;
        $serviceId = $service ? (int) $service['id'] : null;

        $id = DB::transaction(function () use ($u, $p, $d, $serviceId, $duration) {
            $id = DB::insert('bookings', [
                'client_id' => (int) $u['id'], 'provider_id' => (int) $p['id'], 'service_id' => $serviceId,
                'title' => $d['title'], 'details' => $d['details'],
                'scheduled_date' => $d['scheduled_date'], 'scheduled_time' => $d['scheduled_time'], 'duration_hours' => $duration,
                'address' => $d['address'], 'city' => $d['city'], 'amount' => (float) $d['amount'],
            ]);
            DB::insert('booking_events', ['booking_id' => $id, 'actor_id' => (int) $u['id'], 'event' => 'requested']);
            return $id;
        });
        notify((int) $p['id'], 'booking', 'New booking request',
            $u['first_name'] . ' requested “' . $d['title'] . '” on ' . fmt_date($d['scheduled_date'], 'M j') . '.', '/bookings/' . $id);
        flash('success', 'Request sent! ' . $p['first_name'] . ' will confirm or suggest changes soon.');
        redirect('/bookings/' . $id);
    }

    public function index(): void
    {
        $u = require_auth();
        $isPro = $u['role'] === 'provider';
        $col = $isPro ? 'b.provider_id' : 'b.client_id';
        $tabs = [
            'active'    => ['Active', "b.status IN ('requested','accepted','in_progress')"],
            'completed' => ['Completed', "b.status = 'completed'"],
            'closed'    => ['Cancelled', "b.status IN ('cancelled','declined')"],
        ];
        $tab = array_key_exists($_GET['tab'] ?? '', $tabs) ? $_GET['tab'] : 'active';
        $counts = [];
        foreach ($tabs as $k => [, $cond]) {
            $counts[$k] = (int) DB::value("SELECT COUNT(*) FROM bookings b WHERE $col = ? AND $cond", [(int) $u['id']]);
        }
        $other = $isPro ? 'b.client_id' : 'b.provider_id';
        $rows = DB::all(
            "SELECT b.*, o.id AS o_id, o.first_name AS o_first, o.last_name AS o_last, o.avatar AS o_avatar
             FROM bookings b JOIN users o ON o.id = $other
             WHERE $col = ? AND {$tabs[$tab][1]}
             ORDER BY FIELD(b.status, 'requested', 'in_progress', 'accepted', 'completed', 'cancelled', 'declined'), b.scheduled_date DESC, b.created_at DESC",
            [(int) $u['id']]
        );
        view('bookings/index', ['title' => 'Bookings', 'rows' => $rows, 'tab' => $tab, 'tabs' => $tabs, 'counts' => $counts, 'isPro' => $isPro, 'active' => 'bookings'], 'layouts/shell');
    }

    public function show(string $id): void
    {
        $u = require_auth();
        $b = self::find((int) $id, $u);
        $events = DB::all('SELECT e.*, u.first_name FROM booking_events e LEFT JOIN users u ON u.id = e.actor_id WHERE e.booking_id = ? ORDER BY e.created_at, e.id', [(int) $id]);
        $payments = DB::all('SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC', [(int) $id]);
        $review = DB::one('SELECT * FROM reviews WHERE booking_id = ?', [(int) $id]);
        $issues = $u['role'] === 'admin'
            ? DB::all('SELECT * FROM booking_issues WHERE booking_id = ? ORDER BY created_at DESC', [(int) $id])
            : DB::all('SELECT * FROM booking_issues WHERE booking_id = ? AND reporter_id = ? ORDER BY created_at DESC', [(int) $id, (int) $u['id']]);
        view('bookings/show', [
            'title' => $b['title'], 'b' => $b, 'events' => $events, 'payments' => $payments, 'review' => $review, 'issues' => $issues,
            'isClient' => (int) $u['id'] === (int) $b['client_id'], 'isPro' => (int) $u['id'] === (int) $b['provider_id'],
            'active' => 'bookings', 'wide' => true,
        ], 'layouts/shell');
    }

    public function transition(string $id): void
    {
        $u = require_auth();
        $b = self::find((int) $id, $u);
        $action = (string) ($_POST['action'] ?? '');
        $rule = self::TRANSITIONS[$action] ?? null;
        if (!$rule) abort(400);

        $isClient = (int) $u['id'] === (int) $b['client_id'];
        $isPro = (int) $u['id'] === (int) $b['provider_id'];
        $allowed = $rule['by'] === 'either' ? ($isClient || $isPro) : ($rule['by'] === 'provider' ? $isPro : $isClient);
        if (!$allowed || !in_array($b['status'], $rule['from'], true)) {
            flash('error', 'That action is not available for this booking right now.');
            redirect('/bookings/' . $id);
        }

        // Work can't start or finish before the day it's booked for
        if (in_array($action, ['start', 'complete'], true) && !self::dayHasCome($b)) {
            flash('error', 'This job is booked for ' . fmt_date($b['scheduled_date'], 'l, M j') . '. You can start and complete it from that day. Need another day? Propose a new time.');
            redirect('/bookings/' . $id);
        }

        $note = trim((string) ($_POST['note'] ?? ''));
        $note = $note === '' ? null : mb_substr($note, 0, 255);
        if (in_array($action, ['cancel', 'decline'], true)) {
            $list = self::CANCEL_REASONS[$action === 'decline' ? 'decline' : ($isPro ? 'provider' : 'client')];
            $key = (string) ($_POST['reason'] ?? '');
            if (!isset($list[$key])) back_with_errors(['reason' => 'Choose a reason so ' . ($isPro ? $b['c_first'] : $b['p_first']) . ' knows what happened.'], '/bookings/' . $id);
            if ($key === 'other' && mb_strlen((string) $note) < 5) back_with_errors(['note' => 'Tell them a little more (at least 5 characters).'], '/bookings/' . $id);
            $note = mb_substr($list[$key] . ($note ? ': ' . $note : ''), 0, 255);
        }
        $now = date('Y-m-d H:i:s');
        $update = ['status' => $rule['to']];
        if ($action === 'accept') {
            $update['accepted_at'] = $now;
            $amount = (float) ($_POST['amount'] ?? 0);
            if ($amount >= 50 && $amount <= 10000000) $update['amount'] = $amount;
        }
        if ($action === 'start') $update['started_at'] = $now;
        if ($action === 'complete') { $update['completed_at'] = $now; if (!$b['started_at']) $update['started_at'] = $now; }
        if (in_array($action, ['cancel', 'decline'], true)) $update['cancel_reason'] = $note;

        DB::transaction(function () use ($b, $u, $update, $action, $rule, $note) {
            DB::update('bookings', $update, 'id = :id', ['id' => (int) $b['id']]);
            DB::insert('booking_events', ['booking_id' => (int) $b['id'], 'actor_id' => (int) $u['id'], 'event' => $rule['to'], 'note' => $note]);
            if ($action === 'complete') {
                DB::query('UPDATE provider_profiles SET jobs_completed = jobs_completed + 1 WHERE user_id = ?', [(int) $b['provider_id']]);
                if ($b['job_id']) DB::update('jobs', ['status' => 'completed'], 'id = :id', ['id' => (int) $b['job_id']]);
            }
            if (in_array($action, ['cancel', 'decline'], true) && $b['job_id']) {
                // Re-open the job so the client can pick another quote
                DB::update('jobs', ['status' => 'open'], 'id = :id', ['id' => (int) $b['job_id']]);
                if ($b['quote_id']) DB::update('quotes', ['status' => 'declined'], 'id = :id', ['id' => (int) $b['quote_id']]);
            }
        });

        $isClient = (int) $u['id'] === (int) $b['client_id'];
        $otherId = $isClient ? (int) $b['provider_id'] : (int) $b['client_id'];
        $messages = [
            'accept'   => ['Booking confirmed', "{$u['first_name']} confirmed “{$b['title']}”.", 'Booking confirmed. The client has been notified.'],
            'decline'  => ['Booking declined', "{$u['first_name']} can't take “{$b['title']}”.", 'Booking declined.'],
            'start'    => ['Work has started', "{$u['first_name']} started work on “{$b['title']}”.", 'Marked as in progress.'],
            'complete' => ['Job completed', "{$u['first_name']} marked “{$b['title']}” as completed. Leave a review!", 'Marked as completed. Nice work!'],
            'cancel'   => ['Booking cancelled', "{$u['first_name']} cancelled “{$b['title']}”.", 'Booking cancelled.'],
        ][$action];
        notify($otherId, 'booking', $messages[0], $messages[1], '/bookings/' . $b['id']);
        flash('success', $messages[2]);
        redirect('/bookings/' . $b['id']);
    }
}
