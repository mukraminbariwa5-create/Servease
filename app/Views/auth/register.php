<div class="auth-head">
  <h1>Create your account</h1>
  <p class="lead">Free forever. Takes less than a minute.</p>
</div>

<form class="mt-8" method="post" action="<?= e(url('/register')) ?>" novalidate>
  <?= csrf_field() ?>
  <fieldset class="fieldset">
    <legend class="label">I want to…</legend>
    <div class="choices grid-2" style="display:grid">
      <label class="choice">
        <input type="radio" name="role" value="client"<?= checked($role === 'client') ?> data-role-choice>
        <span class="choice-body">
          <span class="choice-icon"><?= icon('home') ?></span>
          <span><span class="choice-title" style="display:block">Hire a pro</span><span class="choice-desc">Get help with jobs at home</span></span>
        </span>
      </label>
      <label class="choice">
        <input type="radio" name="role" value="provider"<?= checked($role === 'provider') ?> data-role-choice>
        <span class="choice-body">
          <span class="choice-icon"><?= icon('wrench') ?></span>
          <span><span class="choice-title" style="display:block">Work as a pro</span><span class="choice-desc">Find clients and grow</span></span>
        </span>
      </label>
    </div>
  </fieldset>

  <div class="form-row mt-6">
    <div class="field<?= invalid('first_name') ?>">
      <label class="label" for="first_name">First name</label>
      <div class="input-icon"><?= icon('user') ?><input class="input" id="first_name" name="first_name" value="<?= e(old('first_name')) ?>" placeholder="Juan" autocomplete="given-name" required></div>
      <?= field_error('first_name') ?>
    </div>
    <div class="field<?= invalid('last_name') ?>">
      <label class="label" for="last_name">Last name</label>
      <input class="input" id="last_name" name="last_name" value="<?= e(old('last_name')) ?>" placeholder="Dela Cruz" autocomplete="family-name" required>
      <?= field_error('last_name') ?>
    </div>
  </div>
  <div class="field<?= invalid('email') ?> mt-5">
    <label class="label" for="email">Email address</label>
    <div class="input-icon"><?= icon('mail') ?><input class="input" id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="you@example.com" autocomplete="email" required></div>
    <?= field_error('email') ?>
  </div>
  <div class="form-row mt-5">
    <div class="field<?= invalid('phone') ?>">
      <label class="label" for="phone">Mobile <span class="opt">optional</span></label>
      <div class="input-icon"><?= icon('phone') ?><input class="input" id="phone" name="phone" type="tel" value="<?= e(old('phone')) ?>" placeholder="09XX XXX XXXX" autocomplete="tel"></div>
      <?= field_error('phone') ?>
    </div>
    <div class="field<?= invalid('city') ?>">
      <label class="label" for="city">City</label>
      <div class="input-icon"><?= icon('map-pin') ?>
        <select class="select" id="city" name="city" required>
          <option value="">Select city</option>
          <?php foreach (cities() as $c): ?><option<?= selected(old('city'), $c) ?>><?= e($c) ?></option><?php endforeach; ?>
        </select>
      </div>
      <?= field_error('city') ?>
    </div>
  </div>

  <fieldset class="fieldset field<?= invalid('category_id') ?> mt-5" data-provider-only <?= $role === 'provider' ? '' : 'hidden' ?>>
    <legend class="label">What's your main service?</legend>
    <div class="tiles">
      <?php foreach (categories() as $c): ?>
        <label class="tile">
          <input type="radio" name="category_id" value="<?= (int) $c['id'] ?>"<?= checked((string) old('category_id') === (string) $c['id']) ?>>
          <span class="tile-body"><span class="tile-icon"><?= icon($c['icon']) ?></span><span class="tile-name"><?= e($c['name']) ?></span></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?= field_error('category_id') ?>
  </fieldset>

  <div class="field<?= invalid('password') ?> mt-5">
    <label class="label" for="password">Password</label>
    <div class="password-wrap input-icon">
      <?= icon('lock') ?>
      <input class="input" id="password" name="password" type="password" placeholder="Create a password" autocomplete="new-password" required data-pw-meter="#pw-meter" data-pw-rules="#pw-rules">
      <button class="password-toggle" type="button" data-pw-toggle aria-label="Show password"><?= icon('eye') ?></button>
    </div>
    <div class="pw-meter-row"><div class="pw-meter" id="pw-meter"><span></span></div><span class="pw-label" data-pw-label></span></div>
    <ul class="pw-rules" id="pw-rules">
      <li data-rule="len">8+ characters</li>
      <li data-rule="letter">A letter</li>
      <li data-rule="number">A number</li>
    </ul>
    <?= field_error('password') ?>
  </div>
  <div class="field">
    <label class="label" for="password_confirmation">Confirm password</label>
    <div class="password-wrap input-icon">
      <?= icon('lock') ?>
      <input class="input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Type it again" autocomplete="new-password" required data-match="#password">
    </div>
    <span class="help" data-match-msg></span>
  </div>
  <div class="field<?= invalid('terms') ?> mt-5">
    <label class="check"><input type="checkbox" name="terms" value="1"<?= checked(old('terms')) ?>> <span>I agree to the <strong>Terms of Service</strong> and <strong>Privacy Policy</strong>.</span></label>
    <?= field_error('terms') ?>
  </div>
  <button class="btn btn-primary btn-lg btn-block mt-6" type="submit">Create account <?= icon('arrow-right') ?></button>
</form>
<p class="auth-switch">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
<script>
  document.querySelectorAll('[data-role-choice]').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelectorAll('[data-provider-only]').forEach(function (el) { el.hidden = r.value !== 'provider'; });
    });
  });
</script>
