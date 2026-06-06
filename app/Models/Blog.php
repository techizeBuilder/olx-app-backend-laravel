<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

class Blog extends Model {
    use HasFactory, ManageTranslations;

    protected static function booted()
    {
        static::deleting(function ($model) {
            if ($model->seoDetail) {
                $model->seoDetail->delete();
            }
        });
    }

    protected $dates = ['created_at', 'updated_at'];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'tags'
    ];
    protected $appends = ['translated_title', 'translated_description', 'translated_tags'];

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getImageAttribute($image) {
        if (!empty($image)) {
            return url(Storage::url($image));
        }
        return $image;
    }

        public function getTagsAttribute($value) {
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                return explode(',', $value);
            }

            return [];
        }


    public function setTagsAttribute($value) {
    if (is_array($value)) {
        $cleaned = array_map(fn($tag) => trim($tag, " \t\n\r\0\x0B\"'"), $value);
        $this->attributes['tags'] = implode(',', $cleaned);
    } elseif (is_string($value)) {
        $this->attributes['tags'] = trim($value, " \t\n\r\0\x0B\"'");
    } else {
        $this->attributes['tags'] = '';
    }
}



    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function seoDetail(): MorphOne
    {
        return $this->morphOne(\App\Models\SeoDetail::class, 'seoable');
    }
    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        $query = $query->where(function ($q) use ($search) {
            $q->orWhere('title', 'LIKE', $search)
                ->orWhere('description', 'LIKE', $search)
                ->orWhere('tags', 'LIKE', $search);
        });
        return $query;
    }

    public function scopeSort($query, $column, $order) {
        if ($column == "category_name") {
            return $query->leftJoin('categories', 'categories.id', '=', 'blogs.category_id')
                ->orderBy('categories.name', $order)
                ->select('blogs.*');
        }
        return $query->orderBy($column, $order);
    }
    public function getTranslatedTitleAttribute()
    {
        return $this->getTranslatedValue('title', $this->title);
    }

    public function getTranslatedTagsAttribute()
    {
        $translatedTags = $this->getTranslatedValue('tags', null);

        if (!empty($translatedTags)) {
            if (is_array($translatedTags)) {
                return array_map(fn($tag) => trim($tag, " \t\n\r\0\x0B\"'"), $translatedTags);
            }

            if (is_string($translatedTags)) {
                return array_map(fn($tag) => trim($tag, " \t\n\r\0\x0B\"'"), explode(',', $translatedTags));
            }
        }

        return array_map(fn($tag) => trim($tag, " \t\n\r\0\x0B\"'"), $this->tags ?? []);
    }

    public function getTranslatedDescriptionAttribute()
    {
        return $this->getTranslatedValue('description', $this->description);
    }

}
