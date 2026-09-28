<?php
declare(strict_types=1);

/* =========================================================================
 |  Config & URLs
 * ========================================================================= */

function config(string $key, $default = null)
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return $default;
        $value = $value[$part];
    }
    return $value;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Path prefix the app is served from, e.g. "/Fixoria" (no trailing slash). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) return $base;
    $configured = (string) config('app.url', '');
    if ($configured !== '') {
        $base = rtrim((string) parse_url($configured, PHP_URL_PATH), '/');
    } else {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/.');
    }
    return $base;
}

function base_url(): string
{
    $configured = (string) config('app.url', '');
    if ($configured !== '') return rtrim($configured, '/');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (is_https() ? 'https' : 'http') . '://' . $host . base_path();
}

function url(string $path = '/', array $query = []): string
{
    $u = base_path() . '/' . ltrim($path, '/');
    if ($query) {
        $query = array_filter($query, fn($v) => $v !== null && $v !== '');
        if ($query) $u .= '?' . http_build_query($query);
    }
    return $u === '' ? '/' : $u;
}

function absolute_url(string $path = '/', array $query = []): string
{
    return base_url() . '/' . ltrim(substr(url($path, $query), strlen(base_path())), '/');
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function upload_url(?string $path): ?string
{
    return $path ? url('uploads/' . ltrim($path, '/')) : null;
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $uri = rawurldecode($uri);
    $base = base_path();
    if ($base !== '' && stripos($uri, $base) === 0) $uri = substr($uri, strlen($base));
    if (strcasecmp($uri, '/index.php') === 0) $uri = '/';
    $uri = '/' . trim($uri, '/');
    return $uri;
}

function current_path_is(string $prefix): bool
{
    $p = request_path();
    return $prefix === '/' ? $p === '/' : ($p === $prefix || str_starts_with($p, rtrim($prefix, '/') . '/'));
}

/** Build the current URL with some query params replaced. */
function qs(array $replace): string
{
    $q = array_merge($_GET, $replace);
    return url(request_path(), $q);
}

/* =========================================================================
 |  Output & responses
 * ========================================================================= */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path, array $query = []): void
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path, $query);
    header('Location: ' . $target, true, 303);
    exit;
}

function back(string $fallback = '/'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
        header('Location: ' . $ref, true, 303);
        exit;
    }
    redirect($fallback);
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $code = 404, string $message = ''): void
{
    http_response_code($code);
    if (wants_json()) json_out(['error' => $message ?: 'Error'], $code);
    $view = in_array($code, [403, 404], true) ? "errors/$code" : 'errors/500';
    echo View::render($view, ['message' => $message], 'layouts/app');
    exit;
}

function view(string $template, array $data = [], string $layout = 'layouts/app'): void
{
    echo View::render($template, $data, $layout);
    exit;
}

/* =========================================================================
 |  Request input, CSRF, flash & old input
 * ========================================================================= */

function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        if (wants_json()) json_out(['error' => 'Your session expired. Please refresh the page.'], 419);
        flash('error', 'Your session expired. Please try again.');
        back();
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Store validation errors + old input and go back to the form. */
function back_with_errors(array $errors, string $fallback = '/'): void
{
    $_SESSION['_errors'] = $errors;
    $old = $_POST;
    unset($old['_token'], $old['password'], $old['password_confirmation'], $old['current_password']);
    $_SESSION['_old'] = $old;
    // Always surface something visible, even when a form has no inline slot for a field
    $first = reset($errors);
    flash('error', count($errors) > 1 ? 'Please fix the highlighted fields. ' . $first : (string) $first);
    back($fallback);
}

function errors(): array
{
    static $errors = null;
    if ($errors === null) { $errors = $_SESSION['_errors'] ?? []; unset($_SESSION['_errors']); }
    return $errors;
}

function error_for(string $field): ?string
{
    return errors()[$field] ?? null;
}

function old(string $key, $default = '')
{
    static $old = null;
    if ($old === null) { $old = $_SESSION['_old'] ?? []; unset($_SESSION['_old']); }
    return $old[$key] ?? $default;
}

/* =========================================================================
 |  Auth shortcuts
 * ========================================================================= */

