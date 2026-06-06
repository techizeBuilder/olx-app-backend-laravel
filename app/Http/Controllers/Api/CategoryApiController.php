<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\CustomField;
use App\Models\ItemCustomFieldValue;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Setting;
use App\Models\UserPurchasedPackage;
use App\Services\HelperService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Throwable;

/** @tags Category */
class CategoryApiController extends BaseApiController
{
    /** Get Categories */
    public function getSubCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|integer',
            'listing'     => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $subcategoryLimit = 15;

            $packageCategoryIds = [];
            $hasGlobalPackage   = false;
            $freeAdListing      = Setting::where('name', 'free_ad_listing')->value('value') ?? 0;

            if (
                ! empty($request->category_id)
                && ! empty($request->listing)
                && $request->listing == 1
                && $freeAdListing != 1
                && Auth::check()
            ) {
                $userPackages = UserPurchasedPackage::onlyActive()
                    ->whereHas('package', static function ($q) {
                        $q->where('type', 'item_listing')->orWhere('is_global', 1);
                    })
                    ->where('user_id', Auth::id())
                    ->with('package.package_categories')
                    ->get();

                foreach ($userPackages as $userPackage) {
                    if ($userPackage->package && $userPackage->package->is_global == 1) {
                        $hasGlobalPackage = true;
                        break;
                    }
                }

                if (! $hasGlobalPackage) {
                    foreach ($userPackages as $userPackage) {
                        if (
                            $userPackage->package
                            && $userPackage->package->is_global != 1
                            && $userPackage->package->package_categories
                        ) {
                            $packageCatIds      = $userPackage->package->package_categories->pluck('category_id')->toArray();
                            $packageCategoryIds = array_merge($packageCategoryIds, $packageCatIds);
                        }
                    }
                    $packageCategoryIds = array_unique($packageCategoryIds);
                }

                $requestedCategory = Category::find($request->category_id);

                if ($requestedCategory) {
                    if (! $hasGlobalPackage) {
                        $hasAccess = false;

                        if (in_array($requestedCategory->id, $packageCategoryIds)) {
                            $hasAccess = true;
                        }

                        if (! $hasAccess) {
                            $ancestorIds = $requestedCategory->ancestors()
                                ->where('status', 1)
                                ->pluck('id')
                                ->toArray();

                            if (! empty(array_intersect($ancestorIds, $packageCategoryIds))) {
                                $hasAccess = true;
                            }
                        }

                        if (! $hasAccess) {
                            $descendantIds = $requestedCategory->descendants()
                                ->where('status', 1)
                                ->pluck('id')
                                ->toArray();

                            if (! empty(array_intersect($descendantIds, $packageCategoryIds))) {
                                $hasAccess = true;
                            }
                        }

                        if (! $hasAccess) {
                            ResponseService::errorResponse(
                                __('You need to purchase a package for this category to access it.')
                            );
                        }
                    }
                } else {
                    ResponseService::errorResponse(__('Category not found.'));
                }
            }

            // ------------------------------------------------------------------
            // Recursive subcategory loader
            // ------------------------------------------------------------------
            $loadSubcategoriesRecursively = function ($query, $depth = 0, $maxDepth = 5) use (&$loadSubcategoriesRecursively) {
                if ($depth >= $maxDepth) {
                    return;
                }

                $query->where('status', 1)
                    ->orderBy('sequence', 'ASC')
                    ->with('translations', 'seoDetail.translations')
                    ->withCount(['approved_items', 'subcategories' => function ($q) {
                        $q->where('status', 1);
                    }]);

                $query->with(['subcategories' => function ($subQuery) use (&$loadSubcategoriesRecursively, $depth, $maxDepth) {
                    $loadSubcategoriesRecursively($subQuery, $depth + 1, $maxDepth);
                }]);
            };

