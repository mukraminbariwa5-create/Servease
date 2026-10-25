<?php
declare(strict_types=1);

final class AdminController
{
    private array $admin;

    public function __construct()
    {
        $this->admin = require_role('admin');
    }

    private function render(string $view, array $data): void
    {
        $data['pendingCount'] = (int) DB::value("SELECT COUNT(*) FROM provider_profiles WHERE verification_status = 'pending'");
        $data['issueCount'] = (int) DB::value("SELECT COUNT(*) FROM booking_issues WHERE status = 'open'");
        view('admin/' . $view, $data, 'layouts/admin');
    }

    public function dashboard(): void
    {
        $monthStart = date('Y-m-01 00:00:00');
        $kpi = [
            'clients'   => (int) DB::value("SELECT COUNT(*) FROM users WHERE role = 'client'"),
            'providers' => (int) DB::value("SELECT COUNT(*) FROM users WHERE role = 'provider'"),
            'verified'  => (int) DB::value("SELECT COUNT(*) FROM provider_profiles WHERE verification_status = 'verified'"),
            'new_users' => (int) DB::value('SELECT COUNT(*) FROM users WHERE created_at >= ?', [$monthStart]),
            'bookings'  => (int) DB::value('SELECT COUNT(*) FROM bookings'),
            'bookings_month' => (int) DB::value('SELECT COUNT(*) FROM bookings WHERE created_at >= ?', [$monthStart]),
            'completed' => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE status = 'completed'"),
            'volume'    => (float) DB::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'"),
            'volume_month' => (float) DB::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND paid_at >= ?", [$monthStart]),
            'open_jobs' => (int) DB::value("SELECT COUNT(*) FROM jobs WHERE status = 'open'"),
            'rating'    => (float) DB::value('SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE is_hidden = 0'),
        ];

        // Bookings per day, last 30 days
        $raw = [];
        foreach (DB::all('SELECT DATE(created_at) d, COUNT(*) n FROM bookings WHERE created_at >= ? GROUP BY DATE(created_at)', [date('Y-m-d', strtotime('-29 days'))]) as $r) {
            $raw[(string) $r['d']] = (int) $r['n'];
        }
        $chart = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $chart[] = ['label' => date('M j', strtotime($d)), 'value' => $raw[$d] ?? 0, 'short' => date('j', strtotime($d))];
        }
        $byStatus = [];
        foreach (DB::all('SELECT status, COUNT(*) n FROM bookings GROUP BY status') as $r) $byStatus[$r['status']] = (int) $r['n'];

