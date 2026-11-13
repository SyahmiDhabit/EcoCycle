<?php

namespace App\Services;

use App\Mail\BusinessApplicationDecision;
use App\Mail\BusinessApplicationSubmitted;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BusinessMailNotifier
{
    public function submitted(User $business, bool $isUpdate = false): void
    {
        $adminEmail = config('services.ecocycle.admin_email') ?: 'ssyahmidhabit@gmail.com';
        $adminRecipients = array_values(array_filter(array_unique(array_merge(
            [$adminEmail],
            Administrator::pluck('email')->all()
        ))));

        if (! empty($adminRecipients)) {
            $this->send('registration', $business->id, implode(', ', $adminRecipients), fn () => Mail::to($adminRecipients)
                ->send(new BusinessApplicationSubmitted($business, $isUpdate)));
        }
    }

    public function decision(User $business, string $decisionType = 'changes_requested'): void
    {
        if (! in_array($decisionType, ['approved', 'rejected', 'changes_requested'], true)) {
            $decisionType = $business->account_status === 'approved' ? 'approved' : 'changes_requested';
        }

        $recipient = trim((string) $business->email);
        if (empty($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Log::warning('EcoCycle email notification skipped: Invalid recipient email', [
                'business_id' => $business->id,
                'email' => $business->email,
                'decision' => $decisionType,
            ]);

            return;
        }

        $this->send('decision', $business->id, $recipient, fn () => Mail::to($recipient)
            ->send(new BusinessApplicationDecision($business, $decisionType)));
    }

    private function send(string $type, int $businessId, string $recipient, callable $send): void
    {
        try {
            $send();
            Log::info('EcoCycle email notification successfully sent.', [
                'type' => $type,
                'business_id' => $businessId,
                'recipient' => $recipient,
            ]);
        } catch (Throwable $exception) {
            Log::error('EcoCycle email notification could not be sent: '.$exception->getMessage(), [
                'type' => $type,
                'business_id' => $businessId,
                'recipient' => $recipient,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
