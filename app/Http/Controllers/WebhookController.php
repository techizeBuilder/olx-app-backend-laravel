<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Package;
use App\Models\PaymentConfiguration;
use App\Models\PaymentTransaction;
use App\Models\Referral;
use App\Models\ReferPointTransaction;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Models\UserPurchasedPackage;
use App\Services\CachingService;
use App\Services\NotificationService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PayPalPayment;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;
use PhonePe\PhonePe;
use Throwable;


class WebhookController extends Controller
{
    public function stripe()
    {
        $payload = @file_get_contents('php://input');
        try {
            // Verify webhook signature and extract the event.
            // See https://stripe.com/docs/webhooks/signatures for more information.
            // $data = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);

            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

            // You can find your endpoint's secret in your webhook settings
            $paymentConfiguration = PaymentConfiguration::select('webhook_secret_key')->where('payment_method', 'Stripe')->first();
            $endpoint_secret = $paymentConfiguration['webhook_secret_key'];
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );

            $metadata = $event->data->object->metadata;

            // Use this lines to Remove Signature verification for debugging purpose
            // $event = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
            // $metadata = (array)$event->data->object->metadata;

            Log::info("Stripe Webhook : ", [$event]);
            // handle the events
            switch ($event->type) {
                case 'payment_intent.created':
                    //Do nothing
                    http_response_code(200);
                    break;
                case 'payment_intent.succeeded':
                    $response = $this->assignPackage($metadata['payment_transaction_id'], $metadata['user_id'], $metadata['package_id']);

                    if ($response['error']) {
                        Log::error("Stripe Webhook : ", [$response['message']]);
                    }
                    http_response_code(200);
                    break;
                case 'payment_intent.payment_failed':
                    $response = $this->failedTransaction($metadata['payment_transaction_id'], $metadata['user_id']);
                    if ($response['error']) {
                        Log::error("Stripe Webhook : ", [$response['message']]);
                    }
                    http_response_code(400);
                    break;
                default:
                    Log::error('Stripe Webhook : Received unknown event type', [$event->type]);
            }
        } catch (UnexpectedValueException) {
            // Invalid payload
            echo "Stripe Webhook : Payload Mismatch";
            Log::error("Stripe Webhook : Payload Mismatch");
            http_response_code(400);
            exit();
        } catch (SignatureVerificationException) {
            // Invalid signature
            echo "Stripe Webhook : Signature Verification Failed";
            Log::error("Stripe Webhook : Signature Verification Failed");
            http_response_code(400);
            exit();
        } catch (Throwable $e) {
            Log::error("Stripe Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            http_response_code(400);
            exit();
        }
    }

    public function razorpay()
    {
        try {
            Log::info("Razorpay Webhook called");
            $paymentConfiguration = PaymentConfiguration::select('webhook_secret_key', 'api_key')->where('payment_method', 'razorpay')->first();
            $webhookSecret = $paymentConfiguration['webhook_secret_key'];
            $webhookPublic = $paymentConfiguration["api_key"];

            // get the json data of payment
            $webhookBody = file_get_contents('php://input');
            $data = json_decode($webhookBody, false, 512, JSON_THROW_ON_ERROR);
            Log::info("Razorpay Webhook : ", [$data]);

            $api = new Api($webhookPublic, $webhookSecret);

            $metadata = $data->payload->payment->entity->notes;

            if (isset($data->event) && $data->event == 'payment.captured') {

                //checks the signature
                $expectedSignature = hash_hmac("SHA256", $webhookBody, $webhookSecret);

                $api->utility->verifyWebhookSignature($webhookBody, $expectedSignature, $webhookSecret);

                $paymentTransactionData = PaymentTransaction::where('id', $metadata->payment_transaction_id)->first();
                if ($paymentTransactionData == null) {
                    Log::error("Stripe Webhook : Payment Transaction id not found");
                }

                if ($paymentTransactionData->status == "succeed") {
                    Log::info("Stripe Webhook : Transaction Already Succeed");
                }
                $response = $this->assignPackage($metadata->payment_transaction_id, $metadata->user_id, $metadata->package_id);

                if ($response['error']) {
                    Log::error("Razorpay Webhook : ", [$response['message']]);
                }
                http_response_code(200);
            } elseif (isset($data->event) && $data->event == 'payment.failed') {
                $response = $this->failedTransaction($metadata->payment_transaction_id, $metadata->user_id);
                if ($response['error']) {
                    Log::error("Razorpay Webhook : ", [$response['message']]);
                }
                http_response_code(400);
            } elseif (isset($data->event) && $data->event == 'payment.authorized') {

                http_response_code(200);
            } else {
                Log::error('Unknown Event Type', [$data->event]);
            }
        } catch (Throwable $th) {
            Log::error($th);
            Log::error('Razorpay --> Webhook Error Occurred');
            http_response_code(400);
        }
    }