        $topCategories = DB::all(
            "SELECT c.name, c.icon, c.slug, COUNT(b.id) AS n FROM categories c
             JOIN provider_profiles pp ON pp.category_id = c.id
             JOIN bookings b ON b.provider_id = pp.user_id
             GROUP BY c.id, c.name, c.icon, c.slug ORDER BY n DESC LIMIT 5"
        );
        $recentUsers = DB::all('SELECT * FROM users ORDER BY created_at DESC LIMIT 6');
        $recentBookings = DB::all(
            'SELECT b.*, c.first_name AS c_first, c.last_name AS c_last, p.first_name AS p_first, p.last_name AS p_last
             FROM bookings b JOIN users c ON c.id = b.client_id JOIN users p ON p.id = b.provider_id
             ORDER BY b.created_at DESC LIMIT 6'
        );
        $this->render('dashboard', compact('kpi', 'chart', 'byStatus', 'topCategories', 'recentUsers', 'recentBookings')
            + ['title' => 'Admin', 'active' => 'dashboard']);
    }

    /* ---------------- Users ---------------- */

    public function users(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $role = in_array($_GET['role'] ?? '', ['client', 'provider', 'admin'], true) ? $_GET['role'] : '';
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = "(CONCAT(first_name, ' ', last_name) LIKE ? OR email LIKE ?)";
            array_push($params, $like, $like);
        }
        if ($role) { $where[] = 'role = ?'; $params[] = $role; }
        [$offset, $perPage, $page] = page_params(15);
        $w = implode(' AND ', $where);
        $total = (int) DB::value("SELECT COUNT(*) FROM users WHERE $w", $params);
        $rows = DB::all(
            "SELECT u.*, pp.verification_status,
                    (SELECT COUNT(*) FROM bookings b WHERE b.client_id = u.id OR b.provider_id = u.id) AS bookings
             FROM users u LEFT JOIN provider_profiles pp ON pp.user_id = u.id
             WHERE $w ORDER BY u.created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        $this->render('users', compact('rows', 'total', 'page', 'perPage', 'q', 'role') + ['title' => 'Users', 'active' => 'users']);
    }

    public function userStatus(string $id): void
    {
        $target = DB::one('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$target) abort(404);
        if ($target['role'] === 'admin') {
            flash('error', 'Admin accounts can\'t be suspended here.');
            back('/admin/users');
        }
        if (Demo::isDemoAccount($target)) {
            flash('error', 'Demo accounts can\'t be suspended in the public demo, so the next visitor can still sign in with them.');
            back('/admin/users');
        }
        $new = $target['status'] === 'active' ? 'suspended' : 'active';
        DB::update('users', ['status' => $new], 'id = :id', ['id' => (int) $id]);
        flash('success', full_name($target) . ($new === 'suspended' ? ' has been suspended.' : ' has been reactivated.'));
        back('/admin/users');
    }

    /* ---------------- Provider verification ---------------- */

    public function verifications(): void
    {
        $tabs = ['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected', 'unverified' => 'Not submitted'];
        $tab = array_key_exists($_GET['tab'] ?? '', $tabs) ? $_GET['tab'] : 'pending';
        $counts = [];
        foreach ($tabs as $k => $_) $counts[$k] = (int) DB::value('SELECT COUNT(*) FROM provider_profiles WHERE verification_status = ?', [$k]);
        $rows = DB::all(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.avatar, u.phone, u.created_at AS joined, pp.*, c.name AS category_name
             FROM provider_profiles pp JOIN users u ON u.id = pp.user_id LEFT JOIN categories c ON c.id = pp.category_id
             WHERE pp.verification_status = ? ORDER BY pp.updated_at DESC",
            [$tab]
        );
        $this->render('verifications', compact('rows', 'tab', 'tabs', 'counts') + ['title' => 'Verifications', 'active' => 'verifications']);
    }

    public function verify(string $id): void
    {
        $p = DB::one('SELECT pp.*, u.first_name FROM provider_profiles pp JOIN users u ON u.id = pp.user_id WHERE pp.user_id = ?', [(int) $id]);
        if (!$p) abort(404);
        $approve = ($_POST['decision'] ?? '') === 'approve';
        $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 255) ?: null;
        if (!$approve && !$note) {
            flash('error', 'Please add a short reason when rejecting, so the pro knows what to fix.');
            back('/admin/verifications');
        }
        DB::update('provider_profiles', [
            'verification_status' => $approve ? 'verified' : 'rejected',
            'verification_note' => $approve ? null : $note,
            'verified_at' => $approve ? date('Y-m-d H:i:s') : null,
        ], 'user_id = :id', ['id' => (int) $id]);
        notify((int) $id, 'verification',
            $approve ? 'You\'re verified!' : 'Verification needs attention',
            $approve ? 'Your profile now shows the Verified badge.' : 'Reason: ' . $note,
            $approve ? '/providers/' . (int) $id : '/provider/profile#verification');
        flash('success', $p['first_name'] . ($approve ? ' is now verified.' : '\'s verification was rejected.'));
        back('/admin/verifications');
    }

    /** Streams a private verification document (never publicly accessible). */
    public function document(string $id): void
    {
        $doc = DB::value('SELECT verification_doc FROM provider_profiles WHERE user_id = ?', [(int) $id]);
        $path = $doc ? realpath(ROOT . '/storage/private/' . $doc) : false;
        $base = realpath(ROOT . '/storage/private');
        if (!$path || !$base || !str_starts_with($path, $base) || !is_file($path)) abort(404, 'Document not found.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="verification-' . (int) $id . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    /* ---------------- Categories ---------------- */

    public const ICONS = ['droplet', 'zap', 'snowflake', 'sparkles', 'hammer', 'paint-roller', 'wrench', 'truck', 'monitor',
                          'book-open', 'scissors', 'leaf', 'camera', 'car', 'pen-tool', 'code', 'home', 'briefcase', 'shield'];

    public function categories(): void
    {
        $rows = DB::all(
            "SELECT c.*, (SELECT COUNT(*) FROM provider_profiles pp WHERE pp.category_id = c.id) AS providers,
                    (SELECT COUNT(*) FROM jobs j WHERE j.category_id = c.id) AS jobs
             FROM categories c ORDER BY c.sort_order, c.name"
        );
        $edit = isset($_GET['edit']) ? DB::one('SELECT * FROM categories WHERE id = ?', [(int) $_GET['edit']]) : null;
        $this->render('categories', compact('rows', 'edit') + ['title' => 'Categories', 'active' => 'categories']);
    }

    public function saveCategory(): void
    {
        $v = Validator::make($_POST, [
            'name'        => 'required|min:2|max:80|string',
            'icon'        => 'required|in:' . implode(',', self::ICONS),
            'description' => 'nullable|max:255|string',
            'sort_order'  => 'nullable|int|min:0|max:999',
        ]);
        if ($v->fails()) back_with_errors($v->errors(), '/admin/categories');
        $d = $v->validated();
        $id = (int) ($_POST['id'] ?? 0);
        $slug = slugify($d['name']);
        if (DB::value('SELECT 1 FROM categories WHERE slug = ? AND id <> ?', [$slug, $id])) {
            back_with_errors(['name' => 'A category with that name already exists.'], '/admin/categories');
        }
        $data = ['name' => $d['name'], 'slug' => $slug, 'icon' => $d['icon'], 'description' => $d['description'], 'sort_order' => (int) ($d['sort_order'] ?? 0)];
        if ($id) {
            // Keep the original slug so existing links keep working
            unset($data['slug']);
            DB::update('categories', $data, 'id = :id', ['id' => $id]);
            flash('success', 'Category updated.');
        } else {
            DB::insert('categories', $data);
            flash('success', 'Category added.');
        }
        redirect('/admin/categories');
    }

    public function toggleCategory(string $id): void
    {
        DB::query('UPDATE categories SET is_active = 1 - is_active WHERE id = ?', [(int) $id]);
        flash('success', 'Category visibility updated.');
        redirect('/admin/categories');
    }

    /* ---------------- Bookings, jobs, payments, reviews ---------------- */

    public function bookings(): void
    {
        $status = array_key_exists($_GET['status'] ?? '', ['requested' => 1, 'accepted' => 1, 'in_progress' => 1, 'completed' => 1, 'cancelled' => 1, 'declined' => 1]) ? $_GET['status'] : '';
        [$offset, $perPage, $page] = page_params(20);
        $where = $status ? 'WHERE b.status = ?' : '';
        $params = $status ? [$status] : [];
        $total = (int) DB::value("SELECT COUNT(*) FROM bookings b $where", $params);
        $rows = DB::all(
            "SELECT b.*, c.first_name AS c_first, c.last_name AS c_last, p.first_name AS p_first, p.last_name AS p_last
             FROM bookings b JOIN users c ON c.id = b.client_id JOIN users p ON p.id = b.provider_id
             $where ORDER BY b.created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        $this->render('bookings', compact('rows', 'total', 'page', 'perPage', 'status') + ['title' => 'Bookings', 'active' => 'bookings']);
    }

    public function jobs(): void
    {
        $status = in_array($_GET['status'] ?? '', ['open', 'assigned', 'completed', 'cancelled'], true) ? $_GET['status'] : '';
        [$offset, $perPage, $page] = page_params(20);
        $where = $status ? 'WHERE j.status = ?' : '';
        $params = $status ? [$status] : [];
        $total = (int) DB::value("SELECT COUNT(*) FROM jobs j $where", $params);
        $rows = DB::all(
            "SELECT j.*, u.first_name, u.last_name, c.name AS category_name FROM jobs j
             JOIN users u ON u.id = j.client_id LEFT JOIN categories c ON c.id = j.category_id
             $where ORDER BY j.created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        $this->render('jobs', compact('rows', 'total', 'page', 'perPage', 'status') + ['title' => 'Jobs', 'active' => 'jobs']);
    }

    public function payments(): void
    {
        [$offset, $perPage, $page] = page_params(20);
        $total = (int) DB::value('SELECT COUNT(*) FROM payments');
        $rows = DB::all(
            'SELECT pay.*, b.title, c.first_name AS c_first, c.last_name AS c_last, p.first_name AS p_first, p.last_name AS p_last
             FROM payments pay JOIN bookings b ON b.id = pay.booking_id
             JOIN users c ON c.id = pay.payer_id JOIN users p ON p.id = pay.payee_id
             ORDER BY pay.created_at DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        );
        $byMethod = DB::all("SELECT method, COUNT(*) n, SUM(amount) total FROM payments WHERE status = 'paid' GROUP BY method ORDER BY total DESC");
        $this->render('payments', compact('rows', 'total', 'page', 'perPage', 'byMethod') + ['title' => 'Payments', 'active' => 'payments']);
    }

    public function reviews(): void
    {
        [$offset, $perPage, $page] = page_params(20);
        $total = (int) DB::value('SELECT COUNT(*) FROM reviews');
        $rows = DB::all(
            'SELECT r.*, c.first_name AS c_first, c.last_name AS c_last, p.first_name AS p_first, p.last_name AS p_last
             FROM reviews r JOIN users c ON c.id = r.client_id JOIN users p ON p.id = r.provider_id
             ORDER BY r.created_at DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        );
        $this->render('reviews', compact('rows', 'total', 'page', 'perPage') + ['title' => 'Reviews', 'active' => 'reviews']);
    }

    public function toggleReview(string $id): void
    {
        $r = DB::one('SELECT * FROM reviews WHERE id = ?', [(int) $id]);
        if (!$r) abort(404);
        DB::update('reviews', ['is_hidden' => $r['is_hidden'] ? 0 : 1], 'id = :id', ['id' => (int) $id]);
        ReviewController::recompute((int) $r['provider_id']);
        flash('success', $r['is_hidden'] ? 'Review is visible again.' : 'Review hidden from the public profile.');
        back('/admin/reviews');
    }

    public function issues(): void
    {
        $tab = ($_GET['tab'] ?? 'open') === 'resolved' ? 'resolved' : 'open';
        [$offset, $perPage, $page] = page_params(20);
        $total = (int) DB::value('SELECT COUNT(*) FROM booking_issues WHERE status = ?', [$tab]);
        $rows = DB::all(
            "SELECT i.*, b.title, b.status AS booking_status, b.amount, b.client_id, b.provider_id,
                    r.first_name AS r_first, r.last_name AS r_last, r.role AS r_role,
                    c.first_name AS c_first, c.last_name AS c_last, p.first_name AS p_first, p.last_name AS p_last
             FROM booking_issues i
             JOIN bookings b ON b.id = i.booking_id
             JOIN users r ON r.id = i.reporter_id
             JOIN users c ON c.id = b.client_id
             JOIN users p ON p.id = b.provider_id
             WHERE i.status = ? ORDER BY i.created_at " . ($tab === 'open' ? 'ASC' : 'DESC') . " LIMIT ? OFFSET ?",
            [$tab, $perPage, $offset]
        );
        $counts = [
            'open' => (int) DB::value("SELECT COUNT(*) FROM booking_issues WHERE status = 'open'"),
            'resolved' => (int) DB::value("SELECT COUNT(*) FROM booking_issues WHERE status = 'resolved'"),
        ];
        $this->render('issues', compact('rows', 'total', 'page', 'perPage', 'tab', 'counts') + ['title' => 'Reported problems', 'active' => 'issues']);
    }

    public function resolveIssue(string $id): void
    {
        $i = DB::one('SELECT i.*, b.title FROM booking_issues i JOIN bookings b ON b.id = i.booking_id WHERE i.id = ?', [(int) $id]);
        if (!$i) abort(404);
        $note = mb_substr(trim((string) ($_POST['admin_note'] ?? '')), 0, 255);
        if (mb_strlen($note) < 5) {
            flash('error', 'Write a short note for the customer and the pro (at least 5 characters).');
            back('/admin/issues');
        }
        if ($i['status'] === 'open') {
            DB::transaction(function () use ($i, $note) {
                DB::update('booking_issues', ['status' => 'resolved', 'admin_note' => $note, 'resolved_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $i['id']]);
                DB::insert('booking_events', ['booking_id' => (int) $i['booking_id'], 'actor_id' => (int) $this->admin['id'], 'event' => 'issue_resolved', 'note' => $note]);
            });
            notify((int) $i['reporter_id'], 'booking', 'Your report was resolved', 'Servease support: ' . $note, '/bookings/' . $i['booking_id']);
            flash('success', 'Marked as resolved. The person who reported it has been notified.');
        }
        back('/admin/issues');
    }
}
