<?php

namespace App\Services;

use App\Models\Invoice;

/**
 * Pure-PHP standards-compliant PDF 1.4 generator for EcoCycle Malaysian business invoices.
 * Renders a crisp, square-corner A4 rectangular layout with zero external dependencies.
 */
class InvoicePdfGenerator
{
    private const PAGE_WIDTH = 595.28;

    private const PAGE_HEIGHT = 841.89;

    private const MARGIN = 40.0;

    /**
     * Generate binary PDF content for the given invoice.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['seller', 'buyer', 'payment', 'exchange']);

        $seller = is_array($invoice->seller_snapshot) && ! empty($invoice->seller_snapshot)
            ? $invoice->seller_snapshot
            : app(InvoiceService::class)->buildBusinessSnapshot($invoice->seller);

        $buyer = is_array($invoice->buyer_snapshot) && ! empty($invoice->buyer_snapshot)
            ? $invoice->buyer_snapshot
            : app(InvoiceService::class)->buildBusinessSnapshot($invoice->buyer);

        $ops = [];
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN * 2);

        // 1. Top Dark Green Rectangular Header Banner (square corners)
        $headerHeight = 76.0;
        $headerTopY = self::PAGE_HEIGHT - 32.0;
        $headerBottomY = $headerTopY - $headerHeight;
        $ops[] = $this->rect(self::MARGIN, $headerBottomY, $contentWidth, $headerHeight, [0.078, 0.220, 0.145], [0.078, 0.220, 0.145]);

        // EcoCycle Brand Mark (square frame + minimal geometric leaf)
        $ops[] = $this->rect(self::MARGIN + 18, $headerBottomY + 20, 36, 36, [0.133, 0.365, 0.239], [1.0, 1.0, 1.0], 1.0);
        $ops[] = $this->text(self::MARGIN + 27, $headerBottomY + 32, 'EC', 'F2', 14, [1.0, 1.0, 1.0]);

        // EcoCycle Title & Subtitle
        $ops[] = $this->text(self::MARGIN + 66, $headerBottomY + 42, 'EcoCycle', 'F2', 20, [1.0, 1.0, 1.0]);
        $ops[] = $this->text(self::MARGIN + 66, $headerBottomY + 24, 'Circular Economy Material Exchange - Malaysia', 'F1', 9, [0.82, 0.90, 0.84]);

        // Right-aligned INVOICE title
        $ops[] = $this->textRight(self::PAGE_WIDTH - self::MARGIN - 18, $headerBottomY + 42, 'INVOICE', 'F2', 20, [1.0, 1.0, 1.0]);
        $ops[] = $this->textRight(self::PAGE_WIDTH - self::MARGIN - 18, $headerBottomY + 24, 'Official Tax / Payment Receipt', 'F1', 8.5, [0.82, 0.90, 0.84]);

        // 2. Invoice Metadata Rectangular Strip (4 columns)
        $metaTopY = $headerBottomY - 14.0;
        $metaHeight = 46.0;
        $metaBottomY = $metaTopY - $metaHeight;
        $ops[] = $this->rect(self::MARGIN, $metaBottomY, $contentWidth, $metaHeight, [0.969, 0.980, 0.969], [0.796, 0.835, 0.882], 0.8);

        $colW = $contentWidth / 4.0;
        for ($i = 1; $i <= 3; $i++) {
            $xDiv = self::MARGIN + ($colW * $i);
            $ops[] = $this->line($xDiv, $metaBottomY, $xDiv, $metaTopY, [0.796, 0.835, 0.882], 0.6);
        }

        $metaCells = [
            ['INVOICE NUMBER', (string) $invoice->invoice_number],
            ['ORDER REFERENCE', (string) $invoice->order_reference],
            ['ISSUE DATE', $invoice->issued_at ? $invoice->issued_at->format('d M Y') : date('d M Y')],
            ['PAYMENT STATUS', strtoupper((string) $invoice->payment_status)],
        ];

        foreach ($metaCells as $idx => [$label, $value]) {
            $cellX = self::MARGIN + ($colW * $idx) + 10.0;
            $ops[] = $this->text($cellX, $metaTopY - 16, $label, 'F2', 7.5, [0.39, 0.45, 0.54]);
            $valColor = ($idx === 3 && strtolower((string) $invoice->payment_status) === 'paid')
                ? [0.082, 0.502, 0.239]
                : [0.059, 0.090, 0.165];
            $ops[] = $this->text($cellX, $metaTopY - 33, $this->truncate($value, 22), 'F2', 9.5, $valColor);
        }

        // 3. Seller & Buyer Information Rectangular Boxes
        $partyTopY = $metaBottomY - 16.0;
        $gap = 14.0;
        $boxW = ($contentWidth - $gap) / 2.0;

        $sellerLines = $this->buildPartyLines($seller);
        $buyerLines = $this->buildPartyLines($buyer);
        $maxLines = max(count($sellerLines), count($buyerLines), 3);
        $partyBoxH = 34.0 + ($maxLines * 13.5) + 10.0;
        $partyBottomY = $partyTopY - $partyBoxH;

        // Seller Box
        $ops[] = $this->rect(self::MARGIN, $partyBottomY, $boxW, $partyBoxH, [1.0, 1.0, 1.0], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->rect(self::MARGIN, $partyTopY - 22, $boxW, 22, [0.945, 0.961, 0.976], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->text(self::MARGIN + 10, $partyTopY - 14.5, 'SELLER INFORMATION', 'F2', 8.5, [0.078, 0.220, 0.145]);

        $lineY = $partyTopY - 36;
        foreach ($sellerLines as $i => $line) {
            $font = $i === 0 ? 'F2' : 'F1';
            $size = $i === 0 ? 10.0 : 8.5;
            $color = $i === 0 ? [0.059, 0.090, 0.165] : [0.278, 0.333, 0.412];
            $ops[] = $this->text(self::MARGIN + 10, $lineY, $line, $font, $size, $color);
            $lineY -= 13.5;
        }

        // Buyer Box
        $buyerX = self::MARGIN + $boxW + $gap;
        $ops[] = $this->rect($buyerX, $partyBottomY, $boxW, $partyBoxH, [1.0, 1.0, 1.0], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->rect($buyerX, $partyTopY - 22, $boxW, 22, [0.945, 0.961, 0.976], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->text($buyerX + 10, $partyTopY - 14.5, 'BUYER INFORMATION', 'F2', 8.5, [0.078, 0.220, 0.145]);

        $lineY = $partyTopY - 36;
        foreach ($buyerLines as $i => $line) {
            $font = $i === 0 ? 'F2' : 'F1';
            $size = $i === 0 ? 10.0 : 8.5;
            $color = $i === 0 ? [0.059, 0.090, 0.165] : [0.278, 0.333, 0.412];
            $ops[] = $this->text($buyerX + 10, $lineY, $line, $font, $size, $color);
            $lineY -= 13.5;
        }

        // 4. Order Details Rectangular Table
        $tableTopY = $partyBottomY - 18.0;
        $thH = 24.0;
        $thBottomY = $tableTopY - $thH;
        $ops[] = $this->rect(self::MARGIN, $thBottomY, $contentWidth, $thH, [0.078, 0.220, 0.145], [0.078, 0.220, 0.145]);

        $cItemX = self::MARGIN + 10;
        $cQtyX = self::MARGIN + 285;
        $cUnitX = self::MARGIN + 395;
        $cSubX = self::PAGE_WIDTH - self::MARGIN - 10;

        $ops[] = $this->text($cItemX, $thBottomY + 8, 'ITEM & DESCRIPTION', 'F2', 8.5, [1.0, 1.0, 1.0]);
        $ops[] = $this->textRight($cQtyX, $thBottomY + 8, 'QUANTITY', 'F2', 8.5, [1.0, 1.0, 1.0]);
        $ops[] = $this->textRight($cUnitX, $thBottomY + 8, 'UNIT PRICE (RM)', 'F2', 8.5, [1.0, 1.0, 1.0]);
        $ops[] = $this->textRight($cSubX, $thBottomY + 8, 'SUBTOTAL (RM)', 'F2', 8.5, [1.0, 1.0, 1.0]);

        $descLines = ! empty($invoice->item_description)
            ? array_slice($this->wrapText((string) $invoice->item_description, 44), 0, 4)
            : [];
        $rowHeight = max(44.0, 26.0 + (count($descLines) * 12.0));
        $rowBottomY = $thBottomY - $rowHeight;

        $ops[] = $this->rect(self::MARGIN, $rowBottomY, $contentWidth, $rowHeight, [1.0, 1.0, 1.0], [0.796, 0.835, 0.882], 0.8);

        $itemNameY = $thBottomY - 16;
        $ops[] = $this->text($cItemX, $itemNameY, $this->truncate((string) $invoice->item_name, 42), 'F2', 9.5, [0.059, 0.090, 0.165]);

        $descY = $itemNameY - 13;
        foreach ($descLines as $dLine) {
            $ops[] = $this->text($cItemX, $descY, $dLine, 'F1', 8.2, [0.39, 0.45, 0.54]);
            $descY -= 12.0;
        }

        $qtyFormatted = rtrim(rtrim(number_format((float) $invoice->quantity, 2, '.', ''), '0'), '.').' '.$invoice->unit;
        $ops[] = $this->textRight($cQtyX, $itemNameY, $qtyFormatted, 'F1', 9.0, [0.059, 0.090, 0.165]);
        $ops[] = $this->textRight($cUnitX, $itemNameY, 'RM '.number_format((float) $invoice->unit_price, 2), 'F1', 9.0, [0.059, 0.090, 0.165]);
        $ops[] = $this->textRight($cSubX, $itemNameY, 'RM '.number_format((float) $invoice->subtotal, 2), 'F2', 9.5, [0.059, 0.090, 0.165]);

        // 5. Right-aligned Totals Box
        $totalsTopY = $rowBottomY - 12.0;
        $totalsW = 225.0;
        $totalsX = self::PAGE_WIDTH - self::MARGIN - $totalsW;
        $hasTax = (float) $invoice->tax_amount > 0 && ! empty($invoice->tax_label);
        $totalsH = $hasTax ? 76.0 : 56.0;
        $totalsBottomY = $totalsTopY - $totalsH;

        $ops[] = $this->rect($totalsX, $totalsBottomY, $totalsW, $totalsH, [1.0, 1.0, 1.0], [0.796, 0.835, 0.882], 0.8);

        $tY = $totalsTopY - 16;
        $ops[] = $this->text($totalsX + 10, $tY, 'Subtotal', 'F1', 9.0, [0.278, 0.333, 0.412]);
        $ops[] = $this->textRight(self::PAGE_WIDTH - self::MARGIN - 10, $tY, 'RM '.number_format((float) $invoice->subtotal, 2), 'F2', 9.0, [0.059, 0.090, 0.165]);

        if ($hasTax) {
            $tY -= 18;
            $taxCaption = $invoice->tax_label.($invoice->tax_rate ? ' ('.number_format((float) $invoice->tax_rate, 2).'%)' : '');
            $ops[] = $this->text($totalsX + 10, $tY, $taxCaption, 'F1', 9.0, [0.278, 0.333, 0.412]);
            $ops[] = $this->textRight(self::PAGE_WIDTH - self::MARGIN - 10, $tY, 'RM '.number_format((float) $invoice->tax_amount, 2), 'F2', 9.0, [0.059, 0.090, 0.165]);
        }

        // Highlighted Total Row
        $ops[] = $this->rect($totalsX, $totalsBottomY, $totalsW, 28.0, [0.929, 0.969, 0.933], [0.078, 0.220, 0.145], 0.9);
        $ops[] = $this->text($totalsX + 10, $totalsBottomY + 10, 'TOTAL AMOUNT (MYR)', 'F2', 9.5, [0.078, 0.220, 0.145]);
        $ops[] = $this->textRight(self::PAGE_WIDTH - self::MARGIN - 10, $totalsBottomY + 10, 'RM '.number_format((float) $invoice->total_amount, 2), 'F2', 11.5, [0.078, 0.220, 0.145]);

        // 6. Payment Information Rectangular Box
        $payTopY = $totalsBottomY - 22.0;
        $payH = 112.0;
        $payBottomY = $payTopY - $payH;

        $ops[] = $this->rect(self::MARGIN, $payBottomY, $contentWidth, $payH, [0.984, 0.988, 0.992], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->rect(self::MARGIN, $payTopY - 22, $contentWidth, 22, [0.945, 0.961, 0.976], [0.796, 0.835, 0.882], 0.8);
        $ops[] = $this->text(self::MARGIN + 10, $payTopY - 14.5, 'PAYMENT INFORMATION', 'F2', 8.5, [0.078, 0.220, 0.145]);

        $paymentMethodLabel = strtoupper((string) ($invoice->payment_method ?: 'STRIPE CHECKOUT'));
        if ($paymentMethodLabel === 'CARD') {
            $paymentMethodLabel = 'Stripe Card Payment';
        } elseif ($paymentMethodLabel === 'FPX') {
            $paymentMethodLabel = 'FPX Online Banking';
        }

        $payRows = [
            ['Payment Method', $paymentMethodLabel],
            ['Stripe Payment Reference', (string) ($invoice->payment_reference ?: 'N/A')],
            ['Payment Date', $invoice->paid_at ? $invoice->paid_at->format('d M Y, h:i A') : ($invoice->issued_at ? $invoice->issued_at->format('d M Y') : 'N/A')],
            ['Transaction Status', strtoupper((string) $invoice->payment_status)],
            ['Total Paid', 'RM '.number_format((float) $invoice->total_amount, 2)],
        ];

        $py = $payTopY - 37;
        foreach ($payRows as [$pLabel, $pVal]) {
            $ops[] = $this->text(self::MARGIN + 12, $py, $pLabel.':', 'F2', 8.5, [0.39, 0.45, 0.54]);
            $ops[] = $this->text(self::MARGIN + 165, $py, $this->truncate($pVal, 58), 'F1', 8.5, [0.059, 0.090, 0.165]);
            $py -= 15.0;
        }

        // 7. Footer Notice
        $footerY = 48.0;
        $ops[] = $this->line(self::MARGIN, $footerY + 14, self::PAGE_WIDTH - self::MARGIN, $footerY + 14, [0.796, 0.835, 0.882], 0.6);
        $ops[] = $this->text(self::MARGIN, $footerY, 'This is a computer-generated invoice issued via EcoCycle Malaysia. No signature is required.', 'F1', 8.0, [0.45, 0.51, 0.59]);
        $ops[] = $this->textRight(self::PAGE_WIDTH - self::MARGIN, $footerY, 'Currency: Malaysian Ringgit (MYR)', 'F1', 8.0, [0.45, 0.51, 0.59]);

        return $this->assemblePdf(implode("\n", $ops));
    }

    /**
     * @param  array<string, string|null>  $party
     * @return array<int, string>
     */
    private function buildPartyLines(array $party): array
    {
        $lines = [];
        $lines[] = $this->truncate((string) ($party['business_name'] ?? 'EcoCycle Partner Business'), 36);

        if (! empty($party['registration_number'])) {
            $lines[] = 'Reg. No: '.$this->truncate((string) $party['registration_number'], 32);
        }
        if (! empty($party['address'])) {
            foreach (array_slice($this->wrapText((string) $party['address'], 38), 0, 3) as $addrLine) {
                $lines[] = $addrLine;
            }
        }
        if (! empty($party['phone'])) {
            $lines[] = 'Tel: '.$this->truncate((string) $party['phone'], 34);
        }
        if (! empty($party['email'])) {
            $lines[] = 'Email: '.$this->truncate((string) $party['email'], 34);
        }

        return $lines;
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
        // Convert common Unicode punctuation to WinAnsi-safe ASCII equivalents
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
     * Approximate right-aligned text positioning for Helvetica/Helvetica-Bold.
     *
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