    public function paystack()
    {
        try {
            // only a post with paystack signature header gets our attention
            if (!array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER) || (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')) {
                echo "Signature not found";
                http_response_code(400);
                exit(0);
            }

            // Retrieve the request's body
            $input = @file_get_contents("php://input");
            $paymentConfiguration = PaymentConfiguration::select('webhook_secret_key')->where('payment_method', 'paystack')->first();
            $endpoint_secret = $paymentConfiguration['webhook_secret_key'];

            if (hash_equals($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'], hash_hmac('sha512', $input, $endpoint_secret))) {
                echo "Signature does not match";
                http_response_code(400);
                exit(0);
            }
            // parse event (which is json string) as object
            // Do something - that will not take long - with $event
            $event = json_decode($input, false, 512, JSON_THROW_ON_ERROR);
            $metadata = $event->data->metadata;
            Log::info("Paystack Webhook event Called", [$event]);
            switch ($event->event) {
                case 'charge.success':
                    $response = $this->assignPackage($metadata->payment_transaction_id, $metadata->user_id, $metadata->package_id);
                    if ($response['error']) {
                        Log::error("Paystack Webhook : ", [$response['message']]);
                    }
                    http_response_code(200);
                    break;
                default:
                    Log::error('Paystack Webhook : Received unknown event type', [$event->event]);
            }
            http_response_code(200);
            exit();
        } catch (Throwable $e) {
            Log::error("Paystack Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            http_response_code(400);
            exit();
        }
    }

    public function paystackSuccessCallback()
    {
        ResponseService::successResponse("Payment done successfully.");
    }

    public function phonePe(Request $request)
    {
        try {
            Log::info("PhonePe Webhook event called");

            // Must be POST
            if ($request->method() !== 'POST') {
                Log::error("Invalid request method");
                return response('Invalid request method', 400);
            }

            // Raw payload
            $content = $request->getContent();
            $jsonInput = json_decode($content, true);
            Log::info("PhonePe Webhook Raw Payload", [$jsonInput]);

            if (!$jsonInput) {
                Log::error("Invalid JSON payload");
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            // --- Step 1: Verify Authorization Header ---
            $authHeader = $request->header('Authorization');
            if (!$authHeader) {
                Log::error("Missing Authorization header");
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $paymentConfiguration = PaymentConfiguration::where('payment_method', 'PhonePe')->first();
            $username = $paymentConfiguration->username ?? '';
            $password = $paymentConfiguration->password ?? '';

            $expectedHash = hash('sha256', $username . ':' . $password);
            if (!hash_equals($expectedHash, $authHeader)) {
                Log::error("Authorization header mismatch");
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // --- Step 2: Extract Order Info ---
            $payload = $jsonInput['payload'] ?? [];
            $orderId = $payload['orderId'] ?? null;
            $state = $payload['state'] ?? null;
            $amount = $payload['amount'] ?? null;
            $metadata = $payload['metadata'] ?? [];

            $transaction_id = null;
            $package_id = null;
            $user_id = null;

            // Try from merchantOrderId (for web)

            // Fallback for App SDK

            Log::info("PhonePe Identified Meta", [
                'transaction_id' => $transaction_id,
                'package_id' => $package_id,
                'user_id' => $user_id,
            ]);

            Log::info("PhonePe Payment Event", [
                'event' => $jsonInput['event'] ?? null,
                'orderId' => $orderId,
                'state' => $state,
                'amount' => $amount,
            ]);

            $paymentTransaction = PaymentTransaction::where(['order_id' => $orderId, 'payment_gateway' => 'phonepe'])->first();

            // --- Step 3: Business Logic ---
            if ($state === "COMPLETED" || $state === "SUCCESS") {

                if ($paymentTransaction) {
                    // Idempotency check
                    if ($paymentTransaction->payment_status === 'completed') {
                        Log::info("PhonePe Webhook already processed", ['transaction_id' => $transaction_id]);
                        return response()->json(['status' => 'already_processed'], 200);
                    }

                    $response = $this->assignPackage(
                        $paymentTransaction->id,
                        $paymentTransaction->user_id,
                        $paymentTransaction->package_id
                    );

                    if ($response['error']) {
                        Log::error("PhonePe Webhook assignPackage error", [$response['message']]);
                    }
                }

                return response()->json(['status' => 'success'], 200);
            } else {
                Log::warning("PhonePe Payment Failed", $jsonInput);
                if ($transaction_id) {
                    PaymentTransaction::where('id', $transaction_id)->update(['payment_status' => 'failed']);
                }
                return response()->json(['status' => 'failed'], 400);
            }
        } catch (\Throwable $e) {
            Log::error("PhonePe Webhook Error", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function flutterwave()
    {
        try {
            if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
                Log::error("Invalid request method");
                return response('Invalid request method', 400);
            }

            $content = trim(file_get_contents("php://input"));
            $payload = json_decode($content, true);
            if (!$payload || empty($payload)) {
                Log::error('Invalid webhook payload');
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            if (!isset($payload['txRef']) || !isset($payload['status'])) {
                Log::error('Missing required fields in webhook payload');
                return response()->json(['error' => 'Invalid payload structure'], 400);
            }

            $transactionRef = $payload['txRef'];
            $status = $payload['status'];
            $amount = $payload['amount'];
            $currency = $payload['currency'];
            $customer = $payload['customer'];
            $transactionId = $payload['id'];

            $parts = explode('-', $transactionRef);
            $transaction_id = $parts[1];
            $package_id = $parts[3];
            $paymentTransaction = PaymentTransaction::findOrFail($transaction_id);

            if ($status === 'successful') {
                Log::info('Flutterwave Payment Successful', [
                    'transactionId' => $transactionId,
                    'transactionRef' => $transactionRef,
                    'amount' => $amount,
                    'currency' => $currency,
                    'customer' => $customer
                ]);

                $metadata = [
                    'payment_transaction_id' => $transaction_id,
                    'package_id' => $package_id,
                    'user_id' => $paymentTransaction->user_id,
                ];

                $response = $this->assignPackage($metadata['payment_transaction_id'], $metadata['user_id'], $metadata['package_id']);
                if ($response['error']) {
                    Log::error("Flutterwave Webhook : ", [$response['message']]);
                }

                return response()->json(['status' => 'success'], 200);
            } else {
                Log::warning('Flutterwave Payment Failed or Incomplete', [$payload]);
                return response()->json(['status' => 'failure'], 400);
            }
        } catch (Throwable $e) {
            Log::error("Flutterwave Webhook Error", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function phonePeSuccessCallback()
    {
        ResponseService::successResponse("Payment done successfully.");
    }

    public function paypal()
    {
        try {
            Log::info("PayPal Webhook event called");

            // ✅ Ensure POST
            if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
                Log::error("Invalid request method");
                return response('Invalid request method', 400);
            }

            // ✅ Raw payload
            $content = trim(file_get_contents("php://input"));
            $jsonInput = json_decode($content, true);
            Log::info("PayPal Webhook Raw Payload", [$jsonInput]);

            if (!$jsonInput || empty($jsonInput)) {
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            $eventType = $jsonInput['event_type'] ?? null;
            $resource  = $jsonInput['resource'] ?? [];

            Log::info("PayPal Event", [
                'event' => $eventType,
                'orderId' => $resource['id'] ?? null,
                'status' => $resource['status'] ?? null,
            ]);
            $paymentConfiguration = PaymentConfiguration::where('payment_method', 'paypal')->first();
            $webhookId = $paymentConfiguration['webhook_url'] ?? url('/webhook/paypal');

            $paypal = new PayPalPayment($paymentConfiguration->api_key, $paymentConfiguration->secret_key, $paymentConfiguration->currency_code, $paymentConfiguration->payment_mode);


            // Get raw body for verification
            $rawBody = file_get_contents('php://input');

            // Extract headers properly (case-insensitive)
            $paypalHeaders = [
                'paypal-auth-algo'         => request()->header('paypal-auth-algo') ?: request()->header('PAYPAL-AUTH-ALGO'),
                'paypal-cert-url'          => request()->header('paypal-cert-url') ?: request()->header('PAYPAL-CERT-URL'),
                'paypal-transmission-id'   => request()->header('paypal-transmission-id') ?: request()->header('PAYPAL-TRANSMISSION-ID'),
                'paypal-transmission-sig'  => request()->header('paypal-transmission-sig') ?: request()->header('PAYPAL-TRANSMISSION-SIG'),
                'paypal-transmission-time' => request()->header('paypal-transmission-time') ?: request()->header('PAYPAL-TRANSMISSION-TIME'),
            ];

            // Verify webhook authenticity
            $isValid = $paypal->verifyWebhookSignature($paypalHeaders, $rawBody, $webhookId);

            if (!$isValid) {
                Log::warning('Invalid PayPal Webhook Signature');
                return response()->json(['status' => 'invalid'], 200); // respond 200 to prevent PayPal retries
            }


            // --- Step 1: Handle APPROVED orders (capture payment) ---
            if ($eventType === "CHECKOUT.ORDER.APPROVED") {
                $orderId = $resource['id'];

                // $paypal = app(PaypalPayment::class);
                // $capture = $paypal->capturePayment($orderId);

                Log::info("PayPal Order checkout approved");

                // update your DB here
                return response()->json(['status' => 'captured'], 200);
            }

            // --- Step 2: Handle final success event ---
            if ($eventType === "PAYMENT.CAPTURE.COMPLETED") {
                $captureId = $resource['id'];
                $amount    = $resource['amount']['value'] ?? null;
                $currency  = $resource['amount']['currency_code'] ?? null;
                $customId  = $resource['custom_id'] ?? null;

                Log::info("PayPal Payment Completed", [
                    'captureId' => $captureId,
                    'amount'    => $amount,
                    'currency'  => $currency,
                    'custom_id' => $customId,
                ]);

                if ($customId) {
                    // Extract transaction_id & package_id
                    $parts = explode('-', $customId);
                    $transaction_id = $parts[1] ?? null;
                    $package_id     = $parts[3] ?? null;

                    if ($transaction_id && $package_id) {
                        $paymentTransaction = PaymentTransaction::find($transaction_id);

                        if ($paymentTransaction) {
                            $metadata = [
                                'payment_transaction_id' => $transaction_id,
                                'package_id'             => $package_id,
                                'user_id'                => $paymentTransaction->user_id,
                            ];

                            $response = $this->assignPackage(
                                $metadata['payment_transaction_id'],
                                $metadata['user_id'],
                                $metadata['package_id']
                            );

                            if ($response['error']) {
                                Log::error("PayPal Webhook assignPackage error", [$response['message']]);
                            }
                        } else {
                            Log::error("PayPal Webhook: PaymentTransaction not found", [
                                'transaction_id' => $transaction_id
                            ]);
                        }
                    }
                }

                return response()->json(['status' => 'success'], 200);
            }

            // --- Step 3: Handle failed/refunded ---
            if (in_array($eventType, ["PAYMENT.CAPTURE.DENIED", "PAYMENT.CAPTURE.REFUNDED"])) {
                Log::warning("PayPal Payment Issue", $jsonInput);
                return response()->json(['status' => 'failed'], 200);
            }

            return response()->json(['status' => 'ignored'], 200);
        } catch (\Throwable $e) {
            Log::error("PayPal Webhook Error", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function handlePaypalCapture($orderId)
    {
        $payment = PaymentConfiguration::where([
            'payment_method' => 'paypal',
            'status' => 1
        ])->first();

        if (!$payment) {
            throw new \Exception("PayPal payment configuration not found.");
        }

        $paypal = new PayPalPayment(
            $payment->api_key,
            $payment->secret_key,
            $payment->currency_code,
            $payment->payment_mode
        );

        return $paypal->capturePayment($orderId);
    }

    public function paypalPaymentSuccess(Request $request)
    {
        try {
            Log::info("PayPal Success Raw Payload", [$request->all()]);

            $orderId = $request->query('token');

            if (!$orderId) {
                return view('payment.paypal', [
                    'trxref'    => null,
                    'reference' => null
                ]);
            }

            $paymentResult = $this->handlePaypalCapture($orderId);

            Log::info("PayPal Success Redirect Capture", (array) $paymentResult);

            return view('payment.paypal', [
                'trxref'    => $orderId,
                'reference' => $paymentResult['id'] ?? null
            ]);
        } catch (\Throwable $e) {
            Log::error("PayPal Success Handler Error", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return view('payment.paypal', [
                'trxref'    => null,
                'reference' => null
            ]);
        }
    }

    public function paypalSuccessCallback(Request $request)
    {
        try {
            Log::info("PayPal Success callback for app");
            Log::info("PayPal Success Raw Payload for app", [$request->all()]);
            $orderId = $request->query('token');
            if (!$orderId) {
                return ResponseService::errorResponse("Missing PayPal order ID.");
            }

            $paymentResult = $this->handlePaypalCapture($orderId);
            Log::info("PayPal Success Redirect Capture For App", (array) $paymentResult);
            return ResponseService::successResponse("Payment done successfully.");
        } catch (\Throwable $e) {
            Log::error("PayPal Success Callback Error", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return ResponseService::errorResponse("Something went wrong during PayPal payment.");
        }
    }

    public function paypalCancelCallback()
    {
        return ResponseService::successResponse("Payment Cancelled successfully.");
    }
    public function paypalCancelCallbackWeb(Request $request)
    {
        try {
            $orderId = $request->query('token');
            return view('payment.paypal', [
                'trxref'    => $orderId ?? null,
                'reference' => null
            ]);
        } catch (\Throwable $e) {
            Log::error("PayPal Success Handler Error", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return view('payment.paypalcancle', [
                'trxref'    => null,
                'reference' => null
            ]);
        }
    }
    /**
     * Success Business Login
     * @param $payment_transaction_id
     * @param $user_id
     * @param $package_id
     * @return array
     */
    private function assignPackage($payment_transaction_id, $user_id, $package_id)
    {
        try {
            $paymentTransactionData = PaymentTransaction::where('id', $payment_transaction_id)->first();
            if ($paymentTransactionData == null) {
                Log::error("Payment Transaction id not found");
                return [
                    'error'   => true,
                    'message' => 'Payment Transaction id not found'
                ];
            }

            if ($paymentTransactionData->status == "succeed") {
                Log::info("Transaction Already Succeed");
                return [
                    'error'   => true,
                    'message' => 'Transaction Already Succeed'
                ];
            }

            DB::beginTransaction();
            $paymentTransactionData->update(['payment_status' => "succeed"]);

            $package = Package::find($package_id);

            if (!empty($package)) {
                // create purchased package record
                $userPackage = UserPurchasedPackage::create([
                    'package_id'  => $package_id,
                    'user_id'     => $user_id,
                    'start_date'  => Carbon::now(),
                    'end_date'    => $package->duration == "unlimited" ? null : Carbon::now()->addDays($package->duration),
                    'total_limit' => $package->item_limit == "unlimited" ? null : $package->item_limit,
                    'listing_duration_type' => $package->listing_duration_type,
                    'listing_duration_days' => $package->listing_duration_days
                ]);
            }

            // Deduct refer points used (if any)
            if ($paymentTransactionData->refer_points_used > 0) {
                $user = User::lockForUpdate()->find($user_id);
                if ($user) {
                    $pointsToDeduct = $paymentTransactionData->refer_points_used;
                    $user->decrement('refer_points', $pointsToDeduct);
                    $user->refresh();

                    ReferPointTransaction::create([
                        'user_id' => $user_id,
                        'points' => $pointsToDeduct,
                        'transaction_type' => 'debit',
                        'type' => 'used_for_purchase',
                        'remark' => 'Used ' . $pointsToDeduct . ' points for ' . ($package->name ?? 'package') . ' purchase',
                        'package_original_price' => $package->price ?? null,
                        'package_discounted_price' => $package->final_price ?? null,
                        'points_used' => $pointsToDeduct,
                        'points_remaining_after' => $user->refer_points,
                        'final_payment_amount' => $paymentTransactionData->amount,
                        'reference_id' => $paymentTransactionData->id,
                        'reference_type' => 'payment_transaction',
                    ]);
                }
            }

            // Award referral points if applicable
            $this->awardReferralPoints($user_id, $package);

            $title = "Package Purchased";
            $body = 'Amount :- ' . $paymentTransactionData->amount;
            if (!empty($user_id)) {
                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    $title,
                    $body,
                    'payment',
                    ['id' => $paymentTransactionData->id],
                    false,
                    array($user_id),
                    true
                );
            }
            DB::commit();

            return [
                'error'   => false,
                'message' => 'Transaction Verified Successfully'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage() . "WebhookController -> assignPackage");
            return [
                'error'   => true,
                'message' => 'Error Occurred'
            ];
        }
    }

    private function awardReferralPoints($user_id, $package)
    {
        try {
            $referEarnEnabled = CachingService::getSystemSettings('refer_earn_enabled');
            if ($referEarnEnabled != '1') {
                Log::info("Refer Points not enabled");
                return;
            }

            // Only award for paid packages
            if (empty($package) || $package->final_price <= 0) {
                Log::info("Package is not paid, Refer points will not be used");
                return;
            }

            // Find unrewarded referral for this user
            $referral = Referral::where('referred_id', $user_id)
                ->where('is_rewarded', false)
                ->first();

            if (empty($referral)) {
                Log::info("Referral is already rewarded");
                return;
            }

            $pointsForReferrer = (int) (CachingService::getSystemSettings('refer_points_for_referrer') ?: 10);
            $pointsForReferred = (int) (CachingService::getSystemSettings('refer_points_for_referred') ?: 5);

            // Credit referrer
            $referrer = User::lockForUpdate()->find($referral->referrer_id);
            if ($referrer) {
                $referrer->increment('refer_points', $pointsForReferrer);
                $referrer->refresh();

                ReferPointTransaction::create([
                    'user_id' => $referrer->id,
                    'points' => $pointsForReferrer,
                    'transaction_type' => 'credit',
                    'type' => 'earned_by_referral',
                    'remark' => 'Earned ' . $pointsForReferrer . ' points - referred user purchased ' . ($package->name ?? 'a package'),
                    'points_remaining_after' => $referrer->refer_points,
                    'reference_id' => $referral->id,
                    'reference_type' => 'referral',
                ]);

                // Notify referrer
                NotificationService::dispatchChunkedNotifications(
                    'Referral Reward',
                    'You earned ' . $pointsForReferrer . ' refer points!',
                    'refer_earn',
                    ['referral_id' => $referral->id],
                    false,
                    [$referrer->id],
                    true
                );
            }

            // Credit referred user
            $referredUser = User::lockForUpdate()->find($user_id);
            if ($referredUser) {
                $referredUser->increment('refer_points', $pointsForReferred);
                $referredUser->refresh();

                ReferPointTransaction::create([
                    'user_id' => $referredUser->id,
                    'points' => $pointsForReferred,
                    'transaction_type' => 'credit',
                    'type' => 'earned_as_referred',
                    'remark' => 'Earned ' . $pointsForReferred . ' points for first paid plan purchase via referral',
                    'points_remaining_after' => $referredUser->refer_points,
                    'reference_id' => $referral->id,
                    'reference_type' => 'referral',
                ]);
            }

            // Mark referral as rewarded
            $referral->update([
                'is_rewarded' => true,
                'rewarded_at' => now(),
            ]);
            Log::info("Referral Rewarded from paid package purchased after webhook verification");
        } catch (Throwable $th) {
            Log::error('Error awarding referral points: ' . $th->getMessage());
        }
    }

    public function paytabs(Request $request)
    {
        try {
            Log::info("PayTabs Webhook called");

            // 1️⃣ Must be POST
            if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
                Log::error("PayTabs Invalid request method");
                return response('Invalid request method', 400);
            }

            // 2️⃣ Get raw payload (CRITICAL)
            $rawBody = file_get_contents('php://input');

            if (empty($rawBody)) {
                Log::error("PayTabs Empty payload");
                return response()->json(['error' => 'Empty payload'], 400);
            }

            // 3️⃣ Get signature header
            $receivedSignature = $request->header('signature');

            if (!$receivedSignature) {
                Log::error("PayTabs Missing signature header");
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // 4️⃣ Load PayTabs server key from DB
            $paymentConfiguration = PaymentConfiguration::where('payment_method', 'PayTabs')->first();

            if (!$paymentConfiguration || empty($paymentConfiguration->secret_key)) {
                Log::critical("PayTabs Secret key not configured");
                return response()->json(['error' => 'Server configuration error'], 500);
            }

            $serverKey = $paymentConfiguration->secret_key;

            // 5️⃣ Generate expected signature
            $expectedSignature = hash_hmac('sha256', $rawBody, $serverKey);

            // 6️⃣ Constant-time comparison
            if (!hash_equals($expectedSignature, $receivedSignature)) {
                Log::error("PayTabs Signature mismatch", [
                    'expected' => $expectedSignature,
                    'received' => $receivedSignature
                ]);

                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // 7️⃣ Decode payload AFTER verification
            $payload = json_decode($rawBody, true);

            if (!$payload || empty($payload)) {
                Log::error("PayTabs Invalid JSON payload");
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            Log::info("PayTabs Webhook Payload", [$payload]);

            /**
             * Expected PayTabs payload structure
             * ----------------------------------
             * tran_ref
             * cart_id
             * payment_result.response_status
             * metadata (optional)
             */

            $tranRef = $payload['tran_ref'] ?? null;
            $cartId  = $payload['cart_id'] ?? null;
            $status  = $payload['payment_result']['response_status'] ?? null;
            $metadata = $payload['metadata'] ?? [];

            // 8️⃣ Resolve IDs (metadata first)
            $transaction_id = $metadata['payment_transaction_id'] ?? null;
            $package_id     = $metadata['package_id'] ?? null;
            $user_id        = $metadata['user_id'] ?? null;

            // 9️⃣ Fallback: parse cart_id → t-{trx}-p-{pkg}
            if ((!$transaction_id || !$package_id) && $cartId) {
                $parts = explode('-', $cartId);
                $transaction_id = $parts[1] ?? null;
                $package_id     = $parts[3] ?? null;
            }

            // 10️⃣ Load user_id if missing
            if ($transaction_id && !$user_id) {
                $paymentTransaction = PaymentTransaction::find($transaction_id);
                $user_id = $paymentTransaction->user_id ?? null;
            }

            if (!$transaction_id || !$package_id || !$user_id) {
                Log::error("PayTabs Missing transaction metadata", [
                    'tran_ref' => $tranRef,
                    'cart_id' => $cartId,
                    'metadata' => $metadata
                ]);

                // IMPORTANT: return 200 to avoid retries
                return response()->json(['status' => 'ignored'], 200);
            }

            // 1️⃣1️⃣ Idempotency check
            $paymentTransaction = PaymentTransaction::find($transaction_id);

            if (!$paymentTransaction) {
                Log::error("PayTabs PaymentTransaction not found", [$transaction_id]);
                return response()->json(['status' => 'ignored'], 200);
            }

            if ($paymentTransaction->payment_status === 'succeed') {
                Log::info("PayTabs Transaction already processed", [$transaction_id]);
                return response()->json(['status' => 'already_processed'], 200);
            }

            // 1️⃣2️⃣ Business logic based on PayTabs status
            if ($status === 'A') {
                // ✅ Approved
                $response = $this->assignPackage($transaction_id, $user_id, $package_id);

                if ($response['error']) {
                    Log::error("PayTabs assignPackage failed", [$response['message']]);
                }

                return response()->json(['status' => 'success'], 200);
            }

            if (in_array($status, ['D', 'E', 'C'])) {
                // ❌ Declined / Error / Cancelled
                $response = $this->failedTransaction($transaction_id, $user_id);

                if ($response['error']) {
                    Log::error("PayTabs failedTransaction failed", [$response['message']]);
                }

                return response()->json(['status' => 'failed'], 200);
            }

            // H / P or unknown → ignore safely
            Log::warning("PayTabs Payment pending/hold", [
                'status' => $status,
                'tran_ref' => $tranRef
            ]);

            return response()->json(['status' => 'pending'], 200);
        } catch (\Throwable $e) {
            Log::error("PayTabs Webhook Fatal Error", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function paytabsSuccessCallback()
    {
        ResponseService::successResponse("Payment done successfully.");
    }

    public function dpoSuccessCallback(Request $request)
    {
        $transactionToken = $request->query('TransactionToken') ?? $request->query('token');
        $companyRef = $request->query('CompanyRef');

        try {
            if (!$transactionToken) {
                Log::warning("DPO Success Callback: Transaction token not found", [
                    'request' => $request->all()
                ]);
                ResponseService::successResponse("Payment received. Your transaction is being processed.");
                return;
            }

            // Get payment mode from configuration
            $dpoConfig = PaymentConfiguration::where(['payment_method' => 'DPO', 'status' => 1])->first();
            $paymentMode = $dpoConfig->payment_mode ?? 'UAT';

            // If test mode (UAT), verify and assign package immediately
            if ($paymentMode === 'UAT') {
                try {
                    $dpo = PaymentService::create('dpo');
                    $verified = $dpo->retrievePaymentIntent($transactionToken);

                    Log::info("DPO Success Callback Verify (UAT Mode)", [
                        'transaction_token' => $transactionToken,
                        'verified_status' => $verified['status'] ?? 'unknown'
                    ]);

                    if ($verified['status'] === 'succeeded') {
                        // Find payment transaction by order_id (token)
                        $paymentTransaction = PaymentTransaction::where('order_id', $transactionToken)->first();
                        
                        if ($paymentTransaction && $paymentTransaction->payment_status !== 'succeed') {
                            // Assign package immediately in test mode
                            $result = $this->assignPackage(
                                $paymentTransaction->id,
                                $paymentTransaction->user_id,
                                $paymentTransaction->package_id
                            );
                            
                            if (isset($result['error']) && $result['error']) {
                                Log::error("DPO Success Callback: Package assignment failed", [
                                    'transaction_id' => $paymentTransaction->id,
                                    'error' => $result['message'] ?? 'Unknown error'
                                ]);
                            }
                            
                            ResponseService::successResponse("Payment verified and package assigned successfully.");
                        } elseif ($paymentTransaction && $paymentTransaction->payment_status === 'succeed') {
                            ResponseService::successResponse("Payment already processed successfully.");
                        } else {
                            Log::warning("DPO Success Callback: Payment transaction not found", [
                                'transaction_token' => $transactionToken
                            ]);
                            ResponseService::successResponse("Payment received. Your transaction is being processed.");
                        }
                    } else {
                        Log::warning("DPO Success Callback: Payment verification status not succeeded", [
                            'transaction_token' => $transactionToken,
                            'status' => $verified['status'] ?? 'unknown'
                        ]);
                        ResponseService::successResponse("Payment received. Your transaction is being processed.");
                    }
                } catch (\Throwable $e) {
                    Log::error("DPO Success Callback Verify Error (UAT Mode)", [
                        'transaction_token' => $transactionToken,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    ResponseService::successResponse("Payment received. Your transaction is being processed.");
                }
            } else {
                // Live mode (PROD) - webhook will handle package assignment in background
                ResponseService::successResponse("Payment received. Your package will be activated shortly.");
            }
        } catch (\Throwable $e) {
            Log::error("DPO Success Callback Handler Error", [
                'transaction_token' => $transactionToken,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            ResponseService::successResponse("Payment received. Your transaction is being processed.");
        }
    }

    public function dpoPaymentSuccess(Request $request)
    {
        $transactionToken = $request->query('TransactionToken') ?? $request->query('token');
        $companyRef = $request->query('CompanyRef');

        try {
            Log::info("DPO Success Raw Payload", [$request->all()]);

            if (!$transactionToken) {
                Log::warning("DPO Success Redirect: Transaction token not found", [
                    'request' => $request->all()
                ]);
                return view('payment.dpo', [
                    'transactionToken' => null,
                    'companyRef' => null,
                    'message' => 'Payment received. Your transaction is being processed.'
                ]);
            }

            // Get payment mode from configuration
            $dpoConfig = PaymentConfiguration::where(['payment_method' => 'DPO', 'status' => 1])->first();
            $paymentMode = $dpoConfig->payment_mode ?? 'UAT';

            // If test mode (UAT), verify and assign package immediately
            if ($paymentMode === 'UAT') {
                try {
                    $dpo = PaymentService::create('dpo');
                    $verified = $dpo->retrievePaymentIntent($transactionToken);

                    Log::info("DPO Success Redirect Verify (UAT Mode)", [
                        'transaction_token' => $transactionToken,
                        'verified_status' => $verified['status'] ?? 'unknown'
                    ]);

                    if ($verified['status'] === 'succeeded') {
                        // Find payment transaction by order_id (token)
                        $paymentTransaction = PaymentTransaction::where('order_id', $transactionToken)->first();
                        
                        if ($paymentTransaction && $paymentTransaction->payment_status !== 'succeed') {
                            // Assign package immediately in test mode
                            $result = $this->assignPackage(
                                $paymentTransaction->id,
                                $paymentTransaction->user_id,
                                $paymentTransaction->package_id
                            );
                            
                            if (isset($result['error']) && $result['error']) {
                                Log::error("DPO Success Redirect: Package assignment failed", [
                                    'transaction_id' => $paymentTransaction->id,
                                    'error' => $result['message'] ?? 'Unknown error'
                                ]);
                            }
                            
                            return view('payment.dpo', [
                                'transactionToken' => $transactionToken,
                                'companyRef' => $companyRef,
                                'message' => 'Payment verified and package assigned successfully.'
                            ]);
                        } elseif ($paymentTransaction && $paymentTransaction->payment_status === 'succeed') {
                            return view('payment.dpo', [
                                'transactionToken' => $transactionToken,
                                'companyRef' => $companyRef,
                                'message' => 'Payment already processed successfully.'
                            ]);
                        } else {
                            Log::warning("DPO Success Redirect: Payment transaction not found", [
                                'transaction_token' => $transactionToken
                            ]);
                            return view('payment.dpo', [
                                'transactionToken' => $transactionToken,
                                'companyRef' => $companyRef,
                                'message' => 'Payment received. Your transaction is being processed.'
                            ]);
                        }
                    } else {
                        Log::warning("DPO Success Redirect: Payment verification status not succeeded", [
                            'transaction_token' => $transactionToken,
                            'status' => $verified['status'] ?? 'unknown'
                        ]);
                        return view('payment.dpo', [
                            'transactionToken' => $transactionToken,
                            'companyRef' => $companyRef,
                            'message' => 'Payment received. Your transaction is being processed.'
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error("DPO Success Verify Error (UAT Mode)", [
                        'transaction_token' => $transactionToken,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return view('payment.dpo', [
                        'transactionToken' => $transactionToken,
                        'companyRef' => $companyRef,
                        'message' => 'Payment received. Your transaction is being processed.'
                    ]);
                }
            } else {
                // Live mode (PROD) - webhook will handle package assignment in background
                return view('payment.dpo', [
                    'transactionToken' => $transactionToken,
                    'companyRef' => $companyRef,
                    'message' => 'Payment received. Your package will be activated shortly.'
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("DPO Success Handler Error", [
                'transaction_token' => $transactionToken,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('payment.dpo', [
                'transactionToken' => $transactionToken,
                'companyRef' => $companyRef,
                'message' => 'Payment received. Your transaction is being processed.'
            ]);
        }
    }

    public function dpo(Request $request)
    {
        try {
            Log::info('DPO Webhook called');

            // 1️⃣ Must be POST
            if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
                return response('Invalid method', 400);
            }

            // 2️⃣ Raw body (DPO sends form / xml)
            $rawBody = file_get_contents('php://input');
            if (empty($rawBody)) {
                return response()->json(['ignored' => true], 200);
            }

            Log::info('DPO Raw Payload', [$rawBody]);

            // 3️⃣ Extract token
            preg_match('/TransactionToken>(.*?)<\/TransactionToken>/', $rawBody, $matches);
            $token = $matches[1] ?? $request->TransactionToken ?? null;

            if (!$token) {
                Log::error('DPO token missing');
                return response()->json(['ignored' => true], 200);
            }

            // 4️⃣ Resolve PaymentTransaction
            $paymentTransaction = PaymentTransaction::where('order_id', $token)->first();
            if (!$paymentTransaction) {
                Log::warning('DPO transaction not found', [$token]);
                return response()->json(['ignored' => true], 200);
            }

            // 5️⃣ Idempotency
            if ($paymentTransaction->payment_status === 'succeed') {
                return response()->json(['already_processed' => true], 200);
            }

            // 6️⃣ Verify with DPO (ONLY TRUTH)
            $dpo = PaymentService::create('dpo');
            $verified = $dpo->retrievePaymentIntent($token);

            if ($verified['status'] === 'succeeded') {
                $this->assignPackage(
                    $paymentTransaction->id,
                    $paymentTransaction->user_id,
                    $paymentTransaction->package_id
                );
                return response()->json(['status' => 'success'], 200);
            }

            $this->failedTransaction(
                $paymentTransaction->id,
                $paymentTransaction->user_id
            );

            return response()->json(['status' => 'failed'], 200);
        } catch (\Throwable $e) {
            Log::error('DPO Webhook Fatal', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => true], 500);
        }
    }

    public function flutterWaveSuccessCallback()
    {
        ResponseService::successResponse("Payment done successfully.");
    }
    /**
     * Failed Business Logic
     * @param $payment_transaction_id
     * @param $user_id
     * @return array
     */
    private function failedTransaction($payment_transaction_id, $user_id)
    {
        try {
            $paymentTransactionData = PaymentTransaction::find($payment_transaction_id);
            if (!$paymentTransactionData) {
                return [
                    'error'   => true,
                    'message' => 'Payment Transaction id not found'
                ];
            }

            $paymentTransactionData->update(['payment_status' => "failed"]);

            $body = 'Amount :- ' . $paymentTransactionData->amount;
            // Dispatch chunked notification jobs using centralized service
            NotificationService::dispatchChunkedNotifications(
                'Package Payment Failed',
                $body,
                'payment',
                ['id' => $paymentTransactionData->id],
                false,
                array($user_id),
                true
            );
            // NotificationService::sendFcmNotification($userTokens, 'Package Payment Failed', $body, 'payment');
            return [
                'error'   => false,
                'message' => 'Transaction Verified Successfully'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage() . "WebhookController -> failedTransaction");
            return [
                'error'   => true,
                'message' => 'Error Occurred'
            ];
        }
    }
}
