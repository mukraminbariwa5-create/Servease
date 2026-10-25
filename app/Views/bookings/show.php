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
    'reschedule_proposed' => ['New time proposed', 'refresh'], 'rescheduled' => ['Moved to a new time', 'calendar-check'],
    'reschedule_declined' => ['New time declined', 'x-circle'], 'reschedule_withdrawn' => ['New time withdrawn', 'x-circle'],
    'issue_reported' => ['Problem reported', 'flag'], 'issue_resolved' => ['Problem resolved by Servease', 'shield-check'],
];
$errors = errors();
$canMove = in_array($status, ['requested', 'accepted'], true) && ($isClient || $isPro);
$overdue = in_array($status, ['accepted', 'in_progress'], true) && $b['scheduled_date'] && $b['scheduled_date'] < date('Y-m-d');
$proposal = $canMove && !empty($b['proposed_date']) ? $b : null;
$proposedByMe = $proposal && (int) $b['proposed_by'] === (int) user_id();
$canReport = in_array($status, IssueController::REPORTABLE, true) && ($isClient || $isPro);
$openIssue = null;
foreach ($issues as $iss) if ($iss['status'] === 'open') { $openIssue = $iss; break; }
/** Radio list of reasons for the cancel/decline dialogs. */
$reasonPicks = function (array $list, string $prefix): string {
    $out = '<div class="reason-picks" role="radiogroup">';
    foreach ($list as $k => $label) {
        $out .= '<label class="reason-pick"><input type="radio" name="reason" value="' . e($k) . '" required' . checked(old('reason') === $k) . '><span>' . e($label) . '</span></label>';
    }
    return $out . '</div>';
};
?>
<?php app_bar('Booking details', '/bookings'); ?>
<div class="page-title">
  <div>
    <a class="small semibold hide-md" href="<?= e(url('/bookings')) ?>"><?= icon('arrow-left', 'icon-sm') ?> All bookings</a>
    <h1 class="mt-2"><?= e($b['title']) ?></h1>
    <p class="muted">Requested <?= e(fmt_date($b['created_at'], 'F j, Y')) ?></p>
  </div>
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

    <?php /* ---------- A new time waiting for an answer ---------- */ ?>
    <?php if ($proposal): $slot = RescheduleController::slotText($b['proposed_date'], $b['proposed_time'], (int) $b['duration_hours']); ?>
      <div class="card resched-card">
        <div class="card-body">
          <div class="row row-top" style="--gap:14px">
            <span class="resched-ic"><?= icon('refresh') ?></span>
            <div class="grow">
              <h3><?= $proposedByMe ? 'You proposed a new time' : e($other['first_name']) . ' proposed a new time' ?></h3>
              <p class="mt-1"><span class="strike muted"><?= e($b['scheduled_date'] ? RescheduleController::slotText($b['scheduled_date'], $b['scheduled_time'], (int) $b['duration_hours']) : 'Not set') ?></span> <?= icon('arrow-right', 'icon-sm') ?> <strong><?= e($slot) ?></strong></p>
              <?php if ($b['proposed_note']): ?><p class="small muted mt-1">“<?= e($b['proposed_note']) ?>”</p><?php endif; ?>
              <div class="row row-wrap mt-4" style="--gap:8px">
                <?php if ($proposedByMe): ?>
                  <span class="small muted">Waiting for <?= e($other['first_name']) ?> to answer.</span>
                  <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/reschedule/answer')) ?>"><?= csrf_field() ?><input type="hidden" name="answer" value="withdraw"><button class="btn btn-sm btn-ghost" type="submit">Withdraw</button></form>
                <?php else: ?>
                  <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/reschedule/answer')) ?>"><?= csrf_field() ?><input type="hidden" name="answer" value="accept"><button class="btn btn-primary btn-sm" type="submit"><?= icon('check') ?> Accept new time</button></form>
                  <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/reschedule/answer')) ?>"><?= csrf_field() ?><input type="hidden" name="answer" value="decline"><button class="btn btn-sm" type="submit">Keep original</button></form>
                  <a class="btn btn-sm btn-ghost" href="<?= e(url('/bookings/' . $b['id'] . '/reschedule')) ?>">Suggest another</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($overdue && ($isClient || $isPro)): ?>
      <div class="alert alert-warning"><?= icon('calendar-check') ?><div>
        <strong>This job was booked for <?= e(fmt_date($b['scheduled_date'], 'l, M j')) ?>.</strong>
        <?= $isPro
            ? 'If the work is done, mark it completed. If it didn\'t happen, propose a new time or cancel it.'
            : 'If the work is done, ask ' . e($b['p_first']) . ' to mark it completed. If it didn\'t happen, message them' . ($status === 'accepted' ? ', propose a new time' : '') . ' or report a problem.' ?>
      </div></div>
    <?php endif; ?>

    <?php foreach ($issues as $iss): ?>
      <div class="alert <?= $iss['status'] === 'open' ? 'alert-info' : 'alert-success' ?>"><?= icon($iss['status'] === 'open' ? 'flag' : 'shield-check') ?><div>
        <strong><?= $iss['status'] === 'open' ? 'Problem reported ' . e(time_ago($iss['created_at'])) : 'Servease resolved your report' ?>:</strong>
        <?= e(IssueController::REASONS[$iss['reason']] ?? '') ?>.
        <?= $iss['status'] === 'open' ? 'Our team is looking into it.' : '“' . e($iss['admin_note']) . '”' ?>
      </div></div>
    <?php endforeach; ?>

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
            <span class="label">Why can't you take it?</span>
            <?= $reasonPicks(BookingController::CANCEL_REASONS['decline'], 'd') ?>
            <label class="label mt-4" for="decline-note">Anything to add for <?= e($b['c_first']) ?>? <span class="opt">(optional)</span></label>
            <textarea class="textarea mt-2" id="decline-note" name="note" maxlength="200" placeholder="e.g. I'm free next week if that works."></textarea>
          </div>
          <div class="modal-foot"><button class="btn" type="button" data-close>Back</button><button class="btn btn-danger-solid" type="submit">Decline request</button></div>
        </form>
      </dialog>
    <?php elseif ($isPro && in_array($status, ['accepted', 'in_progress'], true)): $dayHasCome = BookingController::dayHasCome($b); ?>
      <div class="card card-body">
        <div class="row row-between row-wrap">
          <div>
            <h3><?= $status === 'accepted' ? ($dayHasCome ? 'Ready when you are' : 'Booked for ' . e(fmt_date($b['scheduled_date'], 'l, M j'))) : 'Work in progress' ?></h3>
            <p class="muted small mt-1"><?= $status === 'accepted'
                ? ($dayHasCome ? 'Mark the job as started when you arrive, then complete it when done.' : 'You can start and complete this job from that day. Plans changed? Propose a new time below.')
                : 'Mark the job as completed once the work is done.' ?></p>
          </div>
          <?php if ($dayHasCome): ?>
          <div class="row">
            <?php if ($status === 'accepted'): ?>
              <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="start"><button class="btn" type="submit"><?= icon('play') ?> Start work</button></form>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>" data-confirm="Mark this job as completed? The client will be asked to leave a review.">
              <?= csrf_field() ?><input type="hidden" name="action" value="complete"><button class="btn btn-primary" type="submit"><?= icon('check-circle') ?> Mark completed</button>
            </form>
          </div>
          <?php else: ?>
            <span class="badge badge-blue"><?= icon('calendar', 'icon-sm') ?> <?= e(fmt_date($b['scheduled_date'], 'M j')) ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php elseif ($isClient && $status === 'requested'): ?>
      <div class="alert alert-info"><?= icon('clock') ?><div><strong>Waiting for <?= e($b['p_first']) ?> to confirm.</strong> Most pros respond within a few hours. You can message them in the meantime.</div></div>
    <?php elseif ($isClient && $status === 'accepted'): ?>
      <div class="alert alert-success"><?= icon('check-circle') ?><div><strong>Confirmed!</strong>
        <?php if ($b['scheduled_date'] && $b['scheduled_time']): ?>
          <?= e($b['p_first']) ?> will be there on <?= e(fmt_date($b['scheduled_date'], 'l, M j')) ?>, <?= e(Availability::range($b['scheduled_time'], (int) $b['duration_hours'])) ?>.
        <?php elseif ($b['scheduled_date']): ?>
          <?= e($b['p_first']) ?> is booked for <?= e(fmt_date($b['scheduled_date'], 'l, M j')) ?>. Agree on the exact time in the chat.
        <?php else: ?>
          Agree on the date and time with <?= e($b['p_first']) ?> in the chat.
        <?php endif; ?>
      </div></div>
    <?php endif; ?>

    <?php /* ---------- The job ticket: everything agreed for this booking, like a work-order slip ---------- */
      $stamp = ['requested' => ['Waiting', 'amber'], 'accepted' => ['Confirmed', 'blue'], 'in_progress' => ['In progress', 'violet'],
                'completed' => ['Done', 'green'], 'cancelled' => ['Cancelled', 'red'], 'declined' => ['Declined', 'red']][$status] ?? [ucfirst($status), 'blue']; ?>
    <article class="ticket" aria-label="Job ticket <?= e(booking_ref((int) $b['id'])) ?>">
      <header class="ticket-head">
        <div class="grow">
          <span class="ticket-kind">Job ticket</span>
          <span class="ticket-ref"><?= e(booking_ref((int) $b['id'])) ?></span>
        </div>
        <span class="stamp stamp-<?= $stamp[1] ?>"><?= e($stamp[0]) ?></span>
        <?php if ($b['payment_status'] === 'paid'): ?><span class="stamp stamp-green stamp-paid">Paid</span><?php endif; ?>
      </header>
      <div class="ticket-perf" aria-hidden="true"></div>
      <dl class="ticket-rows">
        <div><dt>When</dt><dd><?= e($b['scheduled_date'] ? fmt_date($b['scheduled_date'], 'l, F j') : 'To be agreed') ?><?php if ($b['scheduled_time']): ?><small><?= e(Availability::range($b['scheduled_time'], (int) $b['duration_hours'])) ?></small><?php endif; ?></dd></div>
        <div><dt>Where</dt><dd><?= e(trim(($b['address'] ?? '') . ', ' . ($b['city'] ?? ''), ', ')) ?: 'To be agreed' ?></dd></div>
        <?php if ($b['category_name']): ?><div><dt>Trade</dt><dd><?= e($b['category_name']) ?></dd></div><?php endif; ?>
        <div><dt><?= $isPro ? 'Customer' : 'Pro' ?></dt><dd><?= e(full_name($other)) ?></dd></div>
        <div class="ticket-total"><dt>Agreed price</dt><dd><span class="price-tag"><?= money($b['amount']) ?></span></dd></div>
      </dl>
      <?php if ($b['details']): ?>
        <div class="ticket-perf" aria-hidden="true"></div>
        <div class="ticket-notes">
          <span class="ticket-label">What needs doing</span>
          <p style="white-space:pre-line"><?= e($b['details']) ?></p>
        </div>
      <?php endif; ?>
      <?php if ($canMove || $canReport): ?>
        <div class="ticket-foot row row-between row-wrap" style="--gap:8px">
          <span class="small muted"><?= $canMove ? 'Plans changed? ' . e($other['first_name']) . ' will be told right away.' : 'Something wrong with this booking?' ?></span>
          <div class="row row-wrap" style="--gap:6px">
            <?php if ($canMove && !$proposal): ?><a class="btn btn-sm" href="<?= e(url('/bookings/' . $b['id'] . '/reschedule')) ?>"><?= icon('refresh') ?> Propose a new time</a><?php endif; ?>
            <?php if ($canMove): ?><button class="btn btn-ghost btn-sm btn-danger-text" type="button" data-open="cancel-dialog"><?= icon('x-circle') ?> Cancel booking</button><?php endif; ?>
            <?php if ($canReport && !$openIssue): ?><button class="btn btn-ghost btn-sm" type="button" data-open="report-dialog" id="report"><?= icon('flag') ?> Report a problem</button><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </article>

    <?php /* ---------- Payment ---------- */ ?>
    <?php if (in_array($status, ['accepted', 'in_progress', 'completed'], true)): ?>
      <div class="card" id="payment">
        <div class="card-header"><h3><?= icon('wallet') ?> Payment</h3><?= badge($b['payment_status']) ?></div>
        <div class="card-body">
          <div class="summary-line total"><span><?= $isPro ? 'Agreed price' : 'Agreed price to pay ' . e($b['p_first']) ?></span><span><?= money($b['amount']) ?></span></div>
          <p class="tiny subtle mt-1">No Servease fees. <?= $isPro ? 'You keep the full amount.' : e($b['p_first']) . ' gets the full amount.' ?></p>

          <?php if ($paidPay): ?>
            <div class="alert alert-success mt-4"><?= icon('receipt') ?><div>
              <strong>Paid <?= money($paidPay['amount']) ?></strong> via <?= e(payment_method_label($paidPay['method'])) ?>
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
            <div class="pay-note mt-4"><?= icon('info') ?><p><?= $isPro
                ? 'Servease doesn\'t handle money. When your customer pays you in person or by transfer, record it so you both have a receipt.'
                : (paymongo_enabled() ? 'Pay online now, or pay ' . e($b['p_first']) . ' directly after the job and record it here.' : 'Pay ' . e($b['p_first']) . ' directly after the job (in person or from your own GCash, Maya or bank app), then record it here so you both have a receipt.') ?></p></div>
            <a class="btn btn-primary btn-lg btn-block mt-4" href="<?= e(url('/bookings/' . $b['id'] . '/pay')) ?>">
              <?= icon('wallet') ?> <?= $isPro ? 'Record payment received' : (paymongo_enabled() ? 'Pay ' . money($b['amount']) : 'Record a payment') ?>
            </a>
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
          <?php if ($review['provider_reply']): ?><div class="review-reply"><strong>Reply:</strong> <?= e($review['provider_reply']) ?></div>
          <?php elseif ($isPro): ?>
            <form class="reply-inline mt-3" method="post" action="<?= e(url('/reviews/' . $review['id'] . '/reply')) ?>">
              <?= csrf_field() ?><input type="hidden" name="return" value="booking">
              <input class="input" name="reply" placeholder="Thank <?= e($b['c_first']) ?> with a public reply…" maxlength="500" required aria-label="Reply to this review">
              <button class="btn" type="submit">Reply</button>
            </form>
          <?php endif; ?>
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

    <?php if (in_array($status, ['requested', 'accepted'], true) && ($isClient || $isPro)): ?>
      <dialog class="modal" id="cancel-dialog">
        <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>">
          <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
          <div class="modal-head"><h3>Cancel this booking?</h3></div>
          <div class="modal-body">
            <p class="muted mb-3"><?= e($other['first_name']) ?> will be notified and sees your reason.</p>
            <span class="label">Why are you cancelling?</span>
            <?= $reasonPicks(BookingController::CANCEL_REASONS[$isPro ? 'provider' : 'client'], 'c') ?>
            <label class="label mt-4" for="cancel-note">Anything to add? <span class="opt">(optional, required for “Something else”)</span></label>
            <textarea class="textarea mt-2" id="cancel-note" name="note" maxlength="200"></textarea>
            <?php if (!$proposal && $canMove): ?><p class="small muted mt-3"><?= icon('info', 'icon-sm') ?> Only the time doesn't work? <a href="<?= e(url('/bookings/' . $b['id'] . '/reschedule')) ?>">Propose a new time</a> instead.</p><?php endif; ?>
          </div>
          <div class="modal-foot"><button class="btn" type="button" data-close>Keep booking</button><button class="btn btn-danger-solid" type="submit">Cancel booking</button></div>
        </form>
      </dialog>
    <?php endif; ?>

    <?php if ($canReport && !$openIssue): ?>
      <dialog class="modal" id="report-dialog">
        <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/report')) ?>">
          <?= csrf_field() ?>
          <div class="modal-head"><h3>Report a problem</h3></div>
          <div class="modal-body">
            <p class="muted mb-3">This goes to the Servease team, not to <?= e($other['first_name']) ?>. We'll look into it and reply on this booking.</p>
            <span class="label">What happened?</span>
            <div class="reason-picks" role="radiogroup">
              <?php foreach (IssueController::REASONS as $k => $label): ?>
                <label class="reason-pick"><input type="radio" name="reason" value="<?= e($k) ?>" required<?= checked(old('reason') === $k) ?>><span><?= e($label) ?></span></label>
              <?php endforeach; ?>
            </div>
            <label class="label mt-4" for="report-details">Tell us more</label>
            <textarea class="textarea mt-2" id="report-details" name="details" rows="4" minlength="20" maxlength="2000" required placeholder="What happened, when, and what you'd like us to do."><?= e(old('details')) ?></textarea>
            <?= field_error('details') ?>
          </div>
          <div class="modal-foot"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('send') ?> Send report</button></div>
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
          <div class="small muted clamp-2"><?= e($isPro ? ($b['city'] ?? '') : ($b['p_headline'] ?? '')) ?></div>
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
