<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banner extends Model
{
    use HasFactory;

    public const PLATFORMS = ['website', 'app'];

    public const PAGES = ['home', 'details', 'listing'];

    public const LAYOUTS = ['single', 'dual'];

    protected $fillable = ['platform', 'page', 'layout', 'sequence', 'status'];

    protected $casts = [
        'status'   => 'boolean',
        'sequence' => 'integer',
    ];

    public function bannerItems(): HasMany
    {
        return $this->hasMany(BannerItem::class)->orderBy('position');
    }

    /** Human labels used by the admin table. */
    public function getPlatformLabelAttribute(): string
    {
        return $this->platform === 'app' ? 'App' : 'Website';
    }

    public function getPageLabelAttribute(): string
    {
        return match ($this->page) {
            'details' => 'Ads Details Page',
            'listing' => 'Listing Page',
            default   => 'Homepage',
        };
    }

    public function getLayoutLabelAttribute(): string
    {
        return $this->layout === 'dual' ? 'Dual' : 'Single';
    }

    /**
     * The ad type shown in the list: the shared type when both banners match,
     * otherwise "Mixed".
     */
    public function getAdTypeLabelAttribute(): string
    {
        $types = $this->bannerItems->pluck('ad_type')->unique();

        if ($types->isEmpty()) {
            return '-';
        }

        if ($types->count() > 1) {
            return 'Mixed';
        }

        return BannerItem::adTypeLabel($types->first());
    }

    public function scopeSearch($query, $search)
    {
        $search = '%' . $search . '%';

        return $query->where(function ($q) use ($search) {
            $q->orWhere('platform', 'LIKE', $search)
                ->orWhere('page', 'LIKE', $search)
                ->orWhere('layout', 'LIKE', $search)
                ->orWhereHas('bannerItems', function ($q) use ($search) {
                    $q->where('ad_type', 'LIKE', $search)
                        ->orWhere('external_link', 'LIKE', $search);
                });
        });
    }
}