function user(): ?array { return Auth::user(); }
function user_id(): ?int { return Auth::id(); }
function is_role(string ...$roles): bool { $u = Auth::user(); return $u && in_array($u['role'], $roles, true); }

function require_auth(): array
{
    $u = Auth::user();
    if (!$u) {
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? url('/dashboard');
        flash('info', 'Please sign in to continue.');
        redirect('/login');
    }
    return $u;
}

function require_role(string ...$roles): array
{
    $u = require_auth();
    if (!in_array($u['role'], $roles, true)) abort(403, 'You do not have access to this page.');
    return $u;
}

/* =========================================================================
 |  Formatting
 * ========================================================================= */

function money($amount, bool $decimals = false): string
{
    if ($amount === null || $amount === '') return '—';
    return config('app.currency', '₱') . number_format((float) $amount, $decimals ? 2 : 0);
}

function fmt_date(?string $date, string $format = 'M j, Y'): string
{
    return $date ? date($format, strtotime($date)) : '—';
}

function time_ago(?string $datetime): string
{
    if (!$datetime) return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    $units = [31536000 => 'y', 2592000 => 'mo', 604800 => 'w', 86400 => 'd', 3600 => 'h', 60 => 'm'];
    foreach ($units as $secs => $label) {
        if ($diff >= $secs) return floor($diff / $secs) . $label . ' ago';
    }
    return 'just now';
}

function str_limit(?string $text, int $limit = 120): string
{
    $text = trim(preg_replace('/\s+/', ' ', (string) $text));
    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)) . '…' : $text;
}

function slugify(string $text): string
{
    $text = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $text ?: 'item';
}

function full_name(array $u): string
{
    return trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: 'Unnamed';
}

function initials(array $u): string
{
    $f = mb_substr($u['first_name'] ?? '', 0, 1);
    $l = mb_substr($u['last_name'] ?? '', 0, 1);
    return mb_strtoupper(($f . $l) ?: 'U');
}

/** Deterministic hue from an id, used for avatars and cover gradients. */
function hue_for($seed): int
{
    return (int) (hexdec(substr(md5((string) $seed), 0, 4)) % 360);
}

function avatar(array $u, string $size = 'md', string $extra = ''): string
{
    $name = e(full_name($u));
    $cls = "avatar avatar-$size $extra";
    if (!empty($u['avatar'])) {
        return '<img class="' . $cls . '" src="' . e(upload_url($u['avatar'])) . '" alt="' . $name . '" loading="lazy">';
    }
    $h = hue_for($u['id'] ?? $name);
    return '<span class="' . $cls . '" style="--h:' . $h . '" aria-label="' . $name . '">' . e(initials($u)) . '</span>';
}

function stars(float $rating, string $size = 'sm'): string
{
    $out = '<span class="stars stars-' . $size . '" aria-label="' . number_format($rating, 1) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $rating >= $i ? 'full' : ($rating >= $i - 0.5 ? 'half' : 'empty');
        $out .= '<span class="star star-' . $fill . '">' . icon('star')
             . ($fill === 'half' ? '<span class="star-half-fill">' . icon('star') . '</span>' : '') . '</span>';
    }
    return $out . '</span>';
}

function badge(string $status): string
{
    $map = [
        // bookings
        'requested'   => ['Requested', 'amber'],
        'accepted'    => ['Confirmed', 'blue'],
        'declined'    => ['Declined', 'gray'],
        'in_progress' => ['In progress', 'violet'],
        'completed'   => ['Completed', 'green'],
        'cancelled'   => ['Cancelled', 'gray'],
        // payments
        'unpaid'      => ['Unpaid', 'gray'],
        'pending'     => ['Pending', 'amber'],
        'paid'        => ['Paid', 'green'],
        'failed'      => ['Failed', 'red'],
        'refunded'    => ['Refunded', 'gray'],
        // jobs & quotes
        'open'        => ['Open', 'green'],
        'assigned'    => ['Assigned', 'blue'],
        'withdrawn'   => ['Withdrawn', 'gray'],
        // verification / users
        'verified'    => ['Verified', 'green'],
        'unverified'  => ['Not verified', 'gray'],
        'rejected'    => ['Rejected', 'red'],
        'active'      => ['Active', 'green'],
        'suspended'   => ['Suspended', 'red'],
    ];
    [$label, $color] = $map[$status] ?? [ucwords(str_replace('_', ' ', $status)), 'gray'];
    return '<span class="badge badge-' . $color . '">' . e($label) . '</span>';
}

