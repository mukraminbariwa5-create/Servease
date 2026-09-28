<div class="auth-head">
  <span class="auth-icon"><?= icon('lock') ?></span>
  <h1>Forgot your password?</h1>
  <p class="lead">No worries. Enter the email you signed up with and we'll send you a secure link to choose a new one.</p>
</div>
<form class="mt-8" method="post" action="<?= e(url('/forgot-password')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field<?= invalid('email') ?>">
    <label class="label" for="email">Email address</label>
    <div class="input-icon"><?= icon('mail') ?><input class="input" id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="you@example.com" autocomplete="email" required autofocus></div>
    <?= field_error('email') ?>
  </div>
  <button class="btn btn-primary btn-lg btn-block mt-6" type="submit"><?= icon('send') ?> Send reset link</button>
</form>
<p class="auth-switch"><a href="<?= e(url('/login')) ?>"><?= icon('arrow-left', 'icon-sm') ?> Back to sign in</a></p>
