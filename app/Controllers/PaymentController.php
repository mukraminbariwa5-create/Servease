<?php
declare(strict_types=1);

/**
 * Payments.
 *  - Direct (default): the client pays the pro in cash / GCash / Maya / bank transfer and records it here;
 *    the pro confirms they received it. If the pro records it, it's marked paid immediately.
 *  - Online (optional): PayMongo hosted checkout, verified on return and by webhook.
 */
final class PaymentController
{
    private const METHODS = ['cash', 'gcash', 'maya', 'bank_transfer'];

    private function payable(array $b): bool
    {
        return in_array($b['status'], ['accepted', 'in_progress', 'completed'], true) && $b['payment_status'] === 'unpaid';
    }

    public function recordDirect(string $id): void
    {
        $u = require_auth();
        $b = BookingController::find((int) $id, $u);
        $isClient = (int) $u['id'] === (int) $b['client_id'];
        $isPro = (int) $u['id'] === (int) $b['provider_id'];
        if (!($isClient || $isPro) || !$this->payable($b)) {
            flash('error', 'A payment can\'t be recorded for this booking right now.');
            redirect('/bookings/' . $id);
        }
        $method = (string) ($_POST['method'] ?? '');
        if (!in_array($method, self::METHODS, true)) back_with_errors(['method' => 'Choose how the payment was made.'], '/bookings/' . $id);
        $reference = mb_substr(trim((string) ($_POST['reference'] ?? '')), 0, 120) ?: null;
        $now = date('Y-m-d H:i:s');

        DB::transaction(function () use ($b, $u, $isPro, $method, $reference, $now) {
            DB::insert('payments', [
                'booking_id' => (int) $b['id'], 'payer_id' => (int) $b['client_id'], 'payee_id' => (int) $b['provider_id'],
                'amount' => (float) $b['amount'], 'method' => $method, 'channel' => 'direct',
                'status' => $isPro ? 'paid' : 'pending', 'reference' => $reference, 'paid_at' => $isPro ? $now : null,
            ]);
            DB::update('bookings', ['payment_status' => $isPro ? 'paid' : 'pending'], 'id = :id', ['id' => (int) $b['id']]);
            DB::insert('booking_events', ['booking_id' => (int) $b['id'], 'actor_id' => (int) $u['id'],
                'event' => $isPro ? 'payment_received' : 'payment_sent', 'note' => payment_method_label($method)]);
        });

        if ($isPro) {
            notify((int) $b['client_id'], 'payment', 'Payment confirmed', $u['first_name'] . ' confirmed receiving ' . money($b['amount']) . '.', '/bookings/' . $b['id']);
            flash('success', 'Payment recorded as received.');
        } else {
            notify((int) $b['provider_id'], 'payment', 'Payment sent: please confirm',
                $u['first_name'] . ' says they paid ' . money($b['amount']) . ' via ' . payment_method_label($method) . '.', '/bookings/' . $b['id']);
            flash('success', 'Payment recorded. ' . $b['p_first'] . ' will confirm once received.');
        }
        redirect('/bookings/' . $b['id']);
    }

    public function confirm(string $paymentId): void
    {
        $this->resolve($paymentId, true);
    }

    public function reject(string $paymentId): void
    {
        $this->resolve($paymentId, false);
    }

    private function resolve(string $paymentId, bool $received): void
    {
        $u = require_role('provider');
        $pay = DB::one('SELECT * FROM payments WHERE id = ? AND payee_id = ?', [(int) $paymentId, (int) $u['id']]);
        if (!$pay) abort(404);
        if ($pay['status'] !== 'pending' || $pay['channel'] !== 'direct') redirect('/bookings/' . $pay['booking_id']);
        $b = DB::one('SELECT * FROM bookings WHERE id = ?', [(int) $pay['booking_id']]);

        DB::transaction(function () use ($pay, $u, $received) {
            DB::update('payments', ['status' => $received ? 'paid' : 'failed', 'paid_at' => $received ? date('Y-m-d H:i:s') : null], 'id = :id', ['id' => (int) $pay['id']]);
            DB::update('bookings', ['payment_status' => $received ? 'paid' : 'unpaid'], 'id = :id', ['id' => (int) $pay['booking_id']]);
            DB::insert('booking_events', ['booking_id' => (int) $pay['booking_id'], 'actor_id' => (int) $u['id'],
                'event' => $received ? 'payment_received' : 'payment_not_received']);
        });
        notify((int) $b['client_id'], 'payment', $received ? 'Payment confirmed' : 'Payment not received',
            $received ? $u['first_name'] . ' confirmed receiving ' . money($pay['amount']) . '.' : $u['first_name'] . ' hasn\'t received your payment yet. Please check and try again.',
            '/bookings/' . $b['id']);
        flash('success', $received ? 'Payment confirmed. Thanks!' : 'Marked as not received. The client has been notified.');
        redirect('/bookings/' . $b['id']);
    }

