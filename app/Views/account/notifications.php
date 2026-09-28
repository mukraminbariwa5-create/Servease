<?php
$icons = ['booking' => 'calendar', 'message' => 'message', 'quote' => 'file-text', 'review' => 'star',
          'payment' => 'wallet', 'verification' => 'shield-check', 'welcome' => 'sparkles', 'job' => 'briefcase'];
$unread = count(array_filter($rows, fn($n) => !$n['read_at']));
?>
<div class="page-title">
  <div><h1>Notifications</h1><p class="muted">Updates about your bookings, quotes, payments and messages.</p></div>
  <?php if ($unread): ?>
    <form method="post" action="<?= e(url('/notifications/read-all')) ?>"><?= csrf_field() ?><button class="btn" type="submit"><?= icon('check') ?> Mark all as read</button></form>
  <?php endif; ?>
</div>
<?php if (!$rows): ?>
  <div class="card empty"><div class="empty-icon"><?= icon('bell') ?></div><h3>No notifications yet</h3><p>We'll let you know when something needs your attention.</p></div>
<?php else: ?>
  <div class="card list-divided">
    <?php foreach ($rows as $n): ?>
      <a class="list-item" href="<?= e(url('/notifications/' . $n['id'] . '/open')) ?>" style="<?= $n['read_at'] ? '' : 'background:color-mix(in srgb, var(--primary-soft) 55%, transparent)' ?>">
        <span class="notif-icon"><?= icon($icons[$n['type']] ?? 'bell') ?></span>
        <div class="grow" style="min-width:0">
          <div class="list-title"><?= e($n['title']) ?></div>
          <?php if ($n['body']): ?><div class="list-sub"><?= e($n['body']) ?></div><?php endif; ?>
        </div>
        <span class="tiny subtle nowrap"><?= e(time_ago($n['created_at'])) ?></span>
        <?php if (!$n['read_at']): ?><span class="unread-dot" aria-label="Unread"></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?= pagination($total, $perPage, $page) ?>
<?php endif; ?>
