<?php
declare(strict_types=1);

final class JobController
{
    private const SELECT = "SELECT j.*, c.name AS category_name, c.icon AS category_icon, c.slug AS category_slug,
                                   u.first_name, u.last_name, u.avatar, u.created_at AS client_since
                            FROM jobs j
                            JOIN users u ON u.id = j.client_id
                            LEFT JOIN categories c ON c.id = j.category_id";

    public function index(): void
    {
        $f = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category' => (string) ($_GET['category'] ?? ''),
            'city' => (string) ($_GET['city'] ?? ''),
            'sort' => in_array($_GET['sort'] ?? '', ['newest', 'budget', 'urgent'], true) ? $_GET['sort'] : 'newest',
        ];
        $where = ["j.status = 'open'"];
        $params = [];
        if ($f['q'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $f['q']) . '%';
            $where[] = '(j.title LIKE ? OR j.description LIKE ?)';
            array_push($params, $like, $like);
        }
        if ($f['category'] !== '') { $where[] = 'c.slug = ?'; $params[] = $f['category']; }
        if ($f['city'] !== '') { $where[] = 'j.city = ?'; $params[] = $f['city']; }
        $order = match ($f['sort']) {
            'budget' => 'COALESCE(j.budget_max, j.budget_min, 0) DESC',
            'urgent' => "FIELD(j.urgency, 'urgent', 'this_week', 'flexible'), j.created_at DESC",
            default  => 'j.created_at DESC',
        };
        $whereSql = implode(' AND ', $where);
        [$offset, $perPage, $page] = page_params(10);
        $total = (int) DB::value("SELECT COUNT(*) FROM jobs j LEFT JOIN categories c ON c.id = j.category_id WHERE $whereSql", $params);
        $jobs = DB::all(self::SELECT . " WHERE $whereSql ORDER BY $order LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));

        $summary = DB::one("SELECT SUM(j.created_at >= ?) AS this_week, MAX(COALESCE(j.budget_max, j.budget_min)) AS top_budget, SUM(j.urgency = 'urgent') AS urgent
                            FROM jobs j LEFT JOIN categories c ON c.id = j.category_id WHERE $whereSql",
                           array_merge([date('Y-m-d H:i:s', strtotime('-7 days'))], $params)) ?: [];
        view('jobs/index', ['title' => 'Job board', 'jobs' => $jobs, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'f' => $f, 'summary' => $summary]);
    }

    public function show(string $id): void
    {
        $job = DB::one(self::SELECT . ' WHERE j.id = ?', [(int) $id]);
        if (!$job) abort(404, 'This job could not be found.');
        $me = user();
        $isOwner = $me && (int) $me['id'] === (int) $job['client_id'];
        if ($job['status'] === 'cancelled' && !$isOwner && !is_role('admin')) abort(404, 'This job is no longer available.');

        $quotes = [];
        $myQuote = null;
        if ($isOwner || is_role('admin')) {
            $quotes = DB::all(
                "SELECT q.*, u.first_name, u.last_name, u.avatar, pp.headline, pp.rating_avg, pp.rating_count,
                        pp.jobs_completed, pp.verification_status, pp.city
                 FROM quotes q JOIN users u ON u.id = q.provider_id JOIN provider_profiles pp ON pp.user_id = u.id
                 WHERE q.job_id = ? ORDER BY FIELD(q.status, 'accepted', 'pending', 'declined', 'withdrawn'), q.amount",
                [(int) $id]
            );
        } elseif ($me && $me['role'] === 'provider') {
            $myQuote = DB::one('SELECT * FROM quotes WHERE job_id = ? AND provider_id = ?', [(int) $id, (int) $me['id']]);
        }
        $clientJobs = (int) DB::value('SELECT COUNT(*) FROM jobs WHERE client_id = ?', [(int) $job['client_id']]);
        $booking = $job['status'] !== 'open'
            ? DB::one('SELECT id FROM bookings WHERE job_id = ? ORDER BY id DESC LIMIT 1', [(int) $id]) : null;

        view('jobs/show', [
            'title' => $job['title'], 'job' => $job, 'quotes' => $quotes, 'myQuote' => $myQuote,
            'isOwner' => $isOwner, 'clientJobs' => $clientJobs, 'booking' => $booking,
        ]);
    }

