<?php $err = errors(); $f = fn($k) => old($k, $u[$k] ?? ''); $inv = fn($k) => isset($err[$k]) ? ' is-invalid' : ''; ?>
<?php app_bar('Settings', '/account');
page_hero([
    'crumbs' => [['Home', home_for($u)], ['Settings', null]], 'narrow' => true,
    'title' => 'Settings', 'lead' => 'Your photo, personal details and password.',
    'stats' => array_values(array_filter([['mail', e($u['email'])], $u['city'] ? ['map-pin', e($u['city'])] : null, [$u['email_verified_at'] ? 'shield-check' : 'alert', $u['email_verified_at'] ? 'Email verified' : 'Email not verified yet']])),
]); ?>
<div class="page-title">
  <div class="app-dup"><h1>Settings</h1><p class="muted">Manage your photo, personal details and password.</p></div>
</div>

<div class="settings">
  <section class="settings-row">
    <div class="settings-intro">
      <h3>Profile photo</h3>
      <p>A clear, friendly face photo builds trust with <?= $u['role'] === 'provider' ? 'clients' : 'pros' ?>.</p>
    </div>
    <div class="card card-body">
      <div class="avatar-editor">
        <?= avatar($u, 'xl') ?>
        <div class="grow">
          <div class="semibold"><?= e(full_name($u)) ?></div>
          <div class="small muted mb-3">JPG, PNG or WebP, up to 3 MB</div>
          <div class="row row-wrap" style="--gap:8px">
            <form method="post" action="<?= e(url('/settings/avatar')) ?>" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <label class="btn btn-sm btn-primary" style="cursor:pointer"><?= icon('upload') ?> <?= $u['avatar'] ? 'Change photo' : 'Upload photo' ?>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="this.form.submit()">
              </label>
            </form>
            <?php if ($u['avatar']): ?>
              <form method="post" action="<?= e(url('/settings/avatar')) ?>" data-confirm="Remove your profile photo?"><?= csrf_field() ?><input type="hidden" name="remove" value="1"><button class="btn btn-sm btn-ghost" type="submit"><?= icon('trash') ?> Remove</button></form>
            <?php endif; ?>
          </div>
          <?php if (!empty($err['avatar'])): ?><div class="field-error mt-2"><?= e($err['avatar']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="settings-row">
    <div class="settings-intro">
      <h3>Personal details</h3>
      <p>Your phone number is only shared with people you have a confirmed booking with.</p>
      <div class="mt-3"><?= $u['email_verified_at'] ? '<span class="badge badge-green">Email verified</span>' : '<span class="badge badge-amber">Email not verified</span>' ?></div>
    </div>
    <form class="card" method="post" action="<?= e(url('/settings')) ?>" data-savebar>
      <?= csrf_field() ?>
      <div class="card-body">
        <div class="form-row">
          <div class="field<?= $inv('first_name') ?>"><label class="label" for="first_name">First name</label><div class="input-icon"><?= icon('user') ?><input class="input" id="first_name" name="first_name" value="<?= e($f('first_name')) ?>" required maxlength="80" autocomplete="given-name"></div><?php if (isset($err['first_name'])): ?><span class="field-error"><?= e($err['first_name']) ?></span><?php endif; ?></div>
          <div class="field<?= $inv('last_name') ?>"><label class="label" for="last_name">Last name</label><input class="input" id="last_name" name="last_name" value="<?= e($f('last_name')) ?>" required maxlength="80" autocomplete="family-name"><?php if (isset($err['last_name'])): ?><span class="field-error"><?= e($err['last_name']) ?></span><?php endif; ?></div>
        </div>
        <div class="field<?= $inv('email') ?> mt-5"><label class="label" for="email">Email address</label><div class="input-icon"><?= icon('mail') ?><input class="input" id="email" name="email" type="email" value="<?= e($f('email')) ?>" required autocomplete="email"></div><?php if (isset($err['email'])): ?><span class="field-error"><?= e($err['email']) ?></span><?php else: ?><span class="help">Changing your email will ask you to confirm the new address.</span><?php endif; ?></div>
        <div class="form-row mt-5">
          <div class="field<?= $inv('phone') ?>"><label class="label" for="phone">Mobile number <span class="opt">optional</span></label><div class="input-icon"><?= icon('phone') ?><input class="input" id="phone" name="phone" value="<?= e($f('phone')) ?>" placeholder="0917 123 4567" autocomplete="tel"></div><?php if (isset($err['phone'])): ?><span class="field-error"><?= e($err['phone']) ?></span><?php endif; ?></div>
          <div class="field"><label class="label" for="city">City</label>
            <div class="input-icon"><?= icon('map-pin') ?><select class="select" id="city" name="city"><option value="">Select your city</option><?php foreach (cities() as $c): ?><option <?= $f('city') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
          </div>
        </div>
      </div>
      <div class="card-footer row" style="justify-content:flex-end"><button class="btn btn-primary" type="submit">Save changes</button></div>
    </form>
  </section>

  <section class="settings-row" id="password">
    <div class="settings-intro">
      <h3>Password</h3>
      <p>Use at least 8 characters with a letter and a number.</p>
    </div>
    <form class="card" method="post" action="<?= e(url('/settings/password')) ?>">
      <?= csrf_field() ?>
      <div class="card-body">
        <div class="field<?= $inv('current_password') ?>">
          <label class="label" for="current_password">Current password</label>
          <div class="password-wrap input-icon"><?= icon('lock') ?><input class="input" id="current_password" name="current_password" type="password" required autocomplete="current-password" placeholder="Enter your current password"><button class="password-toggle" type="button" data-pw-toggle aria-label="Show password"><?= icon('eye') ?></button></div>
          <?php if (isset($err['current_password'])): ?><span class="field-error"><?= e($err['current_password']) ?></span><?php endif; ?>
        </div>
        <div class="form-row mt-5">
          <div class="field<?= $inv('password') ?>">
            <label class="label" for="new_password">New password</label>
            <div class="password-wrap input-icon"><?= icon('lock') ?><input class="input" id="new_password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="New password" data-pw-meter="#pw-meter" data-pw-rules="#pw-rules"><button class="password-toggle" type="button" data-pw-toggle aria-label="Show password"><?= icon('eye') ?></button></div>
            <div class="pw-meter-row"><div class="pw-meter" id="pw-meter"><span></span></div><span class="pw-label" data-pw-label></span></div>
            <?php if (isset($err['password'])): ?><span class="field-error"><?= e($err['password']) ?></span><?php endif; ?>
          </div>
          <div class="field">
            <label class="label" for="password_confirmation">Confirm new password</label>
            <div class="password-wrap input-icon"><?= icon('lock') ?><input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Type it again" data-match="#new_password"></div>
            <span class="help" data-match-msg></span>
          </div>
        </div>
        <ul class="pw-rules mt-3" id="pw-rules"><li data-rule="len">8+ characters</li><li data-rule="letter">A letter</li><li data-rule="number">A number</li></ul>
      </div>
      <div class="card-footer row" style="justify-content:flex-end"><button class="btn btn-dark" type="submit"><?= icon('lock') ?> Update password</button></div>
    </form>
  </section>
</div>
