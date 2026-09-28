<?php
$client = ['id' => $b['client_id'], 'first_name' => $b['c_first'], 'last_name' => $b['c_last'], 'avatar' => $b['c_avatar']];
$pro = ['id' => $b['provider_id'], 'first_name' => $b['p_first'], 'last_name' => $b['p_last'], 'avatar' => $b['p_avatar']];
$other = $isPro ? $client : $pro;
$status = $b['status'];
$flow = ['requested' => 'Requested', 'accepted' => 'Confirmed', 'in_progress' => 'In progress', 'completed' => 'Completed'];
$order = array_keys($flow);
$pos = array_search($status, $order, true);
$payable = in_array($status, ['accepted', 'in_progress', 'completed'], true) && $b['payment_status'] === 'unpaid';
$pendingPay = null;
foreach ($payments as $p) if ($p['status'] === 'pending' && $p['channel'] === 'direct') { $pendingPay = $p; break; }
$paidPay = null;
foreach ($payments as $p) if ($p['status'] === 'paid') { $paidPay = $p; break; }
$eventLabels = [
    'requested' => ['Booking requested', 'calendar'], 'accepted' => ['Booking confirmed', 'check-circle'],
    'in_progress' => ['Work started', 'play'], 'completed' => ['Job completed', 'award'],
    'cancelled' => ['Booking cancelled', 'x-circle'], 'declined' => ['Booking declined', 'x-circle'],
    'payment_sent' => ['Payment sent by client', 'send'], 'payment_received' => ['Payment received', 'wallet'],
    'payment_not_received' => ['Payment not received', 'alert'],
];
$methods = ['gcash' => ['GCash', 'wallet'], 'maya' => ['Maya', 'wallet'], 'cash' => ['Cash', 'banknote'], 'bank_transfer' => ['Bank transfer', 'credit-card']];
$errors = errors();
?>
<div class="page-title">
  <div>
    <a class="small semibold" href="<?= e(url('/bookings')) ?>"><?= icon('arrow-left', 'icon-sm') ?> All bookings</a>
    <h1 class="mt-2"><?= e($b['title']) ?></h1>
    <p class="muted"><?= e(booking_ref((int) $b['id'])) ?> · Requested <?= e(fmt_date($b['created_at'], 'M j, Y')) ?></p>
  </div>
  <div class="row"><?= badge($status) ?><?= $b['payment_status'] !== 'unpaid' ? badge($b['payment_status']) : '' ?></div>
</div>

