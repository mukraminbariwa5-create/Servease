<div class="auth-head">
  <span class="auth-icon"><?= icon('shield-check') ?></span>
  <h1>Choose a new password</h1>
  <p class="lead">Pick something you haven't used before. The link expires in an hour.</p>
</div>
<form class="mt-8" method="post" action="<?= e(url('/reset-password')) ?>" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <div class="field<?= invalid('password') ?>">
    <label class="label" for="password">New password</label>
    <div class="password-wrap input-icon">
      <?= icon('lock') ?>
      <input class="input" id="password" name="password" type="password" placeholder="Create a password" autocomplete="new-password" required autofocus data-pw-meter="#pw-meter" data-pw-rules="#pw-rules">
      <button class="password-toggle" type="button" data-pw-toggle aria-label="Show password"><?= icon('eye') ?></button>
    </div>
    <div class="pw-meter-row"><div class="pw-meter" id="pw-meter"><span></span></div><span class="pw-label" data-pw-label></span></div>
    <ul class="pw-rules" id="pw-rules"><li data-rule="len">8+ characters</li><li data-rule="letter">A letter</li><li data-rule="number">A number</li></ul>
    <?= field_error('password') ?>
  </div>
  <div class="field">
    <label class="label" for="password_confirmation">Confirm new password</label>
    <div class="password-wrap input-icon"><?= icon('lock') ?><input class="input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Type it again" autocomplete="new-password" required data-match="#password"></div>
    <span class="help" data-match-msg></span>
  </div>
  <button class="btn btn-primary btn-lg btn-block mt-6" type="submit">Update password</button>
</form>
