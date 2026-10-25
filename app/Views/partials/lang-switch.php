<?php /* English / Filipino switch. Each option is its own form so it works without JavaScript. */
$cur = Lang::current(); ?>
<div class="lang-switch<?= isset($class) ? ' ' . e($class) : '' ?>" role="group" aria-label="Language">
  <?php foreach (Lang::SUPPORTED as $code => $label): ?>
    <form method="post" action="<?= e(url('/lang')) ?>"><?= csrf_field() ?><input type="hidden" name="lang" value="<?= $code ?>">
      <button type="submit" class="<?= $cur === $code ? 'is-on' : '' ?>" aria-pressed="<?= $cur === $code ? 'true' : 'false' ?>" data-no-lock><?= $code === 'fil' ? 'Filipino' : 'English' ?></button>
    </form>
  <?php endforeach; ?>
</div>