function payment_method_label(string $m): string
{
    return [
        'cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya', 'bank_transfer' => 'Bank transfer',
        'paymongo' => 'Online (PayMongo)', 'card' => 'Card',
    ][$m] ?? ucfirst($m);
}

function price_unit_label(string $u): string
{
    return ['fixed' => '', 'hour' => '/hr', 'day' => '/day', 'starting' => '+'][$u] ?? '';
}

/** Active colour theme: blue (default), green or navy, set in config app.theme */
function brand_theme(): string
{
    $t = (string) config('app.theme', 'blue');
    return in_array($t, ['blue', 'green', 'navy'], true) ? $t : 'blue';
}

function paymongo_enabled(): bool
{
    return (bool) config('payments.paymongo.enabled') && config('payments.paymongo.secret_key');
}

/* =========================================================================
 |  Pagination
 * ========================================================================= */

/** Returns [offset, perPage, page]. */
function page_params(int $perPage = 12): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    return [($page - 1) * $perPage, $perPage, $page];
}

function pagination(int $total, int $perPage, int $page): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) return '';
    $html = '<nav class="pagination" aria-label="Pagination">';
    $html .= $page > 1
        ? '<a class="page-link" href="' . e(qs(['page' => $page - 1])) . '" aria-label="Previous">' . icon('chevron-left') . '</a>'
        : '<span class="page-link is-disabled">' . icon('chevron-left') . '</span>';
    $window = array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], fn($p) => $p >= 1 && $p <= $pages));
    sort($window);
    $prev = 0;
    foreach ($window as $p) {
        if ($p - $prev > 1) $html .= '<span class="page-gap">…</span>';
        $html .= $p === $page
            ? '<span class="page-link is-active" aria-current="page">' . $p . '</span>'
            : '<a class="page-link" href="' . e(qs(['page' => $p])) . '">' . $p . '</a>';
        $prev = $p;
    }
    $html .= $page < $pages
        ? '<a class="page-link" href="' . e(qs(['page' => $page + 1])) . '" aria-label="Next">' . icon('chevron-right') . '</a>'
        : '<span class="page-link is-disabled">' . icon('chevron-right') . '</span>';
    return $html . '</nav>';
}

/* =========================================================================
 |  Domain helpers
 * ========================================================================= */

function notify(int $userId, string $type, string $title, string $body = '', string $link = ''): void
{
    DB::insert('notifications', [
        'user_id' => $userId, 'type' => $type, 'title' => $title,
        'body' => $body, 'link' => $link,
    ]);
}

function categories(): array
{
    static $cats = null;
    if ($cats === null) {
        $cats = DB::all('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name');
    }
    return $cats;
}

function cities(): array
{
    return ['Quezon City', 'Manila', 'Makati', 'Taguig', 'Pasig', 'Mandaluyong', 'Parañaque',
            'Caloocan', 'Las Piñas', 'Muntinlupa', 'Marikina', 'Cebu City', 'Davao City',
            'Zamboanga City', 'Iloilo City', 'Baguio', 'Cagayan de Oro', 'Jolo'];
}

function booking_ref(int $id): string
{
    return 'FX-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
}

/* =========================================================================
 |  Form helpers for templates
 * ========================================================================= */

/** " is-invalid" when the field has a validation error (use on .field wrappers). */
function invalid(string $field): string
{
    return error_for($field) ? ' is-invalid' : '';
}

/** Error message markup for a field, or an empty string. */
function field_error(string $field): string
{
    $m = error_for($field);
    return $m ? '<span class="field-error" role="alert">' . e($m) . '</span>' : '';
}

function selected($a, $b): string { return (string) $a === (string) $b ? ' selected' : ''; }
function checked($cond): string { return $cond ? ' checked' : ''; }
