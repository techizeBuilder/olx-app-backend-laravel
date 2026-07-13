<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\CustomField;
use App\Models\CustomFieldCategory;
use App\Models\Item;
use App\Models\ItemCustomFieldValue;
use App\Models\ItemImages;
use App\Models\State;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Str;
use Throwable;
use Validator;

class ItemController extends Controller
{
    /**
     * A title must contain at least one letter, so pure-number/symbol garbage
     * like "444444444444444" is rejected.
     */
    private const NAME_REGEX = 'regex:/^(?=.*\p{L})[\p{L}\p{N}\s\-&\'.,()\/+#%"]+$/u';

    /** Upper bound for any money field — blocks values like 4.23e+92. */
    private const MAX_AMOUNT = 999999999;

    private static function nameMessages(): array
    {
        return [
            'name.regex' => 'Title must contain letters — it cannot be only numbers or symbols.',
            'name.min' => 'Title must be at least 3 characters.',
            'name.max' => 'Title cannot be longer than 150 characters.',
            'price.max' => 'Price is too large. Maximum allowed is ' . number_format(self::MAX_AMOUNT) . '.',
            'min_salary.max' => 'Salary is too large. Maximum allowed is ' . number_format(self::MAX_AMOUNT) . '.',
            'max_salary.max' => 'Salary is too large. Maximum allowed is ' . number_format(self::MAX_AMOUNT) . '.',
        ];
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['advertisement-list', 'advertisement-update', 'advertisement-delete']);
        $countries = Country::all();
        $categories = Category::all();

