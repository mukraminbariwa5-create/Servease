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

/** Path prefix the app is served from, e.g. "/Servease" (no trailing slash). */
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
    echo Lang::translateHtml(View::render($view, ['message' => $message], 'layouts/app'));
    exit;
}

function view(string $template, array $data = [], string $layout = 'layouts/app'): void
{
    echo Lang::translateHtml(View::render($template, $data, $layout));
    exit;
}

/** Interface text in the chosen language (English or Filipino), for text built in code. */
function t(string $s): string { return Lang::text($s); }

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
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? url('/');
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
    // Whole pesos everywhere; centavos only when the amount actually has them
    $n = round((float) $amount, 2);
    return config('app.currency', '₱') . number_format($n, $decimals || fmod($n, 1.0) !== 0.0 ? 2 : 0);
}

/** Compact amount for tight spots such as chart labels: ₱950, ₱3.2k, ₱1.5M */
function short_money($amount): string
{
    $n = (float) $amount; $cur = config('app.currency', '₱');
    if ($n >= 1000000) return $cur . rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    if ($n >= 1000) return $cur . rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
    return $cur . number_format($n);
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
        'cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank transfer',
        'paymongo' => 'Online (PayMongo)', 'card' => 'Card',
    ][$m] ?? ucfirst($m);
}

/** Price as people read it: "₱900", "From ₱850", "₱400 / hour". */
function price_text($price, string $unit): string
{
    $m = money($price);
    return match ($unit) {
        'starting' => 'From ' . $m,
        'hour'     => $m . ' / hour',
        'day'      => $m . ' / day',
        default    => $m,
    };
}

function price_type_label(string $unit): string
{
    return ['fixed' => 'Fixed price', 'starting' => 'Starting price', 'hour' => 'Hourly rate', 'day' => 'Day rate'][$unit] ?? 'Price';
}

function price_unit_label(string $u): string
{
    return ['fixed' => '', 'hour' => '/hr', 'day' => '/day', 'starting' => '+'][$u] ?? '';
}

