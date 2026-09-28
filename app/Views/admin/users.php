<div class="page-title">
  <div><h1>Users</h1><p class="muted"><?= number_format($total) ?> account<?= $total === 1 ? '' : 's' ?></p></div>
  <form class="row row-wrap" method="get" action="<?= e(url('/admin/users')) ?>" role="search">
    <div class="input-icon"><?= icon('search') ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Name or email" aria-label="Search users" style="width:240px"></div>
    <select class="select" name="role" aria-label="Role" style="width:auto" data-autosubmit>
      <option value="">All roles</option>
      <?php foreach (['client' => 'Clients', 'provider' => 'Pros', 'admin' => 'Admins'] as $k => $l): ?><option value="<?= $k ?>" <?= $role === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Search</button>
  </form>
</div>
<div class="card table-wrap">
  <table class="table">
    <thead><tr><th>User</th><th>Role</th><th>City</th><th>Bookings</th><th>Joined</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><div class="cell-user"><?= avatar($r, 'sm') ?><div><div class="semibold"><?= e(full_name($r)) ?></div><div class="tiny subtle"><?= e($r['email']) ?></div></div></div></td>
          <td><?= e(ucfirst($r['role'])) ?><?php if ($r['role'] === 'provider'): ?><div class="tiny subtle"><?= e(ucfirst($r['verification_status'] ?? 'unverified')) ?></div><?php endif; ?></td>
          <td><?= e($r['city'] ?: '—') ?></td>
          <td><?= (int) $r['bookings'] ?></td>
          <td class="nowrap"><?= e(fmt_date($r['created_at'])) ?></td>
          <td><?= badge($r['status']) ?></td>
          <td class="text-right nowrap">
            <?php if ($r['role'] === 'provider'): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('/providers/' . $r['id'])) ?>">Profile</a><?php endif; ?>
            <?php if ($r['role'] !== 'admin'): ?>
              <form method="post" action="<?= e(url('/admin/users/' . $r['id'] . '/status')) ?>" style="display:inline" data-confirm="<?= $r['status'] === 'active' ? 'Suspend ' . e(full_name($r)) . '? They won\'t be able to sign in.' : 'Reactivate ' . e(full_name($r)) . '?' ?>">
                <?= csrf_field() ?><button class="btn btn-sm <?= $r['status'] === 'active' ? 'btn-danger' : '' ?>" type="submit"><?= $r['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="7" class="text-center muted" style="padding:40px">No users match your search.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?= pagination($total, $perPage, $page) ?>
