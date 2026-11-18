<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MaterialExchange;
use App\Models\Payment;
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
     * Resolve the seller's registered business address for display on Checkout and Receipts.
     */
    public function resolveSellerAddress(MaterialExchange $exchange): string
    {
        $exchange->loadMissing(['item', 'provider']);
        $seller = $exchange->provider;

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

        return $this->resolvePickupAddress($exchange);
    }

    /**
     * Resolve the actual collection / pickup address for an order.
     */
    public function resolvePickupAddress(MaterialExchange $exchange): string
    {
        $exchange->loadMissing(['item', 'provider']);
        $item = $exchange->item;
        $seller = $exchange->provider;

        if (! empty($exchange->collection_address) && trim((string) $exchange->collection_address) !== '') {
            return trim((string) $exchange->collection_address);
        }

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
     * Generate the next unique sequential receipt number in format ECO-YYYY-00001.
     */
    public function generateNextReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = "ECO-{$year}-";

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
     * Idempotently generate or retrieve the single EcoCycle Receipt for a Paid Sell or confirmed Free order.
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

            $exchange->loadMissing(['item', 'provider', 'requester', 'payment']);

            $listingType = $exchange->resolvedListingType();
            $isFree = in_array($listingType, ['free', 'lend'], true) || $exchange->payment_status === 'free';

            if (! $isFree && $exchange->payment_status !== 'paid') {
                throw new RuntimeException('A paid receipt can only be generated after verified payment.');
            }

            $resolvedPayment = $payment ?: $exchange->payment;
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
                : ($resolvedPayment ? (float) $resolvedPayment->amount : $totals['total']);

            $receiptNumber = $this->generateNextReceiptNumber();
            $pickupAddress = $this->resolvePickupAddress($exchange);
            $sellerAddress = $this->resolveSellerAddress($exchange);

            $sellerName = trim((string) ($exchange->seller_business_name ?: ($exchange->provider?->business_name ?: ($exchange->provider?->name ?? 'Seller Business'))));
            $buyerName = trim((string) ($exchange->buyer_business_name ?: ($exchange->requester?->business_name ?: ($exchange->requester?->name ?? 'Buyer Business'))));

            $sellerContact = trim(implode(' | ', array_filter([
                trim((string) ($exchange->provider?->business_phone ?? '')),
                trim((string) ($exchange->provider?->email ?? '')),
            ])));
            $buyerContact = trim(implode(' | ', array_filter([
                trim((string) ($exchange->requester?->business_phone ?? '')),
                trim((string) ($exchange->requester?->email ?? '')),
            ])));

            $paymentReference = $isFree
                ? ($listingType === 'lend' ? 'LEND (RM 0.00)' : 'FREE (RM 0.00)')
                : ($resolvedPayment?->referenceDisplay() ?: ($exchange->payment_reference ?: $exchange->resolvedOrderReference()));

            $invoice = Invoice::create([
                'invoice_number' => $receiptNumber,
                'material_exchange_id' => $exchange->id,
                'payment_id' => $resolvedPayment?->id,
                'seller_business_id' => $exchange->provider_business_id,
                'buyer_business_id' => $exchange->requester_business_id,
                'buyer_business_name' => $buyerName !== '' ? $buyerName : 'Buyer Business',
                'seller_business_name' => $sellerName !== '' ? $sellerName : 'Seller Business',
                'seller_address' => $sellerAddress !== '' ? $sellerAddress : null,
                'seller_contact' => $sellerContact !== '' ? $sellerContact : null,
                'buyer_contact' => $buyerContact !== '' ? $buyerContact : null,
                'item_name' => $exchange->item_name ?: ($item?->name ?: ($item?->title ?: "Material #{$exchange->item_id}")),
                'quantity' => $qty,
                'unit' => $exchange->unit ?: ($item?->unit ?: 'pcs'),
                'unit_price' => $unitPrice,
                'total_amount' => $finalTotal,
                'currency' => 'myr',
                'pickup_address' => $pickupAddress,
                'payment_reference' => $paymentReference,
                'payment_status' => $isFree ? 'free' : 'paid',
                'issued_at' => now(),
            ]);

            if ($exchange->invoice_number !== $receiptNumber) {
                $exchange->invoice_number = $receiptNumber;
                $exchange->save();
            }

            return $invoice;
        });
    }
}
