<?php
declare(strict_types=1);

final class ProviderController
{
    public const SORTS = [
        'recommended' => 'Recommended',
        'top'         => 'Highest rated',
        'reviews'     => 'Most reviews',
        'price_low'   => 'Price: low to high',
        'price_high'  => 'Price: high to low',
        'newest'      => 'Newest',
    ];

    /**
     * Shared provider search used by the listing page and the home page.
     * @return array{rows: array, total: int}
     */
    public static function query(array $f, int $limit, int $offset): array
    {
        $where = ["u.role = 'provider'", "u.status = 'active'"];
        $params = [];

        if (!empty($f['q'])) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $f['q']) . '%';
            $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR pp.headline LIKE ? OR pp.skills LIKE ? OR c.name LIKE ? OR pp.bio LIKE ?)";
            array_push($params, $like, $like, $like, $like, $like);
        }
        if (!empty($f['category'])) { $where[] = 'c.slug = ?'; $params[] = $f['category']; }
        if (!empty($f['city']))     { $where[] = 'pp.city = ?'; $params[] = $f['city']; }
        if (!empty($f['rating']))   { $where[] = 'pp.rating_avg >= ?'; $params[] = (float) $f['rating']; }
        if (!empty($f['verified'])) { $where[] = "pp.verification_status = 'verified'"; }
        if (!empty($f['available'])){ $where[] = 'pp.is_available = 1'; }
        if (!empty($f['max_rate'])) { $where[] = 'pp.hourly_rate <= ?'; $params[] = (float) $f['max_rate']; }

        $order = match ($f['sort'] ?? 'recommended') {
            'top'        => 'pp.rating_avg DESC, pp.rating_count DESC',
            'reviews'    => 'pp.rating_count DESC, pp.rating_avg DESC',
            'price_low'  => 'pp.hourly_rate IS NULL, pp.hourly_rate ASC',
            'price_high' => 'pp.hourly_rate DESC',
            'newest'     => 'u.created_at DESC',
            default      => "(pp.verification_status = 'verified') DESC, (pp.rating_avg * LEAST(pp.rating_count, 10)) DESC, pp.jobs_completed DESC",
        };
        // Optionally list pros from one city first (without hiding the others)
        $orderParams = [];
        if (empty($f['city']) && !empty($f['near'])) { [$nearSql, $orderParams] = near_order('pp.city', $f['near']); $order = $nearSql . ', ' . $order; }

        $from = "FROM users u
                 JOIN provider_profiles pp ON pp.user_id = u.id
                 LEFT JOIN categories c ON c.id = pp.category_id
                 WHERE " . implode(' AND ', $where);

        $total = (int) DB::value("SELECT COUNT(*) $from", $params);

        $viewer = user_id();
        $savedSelect = $viewer
            ? '(SELECT 1 FROM saved_providers s WHERE s.user_id = ' . (int) $viewer . ' AND s.provider_id = u.id) AS is_saved'
            : '0 AS is_saved';

        $rows = DB::all(
            "SELECT u.id, u.first_name, u.last_name, u.avatar, u.created_at,
                    pp.headline, pp.city, pp.hourly_rate, pp.rating_avg, pp.rating_count, pp.jobs_completed,
                    pp.verification_status, pp.is_available, pp.years_experience,
                    c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, $savedSelect,
                    (SELECT pi.image FROM portfolio_items pi WHERE pi.provider_id = u.id ORDER BY pi.id DESC LIMIT 1) AS cover
             $from ORDER BY $order LIMIT ? OFFSET ?",
            array_merge($params, $orderParams, [$limit, $offset])
        );
        $sum = DB::one("SELECT MIN(pp.hourly_rate) AS from_rate, AVG(NULLIF(pp.rating_avg, 0)) AS rating,
                               SUM(pp.verification_status = 'verified') AS verified $from", $params) ?: [];
        return ['rows' => $rows, 'total' => $total, 'summary' => $sum];
    }

    /** provider id => their three lowest-priced services, for the laptop result rows */
    public static function topServices(array $rows): array
    {
        $ids = array_map(fn($r) => (int) $r['id'], $rows);
        if (!$ids) return [];
        $out = [];
        foreach (DB::all('SELECT provider_id, id, title, price, price_unit FROM provider_services WHERE provider_id IN ('
                         . implode(',', $ids) . ') ORDER BY price, id') as $s) {
            if (count($out[$s['provider_id']] ?? []) < 3) $out[$s['provider_id']][] = $s;
        }
        return $out;
    }

    public function index(): void
    {
        $filters = [
            'q'         => trim((string) ($_GET['q'] ?? '')),
            'category'  => (string) ($_GET['category'] ?? ''),
            'city'      => (string) ($_GET['city'] ?? ''),
            'rating'    => (string) ($_GET['rating'] ?? ''),
            'verified'  => (string) ($_GET['verified'] ?? ''),
            'available' => (string) ($_GET['available'] ?? ''),
            'max_rate'  => (string) ($_GET['max_rate'] ?? ''),
            'sort'      => array_key_exists($_GET['sort'] ?? '', self::SORTS) ? $_GET['sort'] : 'recommended',
        ];
        [$offset, $perPage, $page] = page_params(12);
        $result = self::query($filters, $perPage, $offset);

        $category = null;
        foreach (categories() as $c) if ($c['slug'] === $filters['category']) $category = $c;

        $catCounts = [];
        foreach (DB::all(
            "SELECT c.slug, COUNT(*) AS n FROM provider_profiles pp
             JOIN users u ON u.id = pp.user_id AND u.status = 'active' AND u.role = 'provider'
             JOIN categories c ON c.id = pp.category_id GROUP BY c.slug"
        ) as $r) $catCounts[$r['slug']] = (int) $r['n'];

        view('providers/index', [
            'title'    => $category ? $category['name'] . ' pros' : 'Pros',
            'catCounts' => $catCounts,
            'filters'  => $filters,
            'category' => $category,
            'rows'     => $result['rows'],
            'total'    => $result['total'],
            'summary'  => $result['summary'],
            'services' => self::topServices($result['rows']),
            'page'     => $page,
            'perPage'  => $perPage,
        ]);
    }

    public function show(string $id): void
    {
        $p = DB::one(
            "SELECT u.*, pp.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM users u JOIN provider_profiles pp ON pp.user_id = u.id
             LEFT JOIN categories c ON c.id = pp.category_id
             WHERE u.id = ? AND u.role = 'provider'",
            [(int) $id]
        );
        if (!$p || ($p['status'] !== 'active' && !is_role('admin'))) abort(404, 'This pro could not be found.');
        $p['id'] = (int) $id;

        $services = DB::all('SELECT * FROM provider_services WHERE provider_id = ? ORDER BY price', [(int) $id]);
        $portfolio = DB::all('SELECT * FROM portfolio_items WHERE provider_id = ? ORDER BY created_at DESC', [(int) $id]);
        $reviews = DB::all(
            "SELECT r.*, u.first_name, u.last_name, u.avatar, u.id AS client_uid, b.title AS booking_title
             FROM reviews r JOIN users u ON u.id = r.client_id JOIN bookings b ON b.id = r.booking_id
             WHERE r.provider_id = ? AND r.is_hidden = 0 ORDER BY r.created_at DESC LIMIT 20",
            [(int) $id]
        );
        $dist = array_fill(1, 5, 0);
        foreach (DB::all('SELECT rating, COUNT(*) n FROM reviews WHERE provider_id = ? AND is_hidden = 0 GROUP BY rating', [(int) $id]) as $r) {
            $dist[(int) $r['rating']] = (int) $r['n'];
        }
        $similar = [];
        if ($p['category_slug']) {
            $similar = array_values(array_filter(
                self::query(['category' => $p['category_slug'], 'sort' => 'recommended'], 4, 0)['rows'],
                fn($s) => (int) $s['id'] !== (int) $id
            ));
            $similar = array_slice($similar, 0, 3);
        }
        $isSaved = user_id() && DB::value('SELECT 1 FROM saved_providers WHERE user_id = ? AND provider_id = ?', [user_id(), (int) $id]);

        view('providers/show', [
            'title' => full_name($p) . ' — ' . ($p['category_name'] ?? 'Pro'),
            'description' => str_limit($p['headline'] . '. ' . $p['bio'], 155),
            'p' => $p, 'services' => $services, 'portfolio' => $portfolio, 'reviews' => $reviews,
            'dist' => $dist, 'similar' => $similar, 'isSaved' => (bool) $isSaved,
        ]);
    }

    public function toggleSave(string $id): void
    {
        $u = user();
        if (!$u) {
            if (wants_json()) json_out(['error' => 'login'], 401);
            require_auth();
        }
        $pid = (int) $id;
        $exists = DB::value("SELECT 1 FROM users WHERE id = ? AND role = 'provider'", [$pid]);
        if (!$exists || $pid === (int) $u['id']) abort(404);

        if (DB::value('SELECT 1 FROM saved_providers WHERE user_id = ? AND provider_id = ?', [(int) $u['id'], $pid])) {
            DB::query('DELETE FROM saved_providers WHERE user_id = ? AND provider_id = ?', [(int) $u['id'], $pid]);
            $saved = false;
        } else {
            DB::insert('saved_providers', ['user_id' => (int) $u['id'], 'provider_id' => $pid]);
            $saved = true;
        }
        if (wants_json()) json_out(['saved' => $saved]);
        flash('success', $saved ? 'Saved to your list.' : 'Removed from your saved pros.');
        back('/providers/' . $pid);
    }

    public function saved(): void
    {
        $u = require_auth();
        $rows = DB::all(
            "SELECT u.id, u.first_name, u.last_name, u.avatar, pp.headline, pp.city, pp.hourly_rate, pp.rating_avg,
                    pp.rating_count, pp.jobs_completed, pp.verification_status, pp.is_available,
                    c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, 1 AS is_saved
             FROM saved_providers s JOIN users u ON u.id = s.provider_id
             JOIN provider_profiles pp ON pp.user_id = u.id LEFT JOIN categories c ON c.id = pp.category_id
             WHERE s.user_id = ? AND u.status = 'active' ORDER BY s.created_at DESC",
            [(int) $u['id']]
        );
        view('providers/saved', ['title' => 'Saved pros', 'rows' => $rows, 'active' => 'saved'], 'layouts/shell');
    }
}
