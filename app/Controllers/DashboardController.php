<?php
declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        $u = require_auth();
        if ($u['role'] === 'admin') redirect('/admin');
        $u['role'] === 'provider' ? $this->provider($u) : $this->client($u);
    }

    private function client(array $u): void
    {
        $id = (int) $u['id'];
        $stats = [
            'active'    => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status IN ('requested','accepted','in_progress')", [$id]),
            'open_jobs' => (int) DB::value("SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status = 'open'", [$id]),
            'completed' => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status = 'completed'", [$id]),
            'spent'     => (float) DB::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = ? AND status = 'paid'", [$id]),
        ];
        $upcoming = DB::all(
            "SELECT b.*, p.id AS o_id, p.first_name AS o_first, p.last_name AS o_last, p.avatar AS o_avatar
             FROM bookings b JOIN users p ON p.id = b.provider_id
             WHERE b.client_id = ? AND b.status IN ('requested','accepted','in_progress')
             ORDER BY b.scheduled_date IS NULL, b.scheduled_date ASC LIMIT 5",
            [$id]
        );
        $toReview = DB::all(
            "SELECT b.id, b.title, p.first_name AS p_first FROM bookings b JOIN users p ON p.id = b.provider_id
             LEFT JOIN reviews r ON r.booking_id = b.id
             WHERE b.client_id = ? AND b.status = 'completed' AND r.id IS NULL ORDER BY b.completed_at DESC LIMIT 3",
            [$id]
        );
        $toPay = DB::all(
            "SELECT b.id, b.title, b.amount, p.first_name AS p_first FROM bookings b JOIN users p ON p.id = b.provider_id
             WHERE b.client_id = ? AND b.status = 'completed' AND b.payment_status = 'unpaid' ORDER BY b.completed_at DESC LIMIT 3",
            [$id]
        );
        $jobs = DB::all(
            "SELECT j.*, c.name AS category_name, c.icon AS category_icon, c.slug AS category_slug FROM jobs j
             LEFT JOIN categories c ON c.id = j.category_id WHERE j.client_id = ? AND j.status = 'open' ORDER BY j.created_at DESC LIMIT 3",
            [$id]
        );
        $recommended = ProviderController::query(['city' => $u['city'] ?? '', 'verified' => '1', 'sort' => 'recommended'], 3, 0)['rows'];
        if (!$recommended) $recommended = ProviderController::query(['verified' => '1', 'sort' => 'recommended'], 3, 0)['rows'];

        view('dashboard/client', compact('stats', 'upcoming', 'toReview', 'toPay', 'jobs', 'recommended')
            + ['title' => 'Dashboard', 'active' => 'dashboard'], 'layouts/shell');
    }

    private function provider(array $u): void
    {
        $id = (int) $u['id'];
        $profile = DB::one('SELECT pp.*, c.name AS category_name, c.slug AS category_slug FROM provider_profiles pp
                            LEFT JOIN categories c ON c.id = pp.category_id WHERE pp.user_id = ?', [$id]) ?? [];
        $monthStart = date('Y-m-01 00:00:00');
        $stats = [
            'requests'  => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'requested'", [$id]),
            'upcoming'  => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status IN ('accepted','in_progress')", [$id]),
            'earned'    => (float) DB::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payee_id = ? AND status = 'paid' AND paid_at >= ?", [$id, $monthStart]),
            'earned_all'=> (float) DB::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payee_id = ? AND status = 'paid'", [$id]),
            'rating'    => (float) ($profile['rating_avg'] ?? 0),
            'reviews'   => (int) ($profile['rating_count'] ?? 0),
        ];
        $requests = DB::all(
            "SELECT b.*, c.id AS o_id, c.first_name AS o_first, c.last_name AS o_last, c.avatar AS o_avatar
             FROM bookings b JOIN users c ON c.id = b.client_id
             WHERE b.provider_id = ? AND b.status IN ('requested','accepted','in_progress')
             ORDER BY FIELD(b.status,'requested','in_progress','accepted'), b.scheduled_date ASC LIMIT 6",
            [$id]
        );
        $pendingPayments = DB::all(
            "SELECT p.*, b.title FROM payments p JOIN bookings b ON b.id = p.booking_id
             WHERE p.payee_id = ? AND p.status = 'pending' AND p.channel = 'direct' ORDER BY p.created_at DESC LIMIT 3",
            [$id]
        );
        $jobs = [];
        if (!empty($profile['category_id'])) {
            $jobs = DB::all(
                "SELECT j.*, c.name AS category_name, c.icon AS category_icon, c.slug AS category_slug FROM jobs j
                 LEFT JOIN categories c ON c.id = j.category_id
                 WHERE j.status = 'open' AND j.category_id = ? AND j.client_id <> ?
                   AND NOT EXISTS (SELECT 1 FROM quotes q WHERE q.job_id = j.id AND q.provider_id = ?)
                 ORDER BY j.created_at DESC LIMIT 3",
                [(int) $profile['category_id'], $id, $id]
            );
        }
        $reviews = DB::all(
            "SELECT r.*, u.first_name, u.last_name, u.avatar, u.id AS client_uid FROM reviews r JOIN users u ON u.id = r.client_id
             WHERE r.provider_id = ? AND r.is_hidden = 0 ORDER BY r.created_at DESC LIMIT 3",
            [$id]
        );
        // Earnings for the last 6 months (for the mini chart)
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("first day of -$i month"));
            $end = date('Y-m-01', strtotime("first day of -" . ($i - 1) . " month"));
            $months[] = [
                'label' => date('M', strtotime($start)),
                'value' => (float) DB::value("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payee_id = ? AND status = 'paid' AND paid_at >= ? AND paid_at < ?", [$id, $start, $end]),
            ];
        }
        $checklist = [
            ['Add a profile photo', !empty($u['avatar']), '/settings'],
            ['Write a headline and bio', !empty($profile['headline']) && mb_strlen((string) ($profile['bio'] ?? '')) >= 60, '/provider/profile'],
            ['List at least one service with a price', (bool) DB::value('SELECT 1 FROM provider_services WHERE provider_id = ? LIMIT 1', [$id]), '/provider/profile#services'],
            ['Upload portfolio photos', (bool) DB::value('SELECT 1 FROM portfolio_items WHERE provider_id = ? LIMIT 1', [$id]), '/provider/profile#portfolio'],
            ['Get verified', ($profile['verification_status'] ?? '') === 'verified', '/provider/profile#verification'],
        ];

        view('dashboard/provider', compact('profile', 'stats', 'requests', 'pendingPayments', 'jobs', 'reviews', 'months', 'checklist')
            + ['title' => 'Dashboard', 'active' => 'dashboard'], 'layouts/shell');
    }
}
