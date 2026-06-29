<?php

namespace StudyRoomTechLab\MpesaPayment\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use StudyRoomTechLab\MpesaPayment\Models\MpesaSetting;

class MpesaDarajaService
{
    public function accessToken(MpesaSetting $setting): string
    {
        $environment = $setting->environment ?: config('mpesa-payment.environment', 'sandbox');
        $url = config("mpesa-payment.{$environment}.oauth_url");

        $consumerKey = $setting->decryptedConsumerKey() ?: config('mpesa-payment.consumer_key');
        $consumerSecret = $setting->decryptedConsumerSecret() ?: config('mpesa-payment.consumer_secret');

        if (! $url || ! $consumerKey || ! $consumerSecret) {
            throw new \RuntimeException('M-Pesa OAuth credentials are missing.');
        }

        $response = Http::timeout((int) config('mpesa-payment.timeout_seconds', 30))
            ->withBasicAuth($consumerKey, $consumerSecret)
            ->get($url);

        if (! $response->successful()) {
            Log::warning('M-Pesa OAuth failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Unable to get M-Pesa access token.');
        }

        $token = $response->json('access_token');

        if (! $token) {
            throw new \RuntimeException('M-Pesa access token missing in response.');
        }

        return $token;
    }

    public function stkPush(
        MpesaSetting $setting,
        string $phone,
        float $amount,
        string $accountReference,
        string $transactionDesc,
        ?string $callbackUrl = null
    ): array {
        $environment = $setting->environment ?: config('mpesa-payment.environment', 'sandbox');
        $url = config("mpesa-payment.{$environment}.stk_push_url");

        $shortcode = $setting->shortcode ?: config('mpesa-payment.shortcode');
        $passkey = $setting->decryptedPasskey() ?: config('mpesa-payment.passkey');

        if (! $url || ! $shortcode || ! $passkey) {
            throw new \RuntimeException('M-Pesa STK settings are missing.');
        }

        $timestamp = now()->format('YmdHis');

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $this->stkPassword($shortcode, $passkey, $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round($amount),
            'PartyA' => $this->normalizePhone($phone),
            'PartyB' => $shortcode,
            'PhoneNumber' => $this->normalizePhone($phone),
            'CallBackURL' => $callbackUrl ?: $setting->callback_url ?: config('mpesa-payment.callback_url'),
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc,
        ];

        $response = Http::timeout((int) config('mpesa-payment.timeout_seconds', 30))
            ->withToken($this->accessToken($setting))
            ->acceptJson()
            ->post($url, $payload);

        $json = $response->json() ?: [];

        if (! $response->successful()) {
            Log::warning('M-Pesa STK Push failed', [
                'status' => $response->status(),
                'payload' => $payload,
                'response' => $json ?: $response->body(),
            ]);

            throw new \RuntimeException('M-Pesa STK Push request failed.');
        }

        return [
            'payload' => $payload,
            'response' => $json,
        ];
    }

    public function stkQuery(MpesaSetting $setting, string $checkoutRequestId): array
    {
        $environment = $setting->environment ?: config('mpesa-payment.environment', 'sandbox');
        $url = config("mpesa-payment.{$environment}.stk_query_url");

        $shortcode = $setting->shortcode ?: config('mpesa-payment.shortcode');
        $passkey = $setting->decryptedPasskey() ?: config('mpesa-payment.passkey');

        if (! $url || ! $shortcode || ! $passkey) {
            throw new \RuntimeException('M-Pesa query settings are missing.');
        }

        $timestamp = now()->format('YmdHis');

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $this->stkPassword($shortcode, $passkey, $timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $response = Http::timeout((int) config('mpesa-payment.timeout_seconds', 30))
            ->withToken($this->accessToken($setting))
            ->acceptJson()
            ->post($url, $payload);

        return [
            'payload' => $payload,
            'response' => $response->json() ?: [],
            'successful' => $response->successful(),
            'status' => $response->status(),
        ];
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '254' . substr($phone, 1);
        }

        if (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            return '254' . $phone;
        }

        if (str_starts_with($phone, '254')) {
            return $phone;
        }

        return $phone;
    }

    private function stkPassword(string $shortcode, string $passkey, string $timestamp): string
    {
        return base64_encode($shortcode . $passkey . $timestamp);
    }
}