/** Single brand theme (kept as a function so templates stay simple). */
function brand_theme(): string
{
    return 'blue';
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

/** Approximate centre of each city we serve (lat, lng), for distances and maps. */
function city_coords(?string $city): ?array
{
    static $map = [
        'Quezon City' => [14.6760, 121.0437], 'Manila' => [14.5995, 120.9842], 'Makati' => [14.5547, 121.0244],
        'Taguig' => [14.5176, 121.0509], 'Pasig' => [14.5764, 121.0851], 'Mandaluyong' => [14.5794, 121.0359],
        'Parañaque' => [14.4793, 121.0198], 'Caloocan' => [14.6507, 120.9668], 'Las Piñas' => [14.4445, 120.9939],
        'Muntinlupa' => [14.4081, 121.0415], 'Marikina' => [14.6507, 121.1029], 'Cebu City' => [10.3157, 123.8854],
        'Davao City' => [7.1907, 125.4553], 'Zamboanga City' => [6.9214, 122.0790], 'Iloilo City' => [10.7202, 122.5621],
        'Baguio' => [16.4023, 120.5960], 'Cagayan de Oro' => [8.4542, 124.6319], 'Jolo' => [6.0535, 121.0020],
    ];
    return $map[(string) $city] ?? null;
}

/** Straight-line distance between two city centres in km, or null when either is unknown. */
function city_km(?string $a, ?string $b): ?float
{
    $p = city_coords($a); $q = city_coords($b);
    if (!$p || !$q) return null;
    if ($a === $b) return 0.0;
    [$la1, $lo1] = array_map('deg2rad', $p); [$la2, $lo2] = array_map('deg2rad', $q);
    $h = sin(($la2 - $la1) / 2) ** 2 + cos($la1) * cos($la2) * sin(($lo2 - $lo1) / 2) ** 2;
    return 6371 * 2 * asin(min(1, sqrt($h)));
}

/** "In your city", "~6 km away", or null when it's too far to matter (another island). */
function distance_label(?string $proCity, ?string $from = null): ?string
{
    $from ??= current_city();
    $km = city_km($proCity, $from);
    if ($km === null) return null;
    if ($km < 1) return 'In ' . $from;
    if ($km > 80) return null;
    return '~' . max(1, (int) round($km)) . ' km away';
}

/** "Pasig (4 km)" for cards (just the city when it's the viewer's own or far away). */
function city_with_distance(?string $city): string
{
    if (!$city) return '';
    $km = city_km($city, current_city());
    return $km !== null && $km >= 1 && $km <= 80 ? $city . ' (' . (int) round($km) . ' km)' : $city;
}

/** Every city, nearest first, from a starting city (unknown cities keep their order). */
function cities_by_distance(string $from): array
{
    $list = cities();
    usort($list, fn($a, $b) => (city_km($a, $from) ?? 99999) <=> (city_km($b, $from) ?? 99999));
    return $list;
}

/** ORDER BY fragment that lists rows from the nearest cities first: [sql, params]. */
function near_order(string $column, string $from): array
{
    $list = cities_by_distance($from);
    $marks = implode(', ', array_fill(0, count($list), '?'));
    return ["FIELD($column, $marks) = 0, FIELD($column, $marks)", array_merge($list, $list)];
}

function booking_ref(int $id): string
{
    return 'SV-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
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

/**
 * Phone app bar for a drill-down screen: a back arrow, the screen title and an optional action
 * (e.g. a Filters button). Call it at the top of a view; the header reads it when it renders.
 * Laptops keep the full website header. Called with no arguments it returns the current bar.
 */
function app_bar(?string $title = null, ?string $back = null, string $action = ''): ?array
{
    static $bar = null;
    if ($title !== null) $bar = ['title' => $title, 'back' => $back ?: '/', 'action' => $action];
    return $bar;
}

/**
 * Laptop header band for a page (title, one-line lead, numbers, actions), drawn full width by the
 * layout above the page. Takes the same options as the listing-hero partial. Phones don't show it.
 */
function page_hero(?array $opts = null): ?array
{
    static $hero = null;
    if ($opts !== null) $hero = $opts;
    return $hero;
}

/** Where a signed-in person lands: customers on Home, pros on their dashboard, admins on the admin panel. */
function home_for(?array $u): string
{
    return match ($u['role'] ?? null) { 'admin' => '/admin', 'provider' => '/dashboard', default => '/' };
}

function selected($a, $b): string { return (string) $a === (string) $b ? ' selected' : ''; }
function checked($cond): string { return $cond ? ' checked' : ''; }

/* =========================================================================
 |  Navigation helpers (shared by the top bar, tab bar and account menu)
 * ========================================================================= */

/** Unread notifications and messages for the signed-in user (cached per request). */
function nav_counts(): array
{
    static $c = null;
    if ($c !== null) return $c;
    $u = user();
    if (!$u) return $c = ['notifs' => 0, 'msgs' => 0];
    $uid = (int) $u['id'];
    return $c = [
        'notifs' => (int) DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$uid]),
        'msgs'   => (int) DB::value(
            'SELECT COUNT(*) FROM messages m JOIN conversations c ON c.id = m.conversation_id
             WHERE (c.user_one_id = ? OR c.user_two_id = ?) AND m.sender_id <> ? AND m.read_at IS NULL',
            [$uid, $uid, $uid]
        ),
    ];
}

/** The city shown in the location picker: ?city=, then the user's city, then a sensible default. */
function current_city(): string
{
    $q = trim((string) ($_GET['city'] ?? ''));
    if ($q !== '' && in_array($q, cities(), true)) return $q;
    $u = user();
    return ($u['city'] ?? '') ?: 'Quezon City';
}

/** Small count badge text: 1–9 or 9+. */
function count_label(int $n): string
{
    return $n > 9 ? '9+' : (string) $n;
}

/* =========================================================================
 |  Illustrations
 * ========================================================================= */

/** Soft background tint used behind each category's icon. */
function category_tint(?string $slug): string
{
    // One soft blue for every trade (the Blue & Yellow look); kept as a function so views stay simple
    return 'var(--sv-primary-soft)';
}

/** Line icon for a category, drawn in the brand blue on a soft-blue tile by the surrounding CSS. */
function category_art(?string $slug, ?string $icon = 'wrench', string $class = 'cat-art', int $size = 64): string
{
    $map = ['aircon' => 'air-vent', 'cleaning' => 'spray-can', 'plumbing' => 'droplets'];
    return icon($map[(string) $slug] ?? ($icon ?: 'wrench'), $class . ' cat-art-line');
}

/**
 * Photo for a service card: the pro's own latest work photo when there is one, otherwise a
 * photo of that trade (assets/img/photos/<slug>.jpg), otherwise null (the card shows the trade icon).
 * $seed picks between alternates so a page of cleaning services doesn't repeat one picture.
 */
