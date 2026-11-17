<?php

namespace App\Services;

use App\Models\Invoice;

/**
 * Pure-PHP PDF 1.4 generator for simple EcoCycle Malaysian receipts.
 * Contains only the essential transaction details in a clean square-corner layout.
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
        $invoice->loadMissing(['seller', 'buyer', 'exchange.item']);

        $sellerName = $invoice->seller_snapshot['business_name']
            ?? $invoice->seller?->business_name
            ?? $invoice->seller?->name
            ?? 'Seller Business';

        $buyerName = $invoice->buyer_snapshot['business_name']
            ?? $invoice->buyer?->business_name
            ?? $invoice->buyer?->name
            ?? 'Buyer Business';

        $pickupAddress = trim((string) ($invoice->pickup_address ?? ''));
        if ($pickupAddress === '' && $invoice->exchange) {
            $pickupAddress = app(InvoiceService::class)->resolvePickupAddress($invoice->exchange);
        }
        if ($pickupAddress === '') {
            $pickupAddress = 'To be confirmed with seller';
        }

        $isFree = strtolower((string) $invoice->payment_status) === 'free' || (float) $invoice->total_amount <= 0;
        $statusLabel = $isFree ? 'Free' : ucfirst((string) $invoice->payment_status);
        $qtyFormatted = rtrim(rtrim(number_format((float) $invoice->quantity, 2, '.', ''), '0'), '.').' '.$invoice->unit;
        $dateFormatted = $invoice->issued_at ? $invoice->issued_at->format('d M Y') : date('d M Y');
        $totalFormatted = 'RM '.number_format((float) $invoice->total_amount, 2);

        $ops = [];
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN * 2);

        // 1. Clean EcoCycle Header Banner (square corners)
        $headerH = 72.0;
        $headerTopY = self::PAGE_HEIGHT - 44.0;
        $headerBottomY = $headerTopY - $headerH;
        $ops[] = $this->rect(self::MARGIN, $headerBottomY, $contentWidth, $headerH, [0.078, 0.220, 0.145], [0.078, 0.220, 0.145]);

        // Brand Mark & Title
        $ops[] = $this->rect(self::MARGIN + 18, $headerBottomY + 18, 36, 36, [0.133, 0.365, 0.239], [1.0, 1.0, 1.0], 1.0);
        $ops[] = $this->text(self::MARGIN + 27, $headerBottomY + 30, 'EC', 'F2', 14, [1.0, 1.0, 1.0]);
        $ops[] = $this->text(self::MARGIN + 66, $headerBottomY + 40, 'EcoCycle Receipt', 'F2', 18, [1.0, 1.0, 1.0]);
        $ops[] = $this->text(self::MARGIN + 66, $headerBottomY + 22, 'EcoCycle Malaysia Marketplace', 'F1', 9.5, [0.82, 0.90, 0.84]);

        // Status label on top right
        $ops[] = $this->textRight(self::PAGE_WIDTH - self::MARGIN - 18, $headerBottomY + 32, strtoupper($statusLabel), 'F2', 12, [1.0, 1.0, 1.0]);

        // 2. Clean Essential Details Table (Square-corner rows)
        $rows = [
            ['Receipt Number', (string) $invoice->invoice_number],
            ['Date', $dateFormatted],
            ['Item Name', (string) $invoice->item_name],
            ['Quantity', $qtyFormatted],
            ['Total Amount (RM)', $totalFormatted],
            ['Buyer Business Name', (string) $buyerName],
            ['Seller Business Name', (string) $sellerName],
            ['Transaction Status', $statusLabel],
        ];

        $labelColW = 185.0;
        $valColW = $contentWidth - $labelColW;
        $currentTopY = $headerBottomY - 22.0;

        foreach ($rows as $idx => [$label, $value]) {
            $rowH = 34.0;
            $rowBottomY = $currentTopY - $rowH;
            $isTotalRow = ($label === 'Total Amount (RM)');

            $bg = $isTotalRow ? [0.929, 0.969, 0.933] : ($idx % 2 === 0 ? [1.0, 1.0, 1.0] : [0.980, 0.988, 0.980]);
            $borderColor = [0.796, 0.835, 0.882];

            $ops[] = $this->rect(self::MARGIN, $rowBottomY, $labelColW, $rowH, $bg, $borderColor, 0.8);
            $ops[] = $this->rect(self::MARGIN + $labelColW, $rowBottomY, $valColW, $rowH, $bg, $borderColor, 0.8);

            $textY = $rowBottomY + 12.5;
            $ops[] = $this->text(self::MARGIN + 14, $textY, $label, 'F2', 9.5, [0.278, 0.333, 0.412]);

            $valColor = $isTotalRow
                ? [0.078, 0.220, 0.145]
                : ($label === 'Transaction Status' ? [0.082, 0.502, 0.239] : [0.059, 0.090, 0.165]);
            $valFont = ($isTotalRow || $label === 'Receipt Number' || $label === 'Transaction Status') ? 'F2' : 'F1';
            $valSize = $isTotalRow ? 11.5 : 10.0;

            $ops[] = $this->text(self::MARGIN + $labelColW + 14, $textY, $this->truncate($value, 48), $valFont, $valSize, $valColor);

            $currentTopY = $rowBottomY;
        }

        // 3. Collection / Pickup Address Row (multi-line support)
        $addressLines = array_slice($this->wrapText($pickupAddress, 46), 0, 4);
        if (empty($addressLines)) {
            $addressLines = ['To be confirmed with seller'];
        }
        $addrRowH = max(44.0, 22.0 + (count($addressLines) * 14.0));
        $addrBottomY = $currentTopY - $addrRowH;

        $ops[] = $this->rect(self::MARGIN, $addrBottomY, $labelColW, $addrRowH, [0.980, 0.988, 0.980], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->rect(self::MARGIN + $labelColW, $addrBottomY, $valColW, $addrRowH, [1.0, 1.0, 1.0], [0.796, 0.835, 0.882], 0.8);

        $ops[] = $this->text(self::MARGIN + 14, $currentTopY - 22.0, 'Collection / Pickup Address', 'F2', 9.5, [0.278, 0.333, 0.412]);

        $addrY = $currentTopY - 20.0;
        foreach ($addressLines as $line) {
            $ops[] = $this->text(self::MARGIN + $labelColW + 14, $addrY, $line, 'F1', 9.8, [0.059, 0.090, 0.165]);
            $addrY -= 14.0;
        }

        // 4. Clean Minimal Footer
        $footerY = $addrBottomY - 32.0;
        $ops[] = $this->line(self::MARGIN, $footerY + 14, self::PAGE_WIDTH - self::MARGIN, $footerY + 14, [0.796, 0.835, 0.882], 0.6);
        $ops[] = $this->text(self::MARGIN, $footerY, 'EcoCycle Malaysia - Electronic Transaction Receipt (MYR)', 'F1', 8.5, [0.45, 0.51, 0.59]);

        return $this->assemblePdf(implode("\n", $ops));
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
