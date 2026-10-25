<?php /** Servease wordmark. $compact = logo only. */ ?>
<a class="brand" href="<?= e(url('/')) ?>" aria-label="Servease home">
  <?= partial('logo') ?>
  <?php if (empty($compact)): ?><span class="brand-name">Serv<span class="brand-ease">ease</span></span><?php endif; ?>
</a>