function service_photo(?string $slug, ?string $cover = null, int $seed = 0): ?string
{
    if ($cover) return upload_url($cover);
    $alt = ['cleaning' => ['cleaning', 'deep-cleaning']];
    $name = isset($alt[$slug]) ? $alt[$slug][$seed % count($alt[$slug])] : (string) $slug;
    return $name !== '' ? stock_photo($name) : null;
}

/**
 * URL of a trade photo in the brand duotone (navy shadows, Servease blue midtones, cream highlights).
 * The tint is baked into a real image file (assets/img/photos/duo/) instead of a live SVG/CSS filter:
 * live filters can be painted onto the wrong elements by some browsers (e.g. during page transitions
 * the yellow logo and price tags turned blue), and they cost GPU time on every scroll.
 * The tinted copy is (re)made automatically when a source photo is added or replaced.
 */
function stock_photo(string $name): ?string
{
    $name = basename($name);
    $src = ROOT . '/assets/img/photos/' . $name . '.jpg';
    if (!is_file($src)) return null;
    $out = ROOT . '/assets/img/photos/duo/' . $name . '.jpg';
    if (!is_file($out) || filemtime($out) < filemtime($src)) {
        if (!duotone_bake($src, $out)) return asset('img/photos/' . $name . '.jpg'); // no GD: plain photo
    }
    return asset('img/photos/duo/' . $name . '.jpg');
}

/** Writes a duotone copy of a JPEG with GD. */
function duotone_bake(string $src, string $out): bool
{
    if (!function_exists('imagecreatefromjpeg') || !($im = @imagecreatefromjpeg($src))) return false;
    // Luminance 0..1 maps through these stops (shadows → midtones → light → highlights)
    $stops = [[.059, .122, .361], [.122, .275, .784], [.62, .72, .95], [1, .93, .80]];
    $lut = [];
    for ($l = 0; $l < 256; $l++) {
        $t = $l / 255 * 3; $i = min(2, (int) floor($t)); $f = $t - $i;
        $c = [];
        for ($k = 0; $k < 3; $k++) $c[$k] = (int) round(($stops[$i][$k] + ($stops[$i + 1][$k] - $stops[$i][$k]) * $f) * 255);
        $lut[$l] = ($c[0] << 16) | ($c[1] << 8) | $c[2];
    }
    $w = imagesx($im); $h = imagesy($im);
    imagepalettetotruecolor($im);
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $l = (int) round(.2126 * (($rgb >> 16) & 255) + .7152 * (($rgb >> 8) & 255) + .0722 * ($rgb & 255));
            imagesetpixel($im, $x, $y, $lut[min(255, $l)]);
        }
    }
    if (!is_dir(dirname($out))) @mkdir(dirname($out), 0775, true);
    $tmp = $out . '.' . getmypid() . '.tmp';
    $ok = @imagejpeg($im, $tmp, 84) && @rename($tmp, $out);
    if (!$ok) @unlink($tmp);
    imagedestroy($im);
    return $ok;
}

/** A price split for cards: optional "from", the amount, and the unit ("/ hour", "/ job"). */
function price_parts($price, string $unit): array
{
    return [
        'from'   => $unit === 'starting' ? 'from' : '',
        'amount' => money($price),
        'unit'   => ['hour' => '/ hour', 'day' => '/ day', 'fixed' => '/ job', 'starting' => '/ job'][$unit] ?? '',
    ];
}

/** An illustration from assets/img (people/ or scenes/) as an <img> tag. */
function illustration(string $file, string $alt = '', string $class = '', bool $lazy = true): string
{
    if (!is_file(ROOT . '/assets/img/' . $file)) return '';
    [$w, $h] = @getimagesize(ROOT . '/assets/img/' . $file) ?: [0, 0];
    return '<img class="' . e($class) . '" src="' . e(asset('img/' . $file)) . '" alt="' . e($alt) . '"'
        . ($w ? ' width="' . $w . '" height="' . $h . '"' : '')
        . ($lazy ? ' loading="lazy"' : ' fetchpriority="high"') . ' decoding="async">';
}

/** Empty states: a line icon in a soft-blue circle. */
/** Compact empty state that sits inside a card: icon tile, a title, a line of help and an optional action. */
function empty_inline(string $icon, string $title, string $text, string $actionHtml = ''): string
{
    return '<div class="empty-inline"><span class="empty-inline-ic" aria-hidden="true">' . icon($icon) . '</span>'
        . '<span class="grow"><strong>' . e($title) . '</strong><small>' . e($text) . '</small></span>' . $actionHtml . '</div>';
}

function empty_art(string $icon): string
{
    return '<div class="empty-art" aria-hidden="true"><span class="empty-ring"></span>' . icon($icon) . '</div>';
}
