<div class="page-title"><div><h1>Reviews</h1><p class="muted">Hide reviews that break the rules. Hidden reviews don't count toward a pro's rating.</p></div></div>
<div class="card list-divided">
  <?php foreach ($rows as $r): ?>
    <div class="list-item" style="align-items:flex-start;<?= $r['is_hidden'] ? 'opacity:.55' : '' ?>">
      <div class="grow" style="min-width:0">
        <div class="row row-wrap" style="--gap:10px"><?= stars((float) $r['rating']) ?><span class="semibold small"><?= e($r['c_first'] . ' ' . $r['c_last']) ?></span><span class="small subtle">reviewed</span><a class="small semibold" href="<?= e(url('/providers/' . $r['provider_id'])) ?>"><?= e($r['p_first'] . ' ' . $r['p_last']) ?></a><span class="tiny subtle"><?= e(time_ago($r['created_at'])) ?></span><?= $r['is_hidden'] ? '<span class="badge badge-red">Hidden</span>' : '' ?></div>
        <?php if ($r['comment']): ?><p class="mt-2 small"><?= e($r['comment']) ?></p><?php endif; ?>
      </div>
      <form method="post" action="<?= e(url('/admin/reviews/' . $r['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn btn-sm <?= $r['is_hidden'] ? '' : 'btn-danger' ?>" type="submit"><?= $r['is_hidden'] ? 'Unhide' : 'Hide' ?></button></form>
    </div>
  <?php endforeach; ?>
  <?php if (!$rows): ?><div class="empty"><p>No reviews yet.</p></div><?php endif; ?>
</div>
<?= pagination($total, $perPage, $page) ?>
