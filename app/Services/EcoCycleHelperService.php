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
        string $condition = 'Used'
    ): array {
        $imageParts = $this->extractInlineImageParts($images);

        if (empty($imageParts)) {
            throw new \RuntimeException('Uploaded material photos could not be processed. Please upload 1 to 3 valid images and retry analysis.');
        }

        if (! empty(config('services.gemini.api_key'))) {
            $geminiResult = $this->classifyWithGemini($name, $description, $quantity, $unit, $condition, $imageParts);
            if ($geminiResult === null) {
                throw new \RuntimeException('Material analysis failed while processing your images. Please try again.');
            }

            return $geminiResult;
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

        $configuredModel = config('services.gemini.model', 'gemini-2.5-flash');
        $modelsToTry = array_values(array_unique(array_filter([
            $configuredModel,
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-3.1-flash-lite',
            'gemini-flash-lite-latest',
        ])));

        foreach ($modelsToTry as $model) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            try {
                $response = Http::timeout(30)->post($endpoint, [
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
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (! empty($text)) {
                        return $text;
                    }
                }

                Log::warning("Gemini multimodal model {$model} returned non-200 status", [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 300),
                ]);
            } catch (\Throwable $e) {
                Log::warning("Gemini multimodal model {$model} failed: " . $e->getMessage());
            }
        }

        return null;
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
   - "reusable": If the material visible in the photo(s) is structurally sound, clean, durable, multi-use, or can be directly reused, repurposed, or resold to other Malaysian businesses (e.g. intact wooden pallets, clean cardboard boxes, metal bars/sheets, working parts, surplus fabric rolls, reusable drums/containers).
   - "not_reusable": If the material visible in the photo(s) is severely damaged/broken beyond reuse, single-use cutoffs, food/organic waste, contaminated, shredded, slag, sludge, hazardous waste, or degraded scrap that cannot be directly reused without industrial recycling, smelting, granulation, composting, or licensed disposal.

3. Provide a clear, concise reason referencing the observed material condition and visual state explaining why it is classified as Reusable or Non-Reusable.

4. Provide 3 specific recommended next actions.

5. Provide recycling and repurposing guidance.

Return ONLY a valid JSON object matching this schema (no markdown backticks, no markdown formatting):
{
  "suggested_category": "Dynamic concise Title Case material category name",
  "category_confidence": "high",
  "suggested_reusability": "reusable",
  "classification_label": "Reusable",
  "reason": "Clear explanation based on the photos, condition, and specifications",
  "recommended_actions": [
    "Recommended action 1",
    "Recommended action 2",
    "Recommended action 3"
  ],
  "recycling_recommendations": "Concrete recycling steps and processes",
  "repurposing_ideas": [
    "Upcycling or repurposing idea 1",
    "Upcycling or repurposing idea 2"
  ],
  "disposal_method": "Compliant disposal and landfill diversion guidance"
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

        return [
            'suggested_category' => $category,
            'category_confidence' => $confidence,
            'needs_category_review' => ($confidence === 'low' || $category === ''),
            'suggested_reusability' => $reusability,
            'classification_label' => $label,
            'reason' => $decoded['reason'] ?? 'Material evaluated based on uploaded photos and specifications.',
            'recommended_actions' => (array) ($decoded['recommended_actions'] ?? []),
            'recycling_recommendations' => $decoded['recycling_recommendations'] ?? 'Recycle with certified recovery partners.',
            'repurposing_ideas' => (array) ($decoded['repurposing_ideas'] ?? []),
            'disposal_method' => $decoded['disposal_method'] ?? 'Divert from landfill into certified circular channels.',
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
            'reason' => $reason,
            'recommended_actions' => $actions,
            'recycling_recommendations' => $recycling,
            'repurposing_ideas' => $repurposing,
            'disposal_method' => $disposal,
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

        $configuredModel = config('services.gemini.model', 'gemini-2.5-flash');
        $modelsToTry = array_values(array_unique(array_filter([
            $configuredModel,
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-3.1-flash-lite',
            'gemini-flash-lite-latest',
        ])));

        foreach ($modelsToTry as $model) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            try {
                $response = Http::timeout(10)->post($endpoint, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => 1500,
                    ],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (! empty($text)) {
                        return $text;
                    }
                }

                Log::warning("Gemini text model {$model} returned non-200 status", [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 300),
                ]);
            } catch (\Throwable $e) {
                Log::warning("Gemini text model {$model} failed: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Analyze non-reusable scrap using Google Gemini API
     */
    private function analyzeWithGemini(string $name, string $category, string $description, string $condition, float $quantity, string $unit, string $reusability): ?array
    {
        $prompt = <<<PROMPT
You are EcoCycle AI, an expert industrial circular economy and waste recovery consultant in Malaysia.
Analyze the following industrial material/scrap:

Material Name: {$name}
Category: {$category}
Description: {$description}
Condition: {$condition}
Quantity: {$quantity} {$unit}
Classification: Non-reusable / Scrap

Provide an actionable, technical circular economy analysis formatted STRICTLY as a raw JSON object (no markdown wrapping, no code fences) with the exact following keys:
{
  "strategy": "Primary technical circular strategy title (string)",
  "primary_path": "Short primary recovery pathway name e.g. Mechanical Repelletizing, Secondary Smelting, Industrial Composting (string)",
  "action_recommendation": "Main recommended action e.g. Shred & Granulate into Flake, Smelt into Ingot, Digest into Biogas (string)",
  "repurpose_ideas": ["3 practical upcycling or repurposing ideas as an array of strings"],
  "recycling_steps": ["Step 1: ...", "Step 2: ...", "Step 3: ...", "Step 4: ...", "Step 5: ..."],
  "disposal_warning": "Environmental hazard or landfill diversion note (string)",
  "estimated_value_myr": "Estimated scrap price benchmark range in Malaysian Ringgit per unit/kg (string)",
  "carbon_saving_estimate": "Estimated kg of CO2e avoided by recycling instead of virgin production or landfilling (string)",
  "compliance_note": "Malaysian Department of Environment (DOE) / Act 672 / Scheduled Waste regulatory compliance guidance (string)"
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
            'action_recommendation' => $decoded['action_recommendation'] ?? 'Recycle into secondary raw material',
            'repurpose_ideas' => (array) ($decoded['repurpose_ideas'] ?? []),
            'recycling_steps' => (array) ($decoded['recycling_steps'] ?? []),
            'disposal_warning' => $decoded['disposal_warning'] ?? 'Divert from landfill into certified recycling streams.',
            'estimated_value_myr' => $decoded['estimated_value_myr'] ?? 'Subject to market scrap pricing',
            'carbon_saving_estimate' => $decoded['carbon_saving_estimate'] ?? 'Significant carbon footprint reduction achieved by circular recovery.',
            'compliance_note' => $decoded['compliance_note'] ?? 'Comply with Malaysian environmental regulations.',
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
                'strategy' => 'Mechanical Grinding, Optical Sorting & Hot-Melt Repelletizing',
                'primary_path' => 'Industrial Plastic Recovery',
                'action_recommendation' => 'Recycle / Extrude into Post-Industrial Recycled (PIR) Resin',
                'repurpose_ideas' => [
                    'Shred and mold into durable secondary transport pallets or warehouse storage bins.',
                    'Extrude into commercial drainage piping, agricultural irrigation tubing, or electrical conduit.',
                    'Compound with mineral filler for composite lumber and outdoor decking tiles.',
                ],
                'recycling_steps' => [
                    'Step 1: Polymer Segregation — Verify resin code (PET #1, HDPE #2, PP #5). Avoid cross-polymer mixing.',
                    'Step 2: Decontamination — Remove tape, adhesive stickers, and metallic inserts.',
                    'Step 3: Size Reduction — Granulate with a heavy-duty industrial granulator to 8-10mm regrind flake.',
                    'Step 4: Density Wash Bath — Float-sink separation to discard dirt and heavy contaminants.',
                    'Step 5: Extrusion Pelleting — Melt-filter and strand pelletize for blending with virgin resin (up to 30-50%).',
                ],
                'disposal_warning' => 'Do NOT dispose via landfill or open combustion. Clean thermoplastics retain 70-85% of mechanical tensile strength when recycled.',
                'estimated_value_myr' => 'RM 1.20 - RM 2.80 per kg (Clean regrind / flake)',
                'carbon_saving_estimate' => 'Approx. ' . round($qty * 1.8, 1) . ' kg CO₂e avoided compared to virgin petrochemical synthesis.',
                'compliance_note' => 'Non-scheduled industrial solid waste under Act 672. Clean production cutoffs are exempt from Scheduled Waste (SW) licensing.',
            ];
        }

        // 2. Metals
        if (str_contains($cat, 'metal') || str_contains($text, 'metal') || str_contains($text, 'steel') || str_contains($text, 'iron') || str_contains($text, 'copper') || str_contains($text, 'aluminium') || str_contains($text, 'aluminum') || str_contains($text, 'brass') || str_contains($text, 'swarf')) {
            $isNonFerrous = str_contains($text, 'copper') || str_contains($text, 'aluminium') || str_contains($text, 'aluminum') || str_contains($text, 'brass');
            return [
                'strategy' => 'Magnetic / Eddy Current Separation and Secondary Induction Smelting',
                'primary_path' => 'Continuous Scrap Smelting & Ingot Casting',
                'action_recommendation' => 'Smelt / Re-roll into High-Grade Structural or Alloy Stock',
                'repurpose_ideas' => [
                    'Structural offcuts: Fabricate equipment brackets, machine baseplates, or workshop shelving frames.',
                    'Clean sheet offcuts: Stamp into washers, reinforcement shims, or cable duct covers.',
                    'Turnings/swarf: Briquette with hydraulic compactor for direct furnace charging.',
                ],
                'recycling_steps' => [
                    'Step 1: Oil Removal — Centrifuge or degrease swarf to remove cutting fluids and machining coolants.',
                    'Step 2: Magnetic Sorting — Separate ferrous steel/iron from non-ferrous aluminum/copper/brass.',
                    'Step 3: High-Density Baling — Compress sheet metal cutoffs into uniform 30x30cm furnace charge cubes.',
                    'Step 4: Induction Melting — Re-melt in electric induction furnace with minimal oxidation slag loss (<3%).',
                ],
                'disposal_warning' => 'Metals are infinitely recyclable. Zero material degradation over infinite remelting cycles.',
                'estimated_value_myr' => $isNonFerrous ? 'RM 12.00 - RM 28.00 per kg (Non-ferrous copper/aluminum/brass)' : 'RM 1.10 - RM 1.80 per kg (Heavy melting steel / plate)',
                'carbon_saving_estimate' => 'Approx. ' . round($qty * 2.5, 1) . ' kg CO₂e saved compared to iron ore blast furnace extraction.',
                'compliance_note' => 'Oily swarf containing cutting fluid may be classified as SW305/SW306 if oil content exceeds threshold. Ensure oil draining before transport.',
            ];
        }

        // 3. Packaging & Paper / Cardboard
        if (str_contains($cat, 'paper') || str_contains($cat, 'packaging') || str_contains($text, 'carton') || str_contains($text, 'box') || str_contains($text, 'cardboard') || str_contains($text, 'paper')) {
            return [
                'strategy' => 'Hydrapulping, De-inking & Secondary Kraft Paper Board Milling',
                'primary_path' => 'Repulping & Corrugated Core Manufacturing',
                'action_recommendation' => 'Hydrapulp into Recycled Fluting & Kraft Medium',
                'repurpose_ideas' => [
                    'Perforate with cardboard shredder to produce high-protective void fill packaging cushioning.',
                    'Reuse sturdy carton outer boxes for warehouse inter-facility product transfer.',
                    'Die-cut clean sheets into divider partitions for breakable component shipping.',
                ],
                'recycling_steps' => [
                    'Step 1: Dry Contamination Segregation — Ensure moisture content < 12%. Remove wax-coated or oily food paper.',
                    'Step 2: De-taping — Strip wide vinyl packing tape and heavy metal box staples.',
                    'Step 3: Compact Baling — Bale into 300kg industrial mill cubes to optimize flatbed transport economics.',
                    'Step 4: Hydrapulper Batching — Agitate with water into cellulose slurry, screen out residual fibers.',
                ],
                'disposal_warning' => 'Keep completely sheltered from rain. Wet cardboard rapidly degrades cellulose fiber tensile index.',
                'estimated_value_myr' => 'RM 0.35 - RM 0.65 per kg (Old Corrugated Cartons - OCC / Kraft)',
                'carbon_saving_estimate' => 'Approx. ' . round($qty * 1.3, 1) . ' kg CO₂e avoided and ' . round($qty * 0.017, 2) . ' mature trees preserved.',
                'compliance_note' => 'General industrial recyclable solid waste. No scheduled waste license required.',
            ];
        }

        // 4. Timber & Wood
        if (str_contains($cat, 'timber') || str_contains($cat, 'wood') || str_contains($text, 'timber') || str_contains($text, 'wood') || str_contains($text, 'pallet') || str_contains($text, 'plywood') || str_contains($text, 'sawdust')) {
            return [
                'strategy' => 'Chipping, De-nailing, Biomass Pelleting or Secondary Pallet Refurbishment',
                'primary_path' => 'Industrial Biomass Fuel or Composite Wood Boards',
                'action_recommendation' => 'Refurbish Pallets or Chip into Boiler Biomass Pellets',
                'repurpose_ideas' => [
                    'Pallet planks: Replace cracked bottom deckboards to restore damaged pallets back to circulation.',
                    'Heavy timber balks: Cut into dunnage chocks and forklift support skids.',
                    'Clean wood chips: Compress into heat-treated animal bedding or landscape mulch.',
                ],
                'recycling_steps' => [
                    'Step 1: Metal Detection — Pass over magnetic roller to extract hidden nails, staples, and screws.',
                    'Step 2: Drum Chipping — Chip offcuts into 20-50mm woodchips.',
                    'Step 3: Moisture Conditioning — Rotary kiln dry to 10-12% target moisture.',
                    'Step 4: Ring-Die Extrusion — Compress through 8mm die into high-calorific bio-energy wood pellets (4,200 kcal/kg).',
                ],
                'disposal_warning' => 'Prohibited from open burning under Environmental Quality Act (Clean Air Regulations).',
                'estimated_value_myr' => 'RM 12.00 - RM 25.00 per refurbished pallet / RM 280 - RM 360 per tonne biomass chip',
                'carbon_saving_estimate' => 'Approx. ' . round($qty * 1.1, 1) . ' kg CO₂e sequestered in durable circular products.',
                'compliance_note' => 'Treated timber with CCA (Copper Chrome Arsenic) must be handled separately from clean wood waste.',
            ];
        }

        // 5. Electronics / E-Waste
        if (str_contains($cat, 'electronic') || str_contains($text, 'electronic') || str_contains($text, 'circuit') || str_contains($text, 'pcb') || str_contains($text, 'battery') || str_contains($text, 'cable') || str_contains($text, 'e-waste')) {
            return [
                'strategy' => 'Certified Pyrometallurgical & Hydrometallurgical Precious Metal Refining',
                'primary_path' => 'DOE Licensed E-Waste Precious Metal Recovery',
                'action_recommendation' => 'Transfer to Licensed Scheduled Waste Recovery Facility (SW110)',
                'repurpose_ideas' => [
                    'Functional testing: Recover high-value components (IC chips, relays, power transistors, heat sinks).',
                    'Wire harnesses: Strip insulation with mechanical wire stripper to yield 99.9% bare bright copper.',
                    'Chassis casings: Separate polycarbonate/ABS and aluminum enclosures for raw material recycling.',
                ],
                'recycling_steps' => [
                    'Step 1: Manual Demanufacturing — Remove hazardous batteries, mercury switches, and capacitors.',
                    'Step 2: Multi-Stage Shredding — Two-shaft shredder followed by hammer mill granulation.',
                    'Step 3: Electrostatic Separation — Separate non-conductive fiberglass substrate from copper/tin tracks.',
                    'Step 4: Chemical Leaching / Electrowinning — Gold, silver, and palladium recovery in closed-loop hydrometallurgy.',
                ],
                'disposal_warning' => 'CRITICAL: Must NEVER be sent to municipal landfills. Strict DOE enforcement under Act 127.',
                'estimated_value_myr' => 'RM 8.00 - RM 45.00 per kg depending on gold/copper plating grade',
                'carbon_saving_estimate' => 'Approx. ' . round($qty * 3.8, 1) . ' kg CO₂e saved compared to primary mineral mining.',
                'compliance_note' => 'Classified as Scheduled Waste (SW110). Consignment note (e-Consignment) required via DOE ASIS system.',
            ];
        }

        // 6. Default / General Industrial Material
        return [
            'strategy' => 'Industrial Materials Recovery Facility (MRF) Mechanical Classification',
            'primary_path' => 'Solid Material Separation & Secondary Feedstock Recovery',
            'action_recommendation' => 'Segregate into Monostreams for Circular Remanufacturing',
            'repurpose_ideas' => [
                'Audit process scrap generation to minimize raw material trimming loss at source.',
                'Exchange on EcoCycle Reusable Marketplace with matching manufacturing partners.',
                'Explore Refuse-Derived Fuel (RDF) energy recovery for non-recyclable combustible fractions.',
            ],
            'recycling_steps' => [
                'Step 1: Characterize Material Composition — Determine exact resin, alloy, fiber, or chemical makeup.',
                'Step 2: Clean Storage — Segregate from general plant garbage in weather-protected bins.',
                'Step 3: Route via GPS Recycling Locator — Match with certified Malaysian circular processors.',
            ],
            'disposal_warning' => 'Ensure complete separation from organic and contaminated waste streams.',
            'estimated_value_myr' => 'Assessed upon batch inspection by specialized circular recycler',
            'carbon_saving_estimate' => 'Approx. ' . round($qty * 1.2, 1) . ' kg CO₂e avoided through landfill diversion.',
            'compliance_note' => 'Verify whether material is general commercial solid waste (Act 672) or Scheduled Waste (Act 127).',
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
