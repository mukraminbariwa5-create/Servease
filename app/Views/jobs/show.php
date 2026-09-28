<?php
$me = user();
$budget = $job['budget_min'] && $job['budget_max'] ? money($job['budget_min']) . ' – ' . money($job['budget_max'])
    : ($job['budget_max'] ? 'Up to ' . money($job['budget_max']) : ($job['budget_min'] ? 'From ' . money($job['budget_min']) : 'Open to quotes'));
$urgency = ['flexible' => 'Flexible', 'this_week' => 'This week', 'urgent' => 'Urgent'][$job['urgency']];
$client = ['id' => $job['client_id'], 'first_name' => $job['first_name'], 'last_name' => $job['last_name'], 'avatar' => $job['avatar']];
$pendingQuotes = array_filter($quotes, fn($q) => $q['status'] === 'pending');
?>
<section class="page-header">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="<?= e(url('/jobs')) ?>">Job board</a><?= icon('chevron-right') ?><span class="truncate"><?= e(str_limit($job['title'], 50)) ?></span>
    </nav>
    <div class="row row-between row-top row-wrap">
      <div class="grow">
        <div class="row row-wrap mb-2" style="--gap:8px">
          <?= badge($job['status']) ?>
          <span class="tag"><?= icon($job['category_icon'] ?? 'tag') ?><?= e($job['category_name'] ?? 'General') ?></span>
          <?php if ($job['urgency'] === 'urgent'): ?><span class="badge badge-red">Urgent</span><?php endif; ?>
        </div>
        <h1><?= e($job['title']) ?></h1>
        <div class="job-meta" style="font-size:.9rem">
          <span><?= icon('map-pin') ?><?= e($job['city']) ?></span>
          <span><?= icon('clock') ?>Posted <?= e(time_ago($job['created_at'])) ?></span>
          <span><?= icon('file-text') ?><?= (int) $job['quotes_count'] ?> quote<?= (int) $job['quotes_count'] === 1 ? '' : 's' ?></span>
        </div>
      </div>
      <?php if ($isOwner && $job['status'] === 'open'): ?>
        <form method="post" action="<?= e(url('/jobs/' . $job['id'] . '/cancel')) ?>" data-confirm="Cancel this job? Pros who quoted will be notified.">
          <?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('x-circle') ?> Cancel job</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="container page">
  <div class="layout-aside">
    <div class="stack" style="--gap:24px">
      <section class="card card-body">
        <h3 class="mb-3">Job details</h3>
        <p style="white-space:pre-line"><?= e($job['description']) ?></p>
        <dl class="kv mt-6">
          <dt>Budget</dt><dd><?= $budget ?></dd>
          <dt>Preferred date</dt><dd><?= e($job['preferred_date'] ? fmt_date($job['preferred_date'], 'D, M j, Y') : 'Flexible') ?></dd>
          <dt>Timing</dt><dd><?= e($urgency) ?></dd>
          <dt>Location</dt><dd><?= e($job['city']) ?> <span class="small subtle">(exact address shared after booking)</span></dd>
        </dl>
      </section>

      <?php if ($isOwner || is_role('admin')): ?>
        <section class="card">
          <div class="card-header"><h3>Quotes (<?= count($quotes) ?>)</h3><?php if ($pendingQuotes && $job['status'] === 'open'): ?><span class="small subtle">Compare and accept the one you like</span><?php endif; ?></div>
          <?php if (!$quotes): ?>
            <div class="empty">
              <div class="empty-icon"><?= icon('inbox') ?></div>
              <h3>No quotes yet</h3>
              <p>Pros in your area have been notified. Quotes usually arrive within a few hours.</p>
              <a class="btn" href="<?= e(url('/providers', ['category' => $job['category_slug']])) ?>">Browse <?= e(strtolower($job['category_name'] ?? '')) ?> pros instead</a>
            </div>
          <?php endif; ?>
          <?php foreach ($quotes as $q): $pro = ['id' => $q['provider_id'], 'first_name' => $q['first_name'], 'last_name' => $q['last_name'], 'avatar' => $q['avatar']]; ?>
            <article class="quote">
              <div class="row row-top" style="--gap:14px">
                <?= avatar($pro, 'md') ?>
                <div class="grow">
                  <div class="row row-between row-top">
                    <div>
                      <a class="semibold" href="<?= e(url('/providers/' . $q['provider_id'])) ?>" style="color:var(--text)"><?= e(full_name($pro)) ?></a>
                      <?php if ($q['verification_status'] === 'verified'): ?><span class="verified"><?= icon('shield-check', 'icon-sm') ?></span><?php endif; ?>
                      <div class="small muted"><?= e($q['headline']) ?></div>
                    </div>
                    <div class="text-right">
                      <div class="quote-amount"><?= money($q['amount']) ?></div>
                      <?php if ($q['days_needed']): ?><div class="tiny subtle"><?= (int) $q['days_needed'] ?> day<?= (int) $q['days_needed'] === 1 ? '' : 's' ?> of work</div><?php endif; ?>
                    </div>
                  </div>
                  <div class="pcard-tags" style="margin-top:8px">
                    <?php if ($q['rating_count']): ?><span class="tag tag-star"><?= icon('star') ?><?= number_format((float) $q['rating_avg'], 1) ?> · <?= (int) $q['rating_count'] ?> reviews</span><?php endif; ?>
                    <span class="tag"><?= (int) $q['jobs_completed'] ?> jobs done</span>
                    <span class="tag"><?= icon('map-pin') ?><?= e($q['city']) ?></span>
                    <?php if ($q['status'] !== 'pending'): ?><?= badge($q['status']) ?><?php endif; ?>
                  </div>
                  <p class="mt-3" style="white-space:pre-line"><?= e($q['message']) ?></p>
                  <div class="row row-wrap mt-4" style="--gap:8px">
                    <?php if ($isOwner && $q['status'] === 'pending' && $job['status'] === 'open'): ?>
                      <form method="post" action="<?= e(url('/quotes/' . $q['id'] . '/accept')) ?>" data-confirm="Accept <?= e($q['first_name']) ?>'s quote of <?= e(money($q['amount'])) ?>? This creates a confirmed booking and closes the job to other quotes.">
                        <?= csrf_field() ?><button class="btn btn-primary btn-sm" type="submit"><?= icon('check') ?> Accept quote</button>
                      </form>
                      <form method="post" action="<?= e(url('/quotes/' . $q['id'] . '/decline')) ?>">
                        <?= csrf_field() ?><button class="btn btn-sm" type="submit">Decline</button>
                      </form>
                    <?php endif; ?>
                    <?php if ($isOwner): ?>
                      <form method="post" action="<?= e(url('/messages/start')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $q['provider_id'] ?>">
                        <button class="btn btn-ghost btn-sm" type="submit"><?= icon('message') ?> Ask a question</button>
                      </form>
                    <?php endif; ?>
                    <span class="tiny subtle" style="margin-left:auto"><?= e(time_ago($q['created_at'])) ?></span>
                  </div>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
    </div>

    <aside class="sticky stack" style="--gap:20px">
      <?php if ($booking && ($isOwner || is_role('admin'))): ?>
        <div class="alert alert-success"><?= icon('check-circle') ?><div>A pro has been hired for this job. <a class="semibold" href="<?= e(url('/bookings/' . $booking['id'])) ?>">View booking →</a></div></div>
      <?php endif; ?>

      <?php if ($me && $me['role'] === 'provider'): ?>
        <?php if ($myQuote): ?>
          <div class="card card-body">
            <div class="row row-between"><h3>Your quote</h3><?= badge($myQuote['status']) ?></div>
            <div class="quote-amount mt-3"><?= money($myQuote['amount']) ?></div>
            <p class="small muted mt-2" style="white-space:pre-line"><?= e($myQuote['message']) ?></p>
            <?php if ($myQuote['status'] === 'pending'): ?>
              <form class="mt-4" method="post" action="<?= e(url('/quotes/' . $myQuote['id'] . '/withdraw')) ?>" data-confirm="Withdraw your quote for this job?">
                <?= csrf_field() ?><button class="btn btn-danger btn-sm btn-block" type="submit">Withdraw quote</button>
              </form>
            <?php endif; ?>
          </div>
        <?php elseif ($job['status'] === 'open'): ?>
          <?php
            $lo = (int) $job['budget_min']; $hi = (int) $job['budget_max'];
            $suggest = array_values(array_unique(array_filter([$lo ?: null, ($lo && $hi) ? (int) (round(($lo + $hi) / 2 / 50) * 50) : null, $hi ?: null])));
          ?>
          <form class="form-card quote-form" method="post" action="<?= e(url('/jobs/' . $job['id'] . '/quote')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form-section">
              <div class="form-step" style="margin-bottom:16px"><span class="form-step-num"><?= icon('send', 'icon-sm') ?></span><div><h3>Send a quote</h3><p>Clients compare price, reviews and how clearly you explain the work.</p></div></div>
              <?php if ($lo || $hi): ?>
                <div class="budget-hint"><span>Client's budget</span><strong><?= $lo && $hi ? money($lo) . '–' . money($hi) : ($hi ? 'Up to ' . money($hi) : money($lo) . '+') ?></strong></div>
              <?php endif; ?>
              <div class="field<?= invalid('amount') ?>">
                <label class="label" for="amount">Your price</label>
                <div class="input-affix"><span class="affix"><?= e(config('app.currency')) ?></span><input class="input input-lg" id="amount" name="amount" type="number" min="50" step="50" inputmode="numeric" value="<?= e(old('amount')) ?>" placeholder="0" required><span class="suffix">total</span></div>
                <?php if ($suggest): ?>
                  <div class="picks mt-1"><?php foreach ($suggest as $sv): ?><button class="pick pick-sm" type="button" data-fill="#amount" data-value="<?= $sv ?>"><?= money($sv) ?></button><?php endforeach; ?></div>
                <?php endif; ?>
                <?= field_error('amount') ?>
              </div>
              <div class="field<?= invalid('days_needed') ?>">
                <label class="label" for="days_needed">How long will it take? <span class="opt">optional</span></label>
                <input type="hidden" id="days_needed" name="days_needed" value="<?= e(old('days_needed')) ?>">
                <div class="picks">
                  <?php foreach (['1' => 'Same day', '2' => '2 days', '3' => '3 days', '7' => 'About a week', '14' => '2 weeks'] as $dv => $dl): ?>
                    <button class="pick pick-sm" type="button" data-fill="#days_needed" data-value="<?= $dv ?>"><?= $dl ?></button>
                  <?php endforeach; ?>
                </div>
                <?= field_error('days_needed') ?>
              </div>
              <div class="field<?= invalid('message') ?>">
                <div class="label-row"><label class="label" for="message">Message to the client</label><button class="label-aside btn-link" type="button" data-template="#message">Use a template</button></div>
                <textarea class="textarea" id="message" name="message" rows="5" maxlength="2000" data-count data-min="20" placeholder="What's included, when you can start, any questions…" required><?= e(old('message')) ?></textarea>
                <div class="help-row"><span class="help">Mention materials, warranty and your earliest date.</span><span class="counter"></span></div>
                <?= field_error('message') ?>
              </div>
            </div>
            <div class="form-actions" style="padding:16px 22px">
              <span class="note"><?= icon('shield-check') ?> Free to quote</span>
              <button class="btn btn-primary" type="submit"><?= icon('send') ?> Send quote</button>
            </div>
          </form>
          <script>
            (function () {
              var b = document.querySelector('[data-template]'); if (!b) return;
              b.addEventListener('click', function () {
                var t = document.querySelector(b.dataset.template);
                t.value = "Hi! I can take care of this for you.\n\nWhat's included: labor and basic materials. I'll check everything on-site first and explain before starting.\nEarliest start: \nWarranty: 30 days on workmanship.\n\nFeel free to message me if you have questions.";
                t.dispatchEvent(new Event('input', { bubbles: true })); t.focus();
              });
            })();
          </script>
        <?php else: ?>
          <div class="card card-body"><p class="muted">This job is no longer accepting quotes.</p></div>
        <?php endif; ?>
      <?php elseif (!$me && $job['status'] === 'open'): ?>
        <div class="card card-body">
          <h3>Are you a pro?</h3>
          <p class="small muted mt-2">Sign in with a pro account to send a quote for this job.</p>
          <a class="btn btn-primary btn-block mt-4" href="<?= e(url('/login')) ?>">Sign in to quote</a>
          <a class="btn btn-block mt-2" href="<?= e(url('/register', ['role' => 'provider'])) ?>">Become a pro</a>
        </div>
      <?php endif; ?>

      <div class="card card-body">
        <div class="small subtle mb-3">Posted by</div>
        <div class="row" style="--gap:12px">
          <?= avatar($client, 'md') ?>
          <div>
            <div class="semibold"><?= e($job['first_name'] . ' ' . mb_substr($job['last_name'], 0, 1) . '.') ?></div>
            <div class="small muted">Member since <?= e(fmt_date($job['client_since'], 'M Y')) ?> · <?= $clientJobs ?> job<?= $clientJobs === 1 ? '' : 's' ?> posted</div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</div>
