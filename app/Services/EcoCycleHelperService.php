<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EcoCycleHelperService
{
    /**
     * Analyze an item or material and produce structured circular economy recommendations.
     *
     * @param  array<string, mixed>|\Illuminate\Database\Eloquent\Model  $item
     * @return array<string, mixed>
     */
    public function analyze(array|\Illuminate\Database\Eloquent\Model $item): array
    {
        $name = is_array($item) ? ($item['name'] ?? $item['title'] ?? 'Material') : ($item->name ?: $item->title ?: 'Material');
        $category = is_array($item) ? ($item['category'] ?? 'General') : ($item->category ?? 'General');
        $description = is_array($item) ? ($item['description'] ?? '') : ($item->description ?? '');
        $condition = is_array($item) ? ($item['condition'] ?? 'Used') : ($item->condition ?? 'Used');
        $quantity = (float) (is_array($item) ? ($item['quantity'] ?? 1) : ($item->quantity ?? 1));
        $unit = is_array($item) ? ($item['unit'] ?? 'units') : ($item->unit ?? 'units');
        $reusability = is_array($item) ? ($item['reusability'] ?? 'reusable') : ($item->reusability ?? 'reusable');
        $images = is_array($item) ? ($item['images'] ?? []) : (method_exists($item, 'getAllImages') ? $item->getAllImages() : []);

        $normCategory = mb_strtolower(trim($category));
        $normName = mb_strtolower(trim($name));
        $combinedText = $normName . ' ' . mb_strtolower($description);

        $geminiAnalysis = null;
        if (! empty(config('services.gemini.api_key'))) {
            $geminiAnalysis = $this->analyzeWithGemini($name, $category, $description, $condition, $quantity, $unit, $reusability);
        }

        $analysis = $geminiAnalysis ?: $this->determineAnalysisProfile($normCategory, $combinedText, $condition, $quantity, $unit, $reusability);

        return array_merge([
            'item_name' => $name,
            'category' => $category,
            'quantity_formatted' => "{$quantity} {$unit}",
            'condition' => $condition,
            'reusability' => $reusability,
            'has_images' => ! empty($images),
            'image_count' => count($images),
            'ai_source' => $geminiAnalysis ? 'Google Gemini AI' : 'EcoCycle Expert Engine',
            'estimated_value_myr' => 'Varies by licensed Malaysian recycler, sorting purity, and batch volume',
            'carbon_impact' => $analysis['carbon_saving_estimate'] ?? 'Diverting non-reusable material to verified recovery or recycling helps reduce greenhouse gas emissions compared to landfill disposal or open burning.',
            'improper_disposal_risks' => $analysis['disposal_warning'] ?? 'Improper disposal or open dumping can cause pollution and regulatory penalties.',
        ], $analysis);
    }

    /**
     * Provide conversational answers to follow-up questions from the business.
     *
     * @param  string  $userMessage
     * @param  array<string, mixed>|null  $contextItem
     * @param  array<int, array<string, string>>  $history
     * @return array<string, mixed>
     */
    public function reply(string $userMessage, ?array $contextItem = null, array $history = []): array
    {
        if (! empty(config('services.gemini.api_key'))) {
            $geminiReply = $this->replyWithGemini($userMessage, $contextItem, $history);
            if ($geminiReply !== null) {
                return $geminiReply;
            }
        }

        $query = mb_strtolower(trim($userMessage));
        $itemName = $contextItem['name'] ?? $contextItem['item_name'] ?? 'your material';
        $category = mb_strtolower($contextItem['category'] ?? 'general');

        // Check query intents
        if (str_contains($query, 'price') || str_contains($query, 'worth') || str_contains($query, 'value') || str_contains($query, 'harga') || str_contains($query, 'sell')) {
            $reply = $this->getPriceGuidance($category, $itemName);
        } elseif (str_contains($query, 'clean') || str_contains($query, 'prep') || str_contains($query, 'wash') || str_contains($query, 'bale') || str_contains($query, 'store') || str_contains($query, 'simpan')) {
            $reply = $this->getPreparationGuidance($category, $itemName);
        } elseif (str_contains($query, 'doe') || str_contains($query, 'legal') || str_contains($query, 'license') || str_contains($query, 'schedule') || str_contains($query, 'regulation') || str_contains($query, 'lesen')) {
            $reply = $this->getRegulatoryGuidance($category, $itemName);
        } elseif (str_contains($query, 'where') || str_contains($query, 'centre') || str_contains($query, 'facility') || str_contains($query, 'location') || str_contains($query, 'antar') || str_contains($query, 'maps')) {
            $reply = "You can view matched disposal & circular recovery facilities right below in the **GPS Recycling Locator** tab! We automatically calculate distances from your current GPS coordinates to certified Malaysian MRFs and recovery plants with direct Google Maps navigation.";
        } elseif (str_contains($query, 'repurpose') || str_contains($query, 'upcycle') || str_contains($query, 'idea') || str_contains($query, 'create') || str_contains($query, 'reuse')) {
            $reply = $this->getRepurposingIdeas($category, $itemName);
        } else {
            $reply = "For **{$itemName}** ({$category}), circular economic best practice recommends:\n\n" .
                "1. **Segregation:** Keep clean and distinct from other industrial waste streams to preserve raw material purity.\n" .
                "2. **Volume Consolidation:** Store in dry containment until bulk volume is reached to reduce transportation costs and carbon emissions.\n" .
                "3. **Recovery Route:** Use the **GPS Recycling Locator** below to find certified Malaysian facilities that accept {$category} or list on the Reusable Marketplace if durable.";
        }

        return [
            'reply' => $reply,
            'suggested_questions' => [
                "What is the estimated market price for {$itemName}?",
                "How should we store and prepare {$itemName} for pickup?",
                "Are there DOE or licensing requirements for transporting this?",
                "Find certified recyclers near my current GPS location",
            ],
        ];
    }

    /**
     * Classify an inventory material using Gemini Multimodal AI with actual uploaded images.
     *
     * @param  array<int, string|\Illuminate\Http\UploadedFile>  $images
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function classifyMaterial(
        string $name,
        string $description,
        float $quantity,
        string $unit,
        array $images = [],
        string $condition = 'Used',
        bool $allowUnavailableFallback = false
    ): array {
        $imageParts = $this->extractInlineImageParts($images);

        if (empty($imageParts)) {
            throw new \RuntimeException('Uploaded material photos could not be processed. Please upload 1 to 3 valid images and retry analysis.');
        }

        if (! empty(config('services.gemini.api_key'))) {
            $geminiResult = $this->classifyWithGemini($name, $description, $quantity, $unit, $condition, $imageParts);
            if ($geminiResult === null && ! $allowUnavailableFallback) {
                throw new \RuntimeException('Photo analysis could not be completed. Please try again in a moment with the same photos.');
            }

            if ($geminiResult !== null) {
                return $geminiResult;
            }

            return $this->classifyWithExpertEngine($name, $description, $quantity, $unit, $condition, 0);
        }

        return $this->classifyWithExpertEngine($name, $description, $quantity, $unit, $condition, count($imageParts));
    }

    /**
     * Read and base64-encode up to 3 actual uploaded image files for Gemini multimodal inline_data.
     * Deduplicates by file content hash so each image is sent only once.
     *
     * @param  array<int, string|\Illuminate\Http\UploadedFile>  $images
     * @return array<int, array{inline_data: array{mime_type: string, data: string}}>
     */
    private function extractInlineImageParts(array $images): array
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $parts = [];
        $seenHashes = [];

        foreach (array_slice($images, 0, 3) as $img) {
            $mimeType = null;
            $binary = null;

            if ($img instanceof \Illuminate\Http\UploadedFile && $img->isValid()) {
                $realPath = $img->getRealPath();
                if ($realPath && is_readable($realPath)) {
                    $mimeType = $img->getMimeType() ?: 'image/jpeg';
                    $binary = file_get_contents($realPath);
                }
            } elseif (is_string($img) && trim($img) !== '') {
                $cleanPath = ltrim(parse_url($img, PHP_URL_PATH) ?: $img, '/');
                if (str_starts_with($cleanPath, 'storage/')) {
                    $cleanPath = substr($cleanPath, 8);
                }
                if ($disk->exists($cleanPath)) {
                    $mimeType = $disk->mimeType($cleanPath) ?: 'image/jpeg';
                    $binary = $disk->get($cleanPath);
                } elseif (file_exists(public_path($cleanPath))) {
                    $mimeType = mime_content_type(public_path($cleanPath)) ?: 'image/jpeg';
                    $binary = file_get_contents(public_path($cleanPath));
                }
            }

            if (! is_string($binary) || $binary === '' || ! $mimeType || ! str_starts_with($mimeType, 'image/')) {
                continue;
            }

            $hash = hash('sha256', $binary);
            if (isset($seenHashes[$hash])) {
                continue;
            }
            $seenHashes[$hash] = true;

            $base64 = base64_encode($binary);
            // Support up to ~5.5MB raw image (~7.5MB base64) per image
            if (strlen($base64) > 7500000) {
                continue;
            }

            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $base64,
                ],
            ];
        }

        return $parts;
    }

    /**
     * Call Google Gemini API with multimodal parts (Text and Image data)
     */
    public function callGeminiMultimodal(array $parts, float $temperature = 0.2): ?string
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.gemini.photo_model', 'gemini-3.5-flash-lite');
        set_time_limit(120);
        $startedAt = hrtime(true);

        try {
            $request = Http::connectTimeout(10)->timeout(90)->withHeaders([
                'x-goog-api-key' => $apiKey,
            ]);
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => $parts,
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => 2048,
                    'responseMimeType' => 'application/json',
                ],
            ];
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
            $response = $request->post("{$baseUrl}/{$model}:generateContent", $payload);

            $fallbackModel = config('services.gemini.photo_fallback_model');
            $remainingSeconds = 90 - (hrtime(true) - $startedAt) / 1_000_000_000;
            if ($response->status() === 503 && $fallbackModel && $fallbackModel !== $model && $remainingSeconds >= 1) {
                Log::warning('Gemini photo model unavailable, trying the backup model.', ['model' => $model]);
                $model = $fallbackModel;
                $response = $request->timeout($remainingSeconds)->post("{$baseUrl}/{$model}:generateContent", $payload);
            }

            $remainingSeconds = 90 - (hrtime(true) - $startedAt) / 1_000_000_000;
            if ($response->status() === 503 && $remainingSeconds >= 3) {
                \Illuminate\Support\Sleep::for(2)->seconds();
                $remainingSeconds = 90 - (hrtime(true) - $startedAt) / 1_000_000_000;
                if ($remainingSeconds >= 1) {
                    $response = $request->timeout($remainingSeconds)->post("{$baseUrl}/{$model}:generateContent", $payload);
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException) {
            Log::warning('Gemini photo analysis could not complete the connection.', ['model' => $model]);

            return null;
        }

        if ($response->status() === 429) {
            foreach ((array) $response->json('error.details', []) as $detail) {
                if (! is_array($detail) || ($detail['@type'] ?? null) !== 'type.googleapis.com/google.rpc.QuotaFailure') {
                    continue;
                }

                foreach ((array) ($detail['violations'] ?? []) as $violation) {
                    if (is_array($violation) && preg_match('/per[\s_-]*day|daily/i', (string) ($violation['quotaId'] ?? '').' '.(string) ($violation['quotaMetric'] ?? ''))) {
                        throw new \RuntimeException('Gemini has reached its daily photo analysis limit. Please try again tomorrow.');
                    }
                }
            }

            throw new \RuntimeException('Gemini has reached a usage limit. Please wait before trying photo analysis again.');
        }

        if (! $response->successful()) {
            Log::warning('Gemini photo analysis returned an error.', [
                'model' => $model,
                'status' => $response->status(),
            ]);

            return null;
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        return is_string($text) && trim($text) !== '' ? $text : null;
    }

    /**
     * Classify material using Gemini Multimodal AI with actual uploaded images and material details.
     *
     * @param  array<int, array{inline_data: array{mime_type: string, data: string}}>  $imageParts
     */
    private function classifyWithGemini(
        string $name,
        string $description,
        float $quantity,
        string $unit,
        string $condition,
        array $imageParts
    ): ?array {
        $imageCount = count($imageParts);
        $prompt = <<<PROMPT
You are an industrial circular economy specialist and material waste auditor in Malaysia.
Visually inspect ALL {$imageCount} attached material photo(s) together with the submitted material details:

Material Name: {$name}
Description: {$description}
Quantity: {$quantity} {$unit}
Material Condition: {$condition}
Uploaded Photos Attached: {$imageCount}

Evaluate this material thoroughly using BOTH the visual evidence from the attached photo(s) AND the text details (note: names and descriptions may be in English or Bahasa Malaysia):
1. Dynamic Material Category:
   - Identify the most accurate, concise, professional material category directly from what you see in the photo(s) together with the material name and description.
   - DO NOT restrict yourself to a fixed list and DO NOT default to "Other" when the material can be identified.
   - Use a clear Title Case category name (2 to 4 words, e.g., "Organic Food Waste", "Structural Steel", "Timber & Wood Pallets", "HDPE Plastic Containers", "Corrugated Cardboard Packaging", "Borosilicate Glass", "Cotton Fabric Offcuts", "Electronic Circuit Boards", "Rubber & Industrial Tyres", "Concrete & Masonry").
   - Set "category_confidence" to "high" if clearly identifiable, "medium" if reasonably identifiable, or "low" if the photos and text are too vague or contradictory to identify confidently (in which case leave "suggested_category" as "").

2. Reusability Classification (taking visual wear/damage and Material Condition "{$condition}" into account):
   - "reusable": If the material visible in the photo(s) is structurally sound, clean, durable, multi-use, or can be directly reused, repurposed, or resold to other Malaysian businesses.
   - "not_reusable": If the material visible in the photo(s) is severely damaged/broken beyond reuse, single-use cutoffs, food/organic waste, contaminated, shredded, slag, sludge, hazardous waste, or degraded scrap that cannot be directly reused without industrial recycling, smelting, granulation, composting, or licensed disposal.

3. For Reusable materials, provide suggestions (treated as editable suggestions for the business owner):
   - "suggested_condition": One of "New", "Damaged", or "Used".
   - "suggested_listing_type": One of "buy" (Sell), "free" (Free), or "lend" (Lend).
   - "suggested_price_range": Estimated MYR price or price range per {$unit} considering category, condition, quantity, unit, and Malaysian market value (e.g., "RM 8.00 - RM 12.00 per {$unit}", or "RM 0.00 (Free / Lend recommended)" if free/lend).
   - "reuse_applications": 2-3 practical reuse applications.

4. For Non-Reusable materials, tailor the guidance to the material, its condition, and the quantity reported:
   - "disposal_method": Recommend a preferred handling or recovery route and explain why it fits.
   - "owner_actions": Give 5 to 7 ordered, actionable steps. Cover checking for contamination, separating incompatible materials, safe temporary storage, confirming a facility's acceptance and pickup requirements, and recording the handover. Adapt each step to the item and say what the business should verify when information is missing.
   - "repurposing_ideas": Always provide a non-empty list of up to 3 distinct, viable recovery alternatives. For each, explain when it is suitable, what would rule it out, and what the business should ask the operator before choosing it. If recovery is unsuitable, explain why and suggest an appropriate professional disposal route.
   - "environmental_impact" and "carbon_impact": Explain expected impacts qualitatively using the material and the recommended route. Do not invent numerical savings, emissions, or statistics.
   - "improper_disposal_risks": Describe material-specific risks and any checks the business should make with a qualified operator. If the waste may be hazardous or its composition is uncertain, prioritize professional assessment and avoid suggesting on-site treatment.

Return ONLY a valid JSON object matching this schema (no markdown backticks):
{
  "suggested_category": "Dynamic concise Title Case material category name",
  "category_confidence": "high",
  "suggested_reusability": "reusable",
  "classification_label": "Reusable",
  "suggested_condition": "Used",
  "suggested_listing_type": "buy",
  "suggested_price_range": "RM 8.00 - RM 12.00 per kg",
  "reuse_applications": [
    "Practical reuse application 1",
    "Practical reuse application 2"
  ],
  "reason": "Clear explanation based on the photos, condition, and specifications",
  "recommended_actions": [
    "Recommended next step 1",
    "Recommended next step 2",
    "Recommended next step 3"
  ],
  "other_recommendations": "Other relevant practical recommendations",
  "recycling_recommendations": "Possible recycling methods and processes",
  "repurposing_ideas": [
    "Possible repurposing method 1",
    "Possible repurposing method 2"
  ],
  "disposal_method": "Recommended compliant waste handling method",
  "environmental_impact": "Qualitative explanation of potential environmental impact",
  "carbon_impact": "Qualitative explanation of potential carbon impact without invented exact figures",
  "improper_disposal_risks": "Risks of improper disposal or landfilling",
  "owner_actions": [
    "Practical step the business owner can perform 1",
    "Practical step the business owner can perform 2",
    "Practical step the business owner can perform 3"
  ]
}
PROMPT;

        $parts = array_merge(
            [['text' => $prompt]],
            $imageParts
        );

        $rawResponse = $this->callGeminiMultimodal($parts, 0.2);
        if (empty($rawResponse)) {
            return null;
        }

        // Clean markdown backticks if returned
        $cleaned = trim($rawResponse);
        if (str_starts_with($cleaned, '```json')) {
            $cleaned = substr($cleaned, 7);
        } elseif (str_starts_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 3);
        }
        if (str_ends_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 0, -3);
        }
        $cleaned = trim($cleaned);

        $decoded = json_decode($cleaned, true);
        if (! is_array($decoded)) {
            $firstBrace = strpos($cleaned, '{');
            $lastBrace = strrpos($cleaned, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $decoded = json_decode(substr($cleaned, $firstBrace, $lastBrace - $firstBrace + 1), true);
            }
        }
        if (! is_array($decoded)) {
            return null;
        }

        $rawReus = strtolower(trim((string) ($decoded['suggested_reusability'] ?? '')));
        $isNotReusable = (
            str_contains($rawReus, 'not')
            || str_contains($rawReus, 'non')
            || str_contains($rawReus, 'scrap')
            || str_contains($rawReus, 'low')
            || $rawReus === 'no'
            || $rawReus === 'false'
        );
        $reusability = $isNotReusable ? 'not_reusable' : 'reusable';
        $label = ($reusability === 'reusable') ? 'Reusable' : 'Non-Reusable';

        // Preserve the dynamic category returned by Gemini without forcing a hardcoded list
        $rawCategory = trim(preg_replace('/\s+/', ' ', (string) ($decoded['suggested_category'] ?? '')) ?? '');
        $rawCategory = trim($rawCategory, " \t\n\r\0\x0B\"'");
        $lowerCategory = mb_strtolower($rawCategory);

        $invalidCategories = ['', 'other', 'unknown', 'unidentified', 'n/a', 'none', 'null', 'uncertain', 'general'];
        $rawConfidence = strtolower(trim((string) ($decoded['category_confidence'] ?? 'high')));
        if (! in_array($rawConfidence, ['high', 'medium', 'low'], true)) {
            $rawConfidence = 'medium';
        }

        if (in_array($lowerCategory, $invalidCategories, true)) {
            $category = '';
            $confidence = 'low';
        } else {
            $category = mb_substr($rawCategory, 0, 100);
            $confidence = $rawConfidence;
        }

        $suggestedCondition = trim((string) ($decoded['suggested_condition'] ?? $condition));
        if (! in_array($suggestedCondition, Item::ALLOWED_CONDITIONS, true)) {
            $suggestedCondition = in_array($condition, Item::ALLOWED_CONDITIONS, true) ? $condition : 'Used';
        }

        $rawListingType = strtolower(trim((string) ($decoded['suggested_listing_type'] ?? 'buy')));
        $suggestedListingType = match (true) {
            str_contains($rawListingType, 'free') => 'free',
            str_contains($rawListingType, 'lend') || str_contains($rawListingType, 'borrow') => 'lend',
            default => 'buy',
        };

        $repurposingIdeas = array_values(array_filter(
            (array) ($decoded['repurposing_ideas'] ?? []),
            static fn (mixed $idea): bool => is_string($idea) && trim($idea) !== ''
        ));
        if ($repurposingIdeas === []) {
            $repurposingIdeas[] = 'Ask a specialist recovery facility whether it can accept this material. If recovery is unsuitable, request an appropriate disposal referral.';
        }

        $listingTypeLabel = match ($suggestedListingType) {
            'free' => 'Free',
            'lend' => 'Lend',
            default => 'Sell',
        };

        return [
            'suggested_category' => $category,
            'category_confidence' => $confidence,
            'needs_category_review' => ($confidence === 'low' || $category === ''),
            'suggested_reusability' => $reusability,
            'classification_label' => $label,
            'suggested_condition' => $suggestedCondition,
            'suggested_listing_type' => $suggestedListingType,
            'suggested_listing_type_label' => $listingTypeLabel,
            'suggested_price_range' => trim((string) ($decoded['suggested_price_range'] ?? ($suggestedListingType === 'buy' ? "Market rate per {$unit} (editable by owner)" : 'RM 0.00'))),
            'reuse_applications' => (array) ($decoded['reuse_applications'] ?? []),
            'reason' => $decoded['reason'] ?? 'Material evaluated based on uploaded photos and specifications.',
            'recommended_actions' => (array) ($decoded['recommended_actions'] ?? []),
            'other_recommendations' => (string) ($decoded['other_recommendations'] ?? 'Store cleanly and confirm specifications before listing or disposal.'),
            'recycling_recommendations' => $decoded['recycling_recommendations'] ?? 'Recycle with certified recovery partners.',
            'repurposing_ideas' => $repurposingIdeas,
            'disposal_method' => $decoded['disposal_method'] ?? 'Divert from landfill into certified circular channels.',
            'environmental_impact' => (string) ($decoded['environmental_impact'] ?? 'Proper material recovery helps reduce landfill volume and limits soil or water pollution.'),
            'carbon_impact' => (string) ($decoded['carbon_impact'] ?? 'Recovering and recycling secondary materials generally lowers lifecycle greenhouse gas emissions compared to producing virgin materials, though exact emissions depend on transport and processing methods.'),
            'improper_disposal_risks' => (string) ($decoded['improper_disposal_risks'] ?? 'Improper disposal or open dumping can cause environmental contamination, regulatory penalties, and loss of recoverable resources.'),
            'owner_actions' => (array) ($decoded['owner_actions'] ?? []),
            'images_analyzed' => $imageCount,
            'ai_source' => 'Google Gemini AI',
        ];
    }

    /**
     * Offline fallback engine used only when GEMINI_API_KEY is not configured.
     */
    private function classifyWithExpertEngine(
        string $name,
        string $description,
        float $quantity,
        string $unit,
        string $condition = 'Used',
        int $imageCount = 0
    ): array {
        $text = mb_strtolower($name . ' ' . $description . ' ' . $condition);

        // 1. Broad Multilingual Category Detection (English & Bahasa Malaysia) with strict word boundaries
        $category = '';
        $confidence = 'medium';
        if (preg_match('/\b(sisa makanan|makanan|food|organic|organik|spent grain|biomass|compost|kompos|coffee|peel|vegetable|fruit|sayur|buah|agricultural|pertanian)\b/i', $text)) {
            $category = 'Organic Food Waste';
        } elseif (preg_match('/\b(wood|kayu|timber|pallet|pallets|palet|plank|planks|papan|lumber|plywood|sawdust|habuk kayu|furniture|perabot|cedar|oak|pine|bamboo|buluh)\b/i', $text)) {
            $category = 'Timber & Wood Products';
        } elseif (preg_match('/\b(paper|kertas|cardboard|kadbod|carton|cartons|kotak|box|boxes|kraft|packaging|pembungkusan|corrugated|newspaper|surat khabar|pulp)\b/i', $text)) {
            $category = 'Packaging & Paper';
        } elseif (preg_match('/\b(steel|keluli|iron|besi|metal|metals|logam|aluminum|aluminium|copper|tembaga|brass|wire|rod|rods|sheet metal|swarf|scrap metal)\b/i', $text)) {
            $category = 'Metal & Fabrication';
        } elseif (preg_match('/\b(plastic|plastics|plastik|polymer|polymers|hdpe|ldpe|pp|pet|pvc|resin|acrylic|polycarbonate|bottle|bottles|botol|wrap|film|drum|drums|container|containers)\b/i', $text)) {
            $category = 'Plastics & Polymers';
        } elseif (preg_match('/\b(rubber|getah|tyre|tyres|tire|tires|tayar|latex|silicone|gasket|gaskets)\b/i', $text)) {
            $category = 'Rubber & Tyres';
        } elseif (preg_match('/\b(glass|kaca|cermin|mirror|ceramic|ceramics|seramik|porcelain|borosilicate|jar|jars)\b/i', $text)) {
            $category = 'Glass & Ceramics';
        } elseif (preg_match('/\b(electronic|electronics|elektronik|electrical|elektrik|pcb|cable|cables|kabel|wayar|battery|batteries|bateri|motor|circuit|e-waste|solar|panel|laptop|computer)\b/i', $text)) {
            $category = 'Electronics & Electrical';
        } elseif (preg_match('/\b(textile|textiles|tekstil|fabric|fabrics|fabrik|kain|cotton|kapas|polyester|yarn|benang|denim|rag|rags|garment|garments|pakaian|apparel|sewing)\b/i', $text)) {
            $category = 'Textiles & Apparel';
        } elseif (preg_match('/\b(chemical|chemicals|kimia|solvent|solvents|pelarut|lubricant|lubricants|pelincir|oil|oils|minyak|sludge|coolant|paint|cat|coating|acid|alkali)\b/i', $text)) {
            $category = 'Chemicals & Lubricants';
        } elseif (preg_match('/\b(concrete|konkrit|cement|simen|brick|bricks|bata|masonry|gypsum|tile|tiles|jubin|stone|batu|gravel|sand|pasir|construction)\b/i', $text)) {
            $category = 'Construction & Masonry';
        } else {
            $category = '';
            $confidence = 'low';
        }

        // 2. Reusability Detection
        $scrapKeywords = ['scrap', 'slag', 'sludge', 'contaminated', 'tercemar', 'broken', 'pecah', 'rosak', 'shredded', 'dust', 'habuk', 'residue', 'waste', 'sisa', 'sampah', 'single-use', 'damaged', 'chemical residue', 'crushed', 'spent', 'dirty', 'kotor', 'off-spec', 'food waste', 'sisa makanan', 'swarf', 'shavings'];
        $isNonReusable = false;
        foreach ($scrapKeywords as $kw) {
            if (str_contains($text, $kw)) {
                $isNonReusable = true;
                break;
            }
        }

        // Clear indicators of reusable durable items
        $reusableKeywords = ['pallet', 'palet', 'plank', 'timber', 'carton box', 'container', 'drum', 'clean', 'bersih', 'working', 'good condition', 'surplus', 'reclaimed', 'unopened', 'new', 'baru', 'sheet', 'beam', 'bar'];
        $hasReusableKw = false;
        foreach ($reusableKeywords as $kw) {
            if (str_contains($text, $kw)) {
                $hasReusableKw = true;
                break;
            }
        }

        if ($hasReusableKw && ! str_contains($text, 'contaminated') && ! str_contains($text, 'broken') && ! str_contains($text, 'sisa makanan')) {
            $isNonReusable = false;
        }

        $reusability = $isNonReusable ? 'not_reusable' : 'reusable';
        $label = $isNonReusable ? 'Non-Reusable' : 'Reusable';

        if ($reusability === 'reusable') {
            $reason = "{$name} exhibits durable structural characteristics and uniform condition suitable for direct multi-use reuse, remanufacturing, or exchange between Malaysian businesses.";
            $actions = [
                "Publish to EcoCycle Surplus Marketplace for other industrial businesses to purchase or claim.",
                "Ensure standard warehouse storage to preserve material integrity prior to pickup.",
                "Set clear pickup terms and condition ratings so prospective buyers have high confidence.",
            ];
            $recycling = "At end-of-life, material can be segregated and channeled into standard industrial recycling loops.";
            $repurposing = ["Remanufacture into secondary industrial products or crating", "Repurpose for internal facility fixtures or shipping supports"];
            $disposal = "Avoid landfill disposal entirely. This material retains substantial commercial and circular value.";
        } else {
            $reason = "{$name} consists of single-use cutoffs, organic waste, contaminated residues, or production scrap that cannot be safely reused in its current form without industrial processing.";
            $actions = [
                "Forward to EcoCycle Helper to match with certified Malaysian recycling and recovery facilities.",
                "Segregate from general refuse to maintain purity and maximize scrap recovery value.",
                "Log into Waste Monitoring to measure diversion from Malaysian landfills under ESG / Act 672 benchmarks.",
            ];
            $recycling = "Mechanical granulation, smelting, composting, or chemical recycling through licensed Malaysian recovery operators (DOE SW certified if scheduled).";
            $repurposing = ["Downcycle into lower-grade construction aggregate, filler, or composite materials", "Convert clean organic fraction into biogas or compost"];
            $disposal = "Strictly divert from municipal landfills. Send only to DOE-licensed industrial MRFs or recovery plants.";
        }

        return [
            'suggested_category' => $category,
            'category_confidence' => $confidence,
            'needs_category_review' => ($confidence === 'low' || $category === ''),
            'suggested_reusability' => $reusability,
            'classification_label' => $label,
            'suggested_condition' => in_array($condition, Item::ALLOWED_CONDITIONS, true) ? $condition : 'Used',
            'suggested_listing_type' => $reusability === 'reusable' ? 'buy' : 'free',
            'suggested_listing_type_label' => $reusability === 'reusable' ? 'Sell' : 'Free',
            'suggested_price_range' => $reusability === 'reusable' ? "Suggested market rate per {$unit} (editable by owner)" : 'RM 0.00',
            'reuse_applications' => $reusability === 'reusable'
                ? ['Direct reuse by local manufacturing or logistics partners', 'Secondary workshop fabrication or packaging support']
                : [],
            'reason' => $reason,
            'recommended_actions' => $actions,
            'other_recommendations' => 'Review material cleanliness, confirm quantity, and store in dry conditions prior to collection.',
            'recycling_recommendations' => $recycling,
            'repurposing_ideas' => $repurposing,
            'disposal_method' => $disposal,
            'environmental_impact' => 'Proper segregation and recovery prevents landfill accumulation and reduces local soil and water contamination.',
            'carbon_impact' => 'Diverting this material into reuse or recycling avoids energy-intensive virgin production; exact carbon savings depend on local processing and transport distances.',
            'improper_disposal_risks' => 'Improper dumping or open burning can cause air/water pollution and regulatory non-compliance under Malaysian environmental regulations.',
            'owner_actions' => [
                'Separate materials cleanly by type and remove surface contaminants.',
                'Store securely in labelled containers away from moisture or rain.',
                'Contact a verified Malaysian recycling or waste management facility for proper handling.',
            ],
            'images_analyzed' => $imageCount,
            'ai_source' => 'EcoCycle Expert Engine',
        ];
    }

    /**
     * Call Google Gemini Generative Language API
     */
    public function callGemini(string $prompt, float $temperature = 0.4): ?string
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-3.1-flash-lite');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        set_time_limit(120);

        try {
            $response = Http::connectTimeout(10)->timeout(90)->withHeaders([
                'x-goog-api-key' => $apiKey,
            ])->post($endpoint, [
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => 1500,
                ],
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            Log::warning('Gemini text guidance could not complete the connection.', ['model' => $model]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Gemini text guidance returned an error.', [
                'model' => $model,
                'status' => $response->status(),
            ]);

            return null;
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        return is_string($text) && trim($text) !== '' ? $text : null;
    }

    /**
     * Analyze non-reusable scrap using Google Gemini API
     */
    private function analyzeWithGemini(string $name, string $category, string $description, string $condition, float $quantity, string $unit, string $reusability): ?array
    {
        $prompt = <<<PROMPT
You are EcoCycle AI, an expert industrial circular economy and waste recovery consultant in Malaysia.
Analyze the following non-reusable material:

Material Name: {$name}
Category: {$category}
Description: {$description}
Condition: {$condition}
Quantity: {$quantity} {$unit}
Classification: Non-Reusable

IMPORTANT: Do NOT invent exact numerical carbon savings, emission values, or unverified environmental statistics. Provide clear, honest qualitative explanations of environmental and carbon impacts instead.

Provide an actionable circular economy analysis formatted STRICTLY as a raw JSON object (no markdown wrapping) with these keys:
{
  "strategy": "Primary circular waste handling strategy title (string)",
  "primary_path": "Short primary recovery pathway name (string)",
  "action_recommendation": "Main recommended next step (string)",
  "recommended_next_steps": ["Step 1", "Step 2", "Step 3"],
  "repurpose_ideas": ["Possible repurposing method 1", "Possible repurposing method 2"],
  "recycling_steps": ["Possible recycling method 1", "Possible recycling method 2", "Possible recycling method 3"],
  "waste_handling_methods": "Recommended compliant waste handling methods (string)",
  "environmental_impact": "Qualitative explanation of potential environmental impact (string)",
  "carbon_saving_estimate": "Qualitative explanation of potential carbon impact without invented exact figures (string)",
  "disposal_warning": "Risks of improper disposal or landfilling (string)",
  "owner_actions": ["Practical action the business owner can do themselves 1", "Practical action 2", "Practical action 3", "Practical action 4"],
  "other_recommendations": "Other relevant practical recommendations (string)",
  "compliance_note": "Malaysian Department of Environment (DOE) / Act 672 / Scheduled Waste compliance guidance (string)"
}
PROMPT;

        $rawResponse = $this->callGemini($prompt, 0.2);
        if (empty($rawResponse)) {
            return null;
        }

        // Clean any accidental markdown code fences
        $cleaned = trim($rawResponse);
        if (str_starts_with($cleaned, '```json')) {
            $cleaned = substr($cleaned, 7);
        } elseif (str_starts_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 3);
        }
        if (str_ends_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 0, -3);
        }
        $cleaned = trim($cleaned);

        $decoded = json_decode($cleaned, true);
        if (! is_array($decoded) || ! isset($decoded['strategy'], $decoded['primary_path'])) {
            return null;
        }

        return [
            'strategy' => $decoded['strategy'] ?? 'Industrial Circular Recovery',
            'primary_path' => $decoded['primary_path'] ?? 'Material Valorization',
            'action_recommendation' => $decoded['action_recommendation'] ?? 'Segregate and route to a certified recovery facility',
            'recommended_next_steps' => (array) ($decoded['recommended_next_steps'] ?? []),
            'repurpose_ideas' => (array) ($decoded['repurpose_ideas'] ?? []),
            'recycling_steps' => (array) ($decoded['recycling_steps'] ?? []),
            'waste_handling_methods' => (string) ($decoded['waste_handling_methods'] ?? 'Segregate, store safely, and transfer to licensed Malaysian waste recovery facilities.'),
            'environmental_impact' => (string) ($decoded['environmental_impact'] ?? 'Proper waste recovery helps prevent landfill overflow and reduces local land and water pollution.'),
            'carbon_saving_estimate' => (string) ($decoded['carbon_saving_estimate'] ?? 'Recycling secondary materials generally reduces energy use and lifecycle greenhouse gas emissions compared to extracting virgin resources, though exact reductions depend on material purity and transport.'),
            'disposal_warning' => (string) ($decoded['disposal_warning'] ?? 'Improper disposal or open dumping can cause pollution and regulatory penalties.'),
            'owner_actions' => (array) ($decoded['owner_actions'] ?? []),
            'other_recommendations' => (string) ($decoded['other_recommendations'] ?? 'Keep transfer records and confirm facility acceptance before dispatch.'),
            'compliance_note' => (string) ($decoded['compliance_note'] ?? 'Comply with Malaysian environmental regulations.'),
        ];
    }

    /**
     * Conversational Q&A using Google Gemini API
     */
    private function replyWithGemini(string $userMessage, ?array $contextItem, array $history): ?array
    {
        $contextStr = '';
        if ($contextItem) {
            $itemName = $contextItem['name'] ?? $contextItem['item_name'] ?? 'Material';
            $cat = $contextItem['category'] ?? 'General';
            $qty = $contextItem['quantity'] ?? 1;
            $unit = $contextItem['unit'] ?? 'units';
            $cond = $contextItem['condition'] ?? 'Used';
            $desc = $contextItem['description'] ?? '';
            $contextStr = "Current material context:\n- Name: {$itemName}\n- Category: {$cat}\n- Quantity: {$qty} {$unit}\n- Condition: {$cond}\n- Description: {$desc}\n";
        }

        $systemPrompt = <<<PROMPT
You are EcoCycle AI, a knowledgeable, professional circular economy consultant for Malaysian manufacturing, fabrication, and industrial businesses.
{$contextStr}
Answer the business user's question clearly, concisely, and practically with bullet points or step numbers where appropriate. Include references to Malaysian market practices (MYR prices, DOE standards, Act 672) when relevant.
Do NOT say you cannot help. Keep your answer focused on sustainability, recycling, upcycling, compliance, or scrap valorization.

User Question: {$userMessage}
PROMPT;

        $replyText = $this->callGemini($systemPrompt, 0.4);
        if (empty($replyText)) {
            return null;
        }

        $itemName = $contextItem['name'] ?? $contextItem['item_name'] ?? 'this material';

        return [
            'reply' => trim($replyText),
            'suggested_questions' => [
                "What is the estimated market price for {$itemName}?",
                "How should we store and prepare {$itemName} for pickup?",
                "Are there DOE or licensing requirements for transporting this?",
                "Find certified recyclers near my current GPS location",
            ],
        ];
    }

    private function determineAnalysisProfile(string $cat, string $text, string $cond, float $qty, string $unit, string $reusability): array
    {
        // 1. Plastics
        if (str_contains($cat, 'plastic') || str_contains($text, 'plastic') || str_contains($text, 'polymer') || str_contains($text, 'hdpe') || str_contains($text, 'pet') || str_contains($text, 'resin') || str_contains($text, 'film') || str_contains($text, 'bottle')) {
            return [
                'strategy' => 'Polymer Segregation, Granulation & Mechanical Recycling',
                'primary_path' => 'Industrial Plastic Recovery',
                'action_recommendation' => 'Segregate by resin type and send to a verified plastic recycler',
                'recommended_next_steps' => [
                    'Separate plastics by resin code (e.g., HDPE, PP, PET, LDPE).',
                    'Remove adhesives, labels, food residue, and metal fasteners.',
                    'Arrange transfer to a verified Malaysian plastic recycling facility.',
                ],
                'repurpose_ideas' => [
                    'Granulate clean offcuts for secondary industrial trays, crates, or drainage fittings.',
                    'Compound with fillers for outdoor composite boards or protective barriers.',
                ],
                'recycling_steps' => [
                    'Verify resin code and keep incompatible polymers separated.',
                    'Wash or brush off surface dirt and oil residues before baling.',
                    'Mechanical shredding and re-pelletizing into post-industrial recycled resin.',
                ],
                'waste_handling_methods' => 'Store dry in clean jumbo bags or covered bins and transfer to a licensed plastic reprocessor. Avoid mixing with general refuse.',
                'environmental_impact' => 'Plastics persist for decades in landfills and waterways, breaking into microplastics if not recovered.',
                'carbon_saving_estimate' => 'Mechanical recycling of clean plastics typically uses significantly less energy and avoids fossil-fuel feedstock extraction compared to producing virgin plastic; exact emissions depend on sorting and transport.',
                'disposal_warning' => 'Improper disposal or open burning of plastics releases toxic fumes and contributes to persistent microplastic pollution.',
                'owner_actions' => [
                    'Separate clean plastics by resin type (HDPE, PP, LDPE, PET).',
                    'Remove tape, stickers, and metallic inserts before storage.',
                    'Store under cover away from sunlight and rainwater.',
                    'Contact a verified plastic recycling facility to confirm batch acceptance.',
                ],
                'other_recommendations' => 'Keep clear and coloured plastics separate to improve recyclability.',
                'compliance_note' => 'Clean industrial plastic cutoffs are generally non-scheduled solid waste under Act 672; chemically contaminated plastic containers may require DOE scheduled waste handling.',
            ];
        }

        // 2. Metals
        if (str_contains($cat, 'metal') || str_contains($text, 'metal') || str_contains($text, 'steel') || str_contains($text, 'iron') || str_contains($text, 'copper') || str_contains($text, 'aluminium') || str_contains($text, 'aluminum') || str_contains($text, 'brass') || str_contains($text, 'swarf')) {
            return [
                'strategy' => 'Metal Sorting, Oil Draining & Secondary Smelting',
                'primary_path' => 'Scrap Metal Smelting & Recovery',
                'action_recommendation' => 'Separate ferrous and non-ferrous metals for licensed metal recycling',
                'recommended_next_steps' => [
                    'Separate ferrous (steel/iron) from non-ferrous (aluminium/copper/brass) metals.',
                    'Drain cutting oils or coolants from shavings/swarf before transport.',
                    'Deliver or arrange pickup with a verified metal recovery facility.',
                ],
                'repurpose_ideas' => [
                    'Reuse intact heavy offcuts for workshop brackets, baseplates, or fixtures.',
                    'Compact clean metal turnings into briquettes for direct foundry remelting.',
                ],
                'recycling_steps' => [
                    'Magnetic and visual sorting by alloy type.',
                    'Degreasing or draining residual machining fluids.',
                    'Compacting/baling followed by secondary furnace smelting into new metal stock.',
                ],
                'waste_handling_methods' => 'Store in covered steel skips or drip-contained drums to prevent oil runoff into drains.',
                'environmental_impact' => 'Uncontrolled disposal of oily metal scrap can leach lubricants and heavy metals into soil and waterways.',
                'carbon_saving_estimate' => 'Remelting scrap steel and aluminium requires substantially less energy than mining and refining primary ore, reducing overall greenhouse gas emissions qualitatively.',
                'disposal_warning' => 'Never dump oily swarf or sharp metal scrap in municipal bins; untreated cutting fluids can contaminate stormwater drains.',
                'owner_actions' => [
                    'Sort steel/iron separately from copper, brass, and aluminium.',
                    'Drain machining fluids into proper containment trays.',
                    'Store dry to prevent heavy rusting and contamination.',
                    'Verify licensing with the receiving metal recycler before dispatch.',
                ],
                'other_recommendations' => 'Keep alloy grades separated to improve recovery efficiency.',
                'compliance_note' => 'Oily swarf containing cutting fluid may be classified under DOE Scheduled Waste (SW305/SW306) if oil content is significant; drain fluids properly and consult DOE guidelines.',
            ];
        }

        // 3. Packaging & Paper / Cardboard
        if (str_contains($cat, 'paper') || str_contains($cat, 'packaging') || str_contains($text, 'carton') || str_contains($text, 'box') || str_contains($text, 'cardboard') || str_contains($text, 'paper')) {
            return [
                'strategy' => 'Dry Segregation, Baling & Paper Mill Repulping',
                'primary_path' => 'Cellulose Fiber Repulping',
                'action_recommendation' => 'Keep dry, flatten/bale, and send to a paper recycling mill',
                'recommended_next_steps' => [
                    'Separate clean dry cardboard and paper from oily or wax-coated packaging.',
                    'Flatten cartons and remove excessive plastic tape or strapping.',
                    'Store indoors until collection by a verified paper recycler.',
                ],
                'repurpose_ideas' => [
                    'Shred clean corrugated sheets into protective void-fill cushioning for shipping.',
                    'Cut intact board sections into internal pallet layer pads or dividers.',
                ],
                'recycling_steps' => [
                    'Sort clean kraft/corrugated cartons from contaminated or laminated paper.',
                    'Compact or bale dry paper to reduce transport trips.',
                    'Hydrapulping and screening at a paper mill to produce recycled linerboard.',
                ],
                'waste_handling_methods' => 'Keep strictly sheltered from rain and moisture; wet or mouldy cardboard loses fiber strength and may be rejected by paper mills.',
                'environmental_impact' => 'Landfilling paper and cardboard wastes recoverable cellulose fiber and generates methane as organic fibers decompose anaerobically.',
                'carbon_saving_estimate' => 'Repulping paper reduces demand for virgin wood pulp and avoids landfill methane emissions qualitatively; exact carbon figures depend on transport and mill energy sources.',
                'disposal_warning' => 'Do not burn waste cardboard or mix wet organic waste with clean recyclable paper bales.',
                'owner_actions' => [
                    'Keep paper and cardboard completely dry under sheltered storage.',
                    'Remove plastic wraps, foam inserts, and heavy metal staples.',
                    'Flatten boxes neatly to maximize storage and transport efficiency.',
                    'Contact a verified paper recycling mill or MRF for pickup.',
                ],
                'other_recommendations' => 'Separate clean corrugated cartons from mixed office paper for higher recovery quality.',
                'compliance_note' => 'Clean commercial paper and cardboard are non-scheduled recyclables under Malaysian Act 672.',
            ];
        }

        // 4. Timber & Wood
        if (str_contains($cat, 'timber') || str_contains($cat, 'wood') || str_contains($text, 'timber') || str_contains($text, 'wood') || str_contains($text, 'pallet') || str_contains($text, 'plywood') || str_contains($text, 'sawdust')) {
            return [
                'strategy' => 'De-nailing, Wood Chipping & Biomass / Composite Recovery',
                'primary_path' => 'Wood Chipping & Biomass Recovery',
                'action_recommendation' => 'Separate treated from untreated wood and route to a wood recovery facility',
                'recommended_next_steps' => [
                    'Separate clean untreated timber from painted, laminated, or chemically treated wood.',
                    'Remove protruding nails, metal brackets, and plastic strapping where safe.',
                    'Send to a verified wood recycling or biomass facility.',
                ],
                'repurpose_ideas' => [
                    'Salvage intact boards to repair warehouse pallets or dunnage skids.',
                    'Use clean untreated wood chips for landscaping mulch or industrial biomass fuel.',
                ],
                'recycling_steps' => [
                    'Inspection to segregate untreated timber from chemically treated wood.',
                    'Metal extraction (nails/screws) followed by industrial wood chipping.',
                    'Conversion into particleboard feedstock, mulch, or biomass boiler pellets.',
                ],
                'waste_handling_methods' => 'Store dry off the ground and never burn wood waste on site.',
                'environmental_impact' => 'Improper dumping wastes forestry resources and decomposing wood in landfills releases methane; open burning creates particulate air pollution.',
                'carbon_saving_estimate' => 'Keeping wood fiber in composite products or sustainable biomass loops delays carbon release compared to landfilling or virgin timber harvesting; exact carbon impact varies by end use.',
                'disposal_warning' => 'Open burning of wood waste is prohibited under Malaysian Environmental Quality (Clean Air) Regulations. Chemically treated wood must never be composted or burned.',
                'owner_actions' => [
                    'Segregate untreated natural wood from painted or chemically treated timber.',
                    'Extract loose nails and metal fittings safely.',
                    'Keep stacked in a dry area to prevent rot and pest infestation.',
                    'Coordinate with a verified timber recycler or pallet refurbisher.',
                ],
                'other_recommendations' => 'Inspect pallets for reusable deckboards before sending broken pieces for chipping.',
                'compliance_note' => 'Untreated wood waste falls under general solid waste; chemically treated wood (e.g., CCA-treated timber) requires specialist licensed disposal.',
            ];
        }

        // 5. Electronics / E-Waste
        if (str_contains($cat, 'electronic') || str_contains($text, 'electronic') || str_contains($text, 'circuit') || str_contains($text, 'pcb') || str_contains($text, 'battery') || str_contains($text, 'cable') || str_contains($text, 'e-waste')) {
            return [
                'strategy' => 'DOE-Licensed E-Waste Dismantling & Material Recovery',
                'primary_path' => 'Licensed Scheduled Waste (SW110) E-Waste Recovery',
                'action_recommendation' => 'Transfer strictly to a DOE-licensed e-waste recovery facility',
                'recommended_next_steps' => [
                    'Isolate damaged batteries and leaking components in dry, fire-safe containment.',
                    'Do not dismantle hazardous assemblies (CRT screens, lithium packs) manually on site.',
                    'Arrange transfer through a DOE-licensed SW110 e-waste collector.',
                ],
                'repurpose_ideas' => [
                    'Salvage non-hazardous mechanical enclosures or brackets if intact and safe.',
                    'Strip clean insulated copper wiring only via appropriate electrical recyclers.',
                ],
                'recycling_steps' => [
                    'Safe depollution and removal of batteries, capacitors, and mercury components.',
                    'Mechanical shredding and sorting of plastics, ferrous metals, and circuit boards.',
                    'Licensed metallurgical recovery of copper and precious metals.',
                ],
                'waste_handling_methods' => 'Store indoors in labelled, weather-proof containers and log consignment via DOE e-SWIS for licensed collection.',
                'environmental_impact' => 'Electronics contain heavy metals (lead, mercury, cadmium) and flame retardants that can severely pollute soil and groundwater if landfilled.',
                'carbon_saving_estimate' => 'Recovering metals from e-waste avoids energy-intensive mining and refining of primary ores qualitatively; exact emissions depend on recovery technology.',
                'disposal_warning' => 'CRITICAL: Electronic waste must NEVER be placed in municipal rubbish bins or burned. Strict enforcement applies under Malaysian DOE Act 127.',
                'owner_actions' => [
                    'Separate batteries and power packs safely from other e-waste.',
                    'Store dry indoors with clear Scheduled Waste (SW110) labelling.',
                    'Verify the collector holds an active Malaysian DOE license.',
                    'Retain e-Consignment disposal records for compliance.',
                ],
                'other_recommendations' => 'Wipe or physically destroy data-bearing storage media before transferring IT hardware.',
                'compliance_note' => 'Classified as Scheduled Waste (SW110) under the Environmental Quality (Scheduled Wastes) Regulations 2005. Must be handled by DOE-licensed facilities.',
            ];
        }

        // 6. Food / Organic Waste
        if (str_contains($cat, 'food') || str_contains($cat, 'organic') || str_contains($text, 'sisa makanan') || str_contains($text, 'food') || str_contains($text, 'organic') || str_contains($text, 'vegetable') || str_contains($text, 'sayur') || str_contains($text, 'compost')) {
            return [
                'strategy' => 'Source Segregation, Composting or Anaerobic Bio-Conversion',
                'primary_path' => 'Organic Composting & Bio-Recovery',
                'action_recommendation' => 'Segregate clean organic waste from packaging and send for composting or biogas recovery',
                'recommended_next_steps' => [
                    'Separate organic food waste completely from plastic wrappers, foil, and cutlery.',
                    'Drain excess liquid and store in sealed, odour-controlled bins.',
                    'Arrange frequent collection by an organic composting or biogas facility.',
                ],
                'repurpose_ideas' => [
                    'Convert clean vegetable/plant trimmings into on-site or community compost.',
                    'Channel suitable food processing by-products to certified Black Soldier Fly (BSFL) bio-conversion.',
                ],
                'recycling_steps' => [
                    'Strict source separation to remove all non-biodegradable packaging.',
                    'Controlled aerobic composting or anaerobic digestion for biogas generation.',
                    'Curing into soil conditioner or bio-fertiliser.',
                ],
                'waste_handling_methods' => 'Use sealed food-grade bins, keep cool or shaded, and schedule frequent pickups to prevent pests and leachate.',
                'environmental_impact' => 'Organic waste sent to landfills produces leachate that can contaminate groundwater and emits methane gas during anaerobic decay.',
                'carbon_saving_estimate' => 'Diverting organic waste to composting or controlled biogas digestion significantly reduces methane emissions compared to open landfilling; exact reductions depend on composition and treatment method.',
                'disposal_warning' => 'Leaving organic waste unsealed attracts pests, causes odour nuisance, and pollutes storm drains with organic leachate.',
                'owner_actions' => [
                    'Remove all plastic bags, containers, and utensils from food waste.',
                    'Drain excess liquids before placing into sealed organic bins.',
                    'Wash collection bins regularly to maintain hygiene.',
                    'Coordinate regular pickups with a certified organic waste processor.',
                ],
                'other_recommendations' => 'Track recurring food waste batches to help reduce over-ordering or preparation losses.',
                'compliance_note' => 'Handled under Malaysian Solid Waste and Public Cleansing Management Act 2007 (Act 672) commercial waste guidelines.',
            ];
        }

        // 7. Default / General Non-Reusable Industrial Material
        return [
            'strategy' => 'Material Characterization, Segregation & Licensed Waste Recovery',
            'primary_path' => 'Materials Recovery & Compliant Disposal',
            'action_recommendation' => 'Segregate cleanly by material type and verify acceptance with a licensed recovery facility',
            'recommended_next_steps' => [
                'Identify the exact material composition and check for any hazardous contamination.',
                'Segregate from general office or kitchen waste to prevent cross-contamination.',
                'Contact a licensed Malaysian waste management or recovery facility to verify acceptance.',
            ],
            'repurpose_ideas' => [
                'Check whether clean sub-components can be separated and reused internally.',
                'Review production processes to minimize future offcuts or spoilage.',
            ],
            'recycling_steps' => [
                'Sort and label materials clearly by composition.',
                'Remove foreign contaminants such as oil, tape, or mixed rubbish.',
                'Transfer to a verified recovery or licensed waste management facility.',
            ],
            'waste_handling_methods' => 'Store securely in labelled, weather-protected containers and engage a licensed waste contractor if no specific recycling pathway is available.',
            'environmental_impact' => 'Unsegregated industrial waste increases landfill burden and risks environmental pollution if hazardous substances are present.',
            'carbon_saving_estimate' => 'Separating recoverable fractions for recycling generally helps reduce lifecycle greenhouse gas emissions compared to landfilling and virgin production, though exact impacts cannot be quantified without material-specific lab data.',
            'disposal_warning' => 'Never burn or illegally dump industrial waste; always verify whether materials require scheduled waste licensing.',
            'owner_actions' => [
                'Separate materials according to type and cleanliness.',
                'Clean or drain loose contaminants where safe to do so.',
                'Store materials safely in labelled containers away from drains.',
                'Contact suitable licensed recycling or waste disposal contractors to verify acceptance.',
            ],
            'other_recommendations' => 'Keep disposal receipts and waste logs for your business environmental records.',
            'compliance_note' => 'Verify whether the material falls under general commercial solid waste (Act 672) or DOE Scheduled Waste (Act 127).',
        ];
    }

    private function getPriceGuidance(string $category, string $itemName): string
    {
        if (str_contains($category, 'plastic')) {
            return "Current Malaysian market scrap rates for **{$itemName}**:\n\n" .
                "• **Clean Industrial HDPE Regrind:** RM 1.80 – RM 2.80 / kg\n" .
                "• **PP Injection Purge / Lumps:** RM 1.10 – RM 1.70 / kg\n" .
                "• **Clean Stretch Film (LDPE Bales):** RM 1.20 – RM 1.90 / kg\n" .
                "• **Mixed / Contaminated Flake:** RM 0.50 – RM 0.80 / kg\n\n" .
                "💡 *Tip:* Keeping colors separated (e.g. natural/transparent vs colored) increases market value by 25-40%.";
        }

        if (str_contains($category, 'metal')) {
            return "Current Malaysian industrial scrap metal benchmarks for **{$itemName}**:\n\n" .
                "• **Bare Bright Copper Wire:** RM 32.00 – RM 37.00 / kg\n" .
                "• **Clean Extruded Aluminum (Alloy 6063):** RM 7.50 – RM 9.20 / kg\n" .
                "• **Brass Swarf & Rod Ends:** RM 18.00 – RM 22.00 / kg\n" .
                "• **Heavy Melting Steel (HMS 1 & 2):** RM 1.25 – RM 1.65 / kg\n\n" .
                "💡 *Tip:* Degreasing cutting oils before delivery avoids deduction fees at the smelting scale.";
        }

        if (str_contains($category, 'paper') || str_contains($category, 'packaging')) {
            return "Current Malaysian paper & packaging mill rates for **{$itemName}**:\n\n" .
                "• **Old Corrugated Cartons (OCC Bales):** RM 420 – RM 620 / tonne (RM 0.42 – 0.62/kg)\n" .
                "• **Clean White Office Trim / Cutting:** RM 650 – RM 850 / tonne\n" .
                "• **Mixed Industrial Paper Waste:** RM 250 – RM 380 / tonne\n\n" .
                "💡 *Tip:* Baled cardboard delivers nearly double the net return of loose cardboard due to haulage compaction.";
        }

        return "Scrap market prices in Malaysia fluctuate weekly based on international LME (London Metal Exchange) and ICIS petrochemical indices. For **{$itemName}**, certified recyclers typically price according to purity, volume (>500kg yields premium), and moisture level.";
    }

    private function getPreparationGuidance(string $category, string $itemName): string
    {
        return "To maximize circular value and ensure facility acceptance for **{$itemName}**:\n\n" .
            "1. **Segregation:** Store by single material grade. Mixing HDPE with PVC or copper with iron drastically lowers batch price.\n" .
            "2. **Decontamination:** Remove external shrink wrap, strapping bands, adhesives, and liquids.\n" .
            "3. **Dry Storage:** Ensure material is kept under sheltered warehouse roofing to prevent moisture absorption and fungal degradation.\n" .
            "4. **Packaging for Transport:** Use standard 1-tonne jumbo bags (FIBC) or strapped pallets so forklift loading takes under 15 minutes.\n" .
            "5. **Documentation:** Keep weight tickets and dispatch records for your business ESG & carbon accounting.";
    }

    private function getRegulatoryGuidance(string $category, string $itemName): string
    {
        if (str_contains($category, 'chemical') || str_contains($category, 'electronic')) {
            return "⚠️ **DOE Environmental Quality Act 1974 (Scheduled Wastes) Regulations 2005:**\n\n" .
                "• E-waste is categorized under **SW110**.\n" .
                "• Chemical solvents, acids, and contaminated containers are categorized under **SW300 / SW400 series**.\n" .
                "• Requirements:\n" .
                "  1. Must only be transferred to a **DOE-licensed transporter**.\n" .
                "  2. Destination facility must hold a valid **DOE Section 18 / 19 license** (e.g. Cenviro, Shan Poornam).\n" .
                "  3. Online e-Consignment notification must be generated via the **DOE Electronic Scheduled Waste Information System (e-SWIS)**.\n" .
                "  4. On-site storage cannot exceed 20 metric tonnes or 180 days without director-general approval.";
        }

        return "✅ **Malaysian Non-Scheduled Waste Guidelines (Act 672):**\n\n" .
            "Clean secondary industrial materials such as plastics, metals, paper, and timber cutoffs are classed as **non-scheduled commercial recyclables**. You are legally permitted to trade, donate, or sell them to certified circular recovery facilities without specialized DOE hazardous waste permits.";
    }

    private function getRepurposingIdeas(string $category, string $itemName): string
    {
        return "💡 **High-Value Circular Repurposing Ideas for {$itemName}:**\n\n" .
            "1. **Internal Secondary Utilization:** Can this material be used for protective tooling fixtures, pallet dunnage, or internal shopfloor organization?\n" .
            "2. **Cross-Industry Exchange:** List on **EcoCycle Reusable Marketplace** under *Take for Free* or *Discounted Buy* — another SME in packaging or construction might use your offcuts directly as primary raw stock.\n" .
            "3. **Downcycling to Composite Materials:** If damaged or off-spec, shredding into filler for asphalt, composite lumber, or concrete polymer matrices delivers 100% diversion from landfills.";
    }
}
