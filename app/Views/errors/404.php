<div class="container error-page">
  <div class="error-art"><?= icon('search') ?></div>
  <div class="error-copy">
    <div class="error-code">404</div>
    <h1>We couldn't find that page</h1>
    <p class="lead"><?= e($message ?: 'The page may have moved, or the link might be broken.') ?></p>
    <div class="row row-wrap mt-6">
      <a class="btn" href="javascript:history.back()"><?= icon('arrow-left') ?> Go back</a>
      <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to home</a>
    </div>
  </div>
</div>