    public function create(): void
    {
        $u = user();
        if (!$u) {
            $_SESSION['_intended'] = url('/jobs/new');
            flash('info', 'Create a free account (or sign in) to post your job.');
            redirect('/register');
        }
        if ($u['role'] !== 'client') abort(403, 'Only client accounts can post jobs.');
        $category = (string) ($_GET['category'] ?? '');
        view('jobs/create', ['title' => 'Post a job', 'category' => $category]);
    }

    public function store(): void
    {
        $u = require_role('client');
        $v = Validator::make($_POST, [
            'category_id'    => 'required|int|exists:categories,id',
            'title'          => 'required|min:8|max:140|string',
            'description'    => 'required|min:20|max:3000|string',
            'city'           => 'required|max:80',
            'budget_min'     => 'nullable|numeric|min:0|max:10000000',
            'budget_max'     => 'nullable|numeric|min:0|max:10000000',
            'preferred_date' => 'nullable|date|after_today',
            'urgency'        => 'required|in:flexible,this_week,urgent',
        ], ['category_id' => 'category', 'preferred_date' => 'preferred date']);
        $errors = $v->errors();
        $d = $v->validated();
        if (!$errors && $d['budget_min'] !== null && $d['budget_max'] !== null && (float) $d['budget_min'] > (float) $d['budget_max']) {
            $errors['budget_max'] = 'Maximum budget must be higher than the minimum.';
        }
        if ($errors) back_with_errors($errors, '/jobs/new');

        $id = DB::insert('jobs', [
            'client_id' => (int) $u['id'], 'category_id' => (int) $d['category_id'],
            'title' => $d['title'], 'description' => $d['description'], 'city' => $d['city'],
            'budget_min' => $d['budget_min'], 'budget_max' => $d['budget_max'],
            'preferred_date' => $d['preferred_date'], 'urgency' => $d['urgency'],
        ]);

        // Let matching pros know (same category, active; nearest city first)
        $pros = DB::all(
            "SELECT u.id FROM users u JOIN provider_profiles pp ON pp.user_id = u.id
             WHERE pp.category_id = ? AND u.status = 'active' ORDER BY (pp.city = ?) DESC LIMIT 30",
            [(int) $d['category_id'], $d['city']]
        );
        foreach ($pros as $p) {
            notify((int) $p['id'], 'job', 'New job in your category', $d['title'] . ' · ' . $d['city'], '/jobs/' . $id);
        }

        flash('success', 'Your job is live! Pros can now send you quotes.');
        redirect('/jobs/' . $id);
    }

    public function mine(): void
    {
        $u = require_role('client');
        $tab = in_array($_GET['tab'] ?? '', ['open', 'assigned', 'completed', 'cancelled'], true) ? $_GET['tab'] : 'open';
        $counts = [];
        foreach (DB::all('SELECT status, COUNT(*) n FROM jobs WHERE client_id = ? GROUP BY status', [(int) $u['id']]) as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }
        $jobs = DB::all(self::SELECT . ' WHERE j.client_id = ? AND j.status = ? ORDER BY j.created_at DESC', [(int) $u['id'], $tab]);
        view('jobs/mine', ['title' => 'My jobs', 'jobs' => $jobs, 'tab' => $tab, 'counts' => $counts, 'active' => 'jobs'], 'layouts/shell');
    }

    public function cancel(string $id): void
    {
        $u = require_role('client');
        $job = DB::one('SELECT * FROM jobs WHERE id = ? AND client_id = ?', [(int) $id, (int) $u['id']]);
        if (!$job) abort(404);
        if ($job['status'] !== 'open') {
            flash('error', 'Only open jobs can be cancelled.');
            back('/my/jobs');
        }
        DB::transaction(function () use ($job) {
            DB::update('jobs', ['status' => 'cancelled'], 'id = :id', ['id' => (int) $job['id']]);
            foreach (DB::all("SELECT provider_id FROM quotes WHERE job_id = ? AND status = 'pending'", [(int) $job['id']]) as $q) {
                notify((int) $q['provider_id'], 'quote', 'A job you quoted was closed', $job['title'], '/my/quotes');
            }
            DB::query("UPDATE quotes SET status = 'declined' WHERE job_id = ? AND status = 'pending'", [(int) $job['id']]);
        });
        flash('success', 'Your job was cancelled.');
        redirect('/my/jobs', ['tab' => 'cancelled']);
    }
}
