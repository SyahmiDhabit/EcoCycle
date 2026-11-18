<?php

namespace App\Services;

use App\Models\Invoice;

/**
 * Pure-PHP PDF 1.4 generator for clean Malaysian EcoCycle Receipts.
 * Contains only the essential transaction details in a crisp square-corner layout.
 */
class InvoicePdfGenerator
{
    private const PAGE_WIDTH = 595.28;

    private const PAGE_HEIGHT = 841.89;

    private const MARGIN = 48.0;

    /**
     * Generate binary PDF content for the EcoCycle Receipt.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['seller', 'buyer', 'exchange', 'payment']);

        $buyerName = trim((string) ($invoice->buyer_business_name ?: ($invoice->buyer?->business_name ?: ($invoice->buyer?->name ?? 'Buyer Business'))));
        $sellerName = trim((string) ($invoice->seller_business_name ?: ($invoice->seller?->business_name ?: ($invoice->seller?->name ?? 'Seller Business'))));

        $sellerAddress = trim((string) ($invoice->seller_address ?? ''));
        if ($sellerAddress === '' && $invoice->exchange) {
            $sellerAddress = app(InvoiceService::class)->resolveSellerAddress($invoice->exchange);
        }
        $sellerContact = trim((string) ($invoice->seller_contact ?? ''));
        if ($sellerContact === '' && $invoice->seller) {
            $sellerContact = trim(implode(' | ', array_filter([
                trim((string) ($invoice->seller->business_phone ?? '')),
                trim((string) ($invoice->seller->email ?? '')),
            ])));
        }

        $buyerContact = trim((string) ($invoice->buyer_contact ?? ''));
        if ($buyerContact === '' && $invoice->buyer) {
            $buyerContact = trim(implode(' | ', array_filter([
                trim((string) ($invoice->buyer->business_phone ?? '')),
                trim((string) ($invoice->buyer->email ?? '')),
            ])));
        }

        $pickupAddress = trim((string) ($invoice->pickup_address ?? ''));
        if ($pickupAddress === '' && $invoice->exchange) {
            $pickupAddress = app(InvoiceService::class)->resolvePickupAddress($invoice->exchange);
        }
        if ($pickupAddress === '') {
            $pickupAddress = 'To be confirmed with seller';
        }

        $statusLabel = $invoice->statusLabel();
        $qtyFormatted = $invoice->formattedQuantity();
        $dateFormatted = $invoice->issued_at ? $invoice->issued_at->format('d M Y, h:i A') : date('d M Y');
        $unitPriceFormatted = $invoice->formattedUnitPrice();
        $totalFormatted = $invoice->formattedTotal();
        $paymentRef = trim((string) ($invoice->payment_reference ?: ($invoice->payment?->referenceDisplay() ?: ($invoice->exchange?->payment_reference ?: 'N/A'))));

        $ops = [];
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN * 2);

        // 1. EcoCycle Header Banner with EcoCycle Name & Vector Leaf Logo
        $headerH = 68.0;
        $headerTopY = self::PAGE_HEIGHT - 40.0;
        $headerBottomY = $headerTopY - $headerH;
        $ops[] = $this->rect(self::MARGIN, $headerBottomY, $contentWidth, $headerH, [0.078, 0.220, 0.145], [0.078, 0.220, 0.145]);

        // Vector EcoCycle Leaf Logo Box
        $logoX = self::MARGIN + 16.0;
        $logoY = $headerBottomY + 16.0;
        $ops[] = $this->rect($logoX, $logoY, 36.0, 36.0, [1.0, 1.0, 1.0], [0.80, 0.88, 0.82], 0.8);
        $ops[] = $this->drawEcoCycleLeaf($logoX + 8.0, $logoY + 7.0);

        $ops[] = $this->text(self::MARGIN + 64.0, $headerBottomY + 38.0, 'EcoCycle Receipt', 'F2', 17.0, [1.0, 1.0, 1.0]);
        $ops[] = $this->text(self::MARGIN + 64.0, $headerBottomY + 21.0, 'EcoCycle Malaysia Marketplace Transaction Receipt', 'F1', 9.0, [0.82, 0.90, 0.84]);

        // Status label on top right
        $ops[] = $this->textRight(self::PAGE_WIDTH - self::MARGIN - 16.0, $headerBottomY + 30.0, strtoupper($statusLabel), 'F2', 11.5, [1.0, 1.0, 1.0]);

        // 2. Seller & Buyer Information Side-by-Side Boxes
        $infoTopY = $headerBottomY - 16.0;
        $halfW = ($contentWidth - 12.0) / 2.0;
        $sellerAddrLines = array_slice($this->wrapText($sellerAddress !== '' ? $sellerAddress : $pickupAddress, 36), 0, 3);
        $infoBoxH = max(84.0, 56.0 + (count($sellerAddrLines) * 12.0));
        $infoBottomY = $infoTopY - $infoBoxH;

        // Seller Box
        $ops[] = $this->rect(self::MARGIN, $infoBottomY, $halfW, $infoBoxH, [0.980, 0.988, 0.980], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->text(self::MARGIN + 12.0, $infoTopY - 16.0, 'SELLER INFORMATION', 'F2', 8.5, [0.078, 0.220, 0.145]);
        $ops[] = $this->text(self::MARGIN + 12.0, $infoTopY - 31.0, $this->truncate($sellerName, 36), 'F2', 10.0, [0.059, 0.090, 0.165]);
        $sY = $infoTopY - 45.0;
        foreach ($sellerAddrLines as $line) {
            $ops[] = $this->text(self::MARGIN + 12.0, $sY, $line, 'F1', 8.5, [0.278, 0.333, 0.412]);
            $sY -= 12.0;
        }
        if ($sellerContact !== '') {
            $ops[] = $this->text(self::MARGIN + 12.0, max($infoBottomY + 9.0, $sY - 2.0), $this->truncate($sellerContact, 40), 'F1', 8.2, [0.278, 0.333, 0.412]);
        }

        // Buyer Box
        $buyerBoxX = self::MARGIN + $halfW + 12.0;
        $ops[] = $this->rect($buyerBoxX, $infoBottomY, $halfW, $infoBoxH, [1.0, 1.0, 1.0], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->text($buyerBoxX + 12.0, $infoTopY - 16.0, 'BUYER INFORMATION', 'F2', 8.5, [0.078, 0.220, 0.145]);
        $ops[] = $this->text($buyerBoxX + 12.0, $infoTopY - 31.0, $this->truncate($buyerName, 36), 'F2', 10.0, [0.059, 0.090, 0.165]);
        if ($buyerContact !== '') {
            $ops[] = $this->text($buyerBoxX + 12.0, $infoTopY - 46.0, $this->truncate($buyerContact, 40), 'F1', 8.5, [0.278, 0.333, 0.412]);
        }

        // 3. Transaction Details Table
        $rows = [
            ['Receipt ID', (string) $invoice->invoice_number],
            ['Payment Reference', $paymentRef],
            ['Purchase Date', $dateFormatted],
            ['Item Name', (string) $invoice->item_name],
            ['Quantity and Unit', $qtyFormatted],
            ['Price per Unit (RM)', $unitPriceFormatted],
            ['Total Amount (RM)', $totalFormatted],
            ['Payment Status', $statusLabel],
        ];

        $labelColW = 185.0;
        $valColW = $contentWidth - $labelColW;
        $currentTopY = $infoBottomY - 16.0;

        foreach ($rows as $idx => [$label, $value]) {
            $rowH = 30.0;
            $rowBottomY = $currentTopY - $rowH;
            $isTotalRow = ($label === 'Total Amount (RM)');

            $bg = $isTotalRow ? [0.929, 0.969, 0.933] : ($idx % 2 === 0 ? [1.0, 1.0, 1.0] : [0.980, 0.988, 0.980]);
            $borderColor = [0.796, 0.835, 0.882];

            $ops[] = $this->rect(self::MARGIN, $rowBottomY, $labelColW, $rowH, $bg, $borderColor, 0.8);
            $ops[] = $this->rect(self::MARGIN + $labelColW, $rowBottomY, $valColW, $rowH, $bg, $borderColor, 0.8);

            $textY = $rowBottomY + 11.0;
            $ops[] = $this->text(self::MARGIN + 14.0, $textY, $label, 'F2', 9.2, [0.278, 0.333, 0.412]);

            $valColor = $isTotalRow
                ? [0.078, 0.220, 0.145]
                : ($label === 'Payment Status' ? [0.082, 0.502, 0.239] : [0.059, 0.090, 0.165]);
            $valFont = ($isTotalRow || $label === 'Receipt ID' || $label === 'Payment Status') ? 'F2' : 'F1';
            $valSize = $isTotalRow ? 11.0 : 9.6;

            $ops[] = $this->text(self::MARGIN + $labelColW + 14.0, $textY, $this->truncate($value, 48), $valFont, $valSize, $valColor);

            $currentTopY = $rowBottomY;
        }

        // 4. Collection / Pickup Address Row (multi-line support)
        $addressLines = array_slice($this->wrapText($pickupAddress, 46), 0, 4);
        if (empty($addressLines)) {
            $addressLines = ['To be confirmed with seller'];
        }
        $addrRowH = max(42.0, 20.0 + (count($addressLines) * 13.5));
        $addrBottomY = $currentTopY - $addrRowH;

        $ops[] = $this->rect(self::MARGIN, $addrBottomY, $labelColW, $addrRowH, [0.980, 0.988, 0.980], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->rect(self::MARGIN + $labelColW, $addrBottomY, $valColW, $addrRowH, [1.0, 1.0, 1.0], [0.796, 0.835, 0.882], 0.8);

        $ops[] = $this->text(self::MARGIN + 14.0, $currentTopY - 21.0, 'Collection / Pickup Address', 'F2', 9.2, [0.278, 0.333, 0.412]);

        $addrY = $currentTopY - 19.0;
        foreach ($addressLines as $line) {
            $ops[] = $this->text(self::MARGIN + $labelColW + 14.0, $addrY, $line, 'F1', 9.4, [0.059, 0.090, 0.165]);
            $addrY -= 13.5;
        }

        // 5. Clean Minimal Footer
        $footerY = $addrBottomY - 28.0;
        $ops[] = $this->line(self::MARGIN, $footerY + 14.0, self::PAGE_WIDTH - self::MARGIN, $footerY + 14.0, [0.796, 0.835, 0.882], 0.6);
        $ops[] = $this->text(self::MARGIN, $footerY, 'EcoCycle Malaysia - Electronic Transaction Receipt (MYR)', 'F1', 8.5, [0.45, 0.51, 0.59]);

        return $this->assemblePdf(implode("\n", $ops));
    }

    /**
     * Draw a simple vector leaf emblem representing EcoCycle inside the header logo mark.
     */
    private function drawEcoCycleLeaf(float $ox, float $oy): string
    {
        return sprintf(
            '0.094 0.345 0.200 rg 0.078 0.220 0.145 RG 1.1 w %.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c B 1 1 1 RG 1.0 w %.2f %.2f m %.2f %.2f l S',
            $ox + 2.0,
            $oy + 3.0,
            $ox + 2.0,
            $oy + 16.0,
            $ox + 10.0,
            $oy + 22.0,
            $ox + 19.0,
            $oy + 21.0,
            $ox + 19.0,
            $oy + 10.0,
            $ox + 12.0,
            $oy + 3.0,
            $ox + 2.0,
            $oy + 3.0,
            $ox + 3.5,
            $oy + 4.5,
            $ox + 14.5,
            $oy + 16.5
        );
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, int $maxChars): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $this->sanitizeText($text)) ?? '');
        if ($clean === '') {
            return [];
        }

        return explode("\n", wordwrap($clean, $maxChars, "\n", true));
    }

    private function truncate(string $text, int $maxChars): string
    {
        $clean = trim($this->sanitizeText($text));
        if (strlen($clean) <= $maxChars) {
            return $clean;
        }

        return substr($clean, 0, max(1, $maxChars - 3)).'...';
    }

    private function sanitizeText(string $text): string
    {
        $map = [
            '—' => '-',
            '–' => '-',
            '‘' => "'",
            '’' => "'",
            '“' => '"',
            '”' => '"',
            '•' => '-',
            '·' => '-',
            '→' => '->',
            '×' => 'x',
            '²' => '2',
            '³' => '3',
        ];
        $text = strtr($text, $map);

        return preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
    }

    private function escapePdfString(string $text): string
    {
        $safe = $this->sanitizeText($text);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safe);
    }

    /**
     * @param  array<int, float>  $fillRgb
     * @param  array<int, float>  $strokeRgb
     */
    private function rect(float $x, float $y, float $w, float $h, array $fillRgb, array $strokeRgb, float $lineWidth = 0.5): string
    {
        return sprintf(
            '%.3f %.3f %.3f rg %.3f %.3f %.3f RG %.2f w %.2f %.2f %.2f %.2f re B',
            $fillRgb[0],
            $fillRgb[1],
            $fillRgb[2],
            $strokeRgb[0],
            $strokeRgb[1],
            $strokeRgb[2],
            $lineWidth,
            $x,
            $y,
            $w,
            $h
        );
    }

    /**
     * @param  array<int, float>  $strokeRgb
     */
    private function line(float $x1, float $y1, float $x2, float $y2, array $strokeRgb, float $lineWidth = 0.6): string
    {
        return sprintf(
            '%.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S',
            $strokeRgb[0],
            $strokeRgb[1],
            $strokeRgb[2],
            $lineWidth,
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    /**
     * @param  array<int, float>  $rgb
     */
    private function text(float $x, float $y, string $text, string $font, float $size, array $rgb): string
    {
        $escaped = $this->escapePdfString($text);

        return sprintf(
            'BT /%s %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET',
            $font,
            $size,
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $x,
            $y,
            $escaped
        );
    }

    /**
     * @param  array<int, float>  $rgb
     */
    private function textRight(float $rightX, float $y, string $text, string $font, float $size, array $rgb): string
    {
        $clean = $this->sanitizeText($text);
        $avgCharFactor = $font === 'F2' ? 0.55 : 0.51;
        $estimatedWidth = strlen($clean) * $size * $avgCharFactor;
        $x = max(self::MARGIN, $rightX - $estimatedWidth);

        return $this->text($x, $y, $clean, $font, $size, $rgb);
    }

    private function assemblePdf(string $streamContent): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT
            ),
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            6 => sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($streamContent), $streamContent),
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";

        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }
}
