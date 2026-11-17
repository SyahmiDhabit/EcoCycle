<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MaterialExchange;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Calculate server-side subtotal, optional configured tax, and total in MYR and sen.
     *
     * @return array{subtotal: float, tax_label: string|null, tax_rate: float|null, tax_amount: float, total: float, amount_sen: int}
     */
    public function calculateTotals(float $quantity, float $unitPrice): array
    {
        $subtotal = round(max(0, $quantity) * max(0, $unitPrice), 2);

        $rawTaxRate = config('services.stripe.tax_rate');
        $rawTaxLabel = trim((string) (config('services.stripe.tax_label') ?? ''));

        $taxRate = null;
        $taxLabel = null;
        $taxAmount = 0.00;

        if ($rawTaxRate !== null && $rawTaxRate !== '' && is_numeric($rawTaxRate) && (float) $rawTaxRate > 0 && $rawTaxLabel !== '') {
            $taxRate = round((float) $rawTaxRate, 2);
            $taxLabel = $rawTaxLabel;
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
        }

        $total = round($subtotal + $taxAmount, 2);

        return [
            'subtotal' => $subtotal,
            'tax_label' => $taxLabel,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'amount_sen' => (int) round($total * 100),
        ];
    }

    /**
     * Build a clean snapshot of a business containing only real, non-empty information.
     *
     * @return array<string, string|null>
     */
    public function buildBusinessSnapshot(?User $business): array
    {
        if (! $business) {
            return [
                'business_name' => 'EcoCycle Partner Business',
                'address' => null,
                'registration_number' => null,
                'phone' => null,
                'email' => null,
                'contact_name' => null,
            ];
        }

        $addressParts = array_values(array_filter([
            trim((string) ($business->registered_address ?? '')),
            trim(implode(' ', array_filter([
                trim((string) ($business->postcode ?? '')),
                trim((string) ($business->city ?? '')),
            ]))),
            trim((string) ($business->state ?? '')),
        ], fn (string $v): bool => $v !== ''));

        $regNo = trim((string) ($business->business_registration_number ?? ''));
        $phone = trim((string) ($business->business_phone ?? ''));
        $email = trim((string) ($business->email ?? ''));
        $contactName = trim((string) ($business->name ?? ''));
        $businessName = trim((string) ($business->business_name ?: $contactName ?: 'EcoCycle Partner Business'));

        return [
            'business_name' => $businessName,
            'address' => ! empty($addressParts) ? implode(', ', $addressParts) : null,
            'registration_number' => $regNo !== '' ? $regNo : null,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'contact_name' => ($contactName !== '' && $contactName !== $businessName) ? $contactName : null,
        ];
    }

    /**
     * Generate the next unique sequential invoice number in format ECO-INV-YYYY-00001.
     */
    public function generateNextInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "ECO-INV-{$year}-";

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
     * Idempotently generate or retrieve the official Invoice for a paid MaterialExchange order.
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

            $item = $exchange->item;
            $qty = (float) $exchange->quantity;
            $unitPrice = (float) $exchange->unit_price > 0
                ? (float) $exchange->unit_price
                : ($qty > 0 ? round((float) $exchange->total_price / $qty, 2) : 0.00);

            $totals = $this->calculateTotals($qty, $unitPrice);
            $paidTotal = $payment ? (float) $payment->amount : ((float) $exchange->total_price > 0 ? (float) $exchange->total_price : $totals['total']);

            $invoiceNumber = $this->generateNextInvoiceNumber();
            $orderRef = $exchange->resolvedOrderReference();

            $paymentMethod = $payment?->payment_method ?: ($exchange->payment_reference && str_starts_with($exchange->payment_reference, 'FPX-') ? 'fpx' : 'card');
            $paymentRef = $payment?->referenceDisplay() ?: ($exchange->payment_reference ?: 'N/A');

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'material_exchange_id' => $exchange->id,
                'payment_id' => $payment?->id,
                'seller_business_id' => $exchange->provider_business_id,
                'buyer_business_id' => $exchange->requester_business_id,
                'order_reference' => $orderRef,
                'currency' => strtolower((string) ($payment?->currency ?: config('services.stripe.currency', 'myr'))),
                'item_name' => $item?->name ?: ($item?->title ?: "Material Batch #{$exchange->item_id}"),
                'item_description' => $item?->description ?: null,
                'quantity' => $qty,
                'unit' => $exchange->unit ?: ($item?->unit ?: 'units'),
                'unit_price' => $unitPrice,
                'subtotal' => $totals['subtotal'],
                'tax_label' => $totals['tax_label'],
                'tax_rate' => $totals['tax_rate'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $paidTotal,
                'payment_status' => 'paid',
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentRef,
                'issued_at' => now(),
                'paid_at' => $payment?->paid_at ?: ($exchange->paid_at ?: now()),
                'seller_snapshot' => $this->buildBusinessSnapshot($exchange->provider),
                'buyer_snapshot' => $this->buildBusinessSnapshot($exchange->requester),
            ]);

            if ($exchange->invoice_number !== $invoiceNumber) {
                $exchange->invoice_number = $invoiceNumber;
                $exchange->save();
            }

            return $invoice;
        });
    }
}
