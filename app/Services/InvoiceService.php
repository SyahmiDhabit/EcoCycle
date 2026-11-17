<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MaterialExchange;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService
{
    /**
     * Calculate server-side total in MYR and sen (unit_price * quantity).
     *
     * @return array{subtotal: float, total: float, amount_sen: int}
     */
    public function calculateTotals(float $quantity, float $unitPrice): array
    {
        $total = round(max(0, $quantity) * max(0, $unitPrice), 2);

        return [
            'subtotal' => $total,
            'total' => $total,
            'amount_sen' => (int) round($total * 100),
        ];
    }

    /**
     * Resolve the actual collection / pickup address for an order.
     */
    public function resolvePickupAddress(MaterialExchange $exchange): string
    {
        $exchange->loadMissing(['item', 'provider']);
        $item = $exchange->item;
        $seller = $exchange->provider;

        if (! empty($item?->pickup_address) && trim((string) $item->pickup_address) !== '') {
            return trim((string) $item->pickup_address);
        }

        if ($seller) {
            $sellerParts = array_values(array_filter([
                trim((string) ($seller->registered_address ?? '')),
                trim(implode(' ', array_filter([
                    trim((string) ($seller->postcode ?? '')),
                    trim((string) ($seller->city ?? '')),
                ]))),
                trim((string) ($seller->state ?? '')),
            ], fn (string $v): bool => $v !== ''));

            if (! empty($sellerParts)) {
                return implode(', ', $sellerParts);
            }
        }

        $locationParts = array_values(array_filter([
            trim((string) ($item?->location_city ?? '')),
            trim((string) ($item?->location_state ?? '')),
        ], fn (string $v): bool => $v !== ''));

        if (! empty($locationParts)) {
            return implode(', ', $locationParts);
        }

        return 'Collection address to be confirmed with seller';
    }

    /**
     * Build a minimal business snapshot containing only the real business name.
     *
     * @return array<string, string|null>
     */
    public function buildBusinessSnapshot(?User $business): array
    {
        $name = trim((string) ($business?->business_name ?: ($business?->name ?? 'EcoCycle Business')));

        return [
            'business_name' => $name !== '' ? $name : 'EcoCycle Business',
        ];
    }

    /**
     * Generate the next unique sequential receipt number in format ECO-RCP-YYYY-00001.
     */
    public function generateNextReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = "ECO-RCP-{$year}-";

        $latest = Invoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSeq = 1;
        if (is_string($latest) && str_starts_with($latest, $prefix)) {
            $numericPart = (int) substr($latest, strlen($prefix));
            $nextSeq = $numericPart + 1;
        }

        return $prefix.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Idempotently generate or retrieve the simple EcoCycle Receipt for a Paid Sell or Free order.
     */
    public function generateForOrder(MaterialExchange $exchange, ?Payment $payment = null): Invoice
    {
        return DB::transaction(function () use ($exchange, $payment): Invoice {
            $existing = Invoice::query()
                ->where('material_exchange_id', $exchange->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $exchange->loadMissing(['item', 'provider', 'requester']);

            $listingType = $exchange->resolvedListingType();
            $isFree = $listingType === 'free' || $exchange->payment_status === 'free';

            if (! $isFree && $exchange->payment_status !== 'paid') {
                throw new RuntimeException('A paid receipt can only be generated after verified payment.');
            }

            $item = $exchange->item;
            $qty = (float) $exchange->quantity;
            $unitPrice = $isFree
                ? 0.00
                : ((float) $exchange->unit_price > 0
                    ? (float) $exchange->unit_price
                    : ($qty > 0 ? round((float) $exchange->total_price / $qty, 2) : 0.00));

            $totals = $this->calculateTotals($qty, $unitPrice);
            $finalTotal = $isFree
                ? 0.00
                : ($payment ? (float) $payment->amount : $totals['total']);

            $receiptNumber = $this->generateNextReceiptNumber();
            $orderRef = $exchange->resolvedOrderReference();
            $pickupAddress = $this->resolvePickupAddress($exchange);

            $invoice = Invoice::create([
                'invoice_number' => $receiptNumber,
                'material_exchange_id' => $exchange->id,
                'payment_id' => $payment?->id,
                'seller_business_id' => $exchange->provider_business_id,
                'buyer_business_id' => $exchange->requester_business_id,
                'order_reference' => $orderRef,
                'currency' => 'myr',
                'item_name' => $item?->name ?: ($item?->title ?: "Material #{$exchange->item_id}"),
                'item_description' => null,
                'quantity' => $qty,
                'unit' => $exchange->unit ?: ($item?->unit ?: 'units'),
                'unit_price' => $unitPrice,
                'subtotal' => $finalTotal,
                'tax_label' => null,
                'tax_rate' => null,
                'tax_amount' => 0.00,
                'total_amount' => $finalTotal,
                'payment_status' => $isFree ? 'free' : 'paid',
                'payment_method' => $isFree ? 'free' : ($payment?->payment_method ?: 'stripe'),
                'payment_reference' => $isFree ? 'FREE' : ($payment?->referenceDisplay() ?: ($exchange->payment_reference ?: 'STRIPE')),
                'pickup_address' => $pickupAddress,
                'issued_at' => now(),
                'paid_at' => $isFree ? now() : ($payment?->paid_at ?: ($exchange->paid_at ?: now())),
                'seller_snapshot' => $this->buildBusinessSnapshot($exchange->provider),
                'buyer_snapshot' => $this->buildBusinessSnapshot($exchange->requester),
            ]);

            if ($exchange->invoice_number !== $receiptNumber) {
                $exchange->invoice_number = $receiptNumber;
                $exchange->save();
            }

            return $invoice;
        });
    }
}
