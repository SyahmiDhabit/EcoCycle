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
            [
                'id' => 15,
                'name' => 'Alam Flora Environmental Solutions (AFES) Organic Composting Hub',
                'category' => 'Organic & Food Waste',
                'accepted_materials' => ['Organic & Food Waste', 'Food Waste', 'Organic'],
                'subtypes' => ['Food Waste', 'Sisa Makanan', 'Vegetable Trimmings', 'Compostable Organics', 'Biomass'],
                'address' => 'Presint 5, 62200 Putrajaya',
                'city' => 'Putrajaya',
                'state' => 'Putrajaya',
                'phone' => '+60 3-8888 6000',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 5:00 PM',
                'license' => 'SWCorp Registered Commercial Organic Composting Facility',
                'lat' => 2.8942,
                'lng' => 101.6742,
                'is_certified' => true,
            ],
            [
                'id' => 16,
                'name' => 'TES-AMM Malaysia Circular Electronics Recovery',
                'category' => 'Electronics',
                'accepted_materials' => ['Electronics', 'Metals', 'Chemicals'],
                'subtypes' => ['Server Racks', 'E-Waste', 'Lithium Batteries', 'Printed Circuit Boards', 'Industrial Telecomm Scrap'],
                'address' => 'Jalan Ragum 15/17, Seksyen 15, 40200 Shah Alam, Selangor',
                'city' => 'Shah Alam',
                'state' => 'Selangor',
                'phone' => '+60 3-5511 8833',
                'operating_hours' => 'Mon - Fri: 8:30 AM - 5:30 PM',
                'license' => 'DOE Licensed SW110 E-Waste Refurbishment & Material Recovery Facility',
                'lat' => 3.0612,
                'lng' => 101.5278,
                'is_certified' => true,
            ],
            [
                'id' => 17,
                'name' => 'Press Metal Secondary Aluminium Ingot Smelter',
                'category' => 'Metals',
                'accepted_materials' => ['Metals', 'Industrial Scrap', 'Construction & demolition'],
                'subtypes' => ['Aluminium Extrusions', 'Aluminium Offcuts', 'Foundry Dross', 'Swarf & Turnings', 'Alloy Ingots'],
                'address' => 'Batu 12, Jalan Kapar, 42200 Kapar, Klang, Selangor',
                'city' => 'Klang',
                'state' => 'Selangor',
                'phone' => '+60 3-3290 2000',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 6:00 PM',
                'license' => 'MITI Registered Circular Non-Ferrous Aluminium Smelting Complex',
                'lat' => 3.1384,
                'lng' => 101.3745,
                'is_certified' => true,
            ],
            [
                'id' => 18,
                'name' => 'Kian Ho Polymer Recovery & Compounding Sdn Bhd',
                'category' => 'Plastics',
                'accepted_materials' => ['Plastics', 'Packaging & paper'],
                'subtypes' => ['PP Corrugated Sheet', 'HDPE Regrind', 'Blow Molding Scrap', 'Plastic Drums', 'PET Flakes'],
                'address' => 'Kawasan Perindustrian Balakong Jaya, 43300 Seri Kembangan, Selangor',
                'city' => 'Seri Kembangan',
                'state' => 'Selangor',
                'phone' => '+60 3-8961 1122',
                'operating_hours' => 'Mon - Fri: 8:00 AM - 5:30 PM',
                'license' => 'DOE Licensed Industrial Polymer Reprocessing & Pelletizing Facility',
                'lat' => 3.0182,
                'lng' => 101.7456,
                'is_certified' => true,
            ],
            [
                'id' => 19,
                'name' => 'Pentamaster Chemical & Industrial Solvent Recovery',
                'category' => 'Chemicals',
                'accepted_materials' => ['Chemicals', 'Industrial Scrap'],
                'subtypes' => ['Spent Solvents', 'Paint Sludge', 'Degreasing Agents', 'Thinners', 'Contaminated Drums'],
                'address' => 'Kawasan Perindustrian Nilai, 71800 Nilai, Negeri Sembilan',
                'city' => 'Nilai',
                'state' => 'Negeri Sembilan',
                'phone' => '+60 6-799 4488',
                'operating_hours' => 'Mon - Fri: 8:30 AM - 5:00 PM',
                'license' => 'DOE Licensed Scheduled Waste Solvent Fractionation & Recovery (SW322)',
                'lat' => 2.8124,
                'lng' => 101.7981,
                'is_certified' => true,
            ],
            [
                'id' => 20,
                'name' => 'Oji Paper Packaging & Industrial Carton Reclaiming',
                'category' => 'Packaging & paper',
                'accepted_materials' => ['Packaging & paper', 'Timber'],
                'subtypes' => ['Heavy Corrugated Cartons', 'Kraft Paper Cuts', 'Pulp Offcuts', 'Pallet Liners', 'Solid Board'],
                'address' => 'Kawasan Perindustrian Bandar Baru Bangi, 43650 Bangi, Selangor',
                'city' => 'Bangi',
                'state' => 'Selangor',
                'phone' => '+60 3-8925 5566',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 5:00 PM',
                'license' => 'SWCorp Registered Packaging Recycling & Fiber Reprocessing Plant',
                'lat' => 2.9431,
                'lng' => 101.7765,
                'is_certified' => true,
            ],
            [
                'id' => 21,
                'name' => 'Klang Port Biomass & Industrial Pallet Reclaiming Hub',
                'category' => 'Timber',
                'accepted_materials' => ['Timber', 'Packaging & paper', 'Construction & demolition'],
                'subtypes' => ['ISPM 15 Wooden Pallets', 'Industrial Crates', 'Timber Runners', 'Plywood Cutoffs', 'Wood Sawdust'],
                'address' => 'Jalan Pelabuhan Utara, Kawasan Perindustrian Pelabuhan Klang, 42000 Port Klang, Selangor',
                'city' => 'Port Klang',
                'state' => 'Selangor',
                'phone' => '+60 3-3176 3311',
                'operating_hours' => 'Mon - Sat: 8:00 AM - 6:00 PM',
                'license' => 'Malaysian Timber Industry Board (MTIB) Registered Circular Pallet Refurbisher',
                'lat' => 3.0315,
                'lng' => 101.3524,
                'is_certified' => true,
            ],
            [
                'id' => 22,
                'name' => 'Biomax Commercial Food Waste & BSF Bioconversion Facility',
                'category' => 'Organic & Food Waste',
                'accepted_materials' => ['Organic & Food Waste', 'Food Waste', 'Organic'],
                'subtypes' => ['Industrial Food Waste', 'Spent Brewer Grains', 'Bakery Cutoffs', 'Organic Slurries', 'Kitchen Waste'],
                'address' => 'Kawasan Perindustrian Bukit Angkat, 43000 Kajang, Selangor',
                'city' => 'Kajang',
                'state' => 'Selangor',
                'phone' => '+60 3-8733 9900',
                'operating_hours' => 'Mon - Sat: 7:30 AM - 6:30 PM',
                'license' => 'SWCorp Licensed Commercial Organic Bioconversion & Protein Recovery Facility',
                'lat' => 2.9814,
                'lng' => 101.7654,
                'is_certified' => true,
            ],
            [
                'id' => 23,
                'name' => 'Goodway Integrated Tire Pyrolysis & Crumb Rubber Recovery',
                'category' => 'Rubber & tires',
                'accepted_materials' => ['Rubber & tires', 'Industrial Scrap'],
                'subtypes' => ['Industrial OTR Tires', 'Rubber Offcuts', 'Conveyor Belt Scrap', 'Crumb Rubber', 'Latex Trimmings'],
                'address' => 'Nilai Industrial Park, 71800 Nilai, Negeri Sembilan',
                'city' => 'Nilai',
                'state' => 'Negeri Sembilan',
                'phone' => '+60 6-799 1199',
                'operating_hours' => 'Mon - Fri: 8:00 AM - 5:00 PM',
                'license' => 'DOE Licensed Rubber Pyrolysis & Micronized Rubber Recovery Facility',
                'lat' => 2.8256,
                'lng' => 101.8123,
                'is_certified' => true,
            ],
            [
                'id' => 24,
                'name' => 'Kuala Lumpur Glass Cullet Aggregates & Reprocessing Hub',
                'category' => 'Glass',
                'accepted_materials' => ['Glass', 'Construction & demolition'],
                'subtypes' => ['Flat Glass Sheets', 'Automotive Safety Glass', 'Glass Cullet', 'Container Glass', 'Glass Sand'],
                'address' => 'Kawasan Industri Batu Caves, 68100 Batu Caves, Selangor',
                'city' => 'Batu Caves',
                'state' => 'Selangor',
                'phone' => '+60 3-6189 7722',
                'operating_hours' => 'Mon - Sat: 8:30 AM - 5:30 PM',
                'license' => 'DOE & PBT Registered Industrial Glass Crushing & Circular Cullet Depot',
                'lat' => 3.2384,
                'lng' => 101.6845,
                'is_certified' => true,
            ],
        ];
    }

    /**
     * Resolve GPS coordinates from an SME company's registered address, city, and state.
     * Never relies on a fixed hardcoded city; prioritizes the registered business profile.
     *
     * @return array{lat: float, lng: float}
     */
    public static function getBusinessCoordinates(\App\Models\User $business): array
    {
        $city = (string) ($business->city ?? '');
        $state = (string) ($business->state ?? '');
        $address = (string) ($business->registered_address ?? '');

        if (trim($city) !== '') {
            return self::getCityCoordinates($city, $state);
        }

        // Search address string for recognized Malaysian cities/localities
        $addressLower = mb_strtolower($address);
        $cities = [
            'shah alam', 'subang jaya', 'petaling jaya', 'klang', 'kuala lumpur', 'rawang',
            'putrajaya', 'cyberjaya', 'georgetown', 'butterworth', 'perai', 'sungai petani',
            'ipoh', 'johor bahru', 'pasir gudang', 'kulai', 'melaka', 'seremban', 'kuantan',
            'kuching', 'bintulu', 'miri', 'kota kinabalu', 'sandakan',
        ];

        foreach ($cities as $candidate) {
            if (str_contains($addressLower, $candidate)) {
                return self::getCityCoordinates($candidate, $state);
            }
        }

        return self::getCityCoordinates($city, $state);
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
     * Normalize a free-text/AI material category into canonical accepted material buckets.
     *
     * @return array<int, string>
     */
    public static function resolveMaterialTokens(?string $materialCategory): array
    {
        $raw = mb_strtolower(trim((string) $materialCategory));
        if ($raw === '' || $raw === 'all' || $raw === 'general') {
            return [];
        }

        $buckets = [
            'plastics' => ['plastic', 'plastik', 'polymer', 'hdpe', 'ldpe', 'pp', 'pet', 'pvc', 'resin', 'film', 'acrylic'],
            'metals' => ['metal', 'steel', 'iron', 'aluminium', 'aluminum', 'copper', 'brass', 'swarf', 'logam', 'keluli', 'besi'],
            'packaging & paper' => ['paper', 'cardboard', 'carton', 'kraft', 'packaging', 'kertas', 'kadbod', 'kotak', 'pulp'],
            'timber' => ['timber', 'wood', 'pallet', 'plywood', 'sawdust', 'kayu', 'palet', 'furniture'],
            'electronics' => ['electronic', 'electrical', 'e-waste', 'pcb', 'circuit', 'battery', 'cable', 'wire', 'elektronik'],
            'chemicals' => ['chemical', 'solvent', 'oil', 'sludge', 'lubricant', 'paint', 'acid', 'hazardous', 'scheduled', 'kimia', 'minyak'],
            'textiles' => ['textile', 'fabric', 'cotton', 'garment', 'apparel', 'yarn', 'cloth', 'rag', 'kain', 'tekstil'],
            'glass' => ['glass', 'kaca', 'borosilicate', 'cullet', 'mirror'],
            'rubber & tires' => ['rubber', 'tire', 'tyre', 'tayar', 'getah', 'latex'],
            'organic & food waste' => ['organic', 'food', 'sisa makanan', 'compost', 'vegetable', 'fruit', 'kitchen', 'makanan'],
            'construction & demolition' => ['construction', 'concrete', 'masonry', 'brick', 'cement', 'demolition'],
        ];

        $matched = [];
        foreach ($buckets as $bucket => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($raw, $kw)) {
                    $matched[] = $bucket;
                    break;
                }
            }
        }

        return $matched;
    }

    /**
     * Find verified nearby recycling centres that accept the given material category and AI analysis context.
     * Prioritises the most suitable locations (specialized capabilities & exact subtype alignment), not just the nearest ones.
     *
     * @param array<string, mixed>|null $analysisContext
     * @return array<int, array<string, mixed>>
     */
    public static function findNearby(
        float $lat,
        float $lng,
        ?string $materialCategory = null,
        int $limit = 8,
        bool $strictVerifiedOnly = true,
        ?array $analysisContext = null,
        ?string $itemName = null,
        ?float $maxDistanceKm = null,
        ?string $state = null,
        bool $closestFirst = false
    ): array
    {
        $centres = self::getCentres();
        $results = [];

        $filterCat = $materialCategory ? mb_strtolower(trim($materialCategory)) : '';
        $hasSpecificFilter = $filterCat !== '' && $filterCat !== 'all' && $filterCat !== 'general';
        $canonicalTokens = $hasSpecificFilter ? self::resolveMaterialTokens($filterCat) : [];

        $itemHaystack = mb_strtolower(trim(
            ($itemName ?? '') . ' ' .
            ($materialCategory ?? '') . ' ' .
            ($analysisContext['strategy'] ?? '') . ' ' .
            ($analysisContext['action_recommendation'] ?? '') . ' ' .
            ($analysisContext['primary_path'] ?? '') . ' ' .
            (is_array($analysisContext['repurpose_ideas'] ?? null) ? implode(' ', $analysisContext['repurpose_ideas']) : '') . ' ' .
            (is_array($analysisContext['owner_actions'] ?? null) ? implode(' ', $analysisContext['owner_actions']) : '') . ' ' .
            (is_array($analysisContext['recycling_steps'] ?? null) ? implode(' ', $analysisContext['recycling_steps']) : '') . ' ' .
            (is_array($analysisContext['disposal_locations'] ?? null) ? implode(' ', array_map(fn($l) => is_array($l) ? ($l['name'] ?? '') : (string) $l, $analysisContext['disposal_locations'])) : '')
        ));

        $state = mb_strtolower(trim($state ?? ''));
        foreach ($centres as $centre) {
            if ($state !== '' && mb_strtolower($centre['state']) !== $state) {
                continue;
            }

            $centreCategory = mb_strtolower($centre['category']);
            $acceptedLower = array_map('mb_strtolower', $centre['accepted_materials']);
            $subtypesLower = array_map('mb_strtolower', $centre['subtypes']);
            $haystack = $centreCategory . ' ' . implode(' ', $acceptedLower) . ' ' . implode(' ', $subtypesLower);

            $isMatch = true;
            if ($hasSpecificFilter) {
                $isMatch = false;
                if (! empty($canonicalTokens)) {
                    foreach ($canonicalTokens as $token) {
                        if ($centreCategory === $token || in_array($token, $acceptedLower, true) || str_contains($haystack, $token)) {
                            $isMatch = true;
                            break;
                        }
                    }
                } else {
                    $isMatch = ($centreCategory === $filterCat)
                        || in_array($filterCat, $acceptedLower, true)
                        || str_contains(implode(' ', $acceptedLower), $filterCat)
                        || str_contains(implode(' ', $subtypesLower), $filterCat);
                }

                if ($strictVerifiedOnly && ! $isMatch) {
                    continue;
                }
            }

            // Calculate Suitability Score
            $suitabilityScore = 0;
            $matchedReasons = [];

            // 1. Direct Category & Accepted Materials alignment
            if ($hasSpecificFilter && $centreCategory === $filterCat) {
                $suitabilityScore += 40;
                $matchedReasons[] = 'Primary category: ' . $centre['category'];
            } elseif ($hasSpecificFilter && in_array($filterCat, $acceptedLower, true)) {
                $suitabilityScore += 30;
                $matchedReasons[] = 'Certified for ' . $centre['category'];
            } elseif ($isMatch) {
                $suitabilityScore += 20;
            }

            // 2. Exact Material Subtype match against item name and AI analysis
            $matchedSubtypes = [];
            foreach ($centre['subtypes'] as $subtype) {
                $subLower = mb_strtolower($subtype);
                $keywords = explode(' ', str_replace(['(', ')', '/', '-'], ' ', $subLower));
                foreach ($keywords as $kw) {
                    $kw = trim($kw);
                    if (strlen($kw) >= 3 && str_contains($itemHaystack, $kw)) {
                        $matchedSubtypes[] = $subtype;
                        $suitabilityScore += 25;
                        break;
                    }
                }
            }

            if (! empty($matchedSubtypes)) {
                $uniqueSub = array_unique($matchedSubtypes);
                $matchedReasons[] = 'Specializes in ' . implode(', ', array_slice($uniqueSub, 0, 2));
            }

            // 3. Strategic process match (smelting, pyrolysis, pelletizing, BSF bioconversion, MRF, solvent recovery, etc.)
            $processKeywords = [
                'smelt' => 'Secondary smelting & ingot production',
                'pyrolysis' => 'Thermal depolymerization & pyrolysis',
                'pellet' => 'Polymer compounding & pelletizing',
                'shred' => 'Mechanical shredding & granulation',
                'solvent' => 'Fractional solvent distillation (SW322)',
                'e-waste' => 'DOE SW110 e-waste material recovery',
                'scheduled' => 'Licensed scheduled waste recovery',
                'compost' => 'Aerobic / anaerobic organic bioconversion',
                'cullet' => 'Glass cullet crushing & processing',
                'pallet' => 'MTIB circular pallet reclamation',
                'paper' => 'Fiber de-inking & paper pulp reprocessing',
            ];

            foreach ($processKeywords as $kw => $label) {
                if (str_contains($itemHaystack, $kw) && (str_contains($haystack, $kw) || str_contains(mb_strtolower($centre['license']), $kw))) {
                    $suitabilityScore += 30;
                    $matchedReasons[] = $label;
                    break;
                }
            }

            // 4. Certification
            if (! empty($centre['is_certified'])) {
                $suitabilityScore += 10;
            }

            // 5. Direct reference in Add New Inventory AI analysis disposal locations
            if (! empty($analysisContext['disposal_locations'])) {
                foreach ($analysisContext['disposal_locations'] as $loc) {
                    $locText = is_array($loc) ? ($loc['name'] ?? ($loc['address'] ?? '')) : (string) $loc;
                    $locLower = mb_strtolower($locText);
                    if ($locLower !== '' && (str_contains(mb_strtolower($centre['name']), $locLower) || str_contains($locLower, mb_strtolower($centre['name'])))) {
                        $suitabilityScore += 45;
                        $matchedReasons[] = 'Directly recommended in AI analysis';
                        break;
                    }
                }
            }

            $dist = self::calculateDistance($lat, $lng, (float) $centre['lat'], (float) $centre['lng']);
            if ($maxDistanceKm !== null && $dist > $maxDistanceKm) {
                continue;
            }

            $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode("{$centre['name']}, {$centre['address']}");

            // Prioritise suitability: high suitability dominates, distance acts as tie-breaker
            $finalScore = $suitabilityScore - ($dist * 0.25);

            if ($suitabilityScore >= 55) {
                $tier = 'Most Suitable';
                $badge = '⭐ Most Suitable Match';
            } elseif ($suitabilityScore >= 25) {
                $tier = 'Highly Suitable';
                $badge = 'Highly Suitable';
            } else {
                $tier = 'Alternative Facility';
                $badge = 'Alternative Facility';
            }

            $reasonText = ! empty($matchedReasons)
                ? implode(' · ', array_unique($matchedReasons))
                : 'Licensed recovery and materials diversion centre.';

            $results[] = array_merge($centre, [
                'distance_km' => $dist,
                'is_material_match' => $isMatch,
                'verified_acceptance' => $isMatch,
                'suitability_score' => $suitabilityScore,
                'final_score' => round($finalScore, 2),
                'suitability_tier' => $tier,
                'suitability_badge' => $badge,
                'suitability_reason' => $reasonText,
                'directions_url' => $mapsUrl,
            ]);
        }

        // Sort by suitability score descending first, then final score (suitability minus distance) descending
        usort($results, function ($a, $b) use ($closestFirst): int {
            if ($a['is_material_match'] !== $b['is_material_match']) {
                return $b['is_material_match'] <=> $a['is_material_match'];
            }
            if ($closestFirst) {
                return $a['distance_km'] <=> $b['distance_km'];
            }

            if ($a['suitability_score'] !== $b['suitability_score']) {
                return $b['suitability_score'] <=> $a['suitability_score'];
            }

            return $b['final_score'] <=> $a['final_score'];
        });

        return array_slice($results, 0, $limit);
    }
}