    /* ---------------- Online payments (PayMongo) ---------------- */

    public function startOnline(string $id): void
    {
        $u = require_role('client');
        $b = BookingController::find((int) $id, $u);
        if (!paymongo_enabled()) {
            flash('error', 'Online payments are not enabled on this site.');
            redirect('/bookings/' . $id);
        }
        if ((int) $b['client_id'] !== (int) $u['id'] || !$this->payable($b) || (float) $b['amount'] < 100) {
            flash('error', 'This booking can\'t be paid online right now (minimum ₱100).');
            redirect('/bookings/' . $id);
        }
        try {
            $checkout = PayMongo::createCheckout(
                $b,
                absolute_url('/bookings/' . $b['id'] . '/payment-return', ['status' => 'success']),
                absolute_url('/bookings/' . $b['id'] . '/payment-return', ['status' => 'cancelled'])
            );
        } catch (Throwable $e) {
            error_log((string) $e);
            flash('error', 'We couldn\'t start the online payment: ' . $e->getMessage());
            redirect('/bookings/' . $id);
        }
        DB::insert('payments', [
            'booking_id' => (int) $b['id'], 'payer_id' => (int) $b['client_id'], 'payee_id' => (int) $b['provider_id'],
            'amount' => (float) $b['amount'], 'method' => 'paymongo', 'channel' => 'online', 'status' => 'pending',
            'external_id' => $checkout['id'],
        ]);
        redirect($checkout['checkout_url']);
    }

    public function returnFromCheckout(string $id): void
    {
        $u = require_auth();
        $b = BookingController::find((int) $id, $u);
        $pay = DB::one("SELECT * FROM payments WHERE booking_id = ? AND channel = 'online' ORDER BY id DESC LIMIT 1", [(int) $id]);
        if (!$pay) redirect('/bookings/' . $id);

        if ($pay['status'] === 'pending') {
            $paid = false;
            try { $paid = PayMongo::isPaid($pay['external_id']); } catch (Throwable $e) { error_log((string) $e); }
            if ($paid) {
                self::markOnlinePaid($pay);
            } elseif (($_GET['status'] ?? '') === 'cancelled') {
                DB::update('payments', ['status' => 'cancelled'], 'id = :id', ['id' => (int) $pay['id']]);
            }
        }
        $fresh = DB::one('SELECT status FROM payments WHERE id = ?', [(int) $pay['id']]);
        if ($fresh['status'] === 'paid') flash('success', 'Payment successful. Thank you!');
        elseif ($fresh['status'] === 'cancelled') flash('info', 'Payment was cancelled. You can try again or pay your pro directly.');
        else flash('info', 'We\'re still waiting for the payment to be confirmed. This page will update once it is.');
        redirect('/bookings/' . $b['id']);
    }

    public static function markOnlinePaid(array $pay): void
    {
        // Idempotent: the return page and the webhook can both arrive; only the first one wins.
        $won = DB::transaction(function () use ($pay) {
            $changed = DB::query(
                "UPDATE payments SET status = 'paid', paid_at = ? WHERE id = ? AND status <> 'paid'",
                [date('Y-m-d H:i:s'), (int) $pay['id']]
            )->rowCount();
            if (!$changed) return false;
            DB::update('bookings', ['payment_status' => 'paid'], 'id = :id', ['id' => (int) $pay['booking_id']]);
            DB::insert('booking_events', ['booking_id' => (int) $pay['booking_id'], 'actor_id' => (int) $pay['payer_id'], 'event' => 'payment_received', 'note' => 'Paid online']);
            return true;
        });
        if (!$won) return;
        $b = DB::one('SELECT * FROM bookings WHERE id = ?', [(int) $pay['booking_id']]);
        notify((int) $b['provider_id'], 'payment', 'Online payment received', money($pay['amount']) . ' for “' . $b['title'] . '”.', '/bookings/' . $b['id']);
    }

    /** PayMongo webhook: event "checkout_session.payment.paid" */
    public function webhook(): void
    {
        $raw = (string) file_get_contents('php://input');
        if (!PayMongo::verifyWebhook($raw, $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '')) json_out(['error' => 'invalid signature'], 401);
        $event = json_decode($raw, true);
        $type = $event['data']['attributes']['type'] ?? '';
        $checkoutId = $event['data']['attributes']['data']['id'] ?? '';
        if ($type === 'checkout_session.payment.paid' && $checkoutId) {
            $pay = DB::one("SELECT * FROM payments WHERE external_id = ? AND channel = 'online'", [$checkoutId]);
            if ($pay) self::markOnlinePaid($pay);
        }
        json_out(['received' => true]);
    }
}
