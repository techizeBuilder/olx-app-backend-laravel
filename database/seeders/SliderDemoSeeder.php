<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Slider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SliderDemoSeeder extends Seeder
{
    /**
     * Real, category-relevant banner keywords for the home-screen sliders.
     * Falls back to the first word of the category name when not listed.
     */
    private array $keywordMap = [
        'Electronics'               => 'electronics',
        'Home appliances'           => 'home,appliance',
        'Sports Equipment'          => 'sports',
        'Furniture, Home & Garden'  => 'furniture',
        'Clothing & Accessories'    => 'fashion,clothing',
        'Jewelry & Watches'         => 'jewelry',
        'Cameras & Imaging'         => 'camera',
        'Consoles and Video Games'  => 'videogame',
        'Computers & Networking'    => 'computer,laptop',
        'Musical Instruments'       => 'music,instrument',
        'Mobile Phones & Tablets'   => 'smartphone',
        'Cars & Vehicles'           => 'car',
    ];

    /**
     * Seed home-screen ad sliders, one per top-level category.
     *
     * Each slider links to a real category (morph model_type/model_id) so
     * tapping it opens that category. The banner image is a real,
     * category-relevant photo downloaded from loremflickr (1200x500). If the
     * download fails (e.g. no internet), it falls back to the category's own
     * image so the seeder still produces a usable slider.
     *
     * Safe to re-run: previously seeded slider files and rows are cleared first.
     */
    public function run(): void
    {
        $disk = Storage::disk('public');

        // Remove previously seeded banner files to avoid orphans on re-run.
        foreach ($disk->files('sliders') as $file) {
            if (Str::startsWith(basename($file), 'slider_home_')) {
                $disk->delete($file);
            }
        }

        Slider::query()->delete();

        $categories = Category::whereNull('parent_category_id')
            ->where('status', 1)
            ->orderBy('id')
            ->limit(8)
            ->get();

        if ($categories->isEmpty()) {
            $this->command?->warn('SliderDemoSeeder: no top-level categories found. Import category data first.');

            return;
        }

        $sequence = 1;
        $created  = 0;

        foreach ($categories as $category) {
            $target = 'sliders/slider_home_' . $category->id . '_' . Str::random(10) . '.jpg';

            $stored = $this->downloadRealBanner($category, $target, $disk)
                ?: $this->fallbackToCategoryImage($category, $target, $disk);

            if (! $stored) {
                $this->command?->warn("SliderDemoSeeder: no image for category #{$category->id} ({$category->name}), skipped.");
                continue;
            }

            $slider = Slider::create([
                'image'            => $target,
                'third_party_link' => '',   // category-linked slider, no external URL
                'sequence'         => $sequence,
                'country_id'       => null, // shown everywhere (no location filter)
                'state_id'         => null,
                'city_id'          => null,
            ]);

            // Proper link: tapping the slider opens this category.
            $slider->model()->associate($category)->save();

            $sequence++;
            $created++;
        }

        $this->command?->info("SliderDemoSeeder: {$created} home sliders created (category-linked, real images).");
    }

    /**
     * Download a real, category-relevant banner. Returns true on success.
     */
    private function downloadRealBanner(Category $category, string $target, $disk): bool
    {
        $keyword = $this->keywordMap[$category->name]
            ?? Str::of($category->name)->lower()->before(' ')->before('&')->trim()->value();

        if ($keyword === '') {
            return false;
        }

        // `lock` keeps the same photo for a category across re-runs.
        $url = "https://loremflickr.com/1200/500/{$keyword}?lock={$category->id}";

        try {
            $response = Http::timeout(25)->retry(2, 500)->get($url);

            if ($response->successful() && strlen($response->body()) > 3000) {
                $disk->put($target, $response->body());

                return true;
            }
        } catch (\Throwable $e) {
            $this->command?->warn("SliderDemoSeeder: download failed for '{$keyword}' ({$e->getMessage()}), using fallback.");
        }

        return false;
    }

    /**
     * Fallback: copy the category's own image as the banner. Returns true on success.
     */
    private function fallbackToCategoryImage(Category $category, string $target, $disk): bool
    {
        $source = $category->getRawOriginal('image');

        if (! empty($source) && $disk->exists($source)) {
            $disk->copy($source, $target);

            return true;
        }

        return false;
    }
}
