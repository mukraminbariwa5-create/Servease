<div class="container error-page">
  <div class="error-code">Oops</div>
  <h1 class="mt-4">Something went wrong on our side</h1>
  <p class="lead mt-2">Please try again in a moment.</p>
  <?php if (!empty($exception)): ?>
    <pre class="card card-body mt-6" style="text-align:left;overflow:auto;font-size:.8rem;white-space:pre-wrap"><?= e(get_class($exception) . ': ' . $exception->getMessage() . "\n" . $exception->getFile() . ':' . $exception->getLine() . "\n\n" . $exception->getTraceAsString()) ?></pre>
  <?php endif; ?>
  <div class="row mt-6" style="justify-content:center">
    <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to home</a>
  </div>
</div>
