<?php
declare(strict_types=1);

/**
 * "Report a problem" on a booking: no-shows, poor work, payment disputes, damage or safety.
 * The report goes to the admins, who resolve it with a note that both sides can see.
 */
final class IssueController
{
    public const REASONS = [
        'no_show' => 'The other person didn\'t show up',
        'quality' => 'The work wasn\'t done properly',
        'payment' => 'A problem with payment',
        'damage'  => 'Something was damaged or lost',
        'safety'  => 'I felt unsafe or was treated badly',
        'other'   => 'Something else',
    ];

    /** Bookings that can be reported: anything past the request stage. */
    public const REPORTABLE = ['accepted', 'in_progress', 'completed', 'cancelled'];

    public function store(string $id): void
    {
        $u = require_auth();
        $b = BookingController::find((int) $id, $u);
        $isParty = in_array((int) $u['id'], [(int) $b['client_id'], (int) $b['provider_id']], true);
        if (!$isParty) abort(403);
        if (!in_array($b['status'], self::REPORTABLE, true)) {
            flash('error', 'You can report a problem once the booking is confirmed.');
            redirect('/bookings/' . $id);
        }
        if (DB::value("SELECT 1 FROM booking_issues WHERE booking_id = ? AND reporter_id = ? AND status = 'open'", [(int) $id, (int) $u['id']])) {
            flash('info', 'You already have an open report for this booking. Our team will get back to you.');
            redirect('/bookings/' . $id);
        }
        $v = Validator::make($_POST, [
            'reason'  => 'required|in:' . implode(',', array_keys(self::REASONS)),
            'details' => 'required|min:20|max:2000|string',
        ], ['reason' => 'reason', 'details' => 'Details']);
        if ($v->fails()) back_with_errors($v->errors(), '/bookings/' . $id . '#report');
        $d = $v->validated();

        DB::transaction(function () use ($b, $u, $d) {
            DB::insert('booking_issues', ['booking_id' => (int) $b['id'], 'reporter_id' => (int) $u['id'], 'reason' => $d['reason'], 'details' => $d['details']]);
            DB::insert('booking_events', ['booking_id' => (int) $b['id'], 'actor_id' => (int) $u['id'], 'event' => 'issue_reported', 'note' => self::REASONS[$d['reason']]]);
        });
        foreach (DB::all("SELECT id FROM users WHERE role = 'admin' AND status = 'active'") as $a) {
            notify((int) $a['id'], 'booking', 'Problem reported', $u['first_name'] . ' reported a problem with “' . $b['title'] . '”.', '/admin/issues');
        }
        flash('success', 'Thanks for telling us. Our team will look into it and reply here, usually within a day.');
        redirect('/bookings/' . $b['id']);
    }
}
