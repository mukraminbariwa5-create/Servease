<?php
/** Bottom tab bar for phones (hidden on wider screens). */
$u = user();
$counts = nav_counts();
$path = request_path();
$tabs = [['/', 'house', 'Home', $path === '/']];
$tabs[] = ['/services', 'grid', 'Services', current_path_is('/categories') || current_path_is('/services') || current_path_is('/providers')];
if (!$u) {
    $tabs[] = ['/jobs', 'briefcase', 'Jobs', current_path_is('/jobs')];
    $tabs[] = ['/how-it-works', 'help', 'Help', current_path_is('/how-it-works')];
    $tabs[] = ['/login', 'user', 'Log in', current_path_is('/login') || current_path_is('/register') || current_path_is('/welcome')];
} else {
    if (is_role('provider')) {
        $tabs[0] = ['/dashboard', 'dashboard', 'Dashboard', current_path_is('/dashboard') || $path === '/'];
        $tabs[1] = ['/jobs', 'briefcase', 'Find work', current_path_is('/jobs')];
    }
    if (is_role('admin')) {
        $tabs[] = ['/admin', 'shield', 'Admin', current_path_is('/admin')];
    } else {
        $tabs[] = ['/bookings', 'calendar', 'Bookings', current_path_is('/bookings')];
    }
    $tabs[] = ['/messages', 'message', 'Inbox', current_path_is('/messages'), $counts['msgs']];
    $tabs[] = ['/account', 'user', 'Profile', current_path_is('/account') || current_path_is('/settings') || current_path_is('/notifications') || current_path_is('/saved') || current_path_is('/my') || current_path_is('/provider/profile')];
}
?>
<nav class="tabbar" aria-label="App">
  <?php foreach ($tabs as $t): [$href, $ic, $label, $on] = $t; $n = $t[4] ?? 0; ?>
    <a class="tab-item<?= $on ? ' is-active' : '' ?>" href="<?= e(url($href)) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
      <span class="tab-icon"><?php if ($on): ?><span class="tab-pill" aria-hidden="true"></span><?php endif; ?><?= icon($ic) ?><?php if ($n): ?><span class="count-dot"><?= count_label($n) ?></span><?php endif; ?></span>
      <span class="tab-label"><?= e($label) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