            // ------------------------------------------------------------------
            // Limit subcategories after load
            // ------------------------------------------------------------------
            $limitSubcategoriesAfterLoad = function ($categories, $limit, $depth = 0, $maxDepth = 5) use (&$limitSubcategoriesAfterLoad) {
                if ($depth >= $maxDepth || empty($categories)) {
                    return;
                }

                foreach ($categories as $category) {
                    if ($category->relationLoaded('subcategories') && $category->subcategories->isNotEmpty()) {
                        $limitedSubcategories = $category->subcategories->take($limit)->values();
                        $category->setRelation('subcategories', $limitedSubcategories);
                        $limitSubcategoriesAfterLoad($category->subcategories, $limit, $depth + 1, $maxDepth);
                    }
                }
            };

            // ------------------------------------------------------------------
            // Build main query
            // ------------------------------------------------------------------
            $sql = Category::withCount(['subcategories' => function ($q) {
                $q->where('status', 1);
            }])
                ->with('translations', 'seoDetail.translations')
                ->where(['status' => 1])
                ->orderBy('sequence', 'ASC')
                ->with(['subcategories' => function ($query) use (&$loadSubcategoriesRecursively) {
                    $loadSubcategoriesRecursively($query, 0);
                }]);

            $parentCategory = null;

            if (! empty($request->category_id)) {
                $sql            = $sql->where('parent_category_id', $request->category_id);
                $parentCategory = Category::with('seoDetail.translations')->find($request->category_id);
            } elseif (! empty($request->slug)) {
                $parentCategory = Category::where('slug', $request->slug)->with('seoDetail.translations')->firstOrFail();
                $sql            = $sql->where('parent_category_id', $parentCategory->id);
            } else {
                $sql = $sql->whereNull('parent_category_id');
            }

            $sql = $sql->paginate();

            $limitSubcategoriesAfterLoad($sql->items(), $subcategoryLimit);

            // ------------------------------------------------------------------
            // Preload package counts (optimized — no queries inside map)
            // ------------------------------------------------------------------

            // Step 1: Collect paginated category IDs
            $categoryIds = $sql->pluck('id')->toArray();

            // Step 2: Build ancestor+self+descendant ID map per category
            $allRelatedIds = [];
            foreach ($categoryIds as $catId) {
                $ancestorIds   = HelperService::getAllAncestorCategoryIds($catId);
                $descendantIds = HelperService::getAllDescendantCategoryIds($catId);  // includes self
                $allRelatedIds[$catId] = array_unique(array_merge($ancestorIds, $descendantIds));
            }

            // Step 3: Single Eloquent query — fetch all packages with their category mappings
            $flatIds = array_unique(array_merge(...array_values($allRelatedIds)));

            $packageIdsByCategory = PackageCategory::whereIn('category_id', $flatIds)
                ->whereHas('package', function ($q) {
                    $q->where('status', 1);
                })
                ->get()
                ->groupBy('category_id')
                ->map(fn($rows) => $rows->pluck('package_id')->unique()->toArray())
                ->toArray();

            // Step 4: Map — zero DB queries inside
            $sql->map(function ($category) use ($allRelatedIds, $packageIdsByCategory) {
                $relatedCatIds = $allRelatedIds[$category->id] ?? [$category->id];

                // Collect all unique package IDs across related category IDs
                $packageIds = [];
                foreach ($relatedCatIds as $catId) {
                    if (isset($packageIdsByCategory[$catId])) {
                        $packageIds = array_merge($packageIds, $packageIdsByCategory[$catId]);
                    }
                }

                $category->packages_count  = count(array_unique($packageIds));
                $category->all_items_count = $category->approved_items_count
                    + $category->subcategories->sum('approved_items_count');

                return $category;
            });

