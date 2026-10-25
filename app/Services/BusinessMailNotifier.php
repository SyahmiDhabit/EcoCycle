<?php

namespace App\Services;

use App\Mail\BusinessApplicationDecision;
use App\Mail\BusinessApplicationSubmitted;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BusinessMailNotifier
{
    public function submitted(User $business): void
    {
        $this->send('registration', $business->id, fn () => Mail::to(config('services.ecocycle.admin_email'))
            ->send(new BusinessApplicationSubmitted($business)));
    }

    public function decision(User $business): void
    {
        $this->send('decision', $business->id, fn () => Mail::to($business->email)
            ->send(new BusinessApplicationDecision($business, $business->account_status)));
    }

    private function send(string $type, int $businessId, callable $send): void
    {
        if (config('mail.default') === 'log') {
            Log::info('EcoCycle email notification is not sent because MAIL_MAILER is set to log.', [
                'notification' => $type,
                'business_id' => $businessId,
            ]);

            return;
        }

        try {
            $send();
        } catch (Throwable $exception) {
            Log::error('EcoCycle email notification could not be sent.', [
                'notification' => $type,
                'business_id' => $businessId,
                'exception' => $exception::class,
            ]);
        }
    }
}
