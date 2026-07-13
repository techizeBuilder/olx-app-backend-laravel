<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BannerItem extends Model
{
    use HasFactory;

    public const AD_TYPES = ['only_banner', 'category', 'advertisement', 'external_link'];

    protected $fillable = [
        'banner_id',
        'image',
        'ad_type',
        'category_id',
        'item_id',
        'external_link',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getImageAttribute($image)
    {
        if (! empty($image)) {
            return url(Storage::url($image));
        }

        return $image;
    }

    public static function adTypeLabel(?string $type): string
    {
        return match ($type) {
            'category'      => 'Category',
            'advertisement' => 'Advertisement',
            'external_link' => 'External Link',
            'only_banner'   => 'Only Banner',
            default         => '-',
        };
    }

    public function getAdTypeLabelAttribute(): string
    {
        return self::adTypeLabel($this->ad_type);
    }

    /**
     * Where this banner should take the user when tapped.
     */
    public function getTargetAttribute(): ?array
    {
        return match ($this->ad_type) {
            'category'      => $this->category_id ? ['type' => 'category', 'id' => $this->category_id] : null,
            'advertisement' => $this->item_id ? ['type' => 'item', 'id' => $this->item_id] : null,
            'external_link' => $this->external_link ? ['type' => 'link', 'url' => $this->external_link] : null,
            default         => null,
        };
    }
}
