<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdvertisementDemoSeeder extends Seeder
{
    /**
     * Curated, realistic demo advertisements.
     * `cat` is matched against category names (LIKE); `img` is the photo keyword.
     */
    private array $ads = [
        ['cat' => 'Headphones',        'name' => 'Sony WH-1000XM4 Wireless Headphones', 'price' => 18999, 'img' => 'headphones',   'desc' => 'Industry-leading noise cancellation, 30-hour battery. Barely used, with box and warranty.'],
        ['cat' => 'Televisions',       'name' => 'Samsung 55" 4K UHD Smart TV',          'price' => 42000, 'img' => 'television',   'desc' => 'Crystal 4K display, built-in apps. Excellent condition, no scratches, all accessories included.'],
        ['cat' => 'Smart Watches',     'name' => 'Apple Watch Series 8 (GPS, 45mm)',      'price' => 32999, 'img' => 'smartwatch',   'desc' => 'Midnight aluminium case. Great health tracking. 100% battery health, original charger.'],
        ['cat' => 'Speakers',          'name' => 'JBL Flip 6 Portable Speaker',           'price' => 7499,  'img' => 'speaker',      'desc' => 'Powerful bass, waterproof, 12-hour playtime. Like new condition.'],
        ['cat' => 'Refrigerators',     'name' => 'LG 260L Double Door Refrigerator',      'price' => 21500, 'img' => 'refrigerator', 'desc' => 'Frost-free, energy efficient 3-star. Smooth cooling, well maintained.'],
        ['cat' => 'Air Conditioners',  'name' => 'Voltas 1.5 Ton 3-Star Split AC',        'price' => 24999, 'img' => 'airconditioner', 'desc' => 'Copper condenser, fast cooling. 2 years old, recently serviced.'],
        ['cat' => 'Washing Machines',  'name' => 'Bosch 7kg Front Load Washing Machine',  'price' => 27000, 'img' => 'washingmachine', 'desc' => 'Fully automatic, multiple wash programs. Works perfectly, moving out sale.'],
        ['cat' => 'Cars',              'name' => 'Maruti Suzuki Swift VXI 2020',          'price' => 585000, 'img' => 'car',         'desc' => 'Single owner, 32,000 km, petrol, full service history. Excellent mileage and condition.'],
        ['cat' => 'Motorcycles',       'name' => 'Royal Enfield Classic 350 (2021)',      'price' => 148000, 'img' => 'motorcycle',  'desc' => 'Stealth black, 9,500 km run. Perfectly maintained, all papers clear.'],
        ['cat' => 'Shoes',             'name' => 'Nike Air Max Running Shoes (UK 9)',     'price' => 4200,  'img' => 'sneakers',     'desc' => 'Original Nike, worn twice. Extremely comfortable, box included.'],
        ['cat' => 'Cycling',           'name' => 'Trek Marlin 5 Mountain Bike',           'price' => 26500, 'img' => 'bicycle',      'desc' => '21-speed, disc brakes, aluminium frame. Great for trails and city riding.'],
        ['cat' => 'Furniture',         'name' => 'Solid Sheesham Wood 6-Seater Dining Set','price' => 34000, 'img' => 'furniture',   'desc' => 'Handcrafted dining table with 6 chairs. Sturdy and elegant, minimal use.'],
        ['cat' => 'Dog',               'name' => 'Labrador Retriever Puppy',              'price' => 12000, 'img' => 'labrador',     'desc' => 'Healthy, vaccinated 2-month-old lab puppy. Friendly and playful. KCI certified.'],
        ['cat' => 'Cat',               'name' => 'Persian Cat Kitten',                    'price' => 9000,  'img' => 'persiancat',   'desc' => 'Pure Persian kitten, litter trained, vaccinated. Very affectionate.'],
        ['cat' => 'BMW',               'name' => 'BMW 3 Series 320d 2019',                'price' => 2650000, 'img' => 'bmw',        'desc' => 'Luxury sedan, diesel, 41,000 km. Sunroof, leather seats, top condition.'],
        ['cat' => 'Audi',              'name' => 'Audi A4 Premium Plus 2018',             'price' => 2380000, 'img' => 'audi',        'desc' => 'Well-maintained, single owner, full insurance. Smooth drive, no issues.'],
        ['cat' => 'Refrigerators',     'name' => 'Whirlpool 190L Single Door Fridge',     'price' => 11500, 'img' => 'fridge',       'desc' => 'Compact, perfect for small families. 4-star rating, low power usage.'],
        ['cat' => 'Speakers',          'name' => 'Marshall Emberton II Speaker',          'price' => 12999, 'img' => 'bluetoothspeaker', 'desc' => 'Iconic sound, 30+ hour battery, IP67 rated. Mint condition with cable.'],
    ];

    public function run(): void
    {
        $disk = Storage::disk('public');
        $user = User::first();

        if (! $user) {
            $this->command?->warn('AdvertisementDemoSeeder: no user found. Seed base data first.');

            return;
        }

        // Clean previously seeded demo ads (idempotent) — files, images, items.
        foreach ($disk->files('items') as $file) {
            if (Str::startsWith(basename($file), 'demo_ad_')) {
                $disk->delete($file);
            }
        }
        $oldIds = Item::where('slug', 'like', 'demo-ad-%')->pluck('id');
        if ($oldIds->isNotEmpty()) {
            ItemImages::whereIn('item_id', $oldIds)->delete();
            Item::whereIn('id', $oldIds)->forceDelete();
        }

        $created = 0;
        $lock    = 100; // stable image seed per ad

        foreach ($this->ads as $ad) {
            $category = Category::where('status', 1)
                ->where('name', 'like', '%' . $ad['cat'] . '%')
                ->first();

            if (! $category) {
                $this->command?->warn("AdvertisementDemoSeeder: category '{$ad['cat']}' not found, skipped '{$ad['name']}'.");
                continue;
            }

            $item = Item::create([
                'name'                 => $ad['name'],
                'slug'                 => 'demo-ad-' . Str::slug($ad['name']) . '-' . Str::lower(Str::random(5)),
                'description'          => $ad['desc'],
                'price'                => $ad['price'],
                'currency_id'          => null,
                'latitude'             => 21.1702,          // Surat, Gujarat
                'longitude'            => 72.8311,
                'address'              => 'Ring Road, Surat, Gujarat, India',
                'contact'              => '+919876543210',
                'country_code'         => '+91',
                'show_only_to_premium' => 0,
                'show_mobile_number'   => 1,
                'status'               => 'approved',
                'user_id'              => $user->id,
                'country'              => 'India',
                'state'                => 'Gujarat',
                'city'                 => 'Surat',
                'category_id'          => $category->id,
                'all_category_ids'     => (string) $category->id,
                'clicks'               => random_int(5, 250),
                'is_edited_by_admin'   => 0,
                'expiry_date'          => Carbon::now()->addDays(30)->toDateString(),
                'created_at'           => Carbon::now()->subDays(random_int(1, 20)),
            ]);

            // Real, relevant photo for the ad's default image.
            $target = 'items/demo_ad_' . $item->id . '_' . Str::random(8) . '.jpg';
            $stored = $this->downloadImage($ad['img'], $lock++, $target, $disk);

            if (! $stored && ! empty($category->getRawOriginal('image')) && $disk->exists($category->getRawOriginal('image'))) {
                $disk->copy($category->getRawOriginal('image'), $target);
                $stored = true;
            }

            if ($stored) {
                ItemImages::create([
                    'item_id'    => $item->id,
                    'image'      => $target,
                    'is_default' => 1,
                ]);
            }

            $created++;
        }

        $this->command?->info("AdvertisementDemoSeeder: {$created} advertisements created (approved, with images).");
    }

    private function downloadImage(string $keyword, int $lock, string $target, $disk): bool
    {
        $url = "https://loremflickr.com/900/700/{$keyword}?lock={$lock}";

        try {
            $response = Http::timeout(25)->retry(2, 500)->get($url);

            if ($response->successful() && strlen($response->body()) > 3000) {
                $disk->put($target, $response->body());

                return true;
            }
        } catch (\Throwable $e) {
            $this->command?->warn("AdvertisementDemoSeeder: image download failed for '{$keyword}'.");
        }

        return false;
    }
}
