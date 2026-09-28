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
        $featured = ProviderController::query(['sort' => 'recommended', 'verified' => '1'], 8, 0)['rows'];
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
        $latestQuote = DB::one(
            "SELECT q.amount, j.title, u.first_name, u.last_name
             FROM quotes q JOIN jobs j ON j.id = q.job_id JOIN users u ON u.id = q.provider_id
             ORDER BY q.created_at DESC LIMIT 1"
        );
        view('home/index', compact('categories', 'featured', 'jobs', 'stats', 'reviews', 'latestQuote'));
    }

    public function howItWorks(): void
    {
        view('home/how', ['title' => 'How it works']);
    }
}
