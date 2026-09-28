<div class="container error-page">
  <div class="error-code">403</div>
  <h1 class="mt-4">You don't have access</h1>
  <p class="lead mt-2"><?= e($message ?: 'This page is only available to certain accounts.') ?></p>
  <div class="row mt-6" style="justify-content:center">
    <a class="btn btn-primary" href="<?= e(url('/dashboard')) ?>">Go to dashboard</a>
  </div>
</div>