<div class="layout-aside" style="--aside:330px">
  <div class="stack" style="--gap:20px">

    <?php if (in_array($status, ['cancelled', 'declined'], true)): ?>
      <div class="alert alert-warning"><?= icon('info') ?><div>
        This booking was <?= $status ?>.<?= $b['cancel_reason'] ? ' Reason: “' . e($b['cancel_reason']) . '”' : '' ?>
        <?php if (!$isPro && $b['provider_id']): ?> <a href="<?= e(url('/providers')) ?>">Find another pro →</a><?php endif; ?>
      </div></div>
    <?php else: ?>
      <div class="card card-body">
        <div class="progress-steps" aria-label="Booking progress">
          <?php foreach ($order as $i => $key):
              $cls = $i < $pos || $status === 'completed' ? 'done' : ($i === $pos ? 'current' : ''); ?>
            <div class="pstep <?= $cls ?>"><span class="dot"><?= $cls === 'done' ? icon('check') : '' ?></span><?= $flow[$key] ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php /* ---------- What to do next ---------- */ ?>
    <?php if ($isPro && $status === 'requested'): ?>
      <div class="card">
        <div class="card-header"><h3>New request: respond to <?= e($b['c_first']) ?></h3></div>
        <div class="card-body">
          <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="accept">
            <div class="field">
              <label class="label" for="amount">Confirm your price</label>
              <div class="input-affix" style="max-width:260px"><span class="affix"><?= e(config('app.currency')) ?></span><input class="input" id="amount" name="amount" type="number" min="50" step="50" value="<?= e((int) $b['amount']) ?>"></div>
              <span class="help">The client proposed <?= money($b['amount']) ?>. Adjust it if needed before confirming.</span>
            </div>
            <div class="row row-wrap mt-6">
              <button class="btn btn-primary" type="submit"><?= icon('check') ?> Confirm booking</button>
              <button class="btn btn-danger" type="button" data-open="decline-dialog">Decline</button>
            </div>
          </form>
        </div>
      </div>
      <dialog class="modal" id="decline-dialog">
        <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>">
          <?= csrf_field() ?><input type="hidden" name="action" value="decline">
          <div class="modal-head"><h3>Decline this request?</h3></div>
          <div class="modal-body">
            <label class="label" for="decline-note">Let <?= e($b['c_first']) ?> know why <span class="opt">(optional)</span></label>
            <textarea class="textarea mt-2" id="decline-note" name="note" maxlength="255" placeholder="e.g. Fully booked that day, available next week."></textarea>
          </div>
          <div class="modal-foot"><button class="btn" type="button" data-close>Back</button><button class="btn btn-danger-solid" type="submit">Decline request</button></div>
        </form>
      </dialog>
    <?php elseif ($isPro && in_array($status, ['accepted', 'in_progress'], true)): ?>
      <div class="card card-body">
        <div class="row row-between row-wrap">
          <div>
            <h3><?= $status === 'accepted' ? 'Ready when you are' : 'Work in progress' ?></h3>
            <p class="muted small mt-1"><?= $status === 'accepted' ? 'Mark the job as started when you arrive, then complete it when done.' : 'Mark the job as completed once the work is done.' ?></p>
          </div>
          <div class="row">
            <?php if ($status === 'accepted'): ?>
              <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="start"><button class="btn" type="submit"><?= icon('play') ?> Start work</button></form>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>" data-confirm="Mark this job as completed? The client will be asked to leave a review.">
              <?= csrf_field() ?><input type="hidden" name="action" value="complete"><button class="btn btn-primary" type="submit"><?= icon('check-circle') ?> Mark completed</button>
            </form>
          </div>
        </div>
      </div>
    <?php elseif ($isClient && $status === 'requested'): ?>
      <div class="alert alert-info"><?= icon('clock') ?><div><strong>Waiting for <?= e($b['p_first']) ?> to confirm.</strong> Most pros respond within a few hours. You can message them in the meantime.</div></div>
    <?php elseif ($isClient && $status === 'accepted'): ?>
      <div class="alert alert-success"><?= icon('check-circle') ?><div><strong>Confirmed!</strong> <?= e($b['p_first']) ?> will be there on <?= e(fmt_date($b['scheduled_date'], 'l, M j')) ?> at <?= e(BookingController::TIMES[$b['scheduled_time']] ?? $b['scheduled_time']) ?>.</div></div>
    <?php endif; ?>

    <?php /* ---------- Payment ---------- */ ?>
    <?php if (in_array($status, ['accepted', 'in_progress', 'completed'], true)): ?>
      <div class="card" id="payment">
        <div class="card-header"><h3><?= icon('wallet') ?> Payment</h3><?= badge($b['payment_status']) ?></div>
        <div class="card-body">
          <div class="summary-line"><span class="muted">Agreed price</span><span><?= money($b['amount'], true) ?></span></div>
          <div class="summary-line"><span class="muted">Fixoria service fee</span><span><?= money(0, true) ?></span></div>
          <div class="summary-line total"><span>Total to pay your pro</span><span><?= money($b['amount'], true) ?></span></div>

          <?php if ($paidPay): ?>
            <div class="alert alert-success mt-4"><?= icon('receipt') ?><div>
              <strong>Paid <?= money($paidPay['amount'], true) ?></strong> via <?= e(payment_method_label($paidPay['method'])) ?>
              on <?= e(fmt_date($paidPay['paid_at'], 'M j, Y g:i A')) ?><?= $paidPay['reference'] ? ' · Ref ' . e($paidPay['reference']) : '' ?>.
            </div></div>
          <?php elseif ($pendingPay): ?>
            <?php if ($isPro): ?>
              <div class="alert alert-warning mt-4"><?= icon('info') ?><div>
                <strong><?= e($b['c_first']) ?> says they paid <?= money($pendingPay['amount']) ?> via <?= e(payment_method_label($pendingPay['method'])) ?></strong><?= $pendingPay['reference'] ? ' (ref ' . e($pendingPay['reference']) . ')' : '' ?>. Did you receive it?
              </div></div>
              <div class="row mt-4">
                <form method="post" action="<?= e(url('/payments/' . $pendingPay['id'] . '/confirm')) ?>"><?= csrf_field() ?><button class="btn btn-success" type="submit"><?= icon('check') ?> Yes, received</button></form>
                <form method="post" action="<?= e(url('/payments/' . $pendingPay['id'] . '/reject')) ?>" data-confirm="Mark this payment as not received? The client will be asked to check it."><?= csrf_field() ?><button class="btn btn-danger" type="submit">Not received</button></form>
              </div>
            <?php else: ?>
              <div class="alert alert-info mt-4"><?= icon('clock') ?><div>You recorded a <?= e(payment_method_label($pendingPay['method'])) ?> payment. Waiting for <?= e($b['p_first']) ?> to confirm they received it.</div></div>
            <?php endif; ?>
          <?php elseif ($payable && ($isClient || $isPro)): ?>
            <form class="mt-6" method="post" action="<?= e(url('/bookings/' . $b['id'] . '/pay')) ?>">
              <?= csrf_field() ?>
              <div class="label mb-2"><?= $isPro ? 'Record a payment you received' : 'Paid your pro directly? Record it here' ?></div>
              <div class="choices choices-compact" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                <?php foreach ($methods as $key => [$label, $ic]): ?>
                  <label class="choice">
                    <input type="radio" name="method" value="<?= $key ?>" <?= $key === 'gcash' ? 'checked' : '' ?>>
                    <span class="choice-body" style="align-items:center;padding:12px 40px 12px 12px"><span class="choice-icon" style="width:34px;height:34px"><?= icon($ic) ?></span><span class="choice-title"><?= $label ?></span></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <?php if (!empty($errors['method'])): ?><div class="field-error mt-2"><?= e($errors['method']) ?></div><?php endif; ?>
              <div class="row row-wrap mt-4" style="align-items:flex-end">
                <div class="field grow" style="min-width:220px">
                  <label class="label" for="reference">Reference no. <span class="opt">(optional)</span></label>
                  <div class="input-icon"><?= icon('receipt') ?><input class="input" id="reference" name="reference" maxlength="120" placeholder="e.g. GCash ref 1234 567 890"></div>
                </div>
                <button class="btn btn-primary" type="submit" style="height:48px"><?= icon('check') ?> <?= $isPro ? 'Mark as received' : 'I\'ve paid' ?></button>
              </div>
              <p class="help mt-3"><?= $isPro ? 'The booking will be marked as paid right away.' : 'Your pro will be asked to confirm they received it.' ?></p>
            </form>
            <?php if ($isClient && paymongo_enabled()): ?>
              <div class="or">or pay online</div>
              <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/pay-online')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-dark btn-block" type="submit"><?= icon('credit-card') ?> Pay <?= money($b['amount']) ?> with GCash, Maya or card</button>
                <p class="help text-center mt-2">Secure checkout by PayMongo.</p>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php /* ---------- Review ---------- */ ?>
    <?php if ($status === 'completed'): ?>
      <div class="card card-body" id="review">
        <?php if ($review): ?>
          <div class="row row-between"><h3><?= $isClient ? 'Your review' : 'Client review' ?></h3><?= stars((float) $review['rating']) ?></div>
          <?php if ($review['comment']): ?><p class="mt-3">“<?= e($review['comment']) ?>”</p><?php endif; ?>
          <?php if ($review['provider_reply']): ?><div class="review-reply"><strong>Reply:</strong> <?= e($review['provider_reply']) ?></div><?php endif; ?>
        <?php elseif ($isClient): ?>
          <h3>How did <?= e($b['p_first']) ?> do?</h3>
          <p class="muted small mt-1">Your review helps other clients and rewards great pros.</p>
          <form class="mt-4" method="post" action="<?= e(url('/bookings/' . $b['id'] . '/review')) ?>">
            <?= csrf_field() ?>
            <div class="rate-row">
              <fieldset class="rate-input" aria-label="Rating">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <input type="radio" id="r<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>><label for="r<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>"><?= icon('star') ?></label>
                <?php endfor; ?>
              </fieldset>
              <span class="rate-word" data-rate-word>Excellent</span>
            </div>
            <div class="picks mt-4" aria-label="Quick highlights">
              <?php foreach (['On time', 'Clean & tidy', 'Fair price', 'Friendly', 'Explained the work', 'Would book again'] as $tag): ?>
                <button class="pick pick-sm" type="button" data-review-tag="<?= e($tag) ?>"><?= icon('plus') ?> <?= e($tag) ?></button>
              <?php endforeach; ?>
            </div>
            <div class="field mt-4">
              <textarea class="textarea" id="review-comment" name="comment" maxlength="2000" data-count placeholder="What went well? Was <?= e($b['p_first']) ?> on time, careful and fairly priced?" aria-label="Your review"></textarea>
              <div class="help-row"><span class="help">Reviews are public and show your first name.</span><span class="counter"></span></div>
            </div>
            <script>
              (function () {
                var words = {1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Great', 5: 'Excellent'}, w = document.querySelector('[data-rate-word]'), box = document.getElementById('review-comment');
                document.querySelectorAll('.rate-input input').forEach(function (r) { r.addEventListener('change', function () { w.textContent = words[r.value]; }); });
                document.querySelectorAll('[data-review-tag]').forEach(function (b) {
                  b.addEventListener('click', function () {
                    var t = b.dataset.reviewTag; if (box.value.indexOf(t) !== -1) return;
                    box.value = (box.value.trim() ? box.value.trim().replace(/[.]?$/, '. ') : '') + t + '.';
                    b.classList.add('is-active'); box.dispatchEvent(new Event('input', { bubbles: true }));
                  });
                });
              })();
            </script>
            <button class="btn btn-primary mt-4" type="submit">Post review</button>
          </form>
        <?php else: ?>
          <h3>Review</h3>
          <p class="muted small mt-1"><?= e($b['c_first']) ?> hasn't left a review yet.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><h3>Booking details</h3></div>
      <div class="card-body">
        <dl class="kv">
          <dt>Schedule</dt><dd><?= e($b['scheduled_date'] ? fmt_date($b['scheduled_date'], 'l, F j, Y') : 'To be agreed') ?><?= $b['scheduled_time'] ? ' · ' . e(BookingController::TIMES[$b['scheduled_time']] ?? $b['scheduled_time']) : '' ?></dd>
          <dt>Address</dt><dd><?= e(trim(($b['address'] ?? '') . ', ' . ($b['city'] ?? ''), ', ')) ?: '—' ?></dd>
          <dt>Price</dt><dd><?= money($b['amount']) ?></dd>
          <?php if ($b['category_name']): ?><dt>Service</dt><dd><?= e($b['category_name']) ?></dd><?php endif; ?>
        </dl>
        <?php if ($b['details']): ?>
          <hr><div class="label mb-2">Job details</div>
          <p class="muted" style="white-space:pre-line"><?= e($b['details']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <?php if (in_array($status, ['requested', 'accepted'], true) && ($isClient || $isPro)): ?>
      <div class="row" style="justify-content:flex-end">
        <button class="btn btn-ghost btn-sm" type="button" data-open="cancel-dialog"><?= icon('x-circle') ?> Cancel booking</button>
      </div>
      <dialog class="modal" id="cancel-dialog">
        <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>">
          <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
          <div class="modal-head"><h3>Cancel this booking?</h3></div>
          <div class="modal-body">
            <p class="muted mb-3"><?= e($other['first_name']) ?> will be notified.</p>
            <label class="label" for="cancel-note">Reason <span class="opt">(optional)</span></label>
            <textarea class="textarea mt-2" id="cancel-note" name="note" maxlength="255"></textarea>
          </div>
          <div class="modal-foot"><button class="btn" type="button" data-close>Keep booking</button><button class="btn btn-danger-solid" type="submit">Cancel booking</button></div>
        </form>
      </dialog>
    <?php endif; ?>
  </div>

  <aside class="stack sticky" style="--gap:20px">
    <div class="card card-body">
      <div class="small subtle mb-3"><?= $isPro ? 'Client' : 'Your pro' ?></div>
      <div class="row" style="--gap:12px">
        <?= avatar($other, 'lg') ?>
        <div class="grow" style="min-width:0">
          <div class="semibold row" style="--gap:5px"><span class="truncate"><?= e(full_name($other)) ?></span>
            <?php if (!$isPro && $b['p_verified'] === 'verified'): ?><span class="verified"><?= icon('badge-check', 'icon-sm') ?></span><?php endif; ?></div>
          <div class="small muted truncate"><?= e($isPro ? ($b['city'] ?? '') : ($b['p_headline'] ?? '')) ?></div>
        </div>
      </div>
      <?php if (in_array($status, ['accepted', 'in_progress'], true)): ?>
        <div class="small mt-4 stack" style="--gap:6px">
          <?php $ph = $isPro ? $b['c_phone'] : $b['p_phone']; if ($ph): ?><div class="row" style="--gap:8px"><?= icon('phone', 'icon-sm') ?> <?= e($ph) ?></div><?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="row mt-4">
        <form class="grow" method="post" action="<?= e(url('/messages/start')) ?>"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $other['id'] ?>"><button class="btn btn-block" type="submit"><?= icon('message') ?> Message</button></form>
        <?php if (!$isPro): ?><a class="btn btn-icon" href="<?= e(url('/providers/' . $b['provider_id'])) ?>" aria-label="View profile"><?= icon('user') ?></a><?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Activity</h3></div>
      <div class="card-body">
        <ol class="timeline">
          <?php foreach ($events as $ev): [$label, $ic] = $eventLabels[$ev['event']] ?? [ucfirst($ev['event']), 'dot']; ?>
            <li>
              <span class="tl-dot"><?= icon($ic) ?></span>
              <div class="tl-title"><?= e($label) ?></div>
              <div class="tl-time"><?= e(fmt_date($ev['created_at'], 'M j, g:i A')) ?><?= $ev['first_name'] ? ' · ' . e($ev['first_name']) : '' ?></div>
              <?php if ($ev['note']): ?><div class="small muted mt-1"><?= e($ev['note']) ?></div><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </aside>
</div>
