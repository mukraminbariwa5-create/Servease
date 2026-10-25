<?php
declare(strict_types=1);

/**
 * Service-first browsing: people look for a job ("aircon cleaning") and its price,
 * then pick the pro who offers it.
 */
final class ServiceController
{
    public const SORTS = [
        'recommended' => 'Recommended',
        'price_low'   => 'Price: low to high',
        'price_high'  => 'Price: high to low',
        'top'         => 'Highest rated pro',
    ];

    /** Price range filter: key => [label, min, max] */
    public const PRICE_RANGES = [
        'u500'      => ['Under ₱500', null, 499],
        '500-1000'  => ['₱500 – ₱1,000', 500, 1000],
        '1001-2000' => ['₱1,001 – ₱2,000', 1001, 2000],
        '2001'      => ['₱2,001+', 2001, null],
    ];

    /** Rating filter: minimum => label */
    public const RATINGS = ['4.5' => 'Excellent', '4' => 'Very good', '3.5' => 'Good'];

    /** Availability filter */
    public const WHEN = ['today' => 'Today', 'open' => 'Taking new jobs'];

    public const PRICE_TYPES = [
        'fixed'    => 'Fixed price',
        'starting' => 'Starting price',
        'hour'     => 'Per hour',
        'day'      => 'Per day',
    ];

    /** @return array{rows: array, total: int} */
    public static function query(array $f, int $limit, int $offset): array
    {
        $where = ["u.role = 'provider'", "u.status = 'active'"];
        $params = [];
        if (!empty($f['q'])) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $f['q']) . '%';
            $where[] = "(s.title LIKE ? OR s.description LIKE ? OR c.name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($f['category']))   { $where[] = 'c.slug = ?'; $params[] = $f['category']; }
        if (!empty($f['city']))       { $where[] = 'pp.city = ?'; $params[] = $f['city']; }
        if (!empty($f['price_type'])) { $where[] = 's.price_unit = ?'; $params[] = $f['price_type']; }
        if (!empty($f['max_price']))  { $where[] = 's.price <= ?'; $params[] = (float) $f['max_price']; }
        if (!empty($f['rating']))     { $where[] = 'pp.rating_avg >= ?'; $params[] = (float) $f['rating']; }
        if (!empty($f['price']) && isset(self::PRICE_RANGES[$f['price']])) {
            [, $lo, $hi] = self::PRICE_RANGES[$f['price']];
            if ($lo !== null) { $where[] = 's.price >= ?'; $params[] = $lo; }
            if ($hi !== null) { $where[] = 's.price <= ?'; $params[] = $hi; }
        }
        if (($f['when'] ?? '') === 'today') {
            // Taking jobs, works on today's weekday, and not on a day off today
            $where[] = "pp.is_available = 1 AND FIND_IN_SET(?, pp.work_days) > 0
                        AND NOT EXISTS (SELECT 1 FROM provider_days_off d WHERE d.provider_id = u.id AND d.day = ?)";
            array_push($params, date('N'), date('Y-m-d'));
        } elseif (($f['when'] ?? '') === 'open') {
            $where[] = 'pp.is_available = 1';
        }
        if (!empty($f['verified']))   { $where[] = "pp.verification_status = 'verified'"; }
        if (!empty($f['available']))  { $where[] = 'pp.is_available = 1'; }

