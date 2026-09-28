<div class="container error-page">
  <div class="error-code">404</div>
  <h1 class="mt-4">We couldn't find that page</h1>
  <p class="lead mt-2"><?= e($message ?: 'The page may have moved, or the link might be broken.') ?></p>
  <div class="row mt-6" style="justify-content:center">
    <a class="btn" href="javascript:history.back()"><?= icon('arrow-left') ?> Go back</a>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to home</a>
  </div>
</div>
