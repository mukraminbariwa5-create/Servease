<div class="page-title"><div><h1>Pro verification</h1><p class="muted">Check each pro's ID or license before they get the Verified badge.</p></div></div>
<nav class="tabs">
  <?php foreach ($tabs as $k => $l): ?><a class="tab <?= $tab === $k ? 'is-active' : '' ?>" href="<?= e(url('/admin/verifications', ['tab' => $k])) ?>"><?= e($l) ?> <span class="n"><?= $counts[$k] ?></span></a><?php endforeach; ?>
</nav>
<?php if (!$rows): ?>
  <div class="card empty"><div class="empty-icon"><?= icon('shield-check') ?></div><h3><?= $tab === 'pending' ? 'All caught up' : 'Nothing here' ?></h3><p><?= $tab === 'pending' ? 'No pros are waiting for verification right now.' : 'No pros in this list.' ?></p></div>
<?php else: ?>
  <div class="stack" style="--gap:16px">
    <?php foreach ($rows as $r): ?>
      <div class="card card-body">
        <div class="row row-wrap row-top" style="--gap:18px">
          <?= avatar($r, 'lg') ?>
          <div class="grow" style="min-width:240px">
            <div class="row" style="--gap:8px"><a class="semibold" style="color:var(--text);font-size:1.05rem" href="<?= e(url('/providers/' . $r['id'])) ?>"><?= e(full_name($r)) ?></a><?= badge($r['verification_status']) ?></div>
            <div class="small muted"><?= e($r['category_name'] ?? 'No category') ?> · <?= e($r['city'] ?? '') ?> · joined <?= e(fmt_date($r['joined'])) ?></div>
            <div class="small muted"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></div>
            <?php if ($r['headline']): ?><p class="small mt-2">“<?= e($r['headline']) ?>”</p><?php endif; ?>
            <?php if ($r['verification_note']): ?><p class="small mt-2" style="color:var(--red)">Note: <?= e($r['verification_note']) ?></p><?php endif; ?>
          </div>
          <div class="stack" style="--gap:10px;min-width:260px">
            <?php if ($r['verification_doc']): ?>
              <a class="btn btn-block" href="<?= e(url('/admin/verifications/' . $r['id'] . '/document')) ?>" target="_blank" rel="noopener"><?= icon('file-text') ?> Open submitted document</a>
            <?php else: ?><span class="small subtle">No document submitted.</span><?php endif; ?>
            <?php if ($r['verification_status'] !== 'verified' && $r['verification_doc']): ?>
              <form method="post" action="<?= e(url('/admin/verifications/' . $r['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="decision" value="approve"><button class="btn btn-success btn-block" type="submit"><?= icon('check') ?> Approve</button></form>
              <form method="post" action="<?= e(url('/admin/verifications/' . $r['id'])) ?>" class="row" style="--gap:6px">
                <?= csrf_field() ?><input type="hidden" name="decision" value="reject">
                <input class="input" name="note" placeholder="Reason for rejecting" maxlength="255" required style="height:36px">
                <button class="btn btn-danger btn-sm" type="submit">Reject</button>
              </form>
            <?php elseif ($r['verification_status'] === 'verified'): ?>
              <form method="post" action="<?= e(url('/admin/verifications/' . $r['id'])) ?>" class="row" style="--gap:6px">
                <?= csrf_field() ?><input type="hidden" name="decision" value="reject">
                <input class="input" name="note" placeholder="Reason to revoke" maxlength="255" required style="height:36px">
                <button class="btn btn-danger btn-sm" type="submit">Revoke</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
