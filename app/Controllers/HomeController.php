<?php
declare(strict_types=1);

final class HomeController
{
    public function index(): void
    {
        $categories = DB::all(
            "SELECT c.*, (SELECT COUNT(*) FROM provider_profiles pp JOIN users u ON u.id = pp.user_id
                          WHERE pp.category_id = c.id AND u.status = 'active') AS providers
             FROM categories c WHERE c.is_active = 1 ORDER BY c.sort_order, c.name"
        );
        $city = current_city();
        $featured = ProviderController::query(['sort' => 'recommended', 'verified' => '1', 'near' => $city], 8, 0)['rows'];
        $jobs = DB::all(
            "SELECT j.*, c.name AS category_name, c.icon AS category_icon, c.slug AS category_slug
             FROM jobs j LEFT JOIN categories c ON c.id = j.category_id
             WHERE j.status = 'open' ORDER BY j.created_at DESC LIMIT 4"
        );
        $stats = [
            'providers' => (int) DB::value("SELECT COUNT(*) FROM provider_profiles WHERE verification_status = 'verified'"),
            'completed' => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE status = 'completed'"),
            'rating'    => (float) DB::value('SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE is_hidden = 0'),
        ];
        $reviews = DB::all(
            "SELECT r.*, c.first_name, c.last_name, c.id AS client_uid, c.avatar, c.city,
                    p.first_name AS p_first, p.last_name AS p_last, cat.name AS category_name
             FROM reviews r
             JOIN users c ON c.id = r.client_id
             JOIN users p ON p.id = r.provider_id
             LEFT JOIN provider_profiles pp ON pp.user_id = p.id
             LEFT JOIN categories cat ON cat.id = pp.category_id
             WHERE r.is_hidden = 0 AND r.rating = 5 AND CHAR_LENGTH(r.comment) > 60
             ORDER BY r.created_at DESC LIMIT 3"
        );
        // Popular services: in the chosen city when there are enough, otherwise everywhere (that city first)
        $popular = ServiceController::query(['city' => $city, 'sort' => 'recommended'], 8, 0)['rows'];
        $popularInCity = count($popular) >= 4;
        if (!$popularInCity) {
            $popular = ServiceController::query(['near' => $city, 'sort' => 'recommended'], 8, 0)['rows'];
        }

        // Banner: the trade with the most priced services in this city, and where it starts
        $dealSql = "SELECT c.name, c.slug, MIN(s.price) AS from_price, COUNT(*) AS n
                    FROM provider_services s
                    JOIN users u ON u.id = s.provider_id AND u.status = 'active'
                    JOIN provider_profiles pp ON pp.user_id = u.id
                    JOIN categories c ON c.id = pp.category_id
                    WHERE s.price_unit IN ('fixed','starting') %s
                    GROUP BY c.id, c.name, c.slug ORDER BY n DESC, from_price ASC LIMIT 1";
        $deal = DB::one(sprintf($dealSql, 'AND pp.city = ?'), [$city]);
        $dealInCity = (bool) $deal;
        $deal = $deal ?: DB::one(sprintf($dealSql, ''));

