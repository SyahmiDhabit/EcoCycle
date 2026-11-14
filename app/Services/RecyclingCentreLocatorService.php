<?php

namespace App\Services;

class RecyclingCentreLocatorService
{
    /**
     * Curated directory of Malaysian licensed recycling centres, industrial MRFs, and recovery facilities.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getCentres(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Daikkin Plastics Circular Reprocessing Sdn Bhd',
                'category' => 'Plastics',
                'accepted_materials' => ['Plastics', 'Packaging & paper', 'Industrial Scrap'],
                'subtypes' => ['HDPE', 'LDPE', 'PP', 'PET', 'Nylon', 'Rigid Polymers', 'Purge Lumps'],
                'address' => 'Persiaran Kuala Selangor, Seksyen 26, 40400 Shah Alam, Selangor',
                'city' => 'Shah Alam',
                'state' => 'Selangor',
                'phone' => '+60 3-5192 8820',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 6:00 PM',
                'license' => 'DOE Licensed Industrial Plastic Recycler (SW401/402)',
                'lat' => 3.0215,
                'lng' => 101.5540,
                'is_certified' => true,
            ],
            [
                'id' => 2,
                'name' => 'Southern Steel Circular Recovery Hub',
                'category' => 'Metals',
                'accepted_materials' => ['Metals', 'Construction & demolition', 'Industrial Scrap'],
                'subtypes' => ['Ferrous Scrap', 'Steel Rebar', 'Structural Beams', 'Cast Iron', 'Metal Offcuts'],
                'address' => 'Jalan Kapar, Batu 4, 42100 Klang, Selangor',
                'city' => 'Klang',
                'state' => 'Selangor',
                'phone' => '+60 3-3291 5560',
                'operating_hours' => 'Mon - Fri: 8:00 AM - 5:30 PM, Sat: 8:00 AM - 1:00 PM',
                'license' => 'MITI & DOE Registered Metal Smelter & Re-roller',
                'lat' => 3.0674,
                'lng' => 101.3921,
                'is_certified' => true,
            ],
            [
                'id' => 3,
                'name' => 'Shan Poornam Metals (Green Technology Division)',
                'category' => 'Electronics',
                'accepted_materials' => ['Electronics', 'Metals', 'Chemicals'],
                'subtypes' => ['E-Waste', 'PCB Boards', 'Copper Cables', 'Precious Metal Sludge', 'Lithium/Lead Batteries'],
                'address' => 'Plot 12, Jalan Perusahaan, Kawasan Perusahaan Perai, 13600 Perai, Penang',
                'city' => 'Perai',
                'state' => 'Penang',
                'phone' => '+60 4-399 8833',
                'operating_hours' => 'Mon - Fri: 8:30 AM - 5:30 PM',
                'license' => 'DOE Licensed Scheduled Waste Recovery (SW110/SW206)',
                'lat' => 5.3812,
                'lng' => 100.4045,
                'is_certified' => true,
            ],
            [
                'id' => 4,
                'name' => 'Shan Poornam Metals (Selangor Regional Depot)',
                'category' => 'Electronics',
                'accepted_materials' => ['Electronics', 'Metals'],
                'subtypes' => ['E-Waste', 'Computer Scrap', 'Wiring Harnesses', 'Alloy Offcuts'],
                'address' => 'Kawasan Perindustrian Bukit Raja, 41050 Klang, Selangor',
                'city' => 'Klang',
                'state' => 'Selangor',
                'phone' => '+60 3-3344 1120',
                'operating_hours' => 'Mon - Sat: 8:30 AM - 5:00 PM',
                'license' => 'DOE Licensed E-Waste Collection & Recovery Centre',
                'lat' => 3.0722,
                'lng' => 101.4645,
                'is_certified' => true,
            ],
            [
                'id' => 5,
                'name' => 'Alam Flora Environmental Solutions (AFES) Integrated MRF',
                'category' => 'General',
                'accepted_materials' => ['Plastics', 'Packaging & paper', 'Glass', 'Metals', 'Industrial Scrap'],
                'subtypes' => ['Corrugated Carton', 'Plastic Containers', 'Scrap Paper', 'Aluminum Cans', 'Pallets'],
                'address' => 'Jalan Subang 3, Kawasan Perindustrian USJ 1, 47600 Subang Jaya, Selangor',
                'city' => 'Subang Jaya',
                'state' => 'Selangor',
                'phone' => '+60 3-8025 8000',
                'operating_hours' => 'Daily: 8:00 AM - 7:00 PM',
                'license' => 'SWCorp & DOE Certified Materials Recovery Facility',
                'lat' => 3.0485,
                'lng' => 101.5932,
                'is_certified' => true,
            ],
            [
                'id' => 6,
                'name' => 'Petaling Jaya Eco Recycling Plaza (MBPJ)',
                'category' => 'General',
                'accepted_materials' => ['Plastics', 'Packaging & paper', 'Textiles', 'Timber', 'Glass'],
                'subtypes' => ['Timber Cutoffs', 'Fabric Scrap', 'Paper Pulp', 'Recyclable Plastics', 'Glass Cullet'],
                'address' => 'Jalan SS 8/39, Kawasan Perindustrian Bebas Sungai Way, 47300 Petaling Jaya, Selangor',
                'city' => 'Petaling Jaya',
                'state' => 'Selangor',
                'phone' => '+60 3-7954 1440',
                'operating_hours' => 'Tue - Sun: 9:00 AM - 5:00 PM',
                'license' => 'Local Authority Circular Hub & Waste Diversion Centre',
                'lat' => 3.0924,
                'lng' => 101.6143,
                'is_certified' => true,
            ],
            [
                'id' => 7,
                'name' => 'Muda Holdings Paper Recycling Mills',
                'category' => 'Packaging & paper',
                'accepted_materials' => ['Packaging & paper'],
                'subtypes' => ['Old Corrugated Cartons (OCC)', 'Industrial Paper Trim', 'Kraft Paper', 'Cardboard Cores'],
                'address' => 'Lot 7, Jalan Tasek, Kawasan Perindustrian Tasek, 31400 Ipoh, Perak',
                'city' => 'Ipoh',
                'state' => 'Perak',
                'phone' => '+60 5-546 8811',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 5:00 PM',
                'license' => 'DOE Certified Paper & Packaging Pulp Mill',
                'lat' => 4.6394,
                'lng' => 101.1092,
                'is_certified' => true,
            ],
            [
                'id' => 8,
                'name' => 'Thong Guan Green Polyolefin Reclaiming Centre',
                'category' => 'Plastics',
                'accepted_materials' => ['Plastics', 'Packaging & paper'],
                'subtypes' => ['Stretch Film', 'Polyethylene Bags', 'Shrink Wrap', 'Plastic Strapping'],
                'address' => 'Plot 52, Jalan Bakar Arang, 08000 Sungai Petani, Kedah',
                'city' => 'Sungai Petani',
                'state' => 'Kedah',
                'phone' => '+60 4-421 3321',
                'operating_hours' => 'Mon - Fri: 8:00 AM - 5:00 PM',
                'license' => 'Certified Post-Industrial Film Recycler',
                'lat' => 5.6268,
                'lng' => 100.4851,
                'is_certified' => true,
            ],
            [
                'id' => 9,
                'name' => 'Pasir Gudang Circular Scrap & Drum Recovery',
                'category' => 'Metals',
                'accepted_materials' => ['Metals', 'Chemicals', 'Industrial Scrap'],
                'subtypes' => ['Steel Drums (200L)', 'IBC Totes', 'Heavy Equipment Scrap', 'Pipe Sections', 'Brass/Copper'],
                'address' => 'Jalan Perigi Nenas, Kawasan Perindustrian Pasir Gudang, 81700 Pasir Gudang, Johor',
                'city' => 'Pasir Gudang',
                'state' => 'Johor',
                'phone' => '+60 7-251 7722',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 6:00 PM',
                'license' => 'DOE Licensed Industrial Container Washing & Metal Scrap Hub',
                'lat' => 1.4641,
                'lng' => 103.9064,
                'is_certified' => true,
            ],
            [
                'id' => 10,
                'name' => 'Eco-Max Polymers Recycling Hub',
                'category' => 'Plastics',
                'accepted_materials' => ['Plastics', 'Textiles'],
                'subtypes' => ['Industrial Crates', 'Injection Mold Scrap', 'Nylon Yarn Waste', 'PP Pellets'],
                'address' => 'Kawasan Perindustrian Tebrau II, 81100 Johor Bahru, Johor',
                'city' => 'Johor Bahru',
                'state' => 'Johor',
                'phone' => '+60 7-355 4488',
                'operating_hours' => 'Mon - Fri: 8:30 AM - 5:30 PM',
                'license' => 'DOE Licensed Plastics Reprocessor',
                'lat' => 1.5367,
                'lng' => 103.7915,
                'is_certified' => true,
            ],
            [
                'id' => 11,
                'name' => 'Cenviro / Kualiti Alam Environmental Solutions',
                'category' => 'Chemicals',
                'accepted_materials' => ['Chemicals', 'Industrial Scrap'],
                'subtypes' => ['Scheduled Waste', 'Chemical Residues', 'Industrial Sludge', 'Contaminated Rags/Drums', 'Heavy Solvents'],
                'address' => 'Ladang Tanah Merah, A33, 71960 Bukit Pelandok, Negeri Sembilan',
                'city' => 'Bukit Pelandok',
                'state' => 'Negeri Sembilan',
                'phone' => '+60 6-658 1000',
                'operating_hours' => '24/7 Receiving by Appointment',
                'license' => 'National Concessionaire for Scheduled Waste Management',
                'lat' => 2.6514,
                'lng' => 101.7645,
                'is_certified' => true,
            ],
            [
                'id' => 12,
                'name' => 'Timber Circular Bio-Pellet & Pallet Reclaiming',
                'category' => 'Timber',
                'accepted_materials' => ['Timber', 'Construction & demolition'],
                'subtypes' => ['Wood Offcuts', 'Broken Pallets', 'Sawdust', 'Plywood Trim', 'Crates'],
                'address' => 'Kawasan Perindustrian Rawang Intermix, 48000 Rawang, Selangor',
                'city' => 'Rawang',
                'state' => 'Selangor',
                'phone' => '+60 3-6091 9922',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 5:00 PM',
                'license' => 'Timber Board Registered Biomass & Pallet Refurbisher',
                'lat' => 3.3214,
                'lng' => 101.5794,
                'is_certified' => true,
            ],
            [
                'id' => 13,
                'name' => 'Kuching Integrated Waste & Material Recovery Centre',
                'category' => 'General',
                'accepted_materials' => ['Metals', 'Plastics', 'Packaging & paper', 'Timber', 'Industrial Scrap'],
                'subtypes' => ['Ferrous Metals', 'Scrap Plastics', 'Industrial Packaging', 'Bio-waste'],
                'address' => 'Jalan Mambong, 93250 Kuching, Sarawak',
                'city' => 'Kuching',
                'state' => 'Sarawak',
                'phone' => '+60 82-628 333',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 5:00 PM',
                'license' => 'NREB Sarawak Certified Industrial Waste Facility',
                'lat' => 1.3912,
                'lng' => 110.3124,
                'is_certified' => true,
            ],
            [
                'id' => 14,
                'name' => 'Kota Kinabalu Industrial Materials Recovery Station',
                'category' => 'General',
                'accepted_materials' => ['Metals', 'Plastics', 'Rubber & tires', 'Packaging & paper'],
                'subtypes' => ['Used Tires', 'Scrap Iron', 'HDPE Containers', 'Corrugated Box'],
                'address' => 'KKIP South Industrial Zone, 88450 Kota Kinabalu, Sabah',
                'city' => 'Kota Kinabalu',
                'state' => 'Sabah',
                'phone' => '+60 88-498 777',
                'operating_hours' => 'Mon - Fri: 8:00 AM - 4:30 PM',
                'license' => 'Sabah EPA Certified Scrap Recovery Hub',
                'lat' => 6.0682,
                'lng' => 116.1432,
                'is_certified' => true,
            ],
        ];
    }

    /**
     * Approximate default latitude and longitude for Malaysian cities.
     */
    public static function getCityCoordinates(string $city, string $state = ''): array
    {
        $map = [
            'shah alam' => ['lat' => 3.0738, 'lng' => 101.5183],
            'subang jaya' => ['lat' => 3.0485, 'lng' => 101.5932],
            'petaling jaya' => ['lat' => 3.1073, 'lng' => 101.6067],
            'klang' => ['lat' => 3.0449, 'lng' => 101.4456],
            'kuala lumpur' => ['lat' => 3.1390, 'lng' => 101.6869],
            'rawang' => ['lat' => 3.3214, 'lng' => 101.5794],
            'putrajaya' => ['lat' => 2.9264, 'lng' => 101.6964],
            'cyberjaya' => ['lat' => 2.9213, 'lng' => 101.6559],
            'georgetown' => ['lat' => 5.4164, 'lng' => 100.3327],
            'butterworth' => ['lat' => 5.3991, 'lng' => 100.3638],
            'perai' => ['lat' => 5.3812, 'lng' => 100.4045],
            'sungai petani' => ['lat' => 5.6435, 'lng' => 100.4900],
            'ipoh' => ['lat' => 4.5975, 'lng' => 101.0901],
            'johor bahru' => ['lat' => 1.4927, 'lng' => 103.7414],
            'pasir gudang' => ['lat' => 1.4641, 'lng' => 103.9064],
            'kulai' => ['lat' => 1.6633, 'lng' => 103.6033],
            'melaka' => ['lat' => 2.1896, 'lng' => 102.2501],
            'seremban' => ['lat' => 2.7258, 'lng' => 101.9424],
            'kuantan' => ['lat' => 3.8077, 'lng' => 103.3260],
            'kuching' => ['lat' => 1.5533, 'lng' => 110.3592],
            'bintulu' => ['lat' => 3.1823, 'lng' => 113.0443],
            'miri' => ['lat' => 4.3995, 'lng' => 113.9914],
            'kota kinabalu' => ['lat' => 5.9804, 'lng' => 116.0735],
            'sandakan' => ['lat' => 5.8402, 'lng' => 118.1179],
        ];

        $key = mb_strtolower(trim($city));
        if (isset($map[$key])) {
            return $map[$key];
        }

        $stateKey = mb_strtolower(trim($state));
        $stateFallbacks = [
            'selangor' => ['lat' => 3.0738, 'lng' => 101.5183],
            'kuala lumpur' => ['lat' => 3.1390, 'lng' => 101.6869],
            'penang' => ['lat' => 5.4164, 'lng' => 100.3327],
            'pulau pinang' => ['lat' => 5.4164, 'lng' => 100.3327],
            'johor' => ['lat' => 1.4927, 'lng' => 103.7414],
            'perak' => ['lat' => 4.5975, 'lng' => 101.0901],
            'kedah' => ['lat' => 5.6435, 'lng' => 100.4900],
            'melaka' => ['lat' => 2.1896, 'lng' => 102.2501],
            'negeri sembilan' => ['lat' => 2.7258, 'lng' => 101.9424],
            'pahang' => ['lat' => 3.8077, 'lng' => 103.3260],
            'terengganu' => ['lat' => 5.3117, 'lng' => 103.1324],
            'kelantan' => ['lat' => 6.1254, 'lng' => 102.2386],
            'sarawak' => ['lat' => 1.5533, 'lng' => 110.3592],
            'sabah' => ['lat' => 5.9804, 'lng' => 116.0735],
        ];

        return $stateFallbacks[$stateKey] ?? ['lat' => 3.0738, 'lng' => 101.5183]; // Default to Klang Valley / Shah Alam
    }

