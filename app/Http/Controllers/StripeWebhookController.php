<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        
        // Get and decrypt webhook secret
        $webhookSecret = Setting::get('payment.stripe_webhook_secret', '');
        if (!empty($webhookSecret) && str_starts_with($webhookSecret, 'eyJ')) {
            try {
                $webhookSecret = \Illuminate\Support\Facades\Crypt::decryptString($webhookSecret);
            } catch (\Exception $e) {
                Log::warning('Failed to decrypt webhook secret: ' . $e->getMessage());
            }
        }

        // If no webhook secret configured, just log and return success
        if (empty($webhookSecret)) {
            Log::warning('Stripe webhook received but no webhook secret configured');
            return response()->json(['status' => 'webhook secret not configured'], 200);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;

            case 'charge.dispute.created':
                $this->handleDisputeCreated($event->data->object);
                break;

            default:
                Log::info('Stripe Webhook: Unhandled event type ' . $event->type);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Stripe: Payment succeeded for ' . $paymentIntent->id);

        $order = Order::where('transaction_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
            ]);
            Log::info('Order #' . $order->order_number . ' marked as paid via webhook');
        }
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        Log::warning('Stripe: Payment failed for ' . $paymentIntent->id);

        $order = Order::where('transaction_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
            ]);
            Log::info('Order #' . $order->order_number . ' marked as failed via webhook');
        }
    }

    /**
     * Handle refund
     */
    protected function handleChargeRefunded($charge)
    {
        Log::info('Stripe: Charge refunded ' . $charge->id);

        // Find order by payment intent
        if (isset($charge->payment_intent)) {
            $order = Order::where('transaction_id', $charge->payment_intent)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'refunded',
                ]);
                Log::info('Order #' . $order->order_number . ' marked as refunded via webhook');
            }
        }
    }

    /**
     * Handle dispute created
     */
    protected function handleDisputeCreated($dispute)
    {
        Log::warning('Stripe: Dispute created ' . $dispute->id);

        if (isset($dispute->payment_intent)) {
            $order = Order::where('transaction_id', $dispute->payment_intent)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'disputed',
                ]);
                Log::warning('Order #' . $order->order_number . ' has a dispute');
            }
        }
    }
}
