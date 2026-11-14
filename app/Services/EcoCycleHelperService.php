<?php

namespace App\Services;

use App\Models\Item;

class EcoCycleHelperService
{
    /**
     * Analyze an item or material and produce structured circular economy recommendations.
     *
     * @param  array<string, mixed>|Item  $item
     * @return array<string, mixed>
     */
    public function analyze(array|Item $item): array
    {
        $name = is_array($item) ? ($item['name'] ?? $item['title'] ?? 'Material') : ($item->name ?: $item->title ?: 'Material');
        $category = is_array($item) ? ($item['category'] ?? 'General') : ($item->category ?? 'General');
        $description = is_array($item) ? ($item['description'] ?? '') : ($item->description ?? '');
        $condition = is_array($item) ? ($item['condition'] ?? 'Used - Good') : ($item->condition ?? 'Used - Good');
        $quantity = (float) (is_array($item) ? ($item['quantity'] ?? 1) : ($item->quantity ?? 1));
        $unit = is_array($item) ? ($item['unit'] ?? 'units') : ($item->unit ?? 'units');
        $reusability = is_array($item) ? ($item['reusability'] ?? 'reusable') : ($item->reusability ?? 'reusable');
        $images = is_array($item) ? ($item['images'] ?? []) : ($item->getAllImages() ?? []);

        $normCategory = mb_strtolower(trim($category));
        $normName = mb_strtolower(trim($name));
        $combinedText = $normName . ' ' . mb_strtolower($description);

        $analysis = $this->determineAnalysisProfile($normCategory, $combinedText, $condition, $quantity, $unit, $reusability);

        return array_merge([
            'item_name' => $name,
            'category' => $category,
            'quantity_formatted' => "{$quantity} {$unit}",
            'condition' => $condition,
            'reusability' => $reusability,
            'has_images' => ! empty($images),
            'image_count' => count($images),
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
