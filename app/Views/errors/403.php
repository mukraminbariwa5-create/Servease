<div class="container error-page">
  <div class="error-art"><?= icon('lock') ?></div>
  <div class="error-copy">
    <div class="error-code">403</div>
    <h1>You don't have access</h1>
    <p class="lead"><?= e($message ?: 'This page is only available to certain accounts.') ?></p>
    <div class="row row-wrap mt-6">
      <a class="btn btn-primary" href="<?= e(url(home_for(user()))) ?>">Go back home</a>
    </div>
  </div>
</div>
