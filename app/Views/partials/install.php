<?php /** How to install on iPhone/iPad (Safari has no install prompt). Opened by [data-install] on iOS. */ ?>
<dialog class="modal install-help" id="install-help" aria-labelledby="install-help-title">
  <div class="modal-head">
    <span class="install-app-icon"><img src="<?= e(url('/assets/img/icon-192.png')) ?>" alt="" width="48" height="48"></span>
    <div><h3 id="install-help-title">Install Servease</h3><p class="small muted">Add it to your Home Screen in two taps.</p></div>
  </div>
  <div class="modal-body">
    <ol class="install-steps">
      <li><span>1</span><div>Tap the <strong>Share</strong> button <span class="ios-share" aria-hidden="true"><?= icon('upload') ?></span> in Safari's toolbar.</div></li>
      <li><span>2</span><div>Scroll down and tap <strong>Add to Home Screen</strong>.</div></li>
      <li><span>3</span><div>Tap <strong>Add</strong>. Servease opens full screen, like any other app.</div></li>
    </ol>
  </div>
  <div class="modal-foot"><button class="btn btn-primary btn-block" type="button" data-close>Got it</button></div>
</dialog>
