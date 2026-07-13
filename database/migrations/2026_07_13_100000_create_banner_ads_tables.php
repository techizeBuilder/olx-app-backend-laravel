<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A banner "group": where it shows and how it is laid out.
        if (! Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->enum('platform', ['website', 'app']);
                $table->enum('page', ['home', 'details', 'listing']);
                $table->enum('layout', ['single', 'dual']);
                $table->unsignedInteger('sequence')->default(0); // placement order on the page
                $table->boolean('status')->default(1);
                $table->timestamps();

                $table->index(['platform', 'page', 'status']);
            });
        }

        // The individual banner images inside a group (1 for single, 2 for dual).
        if (! Schema::hasTable('banner_items')) {
            Schema::create('banner_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('banner_id')->constrained('banners')->onDelete('cascade');
                $table->string('image');
                $table->enum('ad_type', ['only_banner', 'category', 'advertisement', 'external_link'])
                    ->default('only_banner');
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
                $table->string('external_link')->nullable();
                $table->unsignedTinyInteger('position')->default(1); // 1 = Banner 1, 2 = Banner 2
                $table->timestamps();

                $table->index(['banner_id', 'position']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_items');
        Schema::dropIfExists('banners');
    }
};
