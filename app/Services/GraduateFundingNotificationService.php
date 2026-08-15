<?php

namespace App\Services;

use App\Mail\GraduateFundingEligibleMail;
use App\Models\Certificate;
use App\Support\NotificationPreferences;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Learn → Fund: notify graduates they are eligible for a graduate loan.
 * Triggered after certificate generation when Finance graduate product is enabled.
 */
class GraduateFundingNotificationService
{
    public function __construct(private readonly SmsService $sms) {}

    /**
     * Send email and/or SMS if Finance graduate funding is enabled.
     * Idempotent per certificate (graduate_funding_notified_at).
     *
     * @return array{sent: bool, reason?: string, channels?: array<string>}
     */
    public function notifyIfEligible(Certificate $certificate): array
    {
        $certificate->loadMissing(['user', 'course']);

        $user = $certificate->user;
        $course = $certificate->course;

        if (! $user || ! $course) {
            return ['sent' => false, 'reason' => 'missing_user_or_course'];
        }

        if ($certificate->graduate_funding_notified_at) {
            return ['sent' => false, 'reason' => 'already_notified'];
        }

        if (! filled($certificate->file_path)) {
            return ['sent' => false, 'reason' => 'no_certificate_pdf'];
        }

        $finance = $this->resolveFinanceGraduateFlags();
        if (! $finance['enabled']) {
            return ['sent' => false, 'reason' => 'finance_flag_off'];
        }

        $locale = ($user->locale ?? 'en') === 'ha' ? 'ha' : 'en';
        $amount = $finance['eligible_loan_amount'];
        $registerUrl = rtrim(config('services.finance.url', 'https://lend.agrisiti.com'), '/') . '/apply';
        $amountFormatted = $this->formatNaira($amount);

        $channels = [];

        $emailAllowed = NotificationPreferences::allowsEmail(
            $user->notification_preferences,
            'graduate_funding_eligible'
        );

        if ($emailAllowed && $user->email) {
            Mail::to($user->email)->queue(new GraduateFundingEligibleMail(
                certificate: $certificate,
                eligibleLoanAmount: $amount,
                registerUrl: $registerUrl,
                language: $locale,
            ));
            $channels[] = 'email';
        }

        $smsAllowed = ! $user->sms_opted_out_at && filled($user->phone);
        if ($smsAllowed) {
            $message = $this->smsMessage($locale, $user->name, $course->title, $amountFormatted, $registerUrl);
            if ($this->sms->send($user->phone, $message)) {
                $channels[] = 'sms';
            }
        }

        if ($channels === []) {
            return ['sent' => false, 'reason' => 'no_channels'];
        }

        $certificate->forceFill(['graduate_funding_notified_at' => now()])->save();

        NotificationService::create(
            $user,
            'graduate_funding_eligible',
            $locale === 'ha' ? 'Ka Cancanci Rancen Digiri' : 'You Qualify for a Graduate Loan',
            $locale === 'ha'
                ? "Ka kammala '{$course->title}'. Ka iya neman tallafi har ₦{$amountFormatted}."
                : "You completed '{$course->title}'. You're eligible for graduate funding up to ₦{$amountFormatted}.",
            'certificate',
            $certificate->id,
            [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'eligible_loan_amount' => $amount,
                'register_url' => $registerUrl,
            ]
        );

        Log::info('Graduate funding eligibility notification sent', [
            'certificate_id' => $certificate->id,
            'user_id' => $user->id,
            'channels' => $channels,
            'amount' => $amount,
        ]);

        return ['sent' => true, 'channels' => $channels];
    }

    /**
     * Finance flag: graduate product enabled + eligible amount from public settings
     * (with Academy env fallback so notifications still work if Finance is briefly down).
     *
     * @return array{enabled: bool, eligible_loan_amount: float}
     */
    public function resolveFinanceGraduateFlags(): array
    {
        $fallbackEnabled = (bool) config('services.finance.graduate_funding_enabled', true);
        $fallbackAmount = (float) config('services.finance.graduate_eligible_loan_amount', 7_500_000);

        $apiBase = rtrim((string) config('services.finance.api_url', ''), '/');
        if ($apiBase === '') {
            return ['enabled' => $fallbackEnabled, 'eligible_loan_amount' => $fallbackAmount];
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get($apiBase.'/api/settings/public');

            if (! $response->successful()) {
                Log::warning('Finance public settings unavailable for graduate notify', [
                    'status' => $response->status(),
                ]);

                return ['enabled' => $fallbackEnabled, 'eligible_loan_amount' => $fallbackAmount];
            }

            $data = $response->json() ?? [];
            $enabled = array_key_exists('graduate_product_enabled', $data)
                ? (bool) $data['graduate_product_enabled']
                : $fallbackEnabled;

            $amount = isset($data['graduate_max_loan_amount'])
                ? (float) $data['graduate_max_loan_amount']
                : $fallbackAmount;

            return [
                'enabled' => $enabled && $fallbackEnabled,
                'eligible_loan_amount' => $amount > 0 ? $amount : $fallbackAmount,
            ];
        } catch (\Throwable $e) {
            Log::warning('Finance public settings fetch failed for graduate notify', [
                'error' => $e->getMessage(),
            ]);

            return ['enabled' => $fallbackEnabled, 'eligible_loan_amount' => $fallbackAmount];
        }
    }

    private function smsMessage(
        string $locale,
        string $name,
        string $courseTitle,
        string $amountFormatted,
        string $registerUrl,
    ): string {
        $first = trim(explode(' ', $name)[0] ?? $name);

        if ($locale === 'ha') {
            return "Sannu {$first}! Ka kammala '{$courseTitle}'. Ka cancanci rancen digiri har ₦{$amountFormatted}. Yi rijista: {$registerUrl}";
        }

        return "Hi {$first}! You completed '{$courseTitle}'. You're eligible for a graduate loan up to ₦{$amountFormatted}. Register: {$registerUrl}";
    }

    private function formatNaira(float $amount): string
    {
        return number_format($amount, 0, '.', ',');
    }
}
