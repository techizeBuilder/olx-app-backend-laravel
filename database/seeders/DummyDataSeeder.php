<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

/**
 * Seeds realistic dummy content across the classifieds tables:
 * geography, users, listings (+images), offers, chats, ratings,
 * favourites, CMS (sliders/blogs/faqs/tips/reasons), packages, sections.
 *
 * Placeholder images are generated locally with GD into storage/app/public
 * so they render through the app's Storage::url() accessors.
 *
 * Safe to re-run: each block is skipped if that table already has rows
 * created by this seeder (checked via a marker count).
 */
class DummyDataSeeder extends Seeder
{
    private $faker;
    private string $publicDisk;

    public function run(): void
    {
        $this->faker = Faker::create();
        $this->publicDisk = storage_path('app/public');

        $currencyId = $this->seedCurrency();
        [$countries, $states, $cities, $areas] = $this->seedGeography($currencyId);
        $userIds = $this->seedUsers($countries);
        $itemIds = $this->seedItems($userIds, $currencyId, $cities, $areas);
        $this->seedFavourites($userIds, $itemIds);
        $offerIds = $this->seedOffers($userIds, $itemIds);
        $this->seedChats($offerIds);
        $this->seedRatings($userIds, $itemIds);
        $this->seedCms($cities);
        $this->seedPackages();
        $this->seedFeatureSections();
        $this->seedPopularCategories();

        $this->command->info('Dummy data seeding complete.');
    }

    /* ---------- image helper ---------- */

    private array $palette = ['#2563eb', '#dc2626', '#16a34a', '#d97706', '#7c3aed', '#0891b2', '#db2777', '#4f46e5', '#059669', '#ea580c'];

    private function makeImage(string $subdir, string $label, int $w = 600, int $h = 450): string
    {
        $dir = $this->publicDisk . '/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $subdir . '/dummy_' . Str::random(20) . '.jpg';
        $path = $this->publicDisk . '/' . $file;

        $img = imagecreatetruecolor($w, $h);
        $hex = $this->palette[array_rand($this->palette)];
        [$r, $g, $b] = sscanf($hex, "#%02x%02x%02x");
        $bg = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);
        // subtle darker footer band
        $band = imagecolorallocate($img, max(0, $r - 40), max(0, $g - 40), max(0, $b - 40));
        imagefilledrectangle($img, 0, $h - 70, $w, $h, $band);
        $white = imagecolorallocate($img, 255, 255, 255);

        $label = strtoupper(substr($label, 0, 22));
        $fontSize = 5; // built-in largest
        $tw = imagefontwidth($fontSize) * strlen($label);
        $th = imagefontheight($fontSize);
        imagestring($img, $fontSize, (int)(($w - $tw) / 2), (int)(($h - $th) / 2), $label, $white);
        imagestring($img, 3, 20, $h - 45, 'Eclassify demo image', $white);