        // Real numbers for the banner: ID-checked pros in this city, and how many work tomorrow
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $cityPros = (int) DB::value(
            "SELECT COUNT(*) FROM provider_profiles pp JOIN users u ON u.id = pp.user_id
             WHERE u.status = 'active' AND pp.verification_status = 'verified' AND pp.city = ?", [$city]);
        $freeTomorrow = (int) DB::value(
            "SELECT COUNT(*) FROM provider_profiles pp JOIN users u ON u.id = pp.user_id
             WHERE u.status = 'active' AND pp.is_available = 1 AND pp.city = ? AND FIND_IN_SET(?, pp.work_days) > 0
               AND NOT EXISTS (SELECT 1 FROM provider_days_off d WHERE d.provider_id = u.id AND d.day = ?)",
            [$city, date('N', strtotime($tomorrow)), $tomorrow]);
        // Quick searches: the most-offered services in this city, with their starting price
        $quick = DB::all(
            "SELECT s.title, MIN(s.price) AS from_price, COUNT(*) AS n FROM provider_services s
             JOIN users u ON u.id = s.provider_id AND u.status = 'active'
             JOIN provider_profiles pp ON pp.user_id = u.id
             WHERE pp.city = ? AND s.price_unit IN ('fixed','starting')
             GROUP BY s.title ORDER BY n DESC, from_price ASC LIMIT 3", [$city]);

        // The signed-in person's next booking, shown beside the banner on computers
        $next = null;
        $pendingRequests = 0;
        $reminders = [];
        if ($u = user()) {
            $mine = is_role('provider') ? 'b.provider_id' : 'b.client_id';
            $other = is_role('provider') ? 'b.client_id' : 'b.provider_id';
            if (!is_role('admin')) {
                $next = DB::one(
                    "SELECT b.*, o.first_name, o.last_name, o.id AS other_id, o.avatar, c.icon AS category_icon, c.slug AS category_slug
                     FROM bookings b JOIN users o ON o.id = $other
                     LEFT JOIN provider_profiles pp ON pp.user_id = b.provider_id
                     LEFT JOIN categories c ON c.id = pp.category_id
                     WHERE $mine = ? AND b.status IN ('requested','accepted','in_progress')
                       AND (b.scheduled_date IS NULL OR b.scheduled_date >= CURDATE())
                     ORDER BY b.scheduled_date IS NULL, b.scheduled_date, b.status = 'in_progress' DESC, b.scheduled_time LIMIT 1",
                    [(int) $u['id']]
                );
            }
            if (is_role('provider')) {
                $pendingRequests = (int) DB::value("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'requested'", [(int) $u['id']]);
            }
            // Things a customer should act on: jobs whose day has passed, payments due, then reviews
            if (is_role('client')) {
                foreach (DB::all(
                    "SELECT b.id, b.title, b.scheduled_date, p.first_name AS p_first FROM bookings b JOIN users p ON p.id = b.provider_id
                     WHERE b.client_id = ? AND b.status IN ('accepted','in_progress') AND b.scheduled_date < CURDATE()
                     ORDER BY b.scheduled_date DESC LIMIT 1",
                    [(int) $u['id']]
                ) as $b) {
                    $reminders[] = ['overdue', '/bookings/' . $b['id'], 'Did this job happen?', '“' . $b['title'] . '” with ' . $b['p_first'] . ' was booked for ' . fmt_date($b['scheduled_date'], 'M j')];
                }
                foreach (DB::all(
                    "SELECT b.id, b.title, b.amount, p.first_name AS p_first FROM bookings b JOIN users p ON p.id = b.provider_id
                     WHERE b.client_id = ? AND b.status = 'completed' AND b.payment_status = 'unpaid' ORDER BY b.completed_at DESC LIMIT 2",
                    [(int) $u['id']]
                ) as $b) {
                    $reminders[] = ['pay', '/bookings/' . $b['id'] . '#payment', 'Payment due', money($b['amount']) . ' to ' . $b['p_first'] . ' for “' . $b['title'] . '”'];
                }
                foreach (DB::all(
                    "SELECT b.id, b.title, p.first_name AS p_first FROM bookings b JOIN users p ON p.id = b.provider_id
                     LEFT JOIN reviews r ON r.booking_id = b.id
                     WHERE b.client_id = ? AND b.status = 'completed' AND r.id IS NULL ORDER BY b.completed_at DESC LIMIT 2",
                    [(int) $u['id']]
                ) as $b) {
                    $reminders[] = ['review', '/bookings/' . $b['id'] . '#review', 'How did ' . $b['p_first'] . ' do?', 'Leave a review for “' . $b['title'] . '”'];
                }
                $reminders = array_slice($reminders, 0, 2);
            }
        }

        view('home/index', compact('categories', 'featured', 'jobs', 'stats', 'reviews', 'popular', 'popularInCity', 'next', 'pendingRequests', 'city', 'reminders', 'deal', 'dealInCity', 'cityPros', 'freeTomorrow', 'quick')
            + ['bodyClass' => 'page-home']);
    }

    public function howItWorks(): void
    {
        view('home/how', ['title' => 'How it works']);
    }
}
