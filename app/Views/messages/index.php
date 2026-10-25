<?php
$uid = user_id();
$lastId = $messages ? (int) end($messages)['id'] : 0;
?>
<?php
$unreadConvos = count(array_filter($list, fn($r) => !empty($r['unread'])));
page_hero([
    'crumbs' => [['Home', home_for(user())], ['Inbox', null]],
    'title' => 'Inbox', 'lead' => 'Chat with pros and clients before and after you book.',
    'stats' => [['message', '<strong>' . count($list) . '</strong> conversation' . (count($list) === 1 ? '' : 's')], ['bell', '<strong>' . $unreadConvos . '</strong> unread']],
]);
?>
<div class="page-title"><div><h1>Messages</h1><p class="muted">Chat with pros and clients before and after you book.</p></div></div>

<div class="card chat <?= $c ? 'has-active' : '' ?>">
  <aside class="chat-list" aria-label="Conversations">
    <div class="chat-list-head"><div class="semibold">Inbox</div></div>
    <?php if (!$list): ?>
      <div class="empty" style="padding:30px 18px"><p class="small">No conversations yet. Message a pro from their profile to get started.</p><a class="btn btn-sm btn-primary" href="<?= e(url('/providers')) ?>">Find a pro</a></div>
    <?php endif; ?>
    <?php foreach ($list as $row): $o = ['id' => $row['o_id'], 'first_name' => $row['o_first'], 'last_name' => $row['o_last'], 'avatar' => $row['o_avatar']]; ?>
      <a class="convo <?= $c && (int) $c['id'] === (int) $row['id'] ? 'is-active' : '' ?> <?= $row['unread'] ? 'is-unread' : '' ?>" href="<?= e(url('/messages/' . $row['id'])) ?>">
        <?= avatar($o, 'md') ?>
        <div class="grow" style="min-width:0">
          <div class="row row-between" style="--gap:6px"><span class="convo-name truncate"><?= e(full_name($o)) ?></span><span class="tiny subtle nowrap"><?= e(time_ago($row['last_message_at'])) ?></span></div>
          <div class="convo-last truncate"><?= $row['last_body'] !== null ? ((int) $row['last_sender'] === $uid ? 'You: ' : '') . e($row['last_body']) : '<span class="subtle">No messages yet</span>' ?></div>
        </div>
        <?php if ($row['unread']): ?><span class="unread-dot" aria-label="<?= (int) $row['unread'] ?> unread"></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </aside>

  <section class="chat-main" <?= $c ? 'data-chat data-poll="' . e(url('/messages/' . $c['id'] . '/poll')) . '" data-last="' . $lastId . '"' : '' ?>>
    <?php if (!$c): ?>
      <div class="chat-empty"><div class="empty"><?= empty_art('message') ?><h3>Select a conversation</h3><p>Your messages with pros and clients appear here.</p></div></div>
    <?php else: $o = $c['other']; ?>
      <header class="chat-head">
        <a class="btn btn-ghost btn-icon btn-sm show-md" href="<?= e(url('/messages')) ?>" aria-label="Back to inbox"><?= icon('arrow-left') ?></a>
        <?= avatar($o, 'md') ?>
        <div class="grow" style="min-width:0">
          <div class="semibold truncate"><?= e(full_name($o)) ?></div>
          <div class="small muted truncate"><?= e($o['role'] === 'provider' ? ($o['headline'] ?: 'Pro') : 'Client' . ($o['city'] ? ' · ' . $o['city'] : '')) ?></div>
        </div>
        <?php if ($o['role'] === 'provider'): ?>
          <a class="btn btn-sm" href="<?= e(url('/providers/' . $o['id'])) ?>">View profile</a>
          <?php if (is_role('client')): ?><a class="btn btn-sm btn-primary hide-sm" href="<?= e(url('/providers/' . $o['id'] . '/book')) ?>">Book</a><?php endif; ?>
        <?php endif; ?>
      </header>
      <?php if (!empty($shared)): ?>
        <div class="chat-shared small">
          <span class="subtle nowrap"><?= icon('calendar', 'icon-sm') ?> Bookings:</span>
          <?php foreach ($shared as $s): ?><a class="chip" href="<?= e(url('/bookings/' . $s['id'])) ?>"><?= e(str_limit($s['title'], 34)) ?></a><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="chat-body" aria-live="polite">
        <?php if (!$messages): ?>
          <div class="chat-empty"><p class="small subtle">Say hello and describe what you need.</p></div>
        <?php endif; ?>
        <?php $lastDay = null; foreach ($messages as $m): $mine = (int) $m['sender_id'] === $uid; $day = date('Y-m-d', strtotime($m['created_at'])); ?>
          <?php if ($day !== $lastDay): $lastDay = $day; ?><div class="day-sep"><?= e($day === date('Y-m-d') ? 'Today' : fmt_date($day, 'D, M j')) ?></div><?php endif; ?>
          <div class="msg <?= $mine ? 'msg-me' : 'msg-them' ?>"><?= e($m['body']) ?></div>
          <div class="msg-time <?= $mine ? 'me' : '' ?>"><?= e(date('g:i A', strtotime($m['created_at']))) ?><?= $mine && $m['read_at'] ? ' · Seen' : '' ?></div>
        <?php endforeach; ?>
      </div>
      <form class="chat-form" method="post" action="<?= e(url('/messages/' . $c['id'])) ?>">
        <?= csrf_field() ?>
        <label class="sr-only" for="msg-body">Message</label>
        <textarea class="textarea" id="msg-body" name="body" rows="1" maxlength="2000" placeholder="Write a message…" enterkeyhint="send" required></textarea>
        <button class="btn btn-primary btn-icon" type="submit" aria-label="Send" data-no-lock style="height:44px;width:44px"><?= icon('send') ?></button>
      </form>
    <?php endif; ?>
  </section>
</div>
