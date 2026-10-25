<div class="auth-hero show-md"><?= partial('art', ['kind' => 'welcome']) ?></div>
<div class="auth-head">
  <h1>Log in</h1>
  <p class="lead">Your bookings, quotes and messages are waiting.</p>
</div>

<form id="login-form" class="mt-6" method="post" action="<?= e(url('/login')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field<?= invalid('email') ?>">
    <label class="label" for="email">Email address</label>
    <div class="input-icon"><?= icon('mail') ?><input class="input" id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="you@example.com" autocomplete="email" required></div>
    <?= field_error('email') ?>
  </div>
  <div class="field">
    <div class="label-row"><label class="label" for="password">Password</label><a class="label-aside" href="<?= e(url('/forgot-password')) ?>">Forgot password?</a></div>
    <div class="password-wrap input-icon">
      <?= icon('lock') ?>
      <input class="input" id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
      <button class="password-toggle" type="button" data-pw-toggle aria-label="Show password"><?= icon('eye') ?></button>
    </div>
  </div>
  <div class="stack-buttons mt-6">
    <button class="btn btn-primary btn-lg btn-block" type="submit"><?= icon('log-in') ?> Log in</button>
    <a class="btn btn-dark btn-lg btn-block" href="<?= e(url('/register')) ?>"><?= icon('user-plus') ?> Create account</a>
  </div>
</form>

<?php if (config('app.demo')): ?>
  <div class="or">or try a demo account</div>
  <div class="demo-list">
    <?php foreach ([
        ['client@servease.test', 'Customer', 'Maria Santos', 'Book pros, post jobs, pay and review', 'MS', 150],
        ['provider@servease.test', 'Pro', 'Juan Dela Cruz', 'Quotes, bookings and earnings', 'JD', 215],
        ['admin@servease.test', 'Admin', 'Alex Reyes', 'Verify pros and manage the platform', 'AR', 330],
    ] as [$email, $role, $name, $desc, $ini, $h]): ?>
      <button class="demo-item" type="button" data-demo="<?= e($email) ?>" data-pw="servease123">
        <span class="avatar avatar-md" style="--h:<?= $h ?>"><?= $ini ?></span>
        <span class="grow">
          <span class="demo-name"><?= e($name) ?> <span class="demo-role"><?= e($role) ?></span></span>
          <span class="demo-desc"><?= e($desc) ?></span>
        </span>
        <?= icon('arrow-right') ?>
      </button>
    <?php endforeach; ?>
  </div>
  <?php if (Demo::on()): ?>
    <p class="demo-note">This is a public demo that resets every day. Uploads are turned off.</p>
  <?php endif; ?>
<?php endif; ?>