    /**
     * Compute Haversine distance in kilometers between two GPS coordinate pairs.
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 1);
    }

    /**
     * Find nearby recycling centres sorted by distance, with optional material category filter.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function findNearby(float $lat, float $lng, ?string $materialCategory = null, int $limit = 8): array
    {
        $centres = self::getCentres();
        $results = [];

        $filterCat = $materialCategory ? mb_strtolower(trim($materialCategory)) : null;

        foreach ($centres as $centre) {
            $isMatch = true;
            if ($filterCat && $filterCat !== 'all' && $filterCat !== '') {
                $centreCategory = mb_strtolower($centre['category']);
                $acceptedLower = array_map('mb_strtolower', $centre['accepted_materials']);
                $subtypesLower = array_map('mb_strtolower', $centre['subtypes']);

                $isMatch = ($centreCategory === $filterCat)
                    || in_array($filterCat, $acceptedLower, true)
                    || in_array('general', $acceptedLower, true)
                    || str_contains($centreCategory, $filterCat)
                    || str_contains(implode(' ', $acceptedLower), $filterCat)
                    || str_contains(implode(' ', $subtypesLower), $filterCat);
            }

            $dist = self::calculateDistance($lat, $lng, (float) $centre['lat'], (float) $centre['lng']);

            $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination='.urlencode("{$centre['name']}, {$centre['address']}");

            $results[] = array_merge($centre, [
                'distance_km' => $dist,
                'is_material_match' => $isMatch,
                'directions_url' => $mapsUrl,
            ]);
        }

        // Sort by material match first (matches first), then by distance ascending
        usort($results, function ($a, $b) {
            if ($a['is_material_match'] !== $b['is_material_match']) {
                return $b['is_material_match'] <=> $a['is_material_match'];
            }
            return $a['distance_km'] <=> $b['distance_km'];
        });

        return array_slice($results, 0, $limit);
    }
}
