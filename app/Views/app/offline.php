<div class="offline">
  <div class="offline-card">
    <span class="offline-ic" aria-hidden="true"><?= icon('zap-off') ?></span>
    <h1>You're offline</h1>
    <p>Servease needs an internet connection to load your bookings and messages. Check your Wi-Fi or mobile data, then try again.</p>
    <button class="btn btn-primary btn-lg btn-block" type="button" onclick="location.reload()"><?= icon('refresh') ?> Try again</button>
    <a class="btn btn-ghost btn-block" href="<?= e(url('/')) ?>">Go to Home</a>
  </div>
</div>