        imagejpeg($img, $path, 82);
        imagedestroy($img);
        return $file;
    }

    /* ---------- currency ---------- */

    private function seedCurrency(): int
    {
        $existing = DB::table('currencies')->where('iso_code', 'USD')->first();
        if ($existing) {
            return $existing->id;
        }
        return DB::table('currencies')->insertGetId([
            'iso_code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$',
            'symbol_position' => 'left', 'decimal_places' => 2,
            'thousand_separator' => ',', 'decimal_separator' => '.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /* ---------- geography ---------- */

    private function seedGeography(int $currencyId): array
    {
        if (DB::table('countries')->count() > 0) {
            $countries = DB::table('countries')->pluck('id', 'name')->toArray();
            $states = DB::table('states')->pluck('id')->toArray();
            $cities = DB::table('cities')->get(['id', 'name', 'state_id', 'state_code', 'country_id', 'country_code'])->toArray();
            $areas = DB::table('areas')->pluck('id')->toArray();
            return [$countries, $states, $cities, $areas];
        }

        $data = [
            'United States' => [
                'iso2' => 'US', 'iso3' => 'USA', 'phonecode' => '1', 'emoji' => '🇺🇸',
                'lat' => 38.00, 'lng' => -97.00,
                'states' => [
                    'California' => ['code' => 'CA', 'cities' => ['Los Angeles', 'San Diego', 'San Jose']],
                    'New York'   => ['code' => 'NY', 'cities' => ['New York City', 'Buffalo']],
                    'Texas'      => ['code' => 'TX', 'cities' => ['Houston', 'Austin']],
                ],
            ],
            'India' => [
                'iso2' => 'IN', 'iso3' => 'IND', 'phonecode' => '91', 'emoji' => '🇮🇳',
                'lat' => 20.00, 'lng' => 77.00,
                'states' => [
                    'Maharashtra' => ['code' => 'MH', 'cities' => ['Mumbai', 'Pune', 'Nagpur']],
                    'Delhi'       => ['code' => 'DL', 'cities' => ['New Delhi']],
                    'Karnataka'   => ['code' => 'KA', 'cities' => ['Bengaluru', 'Mysuru']],
                ],
            ],
        ];

        $countries = [];
        $states = [];
        $cities = [];
        $areas = [];

        foreach ($data as $cName => $c) {
            $cid = DB::table('countries')->insertGetId([
                'name' => $cName, 'iso2' => $c['iso2'], 'iso3' => $c['iso3'],
                'phonecode' => $c['phonecode'], 'emoji' => $c['emoji'],
                'currency_id' => $currencyId, 'latitude' => $c['lat'], 'longitude' => $c['lng'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $countries[$cName] = $cid;

            foreach ($c['states'] as $sName => $s) {
                $sid = DB::table('states')->insertGetId([
                    'name' => $sName, 'country_id' => $cid, 'state_code' => $s['code'],
                    'latitude' => $c['lat'], 'longitude' => $c['lng'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $states[] = $sid;

                foreach ($s['cities'] as $cityName) {
                    $lat = $c['lat'] + $this->faker->randomFloat(2, -3, 3);
                    $lng = $c['lng'] + $this->faker->randomFloat(2, -3, 3);
                    $cityId = DB::table('cities')->insertGetId([
                        'name' => $cityName, 'state_id' => $sid, 'state_code' => $s['code'],
                        'country_id' => $cid, 'country_code' => $c['iso2'],
                        'latitude' => $lat, 'longitude' => $lng,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $cities[] = (object)[
                        'id' => $cityId, 'name' => $cityName, 'state_id' => $sid,
                        'state_code' => $s['code'], 'country_id' => $cid, 'country_code' => $c['iso2'],
                        'state_name' => $sName, 'country_name' => $cName, 'lat' => $lat, 'lng' => $lng,
                    ];

                    foreach (['Downtown', 'East Side', 'West End'] as $areaName) {
                        $areas[] = DB::table('areas')->insertGetId([
                            'name' => $cityName . ' ' . $areaName, 'city_id' => $cityId,
                            'state_id' => $sid, 'state_code' => $s['code'], 'country_id' => $cid,
                            'latitude' => round($lat + $this->faker->randomFloat(6, -0.05, 0.05), 8),
                            'longitude' => round($lng + $this->faker->randomFloat(6, -0.05, 0.05), 8),
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        return [$countries, $states, $cities, $areas];
    }

    /* ---------- users ---------- */

    private function seedUsers(array $countries): array
    {
        // keep the existing admin (id 1); only add dummy users once.
        if (DB::table('users')->where('type', 'dummy')->count() > 0) {
            return DB::table('users')->where('type', 'dummy')->pluck('id')->toArray();
        }

        $ids = [];
        for ($i = 0; $i < 18; $i++) {
            $name = $this->faker->name();
            $ids[] = DB::table('users')->insertGetId([
                'name' => $name,
                'email' => 'dummy' . $i . '_' . Str::lower(Str::random(5)) . '@example.com',
                'mobile' => $this->faker->numerify('+1##########'),
                'email_verified_at' => now(),
                'profile' => $this->makeImage('users', substr($name, 0, 12), 300, 300),
                'type' => 'dummy',
                'password' => Hash::make('password'),
                'fcm_id' => Str::random(40),
                'notification' => 1,
                'address' => $this->faker->streetAddress(),
                'country_code' => '1',
                'referral_code' => strtoupper(Str::random(8)),
                'refer_points' => $this->faker->numberBetween(0, 500),
                'show_personal_details' => 1,
                'is_verified' => $this->faker->boolean(60) ? 1 : 0,
                'created_at' => now()->subDays($this->faker->numberBetween(1, 120)),
                'updated_at' => now(),
            ]);
        }
        return $ids;
    }

    /* ---------- items ---------- */

    private function seedItems(array $userIds, int $currencyId, array $cities, array $areas): array
    {
        if (DB::table('items')->count() > 0) {
            return DB::table('items')->pluck('id')->toArray();
        }

        // leaf categories only (categories that are not parents of another)
        $parentIds = DB::table('categories')->whereNotNull('parent_category_id')
            ->distinct()->pluck('parent_category_id')->toArray();
        $leaves = DB::table('categories')
            ->when(!empty($parentIds), fn($q) => $q->whereNotIn('id', $parentIds))
            ->where('status', 1)
            ->get(['id', 'name', 'parent_category_id', 'is_job_category', 'price_optional']);
        if ($leaves->isEmpty()) {
            $leaves = DB::table('categories')->where('status', 1)->get(['id', 'name', 'parent_category_id', 'is_job_category', 'price_optional']);
        }

        $adjectives = ['Brand New', 'Used', 'Excellent Condition', 'Like New', 'Refurbished', 'Premium', 'Vintage', 'Affordable'];
        $statuses = ['approved', 'approved', 'approved', 'approved', 'review', 'sold out', 'featured'];
        $itemIds = [];

        for ($i = 0; $i < 50; $i++) {
            $cat = $leaves->random();
            $city = $cities[array_rand($cities)];
            $name = $adjectives[array_rand($adjectives)] . ' ' . $cat->name;
            $status = $statuses[array_rand($statuses)];
            $isJob = (int)($cat->is_job_category ?? 0) === 1;

            $price = null; $minSal = null; $maxSal = null;
            if ($isJob) {
                $minSal = $this->faker->numberBetween(20000, 40000);
                $maxSal = $minSal + $this->faker->numberBetween(10000, 60000);
            } else {
                $price = $this->faker->numberBetween(50, 5000);
            }

            $sellerId = $userIds[array_rand($userIds)];
            $itemId = DB::table('items')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
                'description' => $this->faker->paragraphs(3, true),
                'price' => $price,
                'currency_id' => $currencyId,
                'min_salary' => $minSal,
                'max_salary' => $maxSal,
                'latitude' => $city->lat,
                'longitude' => $city->lng,
                'address' => $this->faker->streetAddress() . ', ' . $city->name,
                'contact' => $this->faker->numerify('+1##########'),
                'show_only_to_premium' => 0,
                'show_mobile_number' => $this->faker->boolean(70) ? 1 : 0,
                'status' => $status,
                'city' => $city->name,
                'state' => $city->state_name,
                'country' => $city->country_name,
                'area_id' => !empty($areas) ? $areas[array_rand($areas)] : null,
                'user_id' => $sellerId,
                'sold_to' => $status === 'sold out' ? $userIds[array_rand($userIds)] : null,
                'category_id' => $cat->id,
                'all_category_ids' => trim(($cat->parent_category_id ? $cat->parent_category_id . ',' : '') . $cat->id, ','),
                'expiry_date' => now()->addDays($this->faker->numberBetween(10, 90))->toDateString(),
                'clicks' => $this->faker->numberBetween(0, 900),
                'country_code' => '1',
                'created_at' => now()->subDays($this->faker->numberBetween(0, 60)),
                'updated_at' => now(),
            ]);
            $itemIds[] = $itemId;

            // 2-4 gallery images
            $imgCount = $this->faker->numberBetween(2, 4);
            for ($k = 0; $k < $imgCount; $k++) {
                DB::table('item_images')->insert([
                    'image' => $this->makeImage('items', $cat->name, 600, 450),
                    'is_default' => $k === 0 ? 1 : 0,
                    'item_id' => $itemId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        return $itemIds;
    }

    /* ---------- favourites ---------- */

    private function seedFavourites(array $userIds, array $itemIds): void
    {
        if (DB::table('favourites')->count() > 0) return;
        $pairs = [];
        for ($i = 0; $i < 60; $i++) {
            $u = $userIds[array_rand($userIds)];
            $it = $itemIds[array_rand($itemIds)];
            $key = $u . '-' . $it;
            if (isset($pairs[$key])) continue;
            $pairs[$key] = true;
            DB::table('favourites')->insert([
                'user_id' => $u, 'item_id' => $it,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /* ---------- offers ---------- */

    private function seedOffers(array $userIds, array $itemIds): array
    {
        if (DB::table('item_offers')->count() > 0) {
            return DB::table('item_offers')->pluck('id')->toArray();
        }
        $offerIds = [];
        foreach ($itemIds as $itemId) {
            if ($this->faker->boolean(50)) continue; // only some items get offers
            $item = DB::table('items')->where('id', $itemId)->first(['user_id', 'price']);
            $buyer = $userIds[array_rand($userIds)];
            if ($buyer == $item->user_id) continue;
            $offerIds[] = DB::table('item_offers')->insertGetId([
                'seller_id' => $item->user_id,
                'buyer_id' => $buyer,
                'item_id' => $itemId,
                'amount' => $item->price ? round($item->price * $this->faker->randomFloat(2, 0.7, 0.98)) : null,
                'created_at' => now()->subDays($this->faker->numberBetween(0, 20)),
                'updated_at' => now(),
            ]);
        }
        return $offerIds;
    }

    /* ---------- chats ---------- */

    private function seedChats(array $offerIds): void
    {
        if (DB::table('chats')->count() > 0 || empty($offerIds)) return;
        $lines = [
            'Hi, is this still available?',
            'Yes, it is available.',
            'Can you do a better price?',
            'What is the final price?',
            'Where can I pick it up?',
            'Is delivery possible?',
            'Sure, when are you free?',
            'Thanks, I will confirm shortly.',
        ];
        foreach ($offerIds as $offerId) {
            $offer = DB::table('item_offers')->where('id', $offerId)->first(['seller_id', 'buyer_id']);
            $n = $this->faker->numberBetween(2, 6);
            for ($m = 0; $m < $n; $m++) {
                $sender = $m % 2 === 0 ? $offer->buyer_id : $offer->seller_id;
                DB::table('chats')->insert([
                    'sender_id' => $sender,
                    'item_offer_id' => $offerId,
                    'message' => $lines[array_rand($lines)],
                    'is_read' => $this->faker->boolean(70) ? 1 : 0,
                    'created_at' => now()->subMinutes($this->faker->numberBetween(1, 5000)),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /* ---------- ratings ---------- */

    private function seedRatings(array $userIds, array $itemIds): void
    {
        if (DB::table('seller_ratings')->count() > 0) return;
        $reviews = [
            'Great seller, smooth transaction!',
            'Item as described, highly recommend.',
            'Fast response and friendly.',
            'Good deal, would buy again.',
            'Product quality was excellent.',
            'Nice experience overall.',
        ];
        $seen = []; // guard the (item_id, buyer_id) unique constraint
        for ($i = 0; $i < 40; $i++) {
            $itemId = $itemIds[array_rand($itemIds)];
            $item = DB::table('items')->where('id', $itemId)->first(['user_id']);
            $buyer = $userIds[array_rand($userIds)];
            if ($buyer == $item->user_id) continue;
            $key = $itemId . '-' . $buyer;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            DB::table('seller_ratings')->insert([
                'seller_id' => $item->user_id,
                'buyer_id' => $buyer,
                'item_id' => $itemId,
                'review' => $reviews[array_rand($reviews)],
                'ratings' => $this->faker->numberBetween(3, 5),
                'created_at' => now()->subDays($this->faker->numberBetween(0, 40)),
                'updated_at' => now(),
            ]);
        }
    }

    /* ---------- CMS ---------- */

    private function seedCms(array $cities): void
    {
        // sliders
        if (DB::table('sliders')->count() === 0) {
            for ($i = 1; $i <= 4; $i++) {
                DB::table('sliders')->insert([
                    'image' => $this->makeImage('sliders', 'Slide ' . $i, 1200, 400),
                    'sequence' => (string)$i,
                    'third_party_link' => $this->faker->boolean(50) ? 'https://example.com/promo-' . $i : null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // blogs
        if (DB::table('blogs')->count() === 0) {
            $topics = ['Top 10 Tips for Selling Online', 'How to Spot a Good Deal', 'Safety Guide for Buyers',
                'Pricing Your Used Items', 'Best Categories to Sell in 2026', 'Photographing Your Listings'];
            foreach ($topics as $t) {
                DB::table('blogs')->insert([
                    'title' => $t,
                    'slug' => Str::slug($t),
                    'description' => '<p>' . $this->faker->paragraphs(4, true) . '</p>',
                    'image' => $this->makeImage('blogs', substr($t, 0, 12), 800, 500),
                    'tags' => implode(',', $this->faker->words(4)),
                    'views' => $this->faker->numberBetween(10, 2000),
                    'created_at' => now()->subDays($this->faker->numberBetween(1, 90)),
                    'updated_at' => now(),
                ]);
            }
        }

        // faqs
        if (DB::table('faqs')->count() === 0) {
            $faqs = [
                'How do I post an ad?' => 'Log in, tap the "Sell" button, fill in the details and submit for review.',
                'Is it free to list items?' => 'Yes, basic listings are free. Premium packages offer extra visibility.',
                'How do I contact a seller?' => 'Open the listing and use the chat or call button to reach the seller.',
                'How do I mark an item as sold?' => 'Go to your ad and choose "Mark as sold" from the options menu.',
                'How do I report a suspicious listing?' => 'Use the "Report" option on the listing and select a reason.',
            ];
            foreach ($faqs as $q => $a) {
                DB::table('faqs')->insert(['question' => $q, 'answer' => $a, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // report reasons
        if (DB::table('report_reasons')->count() === 0) {
            foreach (['Spam or misleading', 'Prohibited item', 'Fraud or scam', 'Wrong category', 'Offensive content', 'Duplicate listing'] as $r) {
                DB::table('report_reasons')->insert(['reason' => $r, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // tips
        if (DB::table('tips')->count() === 0) {
            $tips = ['Meet in a public place for exchanges.', 'Inspect the item before paying.',
                'Avoid advance payments to strangers.', 'Use secure payment methods.', 'Trust your instincts.'];
            foreach ($tips as $idx => $tip) {
                DB::table('tips')->insert(['description' => $tip, 'sequence' => $idx + 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // contact_us
        if (DB::table('contact_us')->count() === 0) {
            for ($i = 0; $i < 6; $i++) {
                DB::table('contact_us')->insert([
                    'name' => $this->faker->name(),
                    'email' => $this->faker->safeEmail(),
                    'subject' => $this->faker->sentence(4),
                    'message' => $this->faker->paragraph(),
                    'created_at' => now()->subDays($this->faker->numberBetween(0, 30)),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /* ---------- packages ---------- */

    private function seedPackages(): void
    {
        if (DB::table('packages')->count() > 0) return;

        $packages = [
            ['name' => 'Free Starter', 'price' => 0, 'final' => 0, 'type' => 'item_listing', 'limit' => '3', 'dur' => '7'],
            ['name' => 'Basic Listing', 'price' => 9.99, 'final' => 7.99, 'type' => 'item_listing', 'limit' => '20', 'dur' => '30'],
            ['name' => 'Pro Listing', 'price' => 29.99, 'final' => 24.99, 'type' => 'item_listing', 'limit' => 'unlimited', 'dur' => '90'],
            ['name' => 'Featured Boost', 'price' => 14.99, 'final' => 12.99, 'type' => 'advertisement', 'limit' => '10', 'dur' => '15'],
            ['name' => 'Premium Spotlight', 'price' => 49.99, 'final' => 39.99, 'type' => 'advertisement', 'limit' => '50', 'dur' => '60'],
        ];
        $catIds = DB::table('categories')->where('status', 1)->inRandomOrder()->limit(20)->pluck('id')->toArray();

        foreach ($packages as $p) {
            $discount = $p['price'] > 0 ? round((1 - $p['final'] / $p['price']) * 100, 2) : 0;
            $pkgId = DB::table('packages')->insertGetId([
                'name' => $p['name'],
                'final_price' => $p['final'],
                'discount_in_percentage' => $discount,
                'price' => $p['price'],
                'duration' => $p['dur'],
                'listing_duration_type' => 'standard',
                'item_limit' => $p['limit'],
                'type' => $p['type'],
                'is_global' => 1,
                'icon' => $this->makeImage('package', substr($p['name'], 0, 10), 200, 200),
                'description' => $this->faker->sentence(12),
                'key_points' => json_encode(['Priority support', $p['limit'] . ' listings', $p['dur'] . ' days validity']),
                'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            // attach a few categories to non-global-ish demo
            foreach (array_slice($catIds, 0, 4) as $cid) {
                DB::table('package_categories')->insert([
                    'package_id' => $pkgId, 'category_id' => $cid,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    /* ---------- feature sections ---------- */

    private function seedFeatureSections(): void
    {
        if (DB::table('feature_sections')->count() > 0) return;
        $sections = [
            ['title' => 'Most Popular', 'filter' => 'most_liked', 'style' => 'style_1'],
            ['title' => 'Trending Now', 'filter' => 'most_viewed', 'style' => 'style_2'],
            ['title' => 'Featured Ads', 'filter' => 'featured_ads', 'style' => 'style_3'],
            ['title' => 'Budget Deals', 'filter' => 'price_criteria', 'style' => 'style_4', 'min' => 0, 'max' => 500],
        ];
        foreach ($sections as $idx => $s) {
            DB::table('feature_sections')->insert([
                'title' => $s['title'],
                'slug' => Str::slug($s['title']),
                'sequence' => $idx + 1,
                'filter' => $s['filter'],
                'style' => $s['style'],
                'min_price' => $s['min'] ?? null,
                'max_price' => $s['max'] ?? null,
                'description' => $this->faker->sentence(8),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /* ---------- popular categories ---------- */

    private function seedPopularCategories(): void
    {
        if (DB::table('popular_categories')->count() > 0) return;
        $cats = DB::table('categories')->whereNull('parent_category_id')
            ->where('status', 1)->inRandomOrder()->limit(8)->pluck('id')->toArray();
        foreach ($cats as $idx => $cid) {
            DB::table('popular_categories')->insert([
                'category_id' => $cid, 'sequence' => $idx + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
