<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Route;

class PayTabsPayment implements PaymentInterface
{
    private string $profileId;
    private string $serverKey;
    private string $baseUrl;
    private string $callbackUrl;

    public function __construct(
        string $serverKey,
        string $profileId,
        bool $isLive
    ) {
        $this->serverKey = trim($serverKey);
        $this->profileId = trim($profileId);

        $this->baseUrl = 'https://secure.paytabs.com';

        $this->callbackUrl = Route('paytabs.webhook');

        Log::info('PayTabs initialized', [
            'profileId' => $this->profileId,
            'isLive'    => $isLive,
            'baseUrl'   => $this->baseUrl,
        ]);
    }
    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function createPaymentIntent($amount, $customMetaData)
    {

        $redirectUrl = ($customMetaData['platform_type'] == 'app')
            ? route('paytabs.success')
            : route('paytabs.success.web');

        $amount = $this->minimumAmountValidation(
            $customMetaData['currency_code'] ?? 'USD',
            $amount
        );

        $transactionId =
            't-' . $customMetaData['payment_transaction_id']
            . '-p-' . $customMetaData['package_id'];

        $payload = [
            'profile_id' => $this->profileId,
            'tran_type'  => 'sale',
            'tran_class' => 'ecom',

            'cart_id'          => $transactionId,
            'cart_currency'    => $customMetaData['currency_code'] ?? 'USD',
            'cart_amount'      => round($amount, 2),
            'cart_description' => 'Payment',

            'customer_details' => [
                'name'  => $customMetaData['customer_name'] ?? 'Customer',
                'email' => $customMetaData['customer_email'] ?? 'test@test.com',
                'phone' => $customMetaData['customer_phone'] ?? '0000000000',
                'street1' => 'NA',
                'city'    => 'NA',
                'state'   => 'NA',
                'country' => $customMetaData['country'] ?? 'US',
                'zip'     => '00000',
            ],

            'callback' => $this->callbackUrl,
            'return'   => $redirectUrl,

            'metadata' => $customMetaData,
        ];

        $response = Http::withHeaders([
            'authorization' => $this->serverKey,
            'content-type'  => 'application/json',
        ])->post($this->baseUrl . '/payment/request', $payload);

        $data = $response->json();

        return $data;
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function createAndFormatPaymentIntent($amount, $customMetaData): array
    {
        $paymentIntent = $this->createPaymentIntent($amount, $customMetaData);

        $transactionId =
            't-' . $customMetaData['payment_transaction_id']
            . '-p-' . $customMetaData['package_id'];

        return $this->formatPaymentIntent(
            $transactionId,
            $amount,
            $customMetaData['currency_code'] ?? 'USD',
            'PENDING',
            $customMetaData,
            $paymentIntent
        );
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function retrievePaymentIntent($transactionId): array
    {
        $response = Http::withHeaders([
            'authorization' => $this->serverKey,
            'content-type'  => 'application/json',
        ])->post($this->baseUrl . '/payment/query', [
            'profile_id' => $this->profileId,
            'tran_ref'   => $transactionId,
        ]);

        $data = $response->json();

        if (!isset($data['payment_result'])) {
            throw new Exception('Invalid PayTabs verification response');
        }

        return $this->formatPaymentIntent(
            $transactionId,
            $data['cart_amount'] ?? 0,
            $data['cart_currency'] ?? 'USD',
            $data['payment_result']['response_status'] ?? 'FAILED',
            $data['metadata'] ?? [],
            $data
        );
    }

    /** --------------------------------
     * Interface method (FIXED)
     * --------------------------------*/
    public function formatPaymentIntent(
        $id,
        $amount,
        $currency,
        $status,
        $metadata,
        $paymentIntent
    ): array {
        $formatted = [
            'id'       => $id,
            'amount'   => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
            'status'   => match ($status) {
                'A', 'SUCCESS' => 'succeeded',
                'P', 'PENDING' => 'pending',
                default => 'failed'
            },
            'payment_gateway_response' => $paymentIntent
        ];

        // Add payment URL for webview (same format as DPO)
        // PayTabs returns redirect_url in the payment gateway response
        if (is_array($paymentIntent)) {
            if (isset($paymentIntent['redirect_url'])) {
                $formatted['payment_url'] = $paymentIntent['redirect_url'];
            }
        }

        return $formatted;
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function minimumAmountValidation($currency, $amount)
    {
        return match ($currency) {
            'USD', 'SAR', 'AED' => max($amount, 1.00),
            default => max($amount, 0.50),
        };
    }
}