        $round = "(SELECT COUNT(*) FROM provider_services s2 WHERE s2.provider_id = s.provider_id
                   AND (s2.price < s.price OR (s2.price = s.price AND s2.id < s.id)))";
        $sort = $f['sort'] ?? 'recommended';
        $order = match ($sort) {
            'price_low'  => 's.price ASC',
            'price_high' => 's.price DESC',
            'top'        => 'pp.rating_avg DESC, pp.rating_count DESC',
            default      => "(pp.verification_status = 'verified') DESC, (pp.rating_avg * LEAST(pp.rating_count, 10)) DESC, s.id",
        };
        // Customers' own city first
        $near = empty($f['city']) && !empty($f['near']);
        $orderParams = [];
        if ($near) { [$nearSql, $orderParams] = near_order('pp.city', $f['near']); $order = $nearSql . ', ' . $order; }
        // Recommended: one service per pro per "round", so a single pro can't fill the top of the list
        if ($sort === 'recommended') $order = "$round ASC, " . $order;

        $from = "FROM provider_services s
                 JOIN users u ON u.id = s.provider_id
                 JOIN provider_profiles pp ON pp.user_id = u.id
                 LEFT JOIN categories c ON c.id = pp.category_id
                 WHERE " . implode(' AND ', $where);
        $total = (int) DB::value("SELECT COUNT(*) $from", $params);

        $viewer = (int) (user()['id'] ?? 0);
        $saved = $viewer ? '(SELECT 1 FROM saved_providers sv WHERE sv.user_id = ' . $viewer . ' AND sv.provider_id = u.id) AS is_saved' : '0 AS is_saved';
        $rows = DB::all(
            "SELECT s.id, s.title, s.description, s.price, s.price_unit,
                    u.id AS provider_id, u.first_name, u.last_name, u.avatar,
                    pp.city, pp.rating_avg, pp.rating_count, pp.jobs_completed, pp.verification_status, pp.is_available,
                    c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
                    (SELECT pi.image FROM portfolio_items pi WHERE pi.provider_id = u.id ORDER BY pi.id DESC LIMIT 1) AS cover,
                    $saved
             $from ORDER BY $order LIMIT ? OFFSET ?",
            array_merge($params, $orderParams, [$limit, $offset])
        );
        // Numbers for the page header: how many pros, the lowest price and the average rating of these results
        $sum = DB::one("SELECT COUNT(DISTINCT s.provider_id) AS pros, MIN(s.price) AS from_price,
                               AVG(NULLIF(pp.rating_avg, 0)) AS rating $from", $params) ?: [];
        return ['rows' => $rows, 'total' => $total, 'summary' => $sum];
    }

    public function index(): void
    {
        $f = [
            'q'          => trim((string) ($_GET['q'] ?? '')),
            'category'   => (string) ($_GET['category'] ?? ''),
            'city'       => in_array($_GET['city'] ?? '', cities(), true) ? (string) $_GET['city'] : '',
            'price_type' => array_key_exists($_GET['price_type'] ?? '', self::PRICE_TYPES) ? (string) $_GET['price_type'] : '',
            'max_price'  => ctype_digit((string) ($_GET['max_price'] ?? '')) ? (string) $_GET['max_price'] : '',
            'rating'     => array_key_exists($_GET['rating'] ?? '', self::RATINGS) ? (string) $_GET['rating'] : '',
            'price'      => array_key_exists($_GET['price'] ?? '', self::PRICE_RANGES) ? (string) $_GET['price'] : '',
            'when'       => array_key_exists($_GET['when'] ?? '', self::WHEN) ? (string) $_GET['when'] : '',
            'verified'   => ($_GET['verified'] ?? '') === '1' ? '1' : '',
            'available'  => ($_GET['available'] ?? '') === '1' ? '1' : '',
            'sort'       => array_key_exists($_GET['sort'] ?? '', self::SORTS) ? (string) $_GET['sort'] : 'recommended',
        ];
        [$offset, $perPage, $page] = page_params(12);
        $result = self::query($f + ['near' => current_city()], $perPage, $offset);

        $category = null;
        foreach (categories() as $c) if ($c['slug'] === $f['category']) $category = $c;

        $catCounts = [];
        foreach (DB::all(
            "SELECT c.slug, COUNT(*) AS n FROM provider_services s
             JOIN users u ON u.id = s.provider_id AND u.status = 'active' AND u.role = 'provider'
             JOIN provider_profiles pp ON pp.user_id = u.id
             JOIN categories c ON c.id = pp.category_id GROUP BY c.slug"
        ) as $r) $catCounts[$r['slug']] = (int) $r['n'];

        view('services/index', [
            'title'     => $category ? $category['name'] . ' services' : 'Services',
            'filters'   => $f,
            'category'  => $category,
            'catCounts' => $catCounts,
            'rows'      => $result['rows'],
            'total'     => $result['total'],
            'summary'   => $result['summary'],
            'page'      => $page,
            'perPage'   => $perPage,
        ]);
    }

    /** Category tab: every trade with how many services it has and the lowest starting price. */
    public function categories(): void
    {
        $stats = [];
        foreach (DB::all(
            "SELECT c.slug, COUNT(*) AS n, MIN(s.price) AS from_price, COUNT(DISTINCT s.provider_id) AS pros
             FROM provider_services s
             JOIN users u ON u.id = s.provider_id AND u.status = 'active' AND u.role = 'provider'
             JOIN provider_profiles pp ON pp.user_id = u.id
             JOIN categories c ON c.id = pp.category_id GROUP BY c.slug"
        ) as $r) $stats[$r['slug']] = $r;
        view('services/categories', ['title' => 'Categories', 'stats' => $stats]);
    }
}
