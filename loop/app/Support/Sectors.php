<?php

namespace App\Support;

use App\Models\PlatformSetting;

class Sectors
{
    public const KEY = 'sectors';

    /**
     * Legacy key => label map. Kept so hub tests and stored settings can spread it.
     * Existing business rows keep these keys; labels may be refined.
     *
     * @var array<string, string>
     */
    public const OPTIONS = [
        'restaurants' => 'Restaurant',
        'coffee' => 'Café / Coffee Shop',
        'fast_food' => 'Fast Food',
        'bars' => 'Bar & Lounge',
        'bakery' => 'Bakery',
        'fashion' => 'Clothing / Fashion',
        'beauty' => 'Beauty Salon',
        'health' => 'Health & pharmacy',
        'ppe' => 'PPE & safety',
        'grocery' => 'Mini Market / Grocery',
        'electronics' => 'Electronics',
        'retail' => 'General retail',
        'automotive' => 'Automotive',
        'petrol' => 'Petrol Station',
        'fitness' => 'Gym / Fitness Centre',
        'hospitality' => 'Hotels & lodging',
        'education' => 'Education & tutoring',
        'services' => 'Professional services',
        'telecom' => 'Telecom & mobile money',
        'agriculture' => 'Agriculture & agro',
        'other' => 'Other Business / Not Listed',
    ];

    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'food' => 'Food & Drink',
        'beauty' => 'Beauty & Personal Care',
        'laundry' => 'Laundry & Cleaning',
        'retail' => 'Retail',
        'health' => 'Health & Wellness',
        'automotive' => 'Automotive',
        'services' => 'Professional Services',
        'entertainment' => 'Entertainment & Leisure',
        'hospitality' => 'Hospitality & Travel',
        'home' => 'Home & Lifestyle',
        'education' => 'Education',
        'other' => 'Other',
    ];

    /**
     * Compact defaults: key, label, category, aliases, featured, short, rank.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string, 6: int}>
     */
    public static function defaultRows(): array
    {
        return [
            ['restaurants', 'Restaurant', 'food', 'restaurant, mgahawa, chakula, food, eatery, dining', 1, 'Restaurant', 10],
            ['coffee', 'Café / Coffee Shop', 'food', 'cafe, café, coffee, kahawa, coffee shop', 1, 'Café', 11],
            ['fast_food', 'Fast Food', 'food', 'fast food, chips, street food, takeout', 0, '', 12],
            ['bakery', 'Bakery', 'food', 'bakery, pastry, mkate, cakes, bread', 0, '', 13],
            ['ice_cream', 'Ice Cream / Dessert', 'food', 'ice cream, dessert, gelato, sweets', 0, '', 14],
            ['juice', 'Juice / Smoothie Bar', 'food', 'juice, smoothie, juice bar, fresh juice', 0, '', 15],
            ['bars', 'Bar & Lounge', 'food', 'bar, lounge, nightlife, pub, club', 0, '', 16],
            ['takeaway', 'Takeaway', 'food', 'takeaway, takeout, delivery food', 0, '', 17],
            ['catering', 'Catering', 'food', 'catering, events food, outside catering', 0, '', 18],
            ['hair_salon', 'Hair Salon', 'beauty', 'salon, hairdresser, hair, beauty, saluni, nywele', 1, 'Salon', 20],
            ['barbershop', 'Barbershop', 'beauty', 'barber, kinyozi, saluni ya kiume, fade, haircut', 1, 'Barbershop', 21],
            ['nail_salon', 'Nail Salon', 'beauty', 'nails, manicure, pedicure, nail bar', 0, '', 22],
            ['beauty', 'Beauty Salon', 'beauty', 'beauty, makeup, salon, saluni', 0, '', 23],
            ['spa', 'Spa', 'beauty', 'spa, wellness spa', 0, '', 24],
            ['massage', 'Massage', 'beauty', 'massage, physiotherapy massage', 0, '', 25],
            ['cosmetics', 'Cosmetics / Beauty Store', 'beauty', 'cosmetics, makeup store, beauty shop', 0, '', 26],
            ['skincare', 'Skincare', 'beauty', 'skincare, facial, dermatology shop', 0, '', 27],
            ['laundry', 'Dry Cleaning & Laundry', 'laundry', 'laundry, dry cleaner, dry cleaning, laundromat, washing, clothes cleaning, kufua, kufulia', 1, 'Laundry', 30],
            ['laundromat', 'Laundromat', 'laundry', 'laundromat, self service laundry, washing machines', 0, '', 31],
            ['shoe_cleaning', 'Shoe Cleaning', 'laundry', 'shoe cleaning, shoe shine, cobbler clean', 0, '', 32],
            ['carpet_cleaning', 'Carpet Cleaning', 'laundry', 'carpet cleaning, rug cleaning', 0, '', 33],
            ['home_cleaning', 'Home Cleaning Services', 'laundry', 'home cleaning, house cleaning, maid, usafi', 0, '', 34],
            ['supermarket', 'Supermarket', 'retail', 'supermarket, hypermarket', 0, '', 40],
            ['grocery', 'Mini Market / Grocery', 'retail', 'grocery, minimart, shop, duka, mini market', 1, 'Shop', 41],
            ['convenience', 'Convenience Store', 'retail', 'convenience, kiosk, corner shop', 0, '', 42],
            ['fashion', 'Clothing / Fashion', 'retail', 'fashion, clothing, apparel, nguo, boutique', 0, '', 43],
            ['shoes', 'Shoes', 'retail', 'shoes, footwear, viatu', 0, '', 44],
            ['electronics', 'Electronics', 'retail', 'electronics, gadgets, tv, fridge', 0, '', 45],
            ['mobile_phones', 'Mobile Phones & Accessories', 'retail', 'phones, mobile, simu, accessories, smartphone', 0, '', 46],
            ['furniture', 'Furniture', 'retail', 'furniture, sofa, samani', 0, '', 47],
            ['homeware', 'Homeware', 'retail', 'homeware, household, kitchenware', 0, '', 48],
            ['gifts', 'Gifts', 'retail', 'gifts, gift shop, souvenirs', 0, '', 49],
            ['bookshop', 'Bookshop / Stationery', 'retail', 'bookshop, stationery, books, vitabu', 0, '', 50],
            ['hardware', 'Hardware', 'retail', 'hardware, building materials, tools', 0, '', 51],
            ['retail', 'General retail', 'retail', 'retail, shop, duka, store', 0, '', 52],
            ['pharmacy', 'Pharmacy', 'health', 'pharmacy, duka la dawa, chemist, medicines', 0, '', 60],
            ['optical', 'Optical Shop', 'health', 'optical, glasses, spectacles, macho', 0, '', 61],
            ['dental', 'Dental Clinic', 'health', 'dental, dentist, teeth, meno', 0, '', 62],
            ['clinic', 'Clinic', 'health', 'clinic, dispensary, hospital, hospitali', 0, '', 63],
            ['health', 'Health & pharmacy', 'health', 'health, pharmacy, clinic, dawa', 0, '', 64],
            ['fitness', 'Gym / Fitness Centre', 'health', 'gym, fitness, workout, mazoezi', 0, '', 65],
            ['wellness_centre', 'Wellness Centre', 'health', 'wellness, wellbeing', 0, '', 66],
            ['nutrition', 'Nutrition / Health Store', 'health', 'nutrition, supplements, health store', 0, '', 67],
            ['petrol', 'Petrol Station', 'automotive', 'petrol, fuel, gas station, mafuta', 0, '', 70],
            ['car_wash', 'Car Wash', 'automotive', 'car wash, osha gari, auto wash', 0, '', 71],
            ['automotive', 'Automotive', 'automotive', 'automotive, garage, mechanic, gari', 0, '', 72],
            ['auto_garage', 'Auto Garage', 'automotive', 'garage, mechanic, car repair, fundi gari', 0, '', 73],
            ['tyre', 'Tyre Shop', 'automotive', 'tyre, tire, magurudumu', 0, '', 74],
            ['auto_parts', 'Auto Parts', 'automotive', 'auto parts, spare parts, spares', 0, '', 75],
            ['car_accessories', 'Car Accessories', 'automotive', 'car accessories, seat covers', 0, '', 76],
            ['motorcycle', 'Motorcycle Service', 'automotive', 'motorcycle, pikipiki, boda, bike service', 0, '', 77],
            ['printing', 'Printing & Branding', 'services', 'printing, branding, design, print', 0, '', 80],
            ['photography', 'Photography Studio', 'services', 'photography, studio, picha', 0, '', 81],
            ['courier', 'Courier / Delivery', 'services', 'courier, delivery, parcel, posting', 0, '', 82],
            ['business_centre', 'Business Centre', 'services', 'business centre, office services', 0, '', 83],
            ['repair', 'Repair Services', 'services', 'repair, fundi, fixes', 0, '', 84],
            ['device_repair', 'Computer / Phone Repair', 'services', 'phone repair, computer repair, technician', 0, '', 85],
            ['services', 'Professional services', 'services', 'services, professional, office', 0, '', 86],
            ['ppe', 'PPE & safety', 'services', 'ppe, safety, protective', 0, '', 87],
            ['telecom', 'Telecom & mobile money', 'services', 'telecom, mobile money, mpesa, mixx, airtel money', 0, '', 88],
            ['cinema', 'Cinema', 'entertainment', 'cinema, movies, film, sinema', 0, '', 90],
            ['gaming', 'Gaming Centre', 'entertainment', 'gaming, games, playstation', 0, '', 91],
            ['kids_play', 'Kids Play Centre', 'entertainment', 'kids play, indoor play, children', 0, '', 92],
            ['sports_centre', 'Sports Centre', 'entertainment', 'sports, football, pitch', 0, '', 93],
            ['recreation', 'Recreation Centre', 'entertainment', 'recreation, leisure', 0, '', 94],
            ['events', 'Events / Entertainment', 'entertainment', 'events, entertainment, party', 0, '', 95],
            ['hospitality', 'Hotels & lodging', 'hospitality', 'hotel, lodging, stay, hoteli', 0, '', 100],
            ['hotel', 'Hotel', 'hospitality', 'hotel, hoteli', 0, '', 101],
            ['guest_house', 'Guest House', 'hospitality', 'guest house, guesthouse, rooms', 0, '', 102],
            ['lodge', 'Lodge', 'hospitality', 'lodge, safari lodge', 0, '', 103],
            ['resort', 'Resort', 'hospitality', 'resort, beach resort', 0, '', 104],
            ['travel', 'Travel Agency', 'hospitality', 'travel, flights, tickets', 0, '', 105],
            ['tour', 'Tour Operator', 'hospitality', 'tour, safari, tours', 0, '', 106],
            ['florist', 'Florist', 'home', 'florist, flowers, maua', 0, '', 110],
            ['interior', 'Interior / Décor', 'home', 'interior, decor, decoration', 0, '', 111],
            ['garden', 'Garden Centre', 'home', 'garden, plants, nursery', 0, '', 112],
            ['pet', 'Pet Shop / Pet Care', 'home', 'pet, veterinary, dog, cat, pet shop', 0, '', 113],
            ['education', 'Education & tutoring', 'education', 'education, school, tutoring, masomo', 0, '', 120],
            ['training', 'Training Centre', 'education', 'training, courses, academy', 0, '', 121],
            ['tuition', 'Tuition Centre', 'education', 'tuition, tutoring, extra classes', 0, '', 122],
            ['driving_school', 'Driving School', 'education', 'driving school, udereva, licence', 0, '', 123],
            ['language', 'Language Centre', 'education', 'language, english, kiswahili classes', 0, '', 124],
            ['vocational', 'Skills / Vocational Training', 'education', 'vocational, skills, fundi training', 0, '', 125],
            ['agriculture', 'Agriculture & agro', 'other', 'agriculture, agro, farm, kilimo', 0, '', 130],
            ['other', 'Other Business / Not Listed', 'other', 'other, not listed, can\'t find, haijaorodheshwa', 0, '', 999],
        ];
    }

    /**
     * @return list<array{key: string, label: string, category: string, aliases: string, featured: bool, short: string, rank: int}>
     */
    public static function catalog(): array
    {
        return array_map(fn (array $row) => self::hydrateRow($row), self::defaultRows());
    }

    /**
     * @return array<string, array{key: string, label: string, category: string, aliases: string, featured: bool, short: string, rank: int}>
     */
    public static function catalogByKey(): array
    {
        $out = [];
        foreach (self::catalog() as $row) {
            $out[$row['key']] = $row;
        }

        return $out;
    }

    /**
     * @return array<string, array{key: string, label: string, category: string, aliases: string, featured: bool, short: string, rank: int}>
     */
    public static function records(): array
    {
        $defaults = self::catalogByKey();
        $stored = PlatformSetting::getValue(self::KEY, null);
        if (! is_array($stored) || $stored === []) {
            return $defaults;
        }

        $out = $defaults;
        foreach ($stored as $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = (string) ($row['key'] ?? '');
            $label = trim((string) ($row['label'] ?? ''));
            if ($key === '' || $label === '') {
                continue;
            }
            $base = $out[$key] ?? [
                'key' => $key,
                'label' => $label,
                'category' => 'other',
                'aliases' => '',
                'featured' => false,
                'short' => '',
                'rank' => 500,
            ];
            $out[$key] = [
                'key' => $key,
                'label' => $label,
                'category' => self::normalizeCategory((string) ($row['category'] ?? $base['category'])),
                'aliases' => self::normalizeAliases($row['aliases'] ?? $base['aliases']),
                'featured' => array_key_exists('featured', $row) ? (bool) $row['featured'] : (bool) $base['featured'],
                'short' => trim((string) ($row['short'] ?? $base['short'])),
                'rank' => isset($row['rank']) ? (int) $row['rank'] : (int) $base['rank'],
            ];
        }

        if (! isset($out['other'])) {
            $out['other'] = $defaults['other'];
        }

        uasort($out, fn ($a, $b) => ($a['rank'] <=> $b['rank']) ?: strcmp($a['label'], $b['label']));

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return collect(self::records())
            ->mapWithKeys(fn (array $row) => [$row['key'] => $row['label']])
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, category: string, aliases: string, featured: bool, short: string, rank: int}>
     */
    public static function list(): array
    {
        return array_values(self::records());
    }

    /**
     * @return list<array{key: string, label: string, category: string, aliases: string, featured: bool, short: string, rank: int, search: string}>
     */
    public static function pickerRecords(): array
    {
        return array_values(array_map(fn (array $row) => $row + ['search' => self::haystack($row)], self::records()));
    }

    /**
     * @return list<array{key: string, label: string, search: string}>
     */
    public static function sheetOptions(bool $includeAll = true): array
    {
        $rows = [];
        if ($includeAll) {
            $rows[] = [
                'key' => '',
                'label' => __('loop.all'),
                'search' => 'all all sectors',
            ];
        }
        foreach (self::pickerRecords() as $row) {
            $rows[] = [
                'key' => $row['key'],
                'label' => $row['label'],
                'search' => $row['search'],
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{key?: string, label?: string, category?: string, aliases?: mixed, featured?: mixed, short?: string, rank?: mixed}>|array<string, string>  $input
     * @return list<array{key: string, label: string, category: string, aliases: string, featured: bool, short: string, rank: int}>
     */
    public static function normalizeInput(array $input): array
    {
        $defaults = self::catalogByKey();
        $rows = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $k = strtolower(preg_replace('/[^a-z0-9_]+/', '_', (string) ($value['key'] ?? '')) ?? '');
                $label = trim((string) ($value['label'] ?? ''));
                $extra = $value;
            } else {
                $k = is_string($key) ? strtolower(preg_replace('/[^a-z0-9_]+/', '_', $key) ?? '') : '';
                $label = trim((string) $value);
                $extra = [];
            }
            $k = trim($k, '_');
            if ($k === '' || $label === '') {
                continue;
            }
            $base = $defaults[$k] ?? [
                'category' => 'other',
                'aliases' => '',
                'featured' => false,
                'short' => '',
                'rank' => 500,
            ];
            $rows[$k] = [
                'key' => $k,
                'label' => $label,
                'category' => self::normalizeCategory((string) ($extra['category'] ?? $base['category'])),
                'aliases' => self::normalizeAliases($extra['aliases'] ?? $base['aliases']),
                'featured' => array_key_exists('featured', $extra) ? (bool) $extra['featured'] : (bool) $base['featured'],
                'short' => trim((string) ($extra['short'] ?? $base['short'])),
                'rank' => isset($extra['rank']) ? (int) $extra['rank'] : (int) $base['rank'],
            ];
        }

        foreach ($defaults as $key => $row) {
            if (! isset($rows[$key])) {
                $rows[$key] = $row;
            }
        }

        if (! isset($rows['other'])) {
            $rows['other'] = $defaults['other'];
        }

        uasort($rows, fn ($a, $b) => ($a['rank'] <=> $b['rank']) ?: strcmp($a['label'], $b['label']));

        return array_values($rows);
    }

    public static function label(?string $key, ?string $custom = null): string
    {
        if ($key === 'other' && filled($custom)) {
            return $custom;
        }

        return self::all()[$key] ?? (self::OPTIONS['other'] ?? 'Other');
    }

    public static function keysRule(): string
    {
        return 'in:'.implode(',', array_keys(self::all()));
    }

    public static function categoryLabel(string $key): string
    {
        return self::CATEGORIES[$key] ?? self::CATEGORIES['other'];
    }

    public static function rankFor(string $key): int
    {
        return (int) (self::records()[$key]['rank'] ?? 500);
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string, 6: int}  $row
     * @return array{key: string, label: string, category: string, aliases: string, featured: bool, short: string, rank: int}
     */
    private static function hydrateRow(array $row): array
    {
        return [
            'key' => $row[0],
            'label' => $row[1],
            'category' => $row[2],
            'aliases' => $row[3],
            'featured' => (bool) $row[4],
            'short' => $row[5],
            'rank' => (int) $row[6],
        ];
    }

    private static function normalizeCategory(string $category): string
    {
        return isset(self::CATEGORIES[$category]) ? $category : 'other';
    }

    private static function normalizeAliases(mixed $aliases): string
    {
        if (is_array($aliases)) {
            $aliases = implode(', ', $aliases);
        }

        $parts = preg_split('/\s*,\s*/', strtolower(trim((string) $aliases))) ?: [];
        $parts = array_values(array_unique(array_filter($parts)));

        return implode(', ', $parts);
    }

    /**
     * @param  array{key: string, label: string, category?: string, aliases?: string, short?: string}  $row
     */
    public static function haystack(array $row): string
    {
        return strtolower(trim(implode(' ', array_filter([
            $row['key'] ?? '',
            $row['label'] ?? '',
            $row['short'] ?? '',
            $row['aliases'] ?? '',
            self::categoryLabel((string) ($row['category'] ?? 'other')),
        ]))));
    }
}
