<?php
declare(strict_types=1);

/** Suggestions while typing in a search box: matching trades, services and pros. */
final class SearchController
{
    public function suggest(): void
    {
        require_auth();
        $q = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 60));
        if (mb_strlen($q) < 2) json_out(['items' => []]);
        // Match the start of any word ("air" finds "Aircon cleaning", not "Leak repair")
        $esc = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q);
        $starts = $esc . '%';
        $word = '% ' . $esc . '%';
        $items = [];

        foreach (DB::all("SELECT name, slug, icon FROM categories WHERE is_active = 1 AND (name LIKE ? OR name LIKE ?) ORDER BY name LIKE ? DESC, sort_order LIMIT 3", [$starts, $word, $starts]) as $c) {
            $items[] = ['type' => 'Category', 'label' => $c['name'], 'sub' => t('All ' . mb_strtolower($c['name']) . ' services'),
                        'href' => url('/services', ['category' => $c['slug']]), 'svg' => category_art($c['slug'], $c['icon'] ?: 'grid', 'icon')];
        }
        foreach (DB::all(
            "SELECT s.title, MIN(s.price) AS from_price, COUNT(*) AS n FROM provider_services s
             JOIN users u ON u.id = s.provider_id AND u.status = 'active'
             WHERE (s.title LIKE ? OR s.title LIKE ? OR s.title LIKE ?) GROUP BY s.title ORDER BY s.title LIKE ? DESC, n DESC LIMIT 5",
            [$starts, $word, '%(' . $esc . '%', $starts]
        ) as $s) {
            $items[] = ['type' => 'Service', 'label' => $s['title'],
                        'sub' => t((int) $s['n'] . ' pro' . ((int) $s['n'] === 1 ? '' : 's') . ', from ' . money($s['from_price'])),
                        'href' => url('/services', ['q' => $s['title']]), 'svg' => icon('wrench')];
        }
        foreach (DB::all(
            "SELECT u.id, u.first_name, u.last_name, pp.headline, pp.verification_status, c.name AS category_name FROM users u
             JOIN provider_profiles pp ON pp.user_id = u.id LEFT JOIN categories c ON c.id = pp.category_id
             WHERE u.role = 'provider' AND u.status = 'active' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR pp.headline LIKE ? OR pp.headline LIKE ?)
             ORDER BY pp.rating_avg DESC LIMIT 3",
            [$starts, $starts, $starts, $starts, $word]
        ) as $p) {
            $items[] = ['type' => 'Pro', 'label' => $p['first_name'] . ' ' . $p['last_name'], 'badge' => $p['verification_status'] === 'verified' ? icon('badge-check') : '',
                        'sub' => trim(($p['category_name'] ?? '') . ': ' . ($p['headline'] ?? ''), ' :'),
                        'href' => url('/providers/' . $p['id']), 'svg' => icon('user')];
        }
        json_out(['items' => $items, 'all' => url('/services', ['q' => $q])]);
    }
}
