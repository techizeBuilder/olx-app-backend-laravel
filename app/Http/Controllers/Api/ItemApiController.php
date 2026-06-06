<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ItemCollection;
use App\Models\Category;
use App\Models\Favourite;
use App\Models\FeaturedItems;
use App\Models\FeatureSection;
use App\Models\Item;
use App\Models\ItemCustomFieldValue;
use App\Models\ItemImages;
use App\Models\ItemOffer;
use App\Models\Language;
use App\Models\Package;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserPurchasedPackage;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * @tags Item
 */
class ItemApiController extends BaseApiController
{
    private string $uploadFolder;

    public function __construct()
    {
        parent::__construct();
        $this->uploadFolder = 'item_images';
    }

    /**
     * Get Limits
     */
    public function getLimits(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'package_type' => 'required|in:item_listing,advertisement',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $setting = Setting::where('name', 'free_ad_listing')->first()['value'];
            if ($setting == 1 && $request->package_type != 'advertisement') {
                return ResponseService::successResponse(__('User is allowed to create Advertisement'));
            }
            $user_package = UserPurchasedPackage::onlyActive()->whereHas('package', function ($q) use ($request) {
                $q->where('type', $request->package_type);
            })->count();
            if ($user_package > 0) {
                ResponseService::successResponse(__('User is allowed to create Advertisement'));
            }
            ResponseService::errorResponse(__('User is not allowed to create Advertisement'), $user_package);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getLimits');
            ResponseService::errorResponse();
        }
    }

    /**
     * Add Item
     */
    public function addItem(Request $request)
    {
        try {
            // Step 1: Base validation rules
            $rules = [
                'name'                 => 'required',
                'category_id'          => 'required|integer',
                'description'          => 'required',
                'latitude'             => 'required',
                'longitude'            => 'required',
                'address'              => 'required',
                'contact'              => 'nullable|numeric',
                'video_link'           => 'nullable|url',
                'gallery_images'       => 'required|array|min:1',
                'gallery_images.*'     => 'required|mimes:jpeg,png,jpg|max:7168',
                'country'              => 'required',
                'state'                => 'nullable',
                'city'                 => 'required',
                'custom_field_files'   => 'nullable|array',
                'custom_field_files.*' => 'nullable|mimes:jpeg,png,jpg,pdf,doc|max:7168',
                'slug' => [
                    'nullable',
                    'regex:/^(?!-)(?!.*--)(?!.*-$)(?!-$)[a-z0-9-]+$/',
                ],
                'region_code'      => 'nullable|string',
                'country_code'     => 'nullable|string',
                'currency_id'      => 'nullable|exists:currencies,id',
                'all_category_ids'   => 'nullable',
                'seo_details'       => 'nullable|array',
                'seo_details.*'     => 'nullable|array',
                'seo_details.*.meta_title' => 'nullable|string',
                'seo_details.*.meta_description' => 'nullable|string',
                'seo_details.*.meta_keywords' => 'nullable|string',
                'seo_details.*.schema' => 'nullable|string',
            ];

            // Step 2: Extend rules based on category
            $category      = Category::findOrFail($request->category_id);
            $isJobCategory = $category->is_job_category;
            $isPriceOptional = $category->price_optional;

            if ($isJobCategory || $isPriceOptional) {
                $rules['min_salary'] = 'nullable|numeric|min:0';
                if (isset($request->min_salary) && $request->min_salary > 0) {
                    $rules['max_salary'] = 'nullable|numeric|gte:min_salary';
                }
            } else {
                $rules['price'] = 'required|numeric|min:0';
            }

            // Step 3: Run single combined validator
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            // Step 4: Translations validation (separate, throws on failure)
            $translations = json_decode($request->input('translations', '{}'), true, 512, JSON_THROW_ON_ERROR);
            if (!empty($translations)) {
                foreach ($translations as $languageId => $translation) {
                    Validator::make($translation, [
                        'name'              => 'required|string|max:255',
                        'slug'              => 'nullable|regex:/^[a-z0-9-]+$/',
                        'description'       => 'nullable|string',
                        'address'           => 'nullable|string',
                        'video_link'        => 'nullable|url',
                        'rejected_reason'   => 'nullable|string',
                        'admin_edit_reason' => 'nullable|string',
                    ])->validate();
                }
            }
            DB::beginTransaction();
            $user = Auth::user();
            $free_ad_listing = Setting::where('name', 'free_ad_listing')->value('value') ?? 0;
            $auto_approve_item = Setting::where('name', 'auto_approve_item')->value('value') ?? 0;

            if ($auto_approve_item == 1 || $user->auto_approve_item == 1) {
                $status = 'approved';
            } else {
                $status = 'review';
            }

            // Get all active packages for the user
            $user_packages = null;
            $selectedPackageId = null;
            $selectedUserPackage = null;

            // Only check package if free_ad_listing is not enabled
            if ($free_ad_listing != 1) {
                // Get ALL active packages for the user
                $user_packages = UserPurchasedPackage::onlyActive()
                    ->whereHas('package', static function ($q) {
                        $q->where('type', 'item_listing');
                    })
                    ->where('user_id', $user->id)
                    ->with('package.package_categories')
                    ->get();

                if ($user_packages->isEmpty()) {
                    DB::rollBack();
                    ResponseService::errorResponse(__('No Active Package found for Advertisement Creation'));
                }

                // Get all_category_ids from request (should contain category and all parent categories)
                $allCategoryIds = $request->input('all_category_ids', []);

                // If it comes as comma-separated string, convert to array
                if (is_string($allCategoryIds)) {
                    $allCategoryIds = array_filter(
                        array_map('intval', explode(',', $allCategoryIds))
                    );
                }

                // Ensure it's always an array
                if (! is_array($allCategoryIds)) {
                    $allCategoryIds = [];
                }

                foreach ($allCategoryIds as $key => $value) {
                    $category = Category::find($value);
                    if ($category) {
                        $allCategoryIds[] = $category->id;
                    }
                }


                // If all_category_ids not provided, build it from category_id
                if (empty($allCategoryIds) && $request->category_id) {
                    $allCategoryIds = [$request->category_id];
                    $currentCategoryId = $request->category_id;

                    // Traverse up the parent chain
                    while ($currentCategoryId) {
                        $parentCategory = Category::find($currentCategoryId);
                        if ($parentCategory && $parentCategory->parent_category_id) {
                            $allCategoryIds[] = $parentCategory->parent_category_id;
                            $currentCategoryId = $parentCategory->parent_category_id;
                        } else {
                            break;
                        }
                    }
                    $allCategoryIds = array_unique($allCategoryIds);
                }

                // Check for global package first
                $globalPackage = $user_packages->firstWhere(function ($userPackage) {
                    return $userPackage->package && $userPackage->package->is_global == 1;
                });

                if ($globalPackage) {
                    // Use global package
                    $selectedPackageId = $globalPackage->package_id;
                    $selectedUserPackage = $globalPackage;
                } else {
                    // No global package, check if any package contains any category from all_category_ids
                    $matchingPackage = null;

                    foreach ($user_packages as $user_package) {
                        if ($user_package->package && $user_package->package->is_global != 1) {
                            $packageCategoryIds = $user_package->package->package_categories
                                ->pluck('category_id')
                                ->toArray();

                            // Check if any category from all_category_ids is in this package
                            $hasMatchingCategory = ! empty(array_intersect($allCategoryIds, $packageCategoryIds));

                            if ($hasMatchingCategory) {
                                $matchingPackage = $user_package;
                                break;
                            }
                        }
                    }

                    if ($matchingPackage) {
                        $selectedPackageId = $matchingPackage->package_id;
                        $selectedUserPackage = $matchingPackage;
                    } else {
                        DB::rollBack();
                        ResponseService::errorResponse(__('Selected category is not available in your package'));
                    }
                }
            }

            // Increment used_limit for the selected package
            if ($selectedUserPackage) {
                $selectedUserPackage->used_limit++;
                $selectedUserPackage->save();
            }

            $slug = trim($request->input('slug') ?? '');
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($slug));
            $slug = trim($slug, '-');
            if (empty($slug)) {
                $slug = HelperService::generateRandomSlug();
            }
            $uniqueSlug = HelperService::generateUniqueSlug(new Item, $slug);

            // Calculate expiry date based on package listing duration
            $package = $selectedPackageId ? Package::find($selectedPackageId) : null;
            $expiryDate = HelperService::calculateItemExpiryDate($package, $selectedUserPackage);

            // Process files BEFORE creating item to reduce transaction time
            $customFieldFilePaths = [];

            // Process custom field files before transaction
            if ($request->hasFile('custom_field_files')) {
                foreach ($request->file('custom_field_files') as $key => $file) {
                    if (!empty($file)) {
                        $customFieldFilePaths[$key] = FileService::upload($file, 'custom_fields_files');
                    }
                }
            }

            $data = [
                ...$request->all(),
                'name' => $request->name,
                'slug' => $uniqueSlug,
                'status' => $status,
                'active' => 'deactive',
                'user_id' => $user->id,
                'package_id' => $selectedPackageId ?? null,
                'expiry_date' => $expiryDate,
            ];
            $item = Item::create($data);



            if ($request->hasFile('gallery_images')) {
                $files = $request->file('gallery_images');
                $galleryImages = [];
                foreach ($files as $index => $file) {
                    $galleryImages[] = [
                        'image'      => FileService::compressAndUpload($file, 'item_images', true),
                        'is_default' => $index == 0 ? 1 : 0,
                        'item_id'    => $item->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                // Insert in chunks of 10 to avoid large queries
                if(!empty($galleryImages)){
                    foreach (array_chunk($galleryImages, 10) as $chunk) {
                        ItemImages::insert($chunk);
                    }
                }else{
                    ResponseService::validationError(__("Images are empty, need at least one image"));
                }
            }


            if (! empty($translations)) {
                foreach ($translations as $languageId => $translationData) {
                    // Optional: Check if language ID exists
                    if (Language::where('id', $languageId)->exists()) {
                        HelperService::storeTranslations([
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'name', 'value' => $translationData['name'], 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'description', 'value' => $translationData['description'] ?? '', 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'address', 'value' => $translationData['address'] ?? '', 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'rejected_reason', 'value' => $translationData['rejected_reason'] ?? null, 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'admin_edit_reason', 'value' => $translationData['admin_edit_reason'] ?? null, 'language_id' => $languageId],
                        ]);
                    }
                }
            }

            // Process custom fields in chunks
            if ($request->custom_fields) {
                $itemCustomFieldValues = [];
                foreach (json_decode($request->custom_fields, true, 512, JSON_THROW_ON_ERROR) as $key => $custom_field) {
                    $itemCustomFieldValues[] = [
                        'item_id' => $item->id,
                        'language_id' => 1,
                        'custom_field_id' => $key,
                        'value' => json_encode($custom_field, JSON_THROW_ON_ERROR),
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];
                }

                if (count($itemCustomFieldValues) > 0) {
                    foreach (array_chunk($itemCustomFieldValues, 20) as $chunk) {
                        ItemCustomFieldValue::insert($chunk);
                    }
                }
            }

            if (!empty($customFieldFilePaths)) {
                $itemCustomFieldValues = [];
                foreach ($customFieldFilePaths as $key => $filePath) {
                    $itemCustomFieldValues[] = [
                        'item_id' => $item->id,
                        'language_id' => 1,
                        'custom_field_id' => $key,
                        'value' => $filePath,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];
                }

                if (count($itemCustomFieldValues) > 0) {
                    foreach (array_chunk($itemCustomFieldValues, 20) as $chunk) {
                        try {
                            DB::connection()->getPdo();
                        } catch (\Exception $e) {
                            DB::reconnect();
                        }
                        ItemCustomFieldValue::insert($chunk);
                    }
                }
            }
            if ($request->has('custom_field_translations')) {
                $customFieldTranslations = $request->input('custom_field_translations');

                if (! is_array($customFieldTranslations)) {
                    $customFieldTranslations = html_entity_decode($customFieldTranslations);
                    $customFieldTranslations = json_decode($customFieldTranslations, true, 512, JSON_THROW_ON_ERROR);
                }

                $translatedEntries = [];

                foreach ($customFieldTranslations as $languageId => $fieldsByCustomField) {
                    foreach ($fieldsByCustomField as $customFieldId => $translatedValue) {
                        $translatedEntries[] = [
                            'item_id' => $item->id,
                            'custom_field_id' => $customFieldId,
                            'language_id' => $languageId,
                            'value' => json_encode($translatedValue, JSON_THROW_ON_ERROR),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Insert in chunks of 20
                if (! empty($translatedEntries)) {
                    foreach (array_chunk($translatedEntries, 20) as $chunk) {
                        try {
                            DB::connection()->getPdo();
                        } catch (\Exception $e) {
                            DB::reconnect();
                        }
                        ItemCustomFieldValue::insert($chunk);
                    }
                }
            }

            DB::commit();

            // Store SEO details from API (outside transaction)
            if ($request->has('seo_details')) {
                $seoData = $request->seo_details;
                if (!empty($seoData)) {
                    HelperService::storeSeoDetailsFromApi($item, $seoData);
                }
            }

            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                DB::reconnect();
            }

            $result = Item::with([
                'user:id,name,email,mobile,profile,country_code',
                'category:id,name,image,is_job_category,price_optional',
                'gallery_images:id,image,item_id,is_default',
                'area:id,name',
                'translations',
                'seoDetail.translations',
            ])
                ->where('items.id', $item->id)
                ->first();

            if ($result) {
                $result->loadMissing(['featured_items', 'favourites']);
                if ($result->item_custom_field_values()->exists()) {
                    $result->load('item_custom_field_values.custom_field:id,name,type');
                }
            }

            $result = new ItemCollection(collect([$result]));
            try {
                $followerIds = UserFollow::where('following_id', $user->id)
                    ->pluck('follower_id')
                    ->toArray();

                if (!empty($followerIds)) {
                    $userName = $user->name ?? 'Someone';
                    $notificationTitle = __('New Advertisement Posted');
                    $notificationMessage = __(':name has posted a new advertisement', ['name' => $userName]);

                    $customBodyFields = [
                        'item_id' => $item->id,
                        'user_id' => $user->id,
                        'user_name' => $userName,
                        'type' => 'new_item',
                    ];

                    NotificationService::dispatchChunkedNotifications(
                        $notificationTitle,
                        $notificationMessage,
                        'new-item',
                        $customBodyFields,
                        false,
                        $followerIds
                    );
                }
            } catch (Throwable $notificationError) {
                Log::error('Failed to send notifications to followers for new item', [
                    'item_id' => $item->id,
                    'user_id' => $user->id,
                    'error' => $notificationError->getMessage(),
                ]);
            }

            ResponseService::successResponse(__('Advertisement Added Successfully'), $result);
        } catch (Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            ResponseService::logErrorResponse($th, 'API Controller -> addItem');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get My Items
     */
    public function getMyItems(Request $request)
    {
        return $this->getItem($request);
    }

    /**
     * Get Items
     */


    // TODO: need to improve this function
    public function getItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
            'id' => 'nullable',
            'custom_fields' => 'nullable',
            'slug' => 'nullable|string',
            'category_id' => 'nullable',
            'user_id' => 'nullable',
            'min_price' => 'nullable',
            'max_price' => 'nullable',
            'sort_by' => 'nullable|in:new-to-old,old-to-new,price-high-to-low,price-low-to-high,popular_items',
            'posted_since' => 'nullable|in:all-time,today,within-1-week,within-2-week,within-1-month,within-3-month',
            'current_page' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            // TODO : need to simplify this whole module
            $maxPrice = Item::max('price') ?? 0;

            $sql = Item::with(
                'user:id,name,email,mobile,profile,created_at,is_verified,show_personal_details,country_code',
                'category:id,name,image,is_job_category,price_optional',
                'gallery_images:id,image,item_id,is_default',
                'featured_items',
                'favourites',
                'item_custom_field_values.custom_field.translations',
                'area:id,name',
                'job_applications',
                'translations',
                'currency',
                'seoDetail.translations',
            )
                ->withCount('featured_items')
                ->withCount('job_applications')
                ->whereHas('category', function ($q) {
                    $q->where('status', '!=', 0)
                        ->where(function ($query) {
                            // Either no parent or parent status != 0
                            $query->whereDoesntHave('parent') // no parent category
                                ->orWhereHas('parent', function ($q2) {
                                    $q2->where('status', '!=', 0);
                                });
                        });
                })
                ->when($request->id, function ($sql) use ($request) {
                    $sql->where('items.id', $request->id);
                })->when(($request->category_id), function ($sql) use ($request) {
                    $category = Category::where('id', $request->category_id)->with('children')->first();
                    $categoryIDS = HelperService::findAllCategoryIds(collect([$category]));

                    return $sql->whereIn('category_id', $categoryIDS);
                })->when(($request->category_slug), function ($sql) use ($request) {
                    $category = Category::where('slug', $request->category_slug)->with('children')->first();
                    $categoryIDS = HelperService::findAllCategoryIds(collect([$category]));

                    return $sql->whereIn('category_id', $categoryIDS);
                })->when((isset($request->min_price) || isset($request->max_price)), function ($sql) use ($request, $maxPrice) {
                    $min_price = $request->min_price ?? 0;
                    $max_price = $request->max_price ?? $maxPrice;

                    return $sql->whereBetween('price', [$min_price, $max_price]);
                })->when($request->posted_since, function ($sql) use ($request) {
                    return match ($request->posted_since) {
                        // Qualify column to avoid ambiguity once joins are applied (e.g. featured_items)
                        'today' => $sql->whereDate('items.created_at', '>=', now()),
                        'within-1-week' => $sql->whereDate('items.created_at', '>=', now()->subDays(7)),
                        'within-2-week' => $sql->whereDate('items.created_at', '>=', now()->subDays(14)),
                        'within-1-month' => $sql->whereDate('items.created_at', '>=', now()->subMonths()),
                        'within-3-month' => $sql->whereDate('items.created_at', '>=', now()->subMonths(3)),
                        default => $sql
                    };
                })->when($request->area_id, function ($sql) use ($request) {
                    return $sql->where('area_id', $request->area_id);
                })->when($request->user_id, function ($sql) use ($request) {
                    return $sql->where('user_id', $request->user_id);
                })->when($request->slug, function ($sql) use ($request) {
                    return $sql->where('slug', $request->slug);
                });

            if ($request->sort_by == 'new-to-old') {
                $sql->orderBy('items.id', 'DESC');
            } elseif ($request->sort_by == 'old-to-new') {
                $sql->orderBy('items.id', 'ASC');
            } elseif ($request->sort_by == 'price-high-to-low') {
                $sql->orderByRaw('
                    COALESCE(price, max_salary, min_salary, 0) DESC
                ');
            } elseif ($request->sort_by == 'price-low-to-high') {
                $sql->orderByRaw('
                    COALESCE(price, min_salary, max_salary, 0) ASC
                ');
            } elseif ($request->sort_by == 'popular_items') {
                $sql->orderBy('clicks', 'DESC');
            } else {
                $sql->orderBy('items.id', 'DESC');
            }

            // Status
            if (! empty($request->status)) {
                if (in_array($request->status, ['review', 'approved', 'rejected', 'sold out', 'soft rejected', 'permanent rejected', 'resubmitted'])) {
                    $sql->where('status', $request->status)->getNonExpiredItems()->whereNull('deleted_at');
                } elseif ($request->status == 'inactive') {
                    // If status is inactive then display only trashed items
                    $sql->onlyTrashed()->getNonExpiredItems();
                } elseif ($request->status == 'featured') {
                    // If status is featured then display only featured items
                    $sql->where('status', 'approved')->has('featured_items')->getNonExpiredItems();
                } elseif ($request->status == 'expired') {
                    $sql->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', Carbon::now())->whereNull('deleted_at');
                }
            }

            // Feature Section Filtration
            // Only apply feature section filters if user hasn't provided conflicting filters
            // User filters should override feature section defaults
            if (! empty($request->featured_section_id) || ! empty($request->featured_section_slug)) {
                if (! empty($request->featured_section_id)) {
                    $featuredSection = FeatureSection::findOrFail($request->featured_section_id);
                } else {
                    $featuredSection = FeatureSection::where('slug', $request->featured_section_slug)->firstOrFail();
                }

                // Check if user has provided filters that should override feature section filters
                $hasUserPriceFilter = isset($request->min_price) || isset($request->max_price);
                $hasUserSortFilter = ! empty($request->sort_by);
                $hasUserCategoryFilter = ! empty($request->category_id) || ! empty($request->category_slug);

                // Apply feature section filters only if user hasn't provided conflicting filters
                $sql = match ($featuredSection->filter) {
                    'price_criteria' => $hasUserPriceFilter
                        ? $sql // User price filter already applied, skip feature section price filter
                        // : $sql->whereBetween('price', [$featuredSection->min_price, $featuredSection->max_price]),
                        : $sql->where(function ($query) use ($featuredSection) {
                            $query->whereBetween('price', [$featuredSection->min_price, $featuredSection->max_price])
                                ->orWhere(function ($q) use ($featuredSection) {
                                    $q->whereBetween('min_salary', [$featuredSection->min_price, $featuredSection->max_price])
                                        ->whereBetween('max_salary', [$featuredSection->min_price, $featuredSection->max_price]);
                                });
                        }),
                    'most_viewed' => $hasUserSortFilter
                        ? $sql // User sort already applied, skip feature section sort
                        : $sql->reorder()->orderBy('clicks', 'DESC'),

                    'category_criteria' => $hasUserCategoryFilter
                        ? $sql // User category filter already applied, skip feature section category filter
                        : (static function () use ($featuredSection, $sql) {
                            $category = Category::whereIn('id', explode(',', $featuredSection->value))->with('children')->get();
                            $categoryIDS = HelperService::findAllCategoryIds($category);

                            return $sql->whereIn('category_id', $categoryIDS);
                        })(),

                    'most_liked' => $hasUserSortFilter
                        ? $sql // User sort already applied, skip feature section sort
                        : $sql->reorder()->withCount('favourites'), // ->orderBy('favourites_count', 'DESC'),

                    'featured_ads' => $sql->where('status', 'approved')->has('featured_items')->getNonExpiredItems(),
                };
            }

            if (! empty($request->search)) {
                $sql->search($request->search);
            }

            function removeBackslashesRecursive($data)
            {
                $cleaned = [];
                foreach ($data as $key => $value) {
                    $cleanKey = stripslashes($key);
                    if (is_array($value)) {
                        $cleaned[$cleanKey] = removeBackslashesRecursive($value);
                    } else {
                        $cleaned[$cleanKey] = stripslashes($value);
                    }
                }

                return $cleaned;
            }
            // Optimize custom fields filtering - use whereHas instead of joins for better performance
            $cleanedParameters = removeBackslashesRecursive($request->all());
            if (! empty($cleanedParameters['custom_fields'])) {
                $customFields = $cleanedParameters['custom_fields'];
                $sql->whereHas('item_custom_field_values', function ($query) use ($customFields) {
                    $query->whereIn('custom_field_id', array_keys($customFields));
                    foreach ($customFields as $customFieldId => $value) {
                        $query->where(function ($q) use ($customFieldId, $value) {
                            if (is_array($value)) {
                                foreach ($value as $arrayValue) {
                                    $q->orWhere(function ($subQ) use ($customFieldId, $arrayValue) {
                                        $subQ->where('custom_field_id', $customFieldId)
                                            ->where('value', $arrayValue);
                                    });
                                }
                            } else {
                                $q->orWhere(function ($subQ) use ($customFieldId, $value) {
                                    $subQ->where('custom_field_id', $customFieldId)
                                        ->where('value',$value);
                                });
                            }
                        });
                    }
                }, '>=', count($customFields));
            }

            if (Auth::check()) {
                $sql->with(['item_offers' => function ($q) {
                    $q->where('buyer_id', Auth::user()->id);
                }, 'user_reports' => function ($q) {
                    $q->where('user_id', Auth::user()->id);
                }]);

                $currentURI = explode('?', $request->getRequestUri(), 2);

                if ($currentURI[0] == '/api/my-items') { // TODO: This if condition is temporary fix. Need something better
                    $sql->where(['user_id' => Auth::user()->id])->withTrashed();
                } else {
                    $sql->where('status', 'approved')->has('user')->onlyNonBlockedUsers()->getNonExpiredItems();
                }
            } else {
                //  Other users should only get approved items
                $sql->where('status', 'approved')->getNonExpiredItems();
            }

            // Helper function to apply auth filters
            $applyAuthFilters = function ($query) use ($request) {
                if (Auth::check()) {
                    $query->with(['item_offers' => function ($q) {
                        $q->where('buyer_id', Auth::user()->id);
                    }, 'user_reports' => function ($q) {
                        $q->where('user_id', Auth::user()->id);
                    }]);

                    $currentURI = explode('?', $request->getRequestUri(), 2);
                    if ($currentURI[0] == '/api/my-items') {
                        $query->where(['user_id' => Auth::user()->id])->withTrashed();
                    } else {
                        $query->where('status', 'approved')->has('user')->onlyNonBlockedUsers()->getNonExpiredItems();
                    }
                } else {
                    $query->where('status', 'approved')->getNonExpiredItems();
                }

                return $query;
            };

            // Apply location filters using shared function
            $locationResult = HelperService::applyLocationFilters($sql, $request, $applyAuthFilters);
            $sql = $locationResult['query'];
            $locationMessage = $locationResult['message'];

            $baseQuery = clone $sql;

            // FEATURED QUERY
            $featuredQuery = clone $baseQuery;
            $featuredQuery
                ->whereHas('featured_items', function ($q) {
                    $q->whereDate('start_date', '<=', now())
                        ->where(function ($q) {
                            $q->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', now());
                        });
                })
                ->join('featured_items as fi', 'fi.item_id', '=', 'items.id')
                ->orderBy('fi.created_at', 'DESC')
                // IMPORTANT: do not override select() here because location filters may have added
                // `distance` via selectRaw(... AS distance). Overriding select would remove it and
                // break ORDER BY distance with "Unknown column 'distance'".
                ->addSelect('items.*');

            // NORMAL QUERY
            $normalQuery = clone $baseQuery;
            $normalQuery
                ->whereDoesntHave('featured_items', function ($q) {
                    $q->whereDate('start_date', '<=', now())
                        ->where(function ($q) {
                            $q->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', now());
                        });
                })
                ->orderBy('items.created_at', 'DESC');

            // Cast to integers to prevent "string - int" error
            $limit = (int) ($request->limit ?? 10);
            $page = (int) ($request->page ?? 1);

            $totalFeatured = (clone $featuredQuery)->distinct('items.id')->count('items.id');
            $totalNormal = (clone $normalQuery)->count();
            $totalItems = $totalFeatured + $totalNormal;

            // Ensure totalPages is at least 1 to avoid division by zero
            $totalPages = (int) max(1, ceil($totalItems / $limit));

            // Distribute featured items across pages
            $featuredPerPage = (int) ceil($totalFeatured / $totalPages);

            // Now subtraction will work because both are integers
            $normalPerPage = $limit - $featuredPerPage;

            if ($normalPerPage < 0) {
                $normalPerPage = 0;
            }

            $featuredOffset = ($page - 1) * $featuredPerPage;
            $normalOffset = ($page - 1) * $normalPerPage;

            if ($normalPerPage < 0) {
                $normalPerPage = 0;
            }

            $featuredItems = $featuredQuery
                ->skip($featuredOffset)
                ->take($featuredPerPage)
                ->get();

            $normalItems = $normalQuery
                ->skip($normalOffset)
                ->take($normalPerPage)
                ->get();

            $items = $featuredItems->merge($normalItems);

            $paginator = new LengthAwarePaginator(
                $items,
                $totalItems,
                $limit,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );

            // Execute query and get results
            if (! empty($request->id)) {
                /*
                 * Collection does not support first OR find method's result as of now. It's a part of R&D
                 * So currently using this shortcut method get() to fetch the first data
                 */
                $result = $sql->get();
                // dd($result);
                if (count($result) == 0) {
                    ResponseService::errorResponse(__('No item Found'));
                }
            } else {
                if (! empty($request->limit)) {
                    $result = $sql->paginate($request->limit);
                } else {
                    $result = $sql->paginate();
                }
            }

            // Prepare response with location message if applicable
            $responseData = new ItemCollection($paginator);
            // Use location message if available, otherwise use default success message
            $responseMessage = ! empty($locationMessage) ? $locationMessage : __('Advertisement Fetched Successfully');

            // return response()->json($responseData);

            ResponseService::successResponse($responseMessage, $responseData);
            // if (!empty($request->id)) {
            //     /*
            //      * Collection does not support first OR find method's result as of now. It's a part of R&D
            //      * So currently using this shortcut method get() to fetch the first data
            //      */
            //     $result = $sql->get();
            //     if (count($result) == 0) {
            //         ResponseService::errorResponse(__('No item Found'));
            //     }
            // } else {
            //     if (!empty($request->limit)) {
            //         $result = $sql->paginate($request->limit);
            //     } else {
            //         $result = $sql->paginate();
            //     }

            // }
            //                // Add three regular items
            //                for ($i = 0; $i < 3 && $regularIndex < $regularItemCount; $i++) {
            //                    $items->push($regularItems[$regularIndex]);
            //                    $regularIndex++;
            //                }
            //
            //                // Add one featured item if available
            //                if ($featuredIndex < $featuredItemCount) {
            //                    $items->push($featuredItems[$featuredIndex]);
            //                    $featuredIndex++;
            //                }
            //            }
            // Return success response with the fetched items

            // ResponseService::successResponse(__('Advertisement Fetched Successfully'), new ItemCollection($result));

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getItem');
            ResponseService::errorResponse();
        }
    }

    /**
     * Update Item
     */
    public function updateItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'nullable',
            'slug' => [
                'nullable',
                'regex:/^(?!-)(?!.*--)(?!.*-$)(?!-$)[a-z0-9-]+$/',
            ],
            'price' => 'nullable',
            'description' => 'nullable',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'address' => 'nullable',
            'contact' => 'nullable',
            'custom_fields' => 'nullable',
            'custom_field_files' => 'nullable|array',
            'custom_field_files.*' => 'nullable|mimes:jpeg,png,jpg,pdf,doc|max:7168',
            'gallery_images' => 'nullable|array',
            'delete_item_image_id' => 'nullable|array',
            'currency_id' => 'nullable|exists:currencies,id',
            'country_code' => 'nullable|string',
            'seo_details'       => 'nullable|array',
            'seo_details.*'     => 'nullable|array',
            'seo_details.*.meta_title' => 'nullable|string',
            'seo_details.*.meta_description' => 'nullable|string',
            'seo_details.*.meta_keywords' => 'nullable|string',
            'seo_details.*.schema' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        DB::beginTransaction();

        try {

            $item = Item::owner()->findOrFail($request->id);
            $auto_approve_item = Setting::where('name', 'auto_approve_edited_item')->value('value') ?? 0;
            if ($auto_approve_item == 1) {
                $status = 'approved';
            } else {
                $status = 'review';
            }
            $slugInput = $request->input('slug') ?? '';
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($slugInput)));
            $slug = trim($slug, '-');

            // If slug is empty after cleaning, use existing item slug
            if (empty($slug)) {
                $slug = $item->slug;
            }

            // Generate unique slug
            $uniqueSlug = HelperService::generateUniqueSlug(new Item, $slug, $request->id);

            $data = $request->all();
            $data['slug'] = $uniqueSlug;
            $data['status'] = $status;

            // Process images before updating item
            $galleryImages = [];

            $itemImagesExists = ItemImages::where('item_id', $item->id)->exists();
            if ($request->hasFile('gallery_images')) {
                $files = $request->file('gallery_images');
                if (!empty($files)) {
                    foreach ($files as $index => $file) {
                        $galleryImages[] = [
                            'image' => FileService::compressAndUpload($file, $this->uploadFolder, true),
                            'is_default' => (!$itemImagesExists && $index === 0) ? 1 : 0,
                            'item_id' => $item->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }else{
                if(!$itemImagesExists){
                    ResponseService::validationError(__('At least one gallery image is required'));
                }
            }
            $item->update($data);

            if (!empty($galleryImages)) {
                ItemImages::insert($galleryImages);
            }
            // Update or create item translations
            $translations = json_decode($request->input('translations', '{}'), true, 512, JSON_THROW_ON_ERROR);
            if (! empty($translations)) {
                foreach ($translations as $languageId => $translationData) {
                    if (Language::where('id', $languageId)->exists()) {
                        HelperService::storeTranslations([
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'name', 'value' => $translationData['name'], 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'description', 'value' => $translationData['description'] ?? '', 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'address', 'value' => $translationData['address'] ?? '', 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'rejected_reason', 'value' => $translationData['rejected_reason'] ?? null, 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'admin_edit_reason', 'value' => $translationData['admin_edit_reason'] ?? null, 'language_id' => $languageId],
                        ]);
                    }
                }
            }

            // Update Custom Field values for item
            if ($request->custom_fields) {
                $itemCustomFieldValues = [];
                foreach (json_decode($request->custom_fields, true, 512, JSON_THROW_ON_ERROR) as $key => $custom_field) {
                    $itemCustomFieldValues[] = [
                        'item_id' => $item->id,
                        'custom_field_id' => $key,
                        'value' => json_encode($custom_field, JSON_THROW_ON_ERROR),
                        'updated_at' => time(),
                    ];
                }

                if (count($itemCustomFieldValues) > 0) {
                    ItemCustomFieldValue::upsert($itemCustomFieldValues, ['item_id', 'custom_field_id'], ['value', 'updated_at']);
                }
            }

            if ($request->custom_field_files) {
                foreach ($request->custom_field_files as $key => $file) {
                    $value = ItemCustomFieldValue::where(['item_id' => $item->id, 'custom_field_id' => $key])->first();
                    if (! empty($value)) {
                        $existingPath = $this->extractFilePath($value->getRawOriginal('value'));
                        $path = !empty($existingPath) ? FileService::replace($file, 'custom_fields_files', $existingPath) : FileService::upload($file, 'custom_fields_files');
                    } else {
                        $path = FileService::upload($file, 'custom_fields_files');
                    }

                    // Store as single plain path
                    ItemCustomFieldValue::updateOrCreate(
                        ['item_id' => $item->id, 'custom_field_id' => $key],
                        ['value' => $path, 'language_id' => 1, 'updated_at' => time()]
                    );
                }
            }
            // Update or insert custom field translations
            if ($request->has('custom_field_translations')) {
                $customFieldTranslations = $request->input('custom_field_translations');

                if (! is_array($customFieldTranslations)) {
                    $customFieldTranslations = html_entity_decode($customFieldTranslations);
                    $customFieldTranslations = json_decode($customFieldTranslations, true, 512, JSON_THROW_ON_ERROR);
                }
                $translatedEntries = [];

                foreach ($customFieldTranslations as $languageId => $fieldsByCustomField) {
                    foreach ($fieldsByCustomField as $customFieldId => $translatedValue) {
                        $translatedEntries[] = [
                            'item_id' => $item->id,
                            'custom_field_id' => $customFieldId,
                            'language_id' => $languageId,
                            'value' => json_encode($translatedValue, JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ];
                    }
                }

                if (! empty($translatedEntries)) {
                    // Ensure combination is unique
                    ItemCustomFieldValue::upsert(
                        $translatedEntries,
                        ['item_id', 'custom_field_id', 'language_id'], // unique keys
                        ['value', 'updated_at']
                    );
                }
            }

            // Delete gallery images
            if (! empty($request->delete_item_image_id)) {
                $itemImageIds = $request->delete_item_image_id;
                $deletedDefault = false;

                // Check total images of current item id
                $itemImagesInDBCount = ItemImages::where('item_id', $item->id)->count();
                if(!$request->hasFile('gallery_images') && $itemImagesInDBCount == count($request->delete_item_image_id)){
                    ResponseService::validationError(trans('At least one item image is required'));
                }

                // Get Item images of ids passed
                $itemImages = ItemImages::whereIn('id',$itemImageIds)->get();
                if(collect($itemImages)->isEmpty()){
                    ResponseService::validationError(trans('Item Images data not found to delete'));
                }
                foreach ($itemImages as $itemImage) {
                    if ($itemImage->is_default) {
                        $deletedDefault = true;
                    }
                    FileService::delete($itemImage->getRawOriginal('image'));
                    $itemImage->delete();
                }

                if ($deletedDefault || !ItemImages::where('item_id', $item->id)->where('is_default', 1)->exists()) {
                    $firstImg = ItemImages::where('item_id', $item->id)->orderBy('id')->first();
                    if ($firstImg) {
                        $firstImg->update(['is_default' => 1]);
                    }
                }
            }

            // Store SEO details from API
            if ($request->has('seo_details')) {
                $seoData = $request->seo_details;
                if (!empty($seoData)) {
                    HelperService::storeSeoDetailsFromApi($item, $seoData);
                }
            }

            $result = Item::with('user:id,name,email,mobile,profile,country_code', 'category:id,name,image,is_job_category,price_optional', 'gallery_images:id,image,item_id,is_default', 'featured_items', 'favourites', 'item_custom_field_values.custom_field.translations', 'area', 'translations', 'seoDetail.translations')->where('items.id', $item->id)->get();
            /*
               * Collection does not support first OR find method's result as of now. It's a part of R&D
               * So currently using this shortcut method
              */
            $result = new ItemCollection($result);

            DB::commit();
            ResponseService::successResponse(__('Advertisement Fetched Successfully'), $result);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> updateItem');
            ResponseService::errorResponse();
        }
    }

    /**
     * Delete Item
     */
    public function deleteItem(Request $request)
    {
        try {
            // Validation rules
            $rules = [
                'item_id' => 'nullable|exists:items,id',
                'item_ids' => 'nullable|string', // comma-separated IDs
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            // Normalize IDs
            $itemIds = [];

            if ($request->filled('item_id')) {
                $itemIds[] = $request->item_id;
            }

            if ($request->filled('item_ids')) {
                $ids = explode(',', $request->item_ids);
                $ids = array_map('trim', $ids);
                $ids = array_filter($ids, 'strlen');
                $itemIds = array_merge($itemIds, $ids);
            }

            if (empty($itemIds)) {
                return ResponseService::validationError(__('Please provide item_id or item_ids'));
            }

            $results = [];

            foreach ($itemIds as $id) {
                try {
                    $item = Item::owner()->with('gallery_images')->withTrashed()->findOrFail($id);

                    // Delete main image
                    FileService::delete($item->getRawOriginal('image'));

                    // Delete gallery images
                    if ($item->gallery_images->count() > 0) {
                        foreach ($item->gallery_images as $gallery) {
                            FileService::delete($gallery->getRawOriginal('image'));
                        }
                    }

                    // Delete item
                    $item->forceDelete();

                    $results[] = [
                        'status' => 'success',
                        'message' => __('Advertisement Deleted Successfully'),
                        'item_id' => $id,
                    ];
                } catch (Throwable $e) {
                    $results[] = [
                        'status' => 'failed',
                        'message' => __('Failed to delete item'),
                        'item_id' => $id,
                    ];
                }
            }

            // Single item response
            if (count($results) === 1) {
                if ($results[0]['status'] === 'success') {
                    return ResponseService::successResponse(
                        __('Advertisement Deleted Successfully'),
                        ['id' => $results[0]['item_id']]
                    );
                } else {
                    return ResponseService::errorResponse($results[0]['message']);
                }
            }

            // Multiple items response
            return ResponseService::successResponse(__('Items processed successfully'), $results);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> deleteItem');

            return ResponseService::errorResponse();
        }
    }

    /**
     * Update Item Status
     */
    public function updateItemStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'status' => 'required|in:sold out,inactive,active,resubmitted',
            // 'sold_to' => 'required_if:status,==,sold out|integer'
            'sold_to' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $item = Item::owner()->whereNotIn('status', ['review', 'permanent rejected'])->withTrashed()->findOrFail($request->item_id);
            if ($item->status == 'permanent rejected' && $request->status == 'resubmitted') {
                ResponseService::errorResponse(__('This Advertisement is permanently rejected and cannot be resubmitted'));
            }
            if ($request->status == 'inactive') {
                $item->delete();
            } elseif ($request->status == 'active') {
                $item->restore();
                $item->update(['status' => 'review']);
            } elseif ($request->status == 'sold out') {
                $item->update([
                    'status' => 'sold out',
                    'sold_to' => $request->sold_to,
                ]);
            } else {
                $item->update(['status' => $request->status]);
            }
            ResponseService::successResponse(__('Advertisement Status Updated Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController -> updateItemStatus');
            ResponseService::errorResponse(__('Something Went Wrong'));
        }
    }

    /**
     * Get Item Buyer List
     */
    public function getItemBuyerList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            // Verify the authenticated user owns this item
            Item::owner()->findOrFail($request->item_id);
            
            $buyer_ids = ItemOffer::where('item_id', $request->item_id)->select('buyer_id')->pluck('buyer_id');
            $users = User::select(['id', 'name', 'profile'])->whereIn('id', $buyer_ids)->get();
            ResponseService::successResponse(__('Buyer List fetched Successfully'), $users);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController -> updateItemStatus');
            ResponseService::errorResponse(__('Something Went Wrong'));
        }
    }

    /**
     * Renew Item
     */
    public function renewItem(Request $request)
    {
        try {
            $free_ad_listing = Setting::where('name', 'free_ad_listing')->value('value') ?? 0;

            // Validation rules
            $rules = [
                'item_id' => 'nullable|exists:items,id',
                'item_ids' => 'nullable|string', // accept comma-separated string
            ];

            if ($free_ad_listing == 0) {
                $rules['package_id'] = 'required|exists:packages,id';
            } else {
                $rules['package_id'] = 'nullable|exists:packages,id';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            // Normalize input to array
            $itemIds = [];

            if ($request->filled('item_id')) {
                $itemIds[] = $request->item_id;
            }

            if ($request->filled('item_ids')) {
                // Convert comma-separated string into array
                $ids = explode(',', $request->item_ids);
                $ids = array_map('trim', $ids);       // remove spaces
                $ids = array_filter($ids, 'strlen');  // remove empty values
                $itemIds = array_merge($itemIds, $ids);
            }

            if (empty($itemIds)) {
                return ResponseService::validationError(__('Please provide item_id or item_ids'));
            }

            $user = Auth::user();
            $package = null;
            $userPackage = null;

            // Fetch package if provided
            if ($request->filled('package_id')) {
                $package = Package::where('id', $request->package_id)->firstOrFail();

                $userPackage = UserPurchasedPackage::onlyActive()
                    ->where([
                        'user_id' => $user->id,
                        'package_id' => $package->id,
                    ])->first();

                if (! $userPackage) {
                    return ResponseService::errorResponse(__('You have not purchased this package'));
                }
            }

            $currentDate = Carbon::now();
            $results = [];

            foreach ($itemIds as $itemId) {
                $item = Item::findOrFail($itemId);
                $rawStatus = $item->getAttributes()['status'];

                if (Carbon::parse($item->expiry_date)->gt($currentDate)) {
                    $results[$itemId] = [
                        'status' => 'failed',
                        'message' => __('Advertisement has not expired yet, so it cannot be renewed'),
                    ];

                    continue;
                }
                if($rawStatus == 'sold out'){
                    $results[$itemId] = [
                        'status' => 'failed',
                        'message' => __('Advertisement is sold out, so it cannot be renewed'),
                    ];

                    continue;
                }
                if ($package) {
                    // Calculate expiry date based on package listing duration
                    $expiryDate = HelperService::calculateItemExpiryDate($package, $userPackage);

                    $userPackage->used_limit++;
                    $userPackage->save();
                } else {
                    // No package - use standard 30 days
                   $expiryDate = HelperService::calculateItemExpiryDate(null, null);
                }

                $item->expiry_date = $expiryDate;
                $item->status = $rawStatus;
                $item->save();

                $results[$itemId] = [
                    'status' => 'success',
                    'item' => $item,
                ];
            }

            // Return single item response if only one item was renewed
            if (count($itemIds) === 1) {
                $itemId = $itemIds[0];
                if ($results[$itemId]['status'] === 'success') {
                    return ResponseService::successResponse(
                        __('Advertisement renewed successfully'),
                        $results[$itemId]['item']
                    );
                } else {
                    return ResponseService::errorResponse($results[$itemId]['message']);
                }
            }

            // Return multiple items response
            return ResponseService::successResponse(__('Items processed successfully'), $results);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> renewItem');

            return ResponseService::errorResponse();
        }
    }

    /**
     * Make Item Featured
     */
    public function makeFeaturedItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::commit();
            $user = Auth::user();
            Item::where('status', 'approved')->findOrFail($request->item_id);
            $user_package = UserPurchasedPackage::onlyActive()
                ->where(['user_id' => $user->id])
                ->with('package')
                ->whereHas('package', function ($q) {
                    $q->where(['type' => 'advertisement']);
                })
                ->first();

            if (! $user_package) {
                return ResponseService::errorResponse(__('You need to purchase a Featured Ad plan first.'));
            }
            $featuredItems = FeaturedItems::where(['item_id' => $request->item_id, 'package_id' => $user_package->package_id])->first();
            if (! empty($featuredItems)) {
                ResponseService::errorResponse(__('Advertisement is already featured'));
            }

            $user_package->used_limit++;
            $user_package->save();

            FeaturedItems::create([
                'item_id' => $request->item_id,
                'package_id' => $user_package->package_id,
                'user_purchased_package_id' => $user_package->id,
                'start_date' => date('Y-m-d'),
                'end_date' => $user_package->end_date,
            ]);

            DB::commit();
            ResponseService::successResponse(__('Featured Advertisement Created Successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> createAdvertisement');
            ResponseService::errorResponse();
        }
    }

    /**
     * Manage Favourite
     */
    public function manageFavourite(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_id' => 'required',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $favouriteItem = Favourite::where('user_id', Auth::user()->id)->where('item_id', $request->item_id)->first();
            if (empty($favouriteItem)) {
                $favouriteItem = new Favourite;
                $favouriteItem->user_id = Auth::user()->id;
                $favouriteItem->item_id = $request->item_id;
                $favouriteItem->save();
                ResponseService::successResponse(__('Advertisement added to Favourite'));
            } else {
                $favouriteItem->delete();
                ResponseService::successResponse(__('Advertisement remove from Favourite'));
            }
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> manageFavourite');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get Favourite Items
     */
    public function getFavouriteItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer',
                'limit' => 'nullable|integer',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $favouriteItemIDS = Favourite::where('user_id', Auth::user()->id)->select('item_id')->pluck('item_id');
            $items = Item::whereIn('id', $favouriteItemIDS)
                ->with('user:id,name,email,mobile,profile,country_code', 'category:id,name,image,is_job_category', 'gallery_images:id,image,item_id,is_default', 'featured_items', 'favourites', 'item_custom_field_values.custom_field')->where('status', 'approved')->onlyNonBlockedUsers()->getNonExpiredItems()->paginate();

            ResponseService::successResponse(__('Data Fetched Successfully'), new ItemCollection($items));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getFavouriteItem');
            ResponseService::errorResponse();
        }
    }

    /**
     * Set Item Total Click
     */
    public function setItemTotalClick(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'item_id' => 'required',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            Item::findOrFail($request->item_id)->increment('clicks');
            ResponseService::successResponse(null, 'Update Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> setItemTotalClick');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get Item Slugs
     */
    public function getItemSlugs(Request $request)
    {
        try {
            $items = Item::without('translations')
                ->select('id', 'slug', 'updated_at')
                ->where('status', 'approved')
                ->whereNull('deleted_at')
                ->getNonExpiredItems()
                ->get()
                ->each->setAppends([]);

            if ($items->isEmpty()) {
                return ResponseService::errorResponse(__('No active items found.'));
            }

            return ResponseService::successResponse(__('Active item slugs fetched successfully.'), $items);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getItemSlugs');

            return ResponseService::errorResponse();
        }
    }

    /**
     * Get Item Status
     */
    public function getItemStatus(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'item_id' => 'required|exists:items,id',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $item = Item::findOrFail($request->item_id);
            $data = array(
                'id' => $item->id,
                'name' => $item->name,
                'status' => $item->status,
            );
            ResponseService::successResponse(__('Data Fetched Successfully'), $data);
        } catch(Throwable $th){
            ResponseService::logErrorResponse($th, 'API Controller -> getItemStatus');
            ResponseService::errorResponse();
        }
    }
}
