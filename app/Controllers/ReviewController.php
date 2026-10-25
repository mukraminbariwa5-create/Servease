<?php
declare(strict_types=1);

final class ReviewController
{
    public function store(string $bookingId): void
    {
        $u = require_role('client');
        $b = BookingController::find((int) $bookingId, $u);
        if ((int) $b['client_id'] !== (int) $u['id'] || $b['status'] !== 'completed') {
            flash('error', 'You can review a pro after the booking is completed.');
            redirect('/bookings/' . $bookingId);
        }
        if (DB::value('SELECT 1 FROM reviews WHERE booking_id = ?', [(int) $bookingId])) {
            flash('info', 'You already reviewed this booking.');
            redirect('/bookings/' . $bookingId);
        }
        $v = Validator::make($_POST, [
            'rating'  => 'required|int|min:1|max:5',
            'comment' => 'nullable|max:2000|string',
        ]);
        if ($v->fails()) back_with_errors($v->errors(), '/bookings/' . $bookingId);
        $d = $v->validated();

        DB::insert('reviews', [
            'booking_id' => (int) $b['id'], 'provider_id' => (int) $b['provider_id'], 'client_id' => (int) $u['id'],
            'rating' => (int) $d['rating'], 'comment' => $d['comment'],
        ]);
        self::recompute((int) $b['provider_id']);
        notify((int) $b['provider_id'], 'review', 'New ' . (int) $d['rating'] . '-star review',
            $u['first_name'] . ' reviewed “' . $b['title'] . '”.', '/providers/' . $b['provider_id'] . '#reviews');
        flash('success', 'Thanks for your review! It helps other clients choose.');
        redirect('/bookings/' . $bookingId);
    }

    public function reply(string $id): void
    {
        $u = require_role('provider');
        $r = DB::one('SELECT * FROM reviews WHERE id = ? AND provider_id = ?', [(int) $id, (int) $u['id']]);
        if (!$r) abort(404);
        $reply = trim((string) ($_POST['reply'] ?? ''));
        if ($reply === '' || mb_strlen($reply) > 500) {
            flash('error', 'Replies must be between 1 and 500 characters.');
            back('/providers/' . $u['id']);
        }
        if (!$r['provider_reply']) {
            DB::update('reviews', ['provider_reply' => $reply], 'id = :id', ['id' => (int) $id]);
            flash('success', 'Your reply is posted.');
        }
        $return = (string) ($_POST['return'] ?? '');
        redirect(match (true) {
            $return === 'dashboard' => '/dashboard#reviews',
            $return === 'booking'   => '/bookings/' . (int) $r['booking_id'] . '#review',
            default                 => '/providers/' . $u['id'] . '#reviews',
        });
    }

    /** Keep the denormalised rating figures on provider_profiles in sync. */
    public static function recompute(int $providerId): void
    {
        $row = DB::one('SELECT COUNT(*) n, COALESCE(AVG(rating), 0) a FROM reviews WHERE provider_id = ? AND is_hidden = 0', [$providerId]);
        DB::update('provider_profiles', ['rating_avg' => round((float) $row['a'], 2), 'rating_count' => (int) $row['n']],
            'user_id = :id', ['id' => $providerId]);
    }
}
