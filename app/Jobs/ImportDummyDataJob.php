<?php

namespace App\Jobs;

use Database\Seeders\AdvertisementDemoSeeder;
use Database\Seeders\SliderDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImportDummyDataJob
{

    public function handle(): void
    {
        Log::info('🚀 Dummy data import started.');

        try {
            // TRUNCATE operations auto-commit in MySQL, so we don't wrap them in a transaction
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Clear existing custom-field & category data before re-import.
            // Each table is guarded so the job survives schema changes across versions.
            foreach (['custom_field_categories', 'item_custom_field_values', 'custom_fields', 'categories'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            // Translations were unified into the morph-based `translations` table in v2.12.0
            // (the old `custom_fields_translations` table was dropped). Clear related rows.
            if (Schema::hasTable('translations')) {
                DB::table('translations')
                    ->whereIn('translatable_type', ['App\\Models\\CustomField', 'App\\Models\\Category'])
                    ->delete();
            } elseif (Schema::hasTable('custom_fields_translations')) {
                // Backwards-compat for pre-v2.12.0 schema
                DB::table('custom_fields_translations')->truncate();
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Delete storage directories
            Storage::deleteDirectory('category');
            Storage::deleteDirectory('custom-fields');

            // Validate required files
            $sqlFilePath = public_path('categories_and_sub_custom_field_demo.sql');
            $zipFilePath = public_path('dummy_data.zip');

            if (!file_exists($sqlFilePath)) {
                throw new \Exception("SQL file not found at: {$sqlFilePath}");
            }

            if (!file_exists($zipFilePath)) {
                throw new \Exception("Images ZIP file not found at: {$zipFilePath}");
            }

            // Execute SQL file statements
            $sqlContent = file_get_contents($sqlFilePath);
            $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
            $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

            $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        DB::statement($statement);
                    } catch (\Exception $e) {
                        Log::warning('SQL statement failed: ' . $e->getMessage());
                        // Continue with next statement
                    }
                }
            }

            // Extract ZIP file
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath) === TRUE) {
                $extractPath = storage_path('app/public');
                if (!File::exists($extractPath)) {
                    File::makeDirectory($extractPath, 0755, true);
                }
                $zip->extractTo($extractPath);
                $zip->close();
                Log::info('ZIP file extracted successfully.');
            } else {
                throw new \Exception('Failed to extract ZIP file.');
            }

            // Seed home-screen ad sliders (one per top-level category) now that
            // categories and their images have been imported.
            try {
                (new SliderDemoSeeder())->run();
                Log::info('Home sliders seeded successfully.');
            } catch (\Throwable $sliderEx) {
                // Don't fail the whole import if slider seeding hits an issue.
                Log::warning('Home slider seeding failed: ' . $sliderEx->getMessage());
            }

            // Seed demo advertisements (approved item listings with real images).
            try {
                (new AdvertisementDemoSeeder())->run();
                Log::info('Demo advertisements seeded successfully.');
            } catch (\Throwable $adsEx) {
                Log::warning('Advertisement seeding failed: ' . $adsEx->getMessage());
            }

            Log::info('✅ Dummy data import completed successfully.');

        } catch (\Throwable $th) {
            Log::error('❌ Dummy data import failed: ' . $th->getMessage());
            Log::error('Stack trace: ' . $th->getTraceAsString());
            
            // Re-enable foreign key checks in case of error
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $e) {
                Log::warning('Failed to re-enable foreign key checks: ' . $e->getMessage());
            }
        }
    }
}
