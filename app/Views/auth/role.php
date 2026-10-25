<div class="auth-head role-head">
  <?= partial('logo', ['class' => 'role-logo']) ?>
  <span class="role-brand">Servease<small>Home services</small></span>
  <h1>Create your account</h1>
  <p class="lead">Are you hiring, or offering a service?</p>
</div>

<div class="role-list mt-6">
  <a class="role-option" href="<?= e(url('/register', ['role' => 'client'])) ?>">
    <span class="role-option-ic"><?= icon('user') ?></span>
    <span class="grow">
      <strong>Customer</strong>
      <small>Book pros for jobs at home and keep track of every booking.</small>
    </span>
    <span class="role-option-art" aria-hidden="true">
      <?= icon('house', 'role-art-main') ?>
      <span class="role-mini role-mini-a"><?= icon('star') ?></span>
      <span class="role-mini role-mini-b"><?= icon('calendar-check') ?></span>
    </span>
    <span class="btn btn-primary role-option-btn">Get services <?= icon('arrow-right') ?></span>
  </a>
  <a class="role-option role-option-dark" href="<?= e(url('/register', ['role' => 'provider'])) ?>">
    <span class="role-option-ic"><?= icon('briefcase') ?></span>
    <span class="grow">
      <strong>Service provider</strong>
      <small>List your services and prices, get booking requests and send quotes.</small>
    </span>
    <span class="role-option-art" aria-hidden="true">
      <?= icon('wrench', 'role-art-main') ?>
      <span class="role-mini role-mini-a"><?= icon('calendar') ?></span>
      <span class="role-mini role-mini-b role-mini-text">₱2,400</span>
    </span>
    <span class="btn btn-dark role-option-btn">Start working <?= icon('arrow-right') ?></span>
  </a>
</div>

<p class="auth-switch mt-6">You can change this later in your profile settings.<br>Already have an account? <a href="<?= e(url('/login')) ?>">Log in</a></p>