        return view('items.index', compact('countries', 'categories'));
    }

    public function show($status, Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('advertisement-list');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'ASC');
            $sql = Item::with(['custom_fields', 'category:id,name', 'user' => function ($query) {
                $query->withTrashed()->select('id', 'name', 'email', 'profile', 'deleted_at');
            }, 'gallery_images', 'featured_items', 'currency:id,symbol'])->withTrashed();
            if (! empty($request->search)) {
                $sql = $sql->search($request->search);
            }
            if (! empty($request->filter)) {
                $filters = json_decode($request->filter, false, 512, JSON_THROW_ON_ERROR);
                if (is_object($filters) && count((array) $filters) > 0) {
                    // Handle status_not separately if present
                    $hasStatusNot = isset($filters->status_not);
                    $statusNotValue = null;

                    if ($hasStatusNot) {
                        $statusNotValue = $filters->status_not;
                        $sql = $sql->where('status', '!=', $statusNotValue);
                    }

                    // Build remaining filters object (excluding status_not)
                    $remainingFilters = [];
                    foreach ($filters as $key => $value) {
                        if ($key !== 'status_not') {
                            $remainingFilters[$key] = $value;
                        }
                    }

                    // Apply remaining filters (status, country, state, city, featured_status, etc.)
                    if (! empty($remainingFilters)) {
                        $sql = $sql->filter((object) $remainingFilters);
                    }
                }
            }

            $total = $sql->count();
            $sql = $sql->sort($sort, $order)->skip($offset)->take($limit);
            $result = $sql->get();
            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];

            $itemCustomFieldValues = ItemCustomFieldValue::whereIn('item_id', $result->pluck('id'))->get();
            foreach ($result as $row) {
                /* Merged ItemCustomFieldValue's data to main data */
                $itemCustomFieldValue = $itemCustomFieldValues->filter(function ($data) use ($row) {
                    return $data->item_id == $row->id;
                });
                $featured_status = $row->featured_items->isNotEmpty() ? 'Featured' : 'Not-Featured';
                $row->custom_fields = collect($row->custom_fields)->map(function ($customField) use ($itemCustomFieldValue) {
                    $customField['value'] = $itemCustomFieldValue->first(function ($data) use ($customField) {
                        return $data->custom_field_id == $customField->id;
                    });

                    if ($customField->type == 'fileinput' && ! empty($customField['value']->value)) {
                        $rawPath = $customField['value']->getRawOriginal('value');
                        $filePath = $this->extractFilePath($rawPath);
                        $customField['value'] = ! empty($filePath) ? url(Storage::url($filePath)) : '';
                    }

                    return $customField;
                });
                $tempRow = $row->toArray();
                $isUserDeleted = $row->user && $row->user->deleted_at !== null;
                $tempRow['is_user_deleted'] = $isUserDeleted;
                $operate = '';
                if (!$isUserDeleted && Auth::user()->can('advertisement-update')) {
                    // Navigates directly to the advertisement edit page
                    $operate .= BootstrapTableService::editButton(route('advertisement.edit', $row->id));
                }
                if (Auth::user()->can('advertisement-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('advertisement.destroy', $row->id));
                }
                if (Auth::user()->can('advertisement-list')) {
                    // View Custom Field — modal opened via JS delegation in index.blade.php
                    $operate .= BootstrapTableService::button('fa fa-eye', '#', ['editdata', 'btn-secondary'], ['title' => __('View')]);
                }
                if (!$isUserDeleted && $row->status !== 'sold out' && $row->status !== "expired" && Auth::user()->can('advertisement-update')) {
                    // Opens the status approval modal — triggered via JS delegation in index.blade.php
                    $operate .= BootstrapTableService::button('fas fa-toggle-on', "" ,['edit-status', 'btn-warning'], ['title' => __('Update Status'), 'id' => $row->id]);
                }
                $tempRow['active_status'] = empty($row->deleted_at); // IF deleted_at is empty then status is true else false
                $tempRow['featured_status'] = $featured_status;
                $tempRow['operate'] = $operate;
                $tempRow['selectable'] = !$isUserDeleted && !in_array($row->status, ['sold out', 'expired']);
                $tempRow['expiry_date'] = !empty($row->expiry_date) ? date('Y-m-d', strtotime($row->expiry_date)) : null;
                $tempRow['created_at'] = !empty($row->created_at) ? date('Y-m-d', strtotime($row->created_at)) : null;
                $tempRow['updated_at'] = !empty($row->updated_at) ? date('Y-m-d', strtotime($row->updated_at)) : null;

                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;

            return response()->json($bulkData);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController --> show');
            ResponseService::errorResponse();
        }
    }

    public function updateItemApproval(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('advertisement-update');
            $id = $request->id;

            $item = Item::with('user')->withTrashed()->findOrFail($id);

            $data = $request->except(['created_at']);

            // Handle rejected reason
            $data['rejected_reason'] =
                in_array($request->status, ['soft rejected', 'permanent rejected'])
                ? $request->rejected_reason
                : '';

            // ✅ Update created_at ONLY when approved
            if ($request->status === 'approved') {
                $data['created_at'] = Carbon::now();
            }

            $item->update($data);

            if (!empty($item->user->id)) {
                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    'About ' . $item->name,
                    'Your Advertisement is ' . ucfirst($request->status),
                    'item-update',
                    ['id' => $item->id],
                    false,
                    array($item->user->id)
                );
                // NotificationService::sendFcmNotification(
                //     $user_token,
                //     'About ' . $item->name,
                //     'Your Advertisement is ' . ucfirst($request->status),
                //     'item-update',
                //     ['id' => $item->id]
                // );
            }

            ResponseService::successResponse('Advertisement Status Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController ->updateItemApproval');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function bulkUpdateItemApproval(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('advertisement-update');

            $validator = Validator::make($request->all(), [
                'ids'             => 'required|array|min:1',
                'ids.*'           => 'required|integer|exists:items,id',
                'status'          => 'required|string|in:approved,review,soft rejected,permanent rejected',
                'rejected_reason' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Rejection reason is required when soft/permanent rejected
            if (in_array($request->status, ['soft rejected', 'permanent rejected']) && empty($request->rejected_reason)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Rejection reason is required for rejected status.',
                ], 422);
            }

            $itemsQuery = Item::with('user')->withTrashed()->whereIn('id', $request->ids);
            $userIDs = $itemsQuery->pluck('user_id');
            $items = $itemsQuery->get();

            foreach ($items as $item) {
                $data = [
                    'status'          => $request->status,
                    'rejected_reason' => in_array($request->status, ['soft rejected', 'permanent rejected'])
                        ? $request->rejected_reason
                        : '',
                ];

                $item->update($data);

                // if (!empty($item->user->id)) {
                //     NotificationService::dispatchChunkedNotifications(
                //         'About ' . $item->name,
                //         'Your Advertisement is ' . ucfirst($request->status),
                //         'item-update',
                //         ['id' => $item->id],
                //         false,
                //         [$item->user->id]
                //     );
                // }
            }

            if (collect($userIDs)->isNotEmpty()) {
                $userIDsArray = $userIDs->toArray();
                NotificationService::dispatchChunkedNotifications(
                    'About ' . $item->name,
                    'Your Advertisement is ' . ucfirst($request->status),
                    'item-update',
                    ['id' => $item->id],
                    false,
                    $userIDsArray
                );
            }

            ResponseService::successResponse('Advertisement status updated successfully.');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController -> bulkUpdateItemApproval');
            return ResponseService::errorResponse();
        }
    }


    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('advertisement-delete');

        try {

            $item = Item::with('gallery_images')->withTrashed()->findOrFail($id);
            foreach ($item->gallery_images as $gallery_image) {
                FileService::delete($gallery_image->getRawOriginal('image'));
            }
            FileService::delete($item->getRawOriginal('image'));

            $item->forceDelete();

            ResponseService::successResponse('Advertisement deleted successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse('Something went wrong');
        }
    }

    public function requestedItem()
    {
        ResponseService::noAnyPermissionThenRedirect(['advertisement-list', 'advertisement-update', 'advertisement-delete']);
        $countries = Country::all();
        $cities = City::all();

        return view('items.requested_item', compact('countries', 'cities'));
    }

    public function searchState(Request $request)
    {
        $countryName = trim($request->query('country_name'));
        if ($countryName == 'All') {
            return response()->json(['message' => 'Success', 'data' => []]);
        }
        $country = Country::where('name', $countryName)->first();

        if (! $country) {
            return response()->json(['message' => 'Success', 'data' => []]);
        }
        $states = State::where('country_id', $country->id)->get();

        return response()->json(['message' => 'Success', 'data' => $states]);
    }

    public function searchCities(Request $request)
    {
        $stateName = trim($request->query('state_name'));
        if ($stateName == 'All') {
            return response()->json(['message' => 'Success', 'data' => []]);
        }
        $state = State::where('name', $stateName)->first();
        if (! $state) {
            return response()->json(['message' => 'Success', 'data' => []]);
        }
        $cities = City::where('state_id', $state->id)->get();

        return response()->json(['message' => 'Success', 'data' => $cities]);
    }

    public function editForm($id)
    {
        $item = Item::with(
            'user:id,name,email,mobile,profile,country_code',
            'category.custom_fields', // get custom fields from category
            'gallery_images:id,image,item_id,is_default',
            'featured_items',
            'favourites',
            'item_custom_field_values.custom_field',
            'area',
            'currency:id,name'
        )->findOrFail($id);
        $categories = Category::whereNull('parent_category_id')
            ->with([
                'custom_fields',
                'subcategories',
                'subcategories.custom_fields',
                'subcategories.subcategories',
                'subcategories.subcategories.custom_fields',
                'subcategories.subcategories.subcategories',
                'subcategories.subcategories.subcategories.custom_fields',
                'subcategories.subcategories.subcategories.subcategories',
                'subcategories.subcategories.subcategories.subcategories.custom_fields',
                'subcategories.subcategories.subcategories.subcategories.subcategories',
                'subcategories.subcategories.subcategories.subcategories.subcategories.custom_fields',
                'subcategories.subcategories.subcategories.subcategories.subcategories.subcategories',
                'subcategories.subcategories.subcategories.subcategories.subcategories.subcategories.custom_fields',
                'subcategories.subcategories.subcategories.subcategories.subcategories.subcategories.subcategories',
                'subcategories.subcategories.subcategories.subcategories.subcategories.subcategories.subcategories.custom_fields',
            ])
            ->get();
        // $categories=[];

        $currencies = Currency::all();

        $all_categories_till_parent = [];

        $categoryId = $item->category_id; // assume it's integer
        if ($categoryId) {
            $all_categories_till_parent[] = $categoryId;
        }

        while ($categoryId) {
            $parent = Category::without('translations')->where('id', $categoryId)->value('parent_category_id');
            if ($parent) {
                $all_categories_till_parent[] = $parent;
                $categoryId = $parent;
            } else {
                $categoryId = null;
            }
        }

        $all_categories_till_parent = array_unique($all_categories_till_parent);

        $customFieldCategories = CustomFieldCategory::with('custom_fields.translations')
            ->whereIn('category_id', $all_categories_till_parent)
            ->get();

        $savedValues = ItemCustomFieldValue::where('item_id', $item->id)->get();
        $defaultLanguageId = CachingService::getDefaultLanguage()->id;
        $savedValuesByField = $savedValues->filter(function ($item) use ($defaultLanguageId) {
            return $item->language_id === null || $item->language_id == $defaultLanguageId;
        })->keyBy('custom_field_id');
        $savedValuesByLanguage = $savedValues->filter(function ($item) use ($defaultLanguageId) {
            return $item->language_id === null || $item->language_id == $defaultLanguageId;
        })->groupBy('custom_field_id');
        
        $custom_fields = $customFieldCategories->map(function ($relation) use ($savedValuesByField, $savedValuesByLanguage) {
            $field = $relation->custom_fields;
            if (! $field) {
                return null;
            }

            // Use getRawOriginal to get the raw value (JSON string) before accessor decodes it
            $valueRecord = $savedValuesByField->get($field->id);
            $rawValue = $valueRecord ? $valueRecord->getRawOriginal('value') : null;
            
            // Load translated values for this field
            $translatedValues = [];
            if ($savedValuesByLanguage->has($field->id)) {
                foreach ($savedValuesByLanguage->get($field->id) as $translatedValueRecord) {
                    $langId = $translatedValueRecord->language_id;
                    // Use getRawOriginal to get the raw value (JSON string) before accessor decodes it
                    $translatedRawValue = $translatedValueRecord->getRawOriginal('value');
                    if (!empty($translatedRawValue)) {
                        // Decode the JSON string
                        if(json_validate($translatedRawValue)) {
                            $decoded = json_decode($translatedRawValue, true);
                        }else{
                            $decoded = $translatedRawValue;
                        }
                        // Handle the decoded value - if it's an array, get first element or use the array
                        if (is_array($decoded)) {
                            $translatedValues[$langId] = count($decoded) === 1 ? ($decoded[0] ?? '') : $decoded;
                        } else {
                            $translatedValues[$langId] = $decoded ?? '';
                        }
                    }
                }
            }
            // Assign the complete array to the field property
            $field->translated_values = $translatedValues;

            if ($field->type === 'fileinput') {
                // For fileinput, extract single file path (handle old JSON data)
                if (!empty($rawValue)) {
                    $filePath = $this->extractFilePath($rawValue);
                    $field->value = !empty($filePath) ? url(Storage::url($filePath)) : '';
                } else {
                    $field->value = '';
                }
            } else {
                // For other field types, decode the value
                if (!empty($rawValue)) {
                    $decoded = json_decode($rawValue, true);
                    if (is_array($decoded)) {
                        if (in_array($field->type, ['textbox', 'number'])) {
                            // For textbox/number, if single element, use it directly; otherwise implode
                            $field->value = count($decoded) === 1 ? ($decoded[0] ?? '') : implode(', ', $decoded);
                        } else {
                            $field->value = $decoded;
                        }
                    } else {
                        $field->value = $decoded ?? '';
                    }
                } else {
                    $field->value = '';
                }
            }
            if (in_array($field->type, ['dropdown', 'radio'])) {
                if (is_array($field->value)) {
                    $field->value = count($field->value) > 0 ? (string) $field->value[0] : '';
                } elseif (is_object($field->value)) {
                    $field->value = '';
                }
            }

            return $field;
        })->filter();
        $countries = Country::all();
        $selected_category = [$item->category_id];
        $languages = CachingService::getLanguages()->values();
        $defaultLanguage = CachingService::getDefaultLanguage();
        
        // Load existing translations
        $translations = HelperService::transformTranslationsForEdit($item->translations);
        $seoTranslations = HelperService::prepareSeoTranslationsForEdit($item);

        $geminiEnabled = CachingService::getSystemSettings('gemini_ai_enabled') === '1';
        $mapProvider = CachingService::getSystemSettings('map_provider') ?? 'free_api';

        return view('items.update', compact('item', 'categories', 'custom_fields', 'selected_category', 'countries', 'currencies', 'languages', 'defaultLanguage', 'translations', 'seoTranslations', 'geminiEnabled', 'mapProvider'));
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('advertisement-update');
        
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'min:3', 'max:150', self::NAME_REGEX],
                'slug' => 'nullable|regex:/^[a-z0-9-]+$/',
                'description' => 'nullable|string|max:5000',
                'latitude' => 'nullable',
                'longitude' => 'nullable',
                'address' => 'nullable',
                'contact' => 'nullable',
                'custom_fields' => 'nullable',
                'custom_field_files' => 'nullable|array',
                'custom_field_files.*' => 'nullable|mimes:jpeg,png,jpg,pdf,doc|max:7168',
                'gallery_images' => 'nullable|array',
                'delete_item_image_id' => 'nullable|array',
                'admin_edit_reason' => 'required|string|max:1000',
                'currency_id' => 'nullable|exists:currencies,id',
            ], self::nameMessages());

            if ($validator->fails()) {
                $errorMessage = $validator->errors()->first();
                ResponseService::errorResponse($errorMessage);
                return; // Ensure execution stops
            }

            DB::beginTransaction();
            $item = Item::findOrFail($id);

            $category = Category::findOrFail($request->category_id);
            $isJobCategory = $category->is_job_category;
            $isPriceOptional = $category->price_optional;

            // Build validation rules based on category settings
            $validationRules = [];
            
            if ($isJobCategory) {
                // Job category: show salary fields
                if ($isPriceOptional) {
                    // Both job category AND price optional: salary is optional
                    $validationRules = [
                        'min_salary' => 'nullable|numeric|min:0|max:' . self::MAX_AMOUNT,
                        'max_salary' => 'nullable|numeric|gte:min_salary|max:' . self::MAX_AMOUNT,
                    ];
                } else {
                    // Job category but price not optional: salary is required
                    $validationRules = [
                        'min_salary' => 'required|numeric|min:0|max:' . self::MAX_AMOUNT,
                        'max_salary' => 'required|numeric|gte:min_salary|max:' . self::MAX_AMOUNT,
                    ];
                }
            } else {
                // Not a job category
                if ($isPriceOptional) {
                    // Price optional: price is optional
                    $validationRules = [
                        'price' => 'nullable|numeric|min:0|max:' . self::MAX_AMOUNT,
                    ];
                } else {
                    // Price not optional: price is required
                    $validationRules = [
                        'price' => 'required|numeric|min:0|max:' . self::MAX_AMOUNT,
                    ];
                }
            }
        
            $validator = Validator::make($request->all(), $validationRules);
            
            if ($validator->fails()) {
                DB::rollBack();
                $errorMessage = $validator->errors()->first();
                ResponseService::errorResponse($errorMessage);
                return; // Ensure execution stops
            }

            $customFieldCategories = CustomFieldCategory::with('custom_fields')
                ->where('category_id', $request->category_id)
                ->get();

            $customFieldErrors = [];
            foreach ($customFieldCategories as $relation) {
                $field = $relation->custom_fields;
                if (empty($field) || $field->required != 1 || $field->status != 1) {
                    continue;
                }
                $fieldId = $field->id;
                $fieldLabel = $field->name;

                if (in_array($field->type, ['textbox', 'number', 'dropdown', 'radio'])) {
                    if (empty($request->input("custom_fields.$fieldId"))) {
                        $customFieldErrors["custom_fields.$fieldId"] = "The $fieldLabel field is required.";
                    }
                }

                if ($field->type === 'checkbox') {
                    if (! is_array($request->input("custom_fields.$fieldId")) || empty($request->input("custom_fields.$fieldId"))) {
                        $customFieldErrors["custom_fields.$fieldId"] = "The $fieldLabel field is required.";
                    }
                }

                if ($field->type === 'fileinput') {
                    $existing = ItemCustomFieldValue::where([
                        'item_id' => $id,
                        'custom_field_id' => $fieldId,
                    ])->first();

                    if (! $request->hasFile("custom_field_files.$fieldId") && empty($existing?->value)) {
                        $customFieldErrors["custom_field_files.$fieldId"] = "The $fieldLabel file is required.";
                    }
                }
            }
            
            if (! empty($customFieldErrors)) {
                DB::rollBack();
                $errorMessage = reset($customFieldErrors); // Get first error message
                ResponseService::errorResponse($errorMessage);
                return; // Ensure execution stops
            }

            $data = array_merge($request->all(), [
                'is_edited_by_admin' => 1,
                'admin_edit_reason' => $request->admin_edit_reason,
            ]);

            // Address data from map selection
            $data['address'] = $request->input('address') ?? $request->input('address_input') ?? '';
            $data['country'] = $request->input('country_input') ?? '';
            $data['state'] = $request->input('state_input') ?? '';
            $data['city'] = $request->input('city_input') ?? '';
            $data['latitude'] = $request->input('latitude');
            $data['longitude'] = $request->input('longitude');
            $data['country_code'] = $request->input('country_code') ?? $item->country_code ?? null;
            $data['region_code'] = $request->input('region_code') ?? $item->region_code ?? null;

            $oldCategoryId = $item->category_id;
            $newCategoryId = $request->category_id;

            $isCategoryChanged = $oldCategoryId != $newCategoryId;
            $oldCustomFieldValues = ItemCustomFieldValue::where('item_id', $item->id)->get();
            foreach ($oldCustomFieldValues as $fieldValue) {
                $customField = CustomField::find($fieldValue->custom_field_id);
                if ($customField && $customField->type === 'fileinput') {
                    $rawFilePath = $fieldValue->getRawOriginal('value');
                    if (!empty($rawFilePath)) {
                        $filePath = $this->extractFilePath($rawFilePath);
                        if (!empty($filePath)) {
                            FileService::delete($filePath);
                        }
                    }
                }
            }
            if ($isCategoryChanged) {
                ItemCustomFieldValue::where('item_id', $item->id)->delete();
            }
            $item->update($data);

            // Handle translations - only name and description are translatable
            if ($request->has('translations')) {
                $translationData = [];
                foreach ($request->input('translations', []) as $languageId => $transData) {
                    if (!empty($transData['name'])) {
                        $translationData[] = [
                            'translatable_id'   => $item->id,
                            'translatable_type' => get_class($item),
                            'key'               => 'name',
                            'value'             => $transData['name'],
                            'language_id'       => $languageId,
                        ];
                    }
                    if (!empty($transData['description'])) {
                        $translationData[] = [
                            'translatable_id'   => $item->id,
                            'translatable_type' => get_class($item),
                            'key'               => 'description',
                            'value'             => $transData['description'],
                            'language_id'       => $languageId,
                        ];
                    }
                }
                if (!empty($translationData)) {
                    HelperService::storeTranslations($translationData);
                }
            }

            if ($request->custom_fields) {
                foreach ($request->custom_fields as $key => $custom_field) {
                    $value = is_array($custom_field) ? $custom_field : [$custom_field];
                    ItemCustomFieldValue::updateOrCreate(
                        [
                            'item_id' => $item->id,
                            'custom_field_id' => $key,
                        ],
                        [
                            'value' => json_encode($value, JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            // Handle custom field translations
            if ($request->has('custom_field_translations')) {
                $customFieldTranslations = $request->input('custom_field_translations');
                if (is_array($customFieldTranslations)) {
                    foreach ($customFieldTranslations as $languageId => $fieldsByCustomField) {
                        foreach ($fieldsByCustomField as $customFieldId => $translatedValue) {
                            $value = is_array($translatedValue) ? $translatedValue : [$translatedValue];
                            ItemCustomFieldValue::updateOrCreate(
                                [
                                    'item_id' => $item->id,
                                    'custom_field_id' => $customFieldId,
                                    'language_id' => $languageId,
                                ],
                                [
                                    'value' => json_encode($value, JSON_THROW_ON_ERROR),
                                    'updated_at' => now(),
                                ]
                            );
                        }
                    }
                }
            }
            
            if ($request->hasFile('custom_field_files')) {
                $itemCustomFieldValues = [];
                foreach ($request->file('custom_field_files') as $key => $file) {
                    $value = ItemCustomFieldValue::where(['item_id' => $item->id, 'custom_field_id' => $key])->first();

                    // Get existing file path for replacement
                    $existingPath = null;
                    if ($value) {
                        $rawValue = $value->getRawOriginal('value');
                        if (!empty($rawValue)) {
                            $existingPath = $this->extractFilePath($rawValue);
                        }
                    }

                    $path = $existingPath
                        ? FileService::replace($file, 'custom_fields_files', $existingPath)
                        : FileService::upload($file, 'custom_fields_files');

                    
                    // Store as single plain path
                    ItemCustomFieldValue::updateOrCreate([
                        'item_id' => $item->id,
                        'custom_field_id' => $key,
                    ],
                    [
                        'value' => $path,
                        'updated_at' => now(),
                    ]);
                }
            }
            
            $itemImagesExists = ItemImages::where('item_id', $item->id)->exists();
            if ($request->hasFile('gallery_images')) {
                $galleryImages = [];
                foreach ($request->file('gallery_images') as $index => $file) {
                    $galleryImages[] = [
                        'image'      => FileService::compressAndUpload($file, 'item_images', true),
                        'is_default' => (!$itemImagesExists && $index === 0) ? 1 : 0,
                        'item_id'    => $item->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                ItemImages::insert($galleryImages);
            }

            // Custom field files
            foreach ($request->allFiles() as $key => $file) {
                if (Str::startsWith($key, 'custom_fields.')) {
                    $customFieldId = Str::after($key, 'custom_fields.');
                    $value = ItemCustomFieldValue::where(['item_id' => $item->id, 'custom_field_id' => $customFieldId])->first();

                    // Get existing file path for replacement
                    $existingPath = null;
                    if ($value) {
                        $rawValue = $value->getRawOriginal('value');
                        if (!empty($rawValue)) {
                            $existingPath = $this->extractFilePath($rawValue);
                        }
                    }

                    $filePath = $existingPath
                        ? FileService::replace($file, 'custom_fields_files', $existingPath)
                        : FileService::upload($file, 'custom_fields_files');

                    // Store as single plain path
                    ItemCustomFieldValue::updateOrCreate(
                        ['item_id' => $item->id, 'custom_field_id' => $customFieldId],
                        ['value' => $filePath, 'updated_at' => now()]
                    );
                }
            }
            
            if (! empty($request->delete_item_image_id)) {
                $itemImageIds = $request->delete_item_image_id;
                $deletedDefault = false;
                $itemImagesInDBQuery = ItemImages::whereIn('id', $itemImageIds);
                $itemImagesInDBCount = $itemImagesInDBQuery->clone()->count();
                if(!$request->hasFile('gallery_images') && $itemImagesInDBCount == count($request->delete_item_image_id)){
                    ResponseService::validationError(trans('At least one item image is required'));
                }
                $itemImagesInDB = $itemImagesInDBQuery->clone()->get();
                foreach ($itemImagesInDB as $itemImage) {
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

            DB::commit();

            // Store SEO details (outside transaction as it's non-critical)
            $languages = CachingService::getLanguages();
            HelperService::storeSeoDetails($item, $request, $languages->pluck('id')->toArray());

            if (! empty($item->user->id)) {
                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    'About ' . $item->name,
                    'Your Advertisement is edited by admin',
                    'item-edit',
                    ['id' => $request->id],
                    false,
                    array($item->user->id)
                );
            }

            ResponseService::successResponse('Advertisement Updated Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'ItemController -> update', 'An error occurred while updating the Advertisement.', false);
            ResponseService::errorResponse('An error occurred while updating the Advertisement.');
        }
    }

    public function getCustomFields(Request $request, $categoryId)
    {
        $categoryIds = $this->getParentCategoryIds($categoryId);
        $category = Category::find($categoryId);
        $itemId = $request->input('item_id');
        
        $customFields = CustomField::with('translations')
            ->whereHas('custom_field_category', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            })
            ->where('status', 1)
            ->get();
        
        // Load existing values if item_id is provided
        $savedValuesByField = collect();
        $savedValuesByLanguage = collect();
        if ($itemId) {
            $savedValues = ItemCustomFieldValue::where('item_id', $itemId)->get();
            $defaultLanguageId = CachingService::getDefaultLanguage()->id;
            $savedValuesByField = $savedValues->filter(function ($item) use ($defaultLanguageId) {
                return $item->language_id === null || $item->language_id == $defaultLanguageId;
            })->keyBy('custom_field_id');
            $savedValuesByLanguage = $savedValues->filter(function ($item) use ($defaultLanguageId) {
                return $item->language_id !== null && $item->language_id != $defaultLanguageId;
            })->groupBy('custom_field_id');
        }
        
        $customFields = $customFields->map(function ($field) use ($savedValuesByField, $savedValuesByLanguage) {
            $field->has_translations = $field->translations->isNotEmpty();
            $field->translations_count = $field->translations->count();
            
            // Load existing value for this field
            $valueRecord = $savedValuesByField->get($field->id);
            if ($valueRecord) {
                $rawValue = $valueRecord->getRawOriginal('value');
                if (!empty($rawValue)) {
                    if ($field->type === 'fileinput') {
                        $filePath = $this->extractFilePath($rawValue);
                        $field->value = !empty($filePath) ? url(Storage::url($filePath)) : '';
                    } else {
                        $decoded = json_decode($rawValue, true);
                        if (is_array($decoded)) {
                            if (in_array($field->type, ['textbox', 'number'])) {
                                $field->value = count($decoded) === 1 ? ($decoded[0] ?? '') : implode(', ', $decoded);
                            } else {
                                $field->value = $decoded;
                            }
                        } else {
                            $field->value = $decoded ?? '';
                        }
                    }
                } else {
                    $field->value = '';
                }
            } else {
                $field->value = '';
            }
            
            // Load translated values
            $translatedValues = [];
            if ($savedValuesByLanguage->has($field->id)) {
                foreach ($savedValuesByLanguage->get($field->id) as $translatedValueRecord) {
                    $langId = $translatedValueRecord->language_id;
                    $rawValue = $translatedValueRecord->getRawOriginal('value');
                    if (!empty($rawValue)) {
                        $decoded = json_decode($rawValue, true);
                        if (is_array($decoded)) {
                            $translatedValues[$langId] = count($decoded) === 1 ? ($decoded[0] ?? '') : $decoded;
                        } else {
                            $translatedValues[$langId] = $decoded ?? '';
                        }
                    }
                }
            }
            $field->translated_values = $translatedValues;
            
            return $field;
        });

        return response()->json([
            'fields' => $customFields,
            'is_job_category' => $category->is_job_category,
            'price_optional' => $category->price_optional,
            'category_ids' => $categoryIds,
        ]);
    }

    protected function getParentCategoryIds($categoryId, &$ids = [])
    {
        $category = Category::find($categoryId);

        if ($category) {
            $ids[] = $category->id;
            if ($category->parent_category_id) {
                $this->getParentCategoryIds($category->parent_category_id, $ids);
            }
        }

        return array_reverse($ids);
    }

    /**
     * Extract a single file path from a raw value.
     * Handles both old JSON-encoded data (e.g. '["path/to/file.jpg"]') and plain path strings.
     */
    protected function extractFilePath($rawValue)
    {
        if (empty($rawValue)) {
            return '';
        }

        if (json_validate($rawValue)) {
            $decoded = json_decode($rawValue, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded[0] ?? '';
            }
            return is_string($decoded) ? $decoded : '';
        }

        return $rawValue;
    }

    public function create()
    {
        ResponseService::noAnyPermissionThenRedirect(['advertisement-create']);

        // No need to load categories here, they'll be loaded via AJAX
        $countries = Country::all();
        $currencies = Currency::all();
        $languages = CachingService::getLanguages()->values();
        $defaultLanguage = CachingService::getDefaultLanguage();

        $geminiEnabled = CachingService::getSystemSettings('gemini_ai_enabled') === '1';
        $mapProvider = CachingService::getSystemSettings('map_provider') ?? 'free_api';

        return view('items.create', compact('countries', 'currencies', 'languages', 'defaultLanguage', 'geminiEnabled', 'mapProvider'));
    }

    public function getParentCategories(Request $request)
    {
        ResponseService::noPermissionThenSendJson('advertisement-create');

        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            $categories = Category::whereNull('parent_category_id')
                ->where('status', 1)
                ->orderBy('sequence', 'ASC')
                ->withCount(['subcategories' => function ($q) {
                    $q->where('status', 1);
                }])
                ->skip(($page - 1) * $perPage)
                ->take($perPage + 1)
                ->get(['id', 'name', 'status', 'image']);

            $hasMore = $categories->count() > $perPage;
            $categories = $categories->take($perPage);

            return response()->json([
                'message' => 'Success',
                'data' => $categories,
                'has_more' => $hasMore,
                'current_page' => $page,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController -> getParentCategories');

            return response()->json(['message' => 'Error loading categories'], 500);
        }
    }

    public function getSubCategories(Request $request)
    {
        ResponseService::noPermissionThenSendJson('advertisement-create');

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            $subcategories = Category::where('parent_category_id', $request->category_id)
                ->where('status', 1)
                ->orderBy('sequence', 'ASC')
                ->withCount(['subcategories' => function ($q) {
                    $q->where('status', 1);
                }])
                ->skip(($page - 1) * $perPage)
                ->take($perPage + 1)
                ->get(['id', 'name', 'parent_category_id', 'status', 'image']);

            $hasMore = $subcategories->count() > $perPage;
            $subcategories = $subcategories->take($perPage);

            return response()->json([
                'message' => 'Success',
                'data' => $subcategories,
                'has_more' => $hasMore,
                'current_page' => $page,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController -> getSubCategories');

            return response()->json(['message' => 'Error loading subcategories'], 500);
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('advertisement-create');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:150', self::NAME_REGEX],
            'slug' => 'nullable|regex:/^[a-z0-9-]+$/',
            'description' => 'required|string|max:5000',
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'nullable',
            'contact' => 'nullable',
            'custom_fields' => 'nullable',
            'custom_field_files' => 'nullable|array',
            'custom_field_files.*' => 'nullable|mimes:jpeg,png,jpg,pdf,doc|max:7168',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|mimes:jpeg,png,jpg|max:7168',
            'video_link' => 'nullable|url',
            'category_id' => 'required|integer',
            'currency_id' => 'nullable|integer',
        ], self::nameMessages());

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            ResponseService::errorResponse($errorMessage);
            return; // Ensure execution stops
        }

        // Ensure database connection is alive before starting transaction
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            DB::reconnect();
        }

        DB::beginTransaction();
        try {
            $category = Category::findOrFail($request->category_id);
            $isJobCategory = $category->is_job_category;
            $isPriceOptional = $category->price_optional;

            // Build validation rules based on category settings
            $validationRules = [];
            
            if ($isJobCategory) {
                // Job category: show salary fields
                if ($isPriceOptional) {
                    // Both job category AND price optional: salary is optional
                    $validationRules = [
                        'min_salary' => 'nullable|numeric|min:0|max:' . self::MAX_AMOUNT,
                        'max_salary' => 'nullable|numeric|gte:min_salary|max:' . self::MAX_AMOUNT,
                    ];
                } else {
                    // Job category but price not optional: salary is required
                    $validationRules = [
                        'min_salary' => 'required|numeric|min:0|max:' . self::MAX_AMOUNT,
                        'max_salary' => 'required|numeric|gte:min_salary|max:' . self::MAX_AMOUNT,
                    ];
                }
            } else {
                // Not a job category
                if ($isPriceOptional) {
                    // Price optional: price is optional
                    $validationRules = [
                        'price' => 'nullable|numeric|min:0|max:' . self::MAX_AMOUNT,
                    ];
                } else {
                    // Price not optional: price is required
                    $validationRules = [
                        'price' => 'required|numeric|min:0|max:' . self::MAX_AMOUNT,
                    ];
                }
            }
            
            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                DB::rollBack();
                $errorMessage = $validator->errors()->first();
                ResponseService::errorResponse($errorMessage);
                return; // Ensure execution stops
            }

            $customFieldCategories = CustomFieldCategory::with('custom_fields')
                ->where('category_id', $request->category_id)
                ->get();

            $customFieldErrors = [];
            foreach ($customFieldCategories as $relation) {
                $field = $relation->custom_fields;
                if (empty($field) || $field->required != 1 || $field->status != 1) {
                    continue;
                }

                $fieldId = $field->id;
                $fieldLabel = $field->name;

                if (in_array($field->type, ['textbox', 'number', 'dropdown', 'radio'])) {
                    if (empty($request->input("custom_fields.$fieldId"))) {
                        $customFieldErrors["custom_fields.$fieldId"] = "The $fieldLabel field is required.";
                    }
                }

                if ($field->type === 'checkbox') {
                    if (! is_array($request->input("custom_fields.$fieldId")) || empty($request->input("custom_fields.$fieldId"))) {
                        $customFieldErrors["custom_fields.$fieldId"] = "The $fieldLabel field is required.";
                    }
                }

                if ($field->type === 'fileinput') {
                    if (! $request->hasFile("custom_field_files.$fieldId")) {
                        $customFieldErrors["custom_field_files.$fieldId"] = "The $fieldLabel file is required.";
                    }
                }
            }

            if (! empty($customFieldErrors)) {
                DB::rollBack();
                $errorMessage = reset($customFieldErrors); // Get first error message
                ResponseService::errorResponse($errorMessage);
                return; // Ensure execution stops
            }

            $slug = trim($request->input('slug') ?? '');
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($slug));
            $slug = trim($slug, '-');
            if (empty($slug)) {
                $slug = HelperService::generateRandomSlug();
            }
            $uniqueSlug = HelperService::generateUniqueSlug(new Item, $slug);

            $user = Auth::user();

            $data = [
                'name' => $request->name,
                'slug' => $uniqueSlug,
                'description' => $request->description,
                'address' => $request->input('address') ?? $request->input('address_input') ?? '',
                'country' => $request->input('country_input') ?? '',
                'state' => $request->input('state_input') ?? '',
                'city' => $request->input('city_input') ?? '',
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'contact' => $request->contact ?? $user->contact,
                'country_code' => $request->country_code ?? $user->country_code ?? null,
                'region_code' => $request->region_code ?? $user->region_code ?? null,
                'category_id' => $request->category_id,
                'price' => $request->price,
                'min_salary' => $request->min_salary,
                'max_salary' => $request->max_salary,
                'video_link' => $request->video_link,
                'user_id' => $user->id,
                'status' => 'approved',
                'currency_id' => $request->currency_id ?? null,
            ];

            $item = Item::create($data);

            // Handle translations - only name and description are translatable
            if ($request->has('translations')) {
                foreach ($request->input('translations', []) as $languageId => $translationData) {
                    if (!empty($translationData['name']) || !empty($translationData['description'])) {
                        HelperService::storeTranslations([
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'name', 'value' => $translationData['name'] ?? '', 'language_id' => $languageId],
                            ['translatable_id' => $item->id, 'translatable_type' => \App\Models\Item::class, 'key' => 'description', 'value' => $translationData['description'] ?? '', 'language_id' => $languageId],
                        ]);
                    }
                }
            }

            if ($request->custom_fields) {
                foreach ($request->custom_fields as $key => $custom_field) {
                    $value = is_array($custom_field) ? $custom_field : [$custom_field];
                    ItemCustomFieldValue::create([
                        'item_id' => $item->id,
                        'custom_field_id' => $key,
                        'value' => json_encode($value, JSON_THROW_ON_ERROR),
                    ]);
                }
            }

            // Handle custom field translations
            if ($request->has('custom_field_translations')) {
                $customFieldTranslations = $request->input('custom_field_translations');
                if (is_array($customFieldTranslations)) {
                    foreach ($customFieldTranslations as $languageId => $fieldsByCustomField) {
                        foreach ($fieldsByCustomField as $customFieldId => $translatedValue) {
                            $value = is_array($translatedValue) ? $translatedValue : [$translatedValue];
                            ItemCustomFieldValue::create([
                                'item_id' => $item->id,
                                'custom_field_id' => $customFieldId,
                                'language_id' => $languageId,
                                'value' => json_encode($value, JSON_THROW_ON_ERROR),
                            ]);
                        }
                    }
                }
            }

            if ($request->hasFile('custom_field_files')) {
                foreach ($request->file('custom_field_files') as $key => $file) {
                    $path = FileService::upload($file, 'custom_fields_files');
                    ItemCustomFieldValue::create([
                        'item_id' => $item->id,
                        'custom_field_id' => $key,
                        'value' => $path,
                    ]);
                }
            }
            
            if ($request->hasFile('gallery_images')) {
                $galleryImages = [];
                foreach ($request->file('gallery_images') as $index => $file) {
                    $galleryImages[] = [
                        'image'      => FileService::compressAndUpload($file, 'item_images', true),
                        'is_default' => ($index === 0) ? 1 : 0,
                        'item_id'    => $item->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                 if (count($galleryImages) > 0) {
                    ItemImages::insert($galleryImages);
                }
            }

            // Custom field files from direct custom_fields input
            foreach ($request->allFiles() as $key => $file) {
                if (Str::startsWith($key, 'custom_fields.')) {
                    $customFieldId = Str::after($key, 'custom_fields.');
                    $filePath = FileService::upload($file, 'custom_fields_files');
                    ItemCustomFieldValue::create([
                        'item_id' => $item->id,
                        'custom_field_id' => $customFieldId,
                        'value' => $filePath,
                    ]);
                }
            }

            DB::commit();

            // Store SEO details (outside transaction as it's non-critical)
            $languages = CachingService::getLanguages();
            HelperService::storeSeoDetails($item, $request, $languages->pluck('id')->toArray());

            ResponseService::successResponse('Advertisement Created Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'ItemController -> store', 'An error occurred while creating the Advertisement.', false);

            ResponseService::errorResponse('An error occurred while creating the Advertisement.');
        }
    }
}
