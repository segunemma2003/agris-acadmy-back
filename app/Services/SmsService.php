<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);
        if ($phone === '' || trim($message) === '') {
            return false;
        }

        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.key');

        if (! $apiKey || ! $username) {
            Log::warning("Africa's Talking SMS not configured", ['phone' => $phone]);

            return false;
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => $username,
                'to' => $phone,
                'message' => $message,
            ]);

            if (! $response->successful()) {
                Log::error('SMS dispatch rejected', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS dispatch failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', trim($phone)) ?? '';
        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0') && strlen($phone) === 11) {
            return '+234'.substr($phone, 1);
        }

        if (str_starts_with($phone, '234') && ! str_starts_with($phone, '+')) {
            return '+'.$phone;
        }

        return $phone;
    }
}
