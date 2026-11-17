<?php

namespace App\Http\Controllers;

use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripePaymentService $stripeService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! $this->stripeService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Rejected Stripe webhook with invalid signature');

            return response()->json([
                'error' => 'Invalid Stripe webhook signature.',
            ], 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event) || empty($event['type'])) {
            return response()->json([
                'error' => 'Malformed webhook payload.',
            ], 400);
        }

        $result = $this->stripeService->processWebhookEvent($event);

        return response()->json([
            'received' => true,
            'status' => $result['status'],
        ], 200);
    }
}
