<?php
$role = $u['role'];
$roleLabel = ['client' => 'Customer', 'provider' => 'Service pro', 'admin' => 'Admin'][$role] ?? '';
$groups = [];
$groups['Activity'] = array_merge($role === 'provider' ? [['/dashboard', 'dashboard', 'Dashboard', '']] : [], [
    ['/bookings', 'calendar', 'Bookings', ''],
    ['/notifications', 'bell', 'Notifications', nav_counts()['notifs'] ? (string) nav_counts()['notifs'] : ''],
]);
if ($role === 'client') {
    $groups['Hiring'] = [
        ['/jobs/new', 'plus', 'Post a job', ''],
        ['/my/jobs', 'briefcase', 'My jobs', ''],
        ['/saved', 'heart', 'Saved pros', ''],
    ];
} elseif ($role === 'provider') {
    $groups['Your business'] = [
        ['/providers/' . $u['id'], 'user', 'View public profile', ''],
        ['/provider/profile', 'pencil', 'Edit pro profile', ''],
        ['/my/quotes', 'file-text', 'My quotes', ''],
        ['/jobs', 'search', 'Find work', ''],
    ];
} else {
    $groups['Admin'] = [['/admin', 'shield', 'Admin panel', '']];
}
$groups['Account'] = [
    ['/settings', 'settings', 'Settings', ''],
    ['/how-it-works', 'help', 'Help and how it works', ''],
];
?>
<div class="profile-card card">
  <?= avatar($u, 'lg') ?>
  <div class="grow">
    <h1 class="profile-card-name"><?= e(full_name($u)) ?></h1>
    <p class="small muted truncate"><?= e($u['email']) ?></p>
    <span class="role-pill"><?= e($roleLabel) ?><?= $u['city'] ? ' · ' . e($u['city']) : '' ?></span>
  </div>
  <a class="icon-btn" href="<?= e(url('/settings')) ?>" aria-label="Edit profile"><?= icon('pencil') ?></a>
</div>

<?php if ($stats): ?>
  <div class="mini-stats">
    <div><strong><?= number_format((float) $stats['rating_avg'], 1) ?></strong><span>Rating</span></div>
    <div><strong><?= (int) $stats['rating_count'] ?></strong><span>Reviews</span></div>
    <div><strong><?= (int) $stats['jobs_completed'] ?></strong><span>Jobs done</span></div>
  </div>
<?php endif; ?>

<div class="menu-groups">
<?php foreach ($groups as $label => $items): ?>
  <section class="menu-group">
    <h2 class="menu-label"><?= e($label) ?></h2>
    <div class="menu-list card">
      <?php foreach ($items as [$href, $ic, $text, $count]): ?>
        <a class="menu-row" href="<?= e(url($href)) ?>">
          <span class="menu-icon"><?= icon($ic) ?></span>
          <span class="grow"><?= e($text) ?></span>
          <?php if ($count): ?><span class="count-pill"><?= e($count) ?></span><?php endif; ?>
          <?= icon('chevron-right', 'menu-chev') ?>
        </a>
      <?php endforeach; ?>
      <?php if ($label === 'Account'): ?>
        <button class="menu-row" type="button" data-install hidden>
          <span class="menu-icon menu-icon-brand"><?= icon('upload') ?></span>
          <span class="grow">Install the Servease app<small class="menu-sub">Opens from your home screen, like any app</small></span>
          <?= icon('chevron-right', 'menu-chev') ?>
        </button>
        <div class="menu-row menu-row-static">
          <span class="menu-icon"><?= icon('message-square') ?></span>
          <span class="grow">Language</span>
          <?= partial('lang-switch') ?>
        </div>
        <button class="menu-row" type="button" data-sound-toggle aria-pressed="true">
          <span class="menu-icon"><?= icon('volume') ?></span>
          <span class="grow">Sounds<small class="menu-sub" data-sound-label>Sound on</small></span>
          <span class="switch-pill" aria-hidden="true"></span>
        </button>
        <button class="menu-row theme-toggle" type="button" data-theme-toggle>
          <span class="menu-icon"><?= icon('moon', 'when-light') ?><?= icon('sun', 'when-dark') ?></span>
          <span class="grow"><span class="when-light">Dark mode</span><span class="when-dark">Light mode</span></span>
        </button>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>
</div>

<form class="mt-6 menu-logout" method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?>
  <button class="btn btn-block btn-lg btn-danger" type="submit"><?= icon('log-out') ?> Log out</button>
</form>