            ResponseService::successResponse(null, $sql, ['self_category' => $parentCategory ?? null]);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }

    /** Get Parent Category Tree */
    public function getParentCategoryTree(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_category_id' => 'nullable|integer',
            'tree' => 'nullable|boolean',
            'slug' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sql = Category::when($request->child_category_id, function ($sql) use ($request) {
                $sql->where('categories.id', $request->child_category_id);
            })
            ->when($request->slug, function ($sql) use ($request) {
                $sql->where('slug', $request->slug);
            })
            ->firstOrFail()
            ->ancestorsAndSelf()
            ->breadthFirst()
            ->with('translations', 'seoDetail.translations')
            ->get();

            if ($request->tree) {
                $sql = $sql->toTree();
            }
            ResponseService::successResponse(null, $sql);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }

    /** Get Categories */
    public function getCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $categories = Category::all();
            $languageCode = $request->get('language_code', 'en');

            $translator = new GoogleTranslate($languageCode);
            $categoriesJson = $categories->toJson();
            $translatedJson = $translator->translate($categoriesJson);
            $translatedCategories = json_decode($translatedJson, true);

            return ResponseService::successResponse(null, $translatedCategories);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }

    /** Get Categories Slug */
    public function getCategoriesSlug(Request $request)
    {
        try {
            $categories = Category::without('translations')
                ->select('id', 'slug', 'updated_at')
                ->where('status', 1)
                ->get()
                ->each->setAppends([]);

            if ($categories->isEmpty()) {
                return ResponseService::errorResponse(__('No active Categories found.'));
            }

            return ResponseService::successResponse(__('Active Categories slugs fetched successfully.'), $categories);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategoriesSlug');
            ResponseService::errorResponse();
        }
    }

    /** Get Custom Fields */
    public function getCustomFields(Request $request)
    {
        try {

            $filter = filter_var($request->input('filter', false), FILTER_VALIDATE_BOOLEAN);
            $categoryId = $request->input('category_id');

            $categoryIds = [(int)$categoryId];
            $category = Category::find($categoryId);
            while ($category && $category->parent_category_id) {
                $categoryIds[] = $category->parent_category_id;
                $category = $category->parent;
            }

            $customFields = CustomField::with('translations')
                ->whereHas('custom_field_category', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                })
                ->where('status', 1)
                ->get();

            if ($filter === true) {

                $customFields = $customFields->filter(function ($field) use ($categoryIds) {

                    if (! in_array($field->type, ['dropdown', 'checkbox', 'radio'])) {
                        return true;
                    }

                    $values = ItemCustomFieldValue::where('custom_field_id', $field->id)
                        ->whereHas('item', function ($q) use ($categoryIds) {
                            $q->getNonExpiredItems()
                                ->whereNull('deleted_at')
                                ->where('status', 'approved')
                                ->whereIn('category_id', $categoryIds);
                        })
                        ->pluck('value')
                        ->toArray();

                    $used = [];

                    foreach ($values as $raw) {
                        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

                        if (is_array($decoded)) {
                            $used = array_merge($used, $decoded);
                        } else {
                            $used[] = $decoded;
                        }
                    }

                    $used = array_unique(array_filter($used));

                    if (empty($used)) {
                        return false;
                    }

                    $originalMainValues = $field->values ?? [];

                    $field->values = array_values(array_intersect($originalMainValues, $used));

                    $usedIndices = [];
                    foreach ($field->values as $usedValue) {
                        $index = array_search($usedValue, $originalMainValues);
                        if ($index !== false) {
                            $usedIndices[] = $index;
                        }
                    }

                    foreach ($field->translations as $t) {
                        $translationValues = $t->value ?? [];
                        if (is_array($translationValues) && count($translationValues) > 0) {
                            $filteredTranslationValues = [];
                            foreach ($usedIndices as $idx) {
                                if (isset($translationValues[$idx])) {
                                    $filteredTranslationValues[] = $translationValues[$idx];
                                }
                            }
                            $t->value = array_values($filteredTranslationValues);
                        }
                    }

                    return true;
                })->values();
            }

            $customFields->each(function ($field) {
                $field->translated_name = $field->translated_name;
                $field->translated_value = $field->translated_value;
            });

            ResponseService::successResponse(__('Data Fetched successfully'), $customFields);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCustomFields');
            ResponseService::errorResponse();
        }
    }
}
