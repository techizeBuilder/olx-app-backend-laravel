<?php

namespace App\Http\Resources;

use App\Models\City;
use App\Models\Language;
use App\Services\CurrencyFormatterService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use JsonSerializable;
use Throwable;

class ItemCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     *
     * @throws Throwable
     */
    public function toArray(Request $request)
    {

        try {
            $formatter = app(CurrencyFormatterService::class);
            $response = [];

            // Get current language once
            $contentLangCode = $request->header('Content-Language') ?? app()->getLocale();
            $currentLanguage = Language::where('code', $contentLangCode)->first();
            $currentLangId = $currentLanguage->id ?? 1;
            $defaultLangId = 1;

            foreach ($this->collection as $key => $collection) {

                // Base response
                $response[$key] = $collection->toArray();
                
                if(Auth::check() == false){
                    $response[$key]['contact'] = '';
                }

                // return response()->json($collection);

                // Currency & price formatting
                // $response[$key]['currency_symbol'] = $collection->currency_symbol;
                // $response[$key]['currency_position'] = $collection->currency_position;
                // $response[$key]['formatted_price'] = $collection->formatted_price;

                // $response[$key]['formatted_min_salary'] = $collection->formatted_min_salary;
                // $response[$key]['formatted_max_salary'] = $collection->formatted_max_salary;
                // $response[$key]['formatted_salary_range'] = $collection->formatted_salary_range ?? null;

                // $response[$key]['price'] = $collection->price;

                // return response()->json($collection->currencyRelation->currency_symbol);

                $response[$key]['formatted_price'] = $formatter->formatPrice(
                    $collection?->price,
                    $collection?->currency
                );

                $response[$key]['formatted_salary_range'] = $formatter->formatSalaryRange(
                    $collection?->min_salary,
                    $collection?->max_salary,
                    $collection?->currency
                );

                // Feature status
                $response[$key]['is_feature'] = $collection->status == 'approved' && $collection->relationLoaded('featured_items')
                    ? $collection->featured_items->isNotEmpty()
                    : false;

                // Favourites
                if ($collection->relationLoaded('favourites')) {
                    $response[$key]['total_likes'] = $collection->favourites->count();
                    $response[$key]['is_liked'] = Auth::check()
                        ? $collection->favourites->where('item_id', $collection->id)->where('user_id', Auth::id())->count() > 0
                        : false;
                }
                // Parse Expiry Date
                $response[$key]['expiry_date'] = Carbon::parse($collection->expiry_date);

                // Item is sold or not
                $response[$key]['is_sold'] = $collection->getRawOriginal('status') == 'sold out' ? 1 : 0;

                // User info
                if ($collection->relationLoaded('user') && ! is_null($collection->user)) {
                    $response[$key]['user'] = $collection->user;
                    $response[$key]['user']['reviews_count'] = $collection->user->sellerReview()->count();
                    $response[$key]['user']['average_rating'] = $collection->user->sellerReview->avg('ratings');
                    if ($collection->user->show_personal_details == 0 || Auth::check() == false) {
                        $response[$key]['user']['mobile'] = '';
                        $response[$key]['user']['country_code'] = '';
                        $response[$key]['user']['email'] = '';
                    }
                }

                // Load city once
                $city = City::with(['translations', 'state', 'country'])
                    ->where('name', $collection->city)
                    ->whereHas('state', fn($q) => $q->where('name', $collection->state))
                    ->first();

                // Translated item
                $translatedItem = [
                    'name' => $collection->name,
                    'description' => $collection->description,
                    'address' => $collection->address,
                    'rejected_reason' => $collection->rejected_reason ?? null,
                    'admin_edit_reason' => $collection->admin_edit_reason ?? null,
                    'city' => $city->translated_name ?? $collection->city,
                    'state' => $city->state->translated_name ?? $collection->state,
                    'country' => $city->country->translated_name ?? $collection->country,
                ];

                if ($currentLanguage && $collection->relationLoaded('translations')) {
                    $langTranslations = $collection->translations->where('language_id', $currentLangId);
                    if ($langTranslations->isNotEmpty()) {
                        $translatedItem = [
                            'name' => $langTranslations->where('key', 'name')->first()?->value ?? $translatedItem['name'],
                            'description' => $langTranslations->where('key', 'description')->first()?->value ?? $translatedItem['description'],
                            'address' => $langTranslations->where('key', 'address')->first()?->value ?? $translatedItem['address'],
                            'rejected_reason' => $langTranslations->where('key', 'rejected_reason')->first()?->value ?? ($translatedItem['rejected_reason'] ?? null),
                            'admin_edit_reason' => $langTranslations->where('key', 'admin_edit_reason')->first()?->value ?? ($translatedItem['admin_edit_reason'] ?? null),
                            'city' => $city->translated_name ?? $collection->city,
                            'state' => $city->state->translated_name ?? $collection->state,
                            'country' => $city->country->translated_name ?? $collection->country,
                        ];
                    }
                }

                $response[$key]['translated_item'] = $translatedItem;
                $response[$key]['translated_area'] = $collection->area->translated_name ?? '';
                $response[$key]['translated_city'] = $city?->translated_name ?? $collection->city;
                $response[$key]['translated_state'] = $city->state->translated_name ?? $collection->state;
                $response[$key]['translated_country'] = $city->country->translated_name ?? $collection->country;
                $response[$key]['translated_address'] =
                    (! empty($response[$key]['translated_area']) ? $response[$key]['translated_area'] . ', ' : '') .
                    $response[$key]['translated_city'] . ', ' .
                    $response[$key]['translated_state'] . ', ' .
                    $response[$key]['translated_country'];

                // Custom fields
                if ($collection->relationLoaded('item_custom_field_values')) {
                    $response[$key]['custom_fields'] = [];
                    $response[$key]['translated_custom_fields'] = [];
                    $response[$key]['all_translated_custom_fields'] = [];

                    $grouped = $collection->item_custom_field_values->groupBy('custom_field_id');

                    foreach ($grouped as $customFieldId => $fieldValues) {
                        $default = $fieldValues->firstWhere('language_id', $defaultLangId);
                        $translated = $currentLanguage ? $fieldValues->firstWhere('language_id', $currentLangId) : null;

                        // Default fields
                        if ($default && $default->relationLoaded('custom_field') && ! empty($default->custom_field)) {
                            $field = $default->custom_field;
                            $tempRow = $field->toArray();
                            $tempRow['value'] = $field->type === 'fileinput'
                                ? (! empty($default->value) ? url(Storage::url($this->extractFilePath($default->getRawOriginal('value')))) : '')
                                : (is_array($default->value) ? $default->value : json_decode($default->value, true));
                            $tempRow['custom_field_value'] = $default->toArray();
                            unset($tempRow['custom_field_value']['custom_field']);
                            $tempRow['translated_selected_values'] = $this->resolveTranslatedSelectedValues($field, $default);
                            $response[$key]['custom_fields'][] = $tempRow;
                        }

                        // Translated fields
                        $activeField = $translated ?? $default;
                        if ($activeField && $activeField->relationLoaded('custom_field') && ! empty($activeField->custom_field)) {
                            $field = $activeField->custom_field;
                            $tempRow = $field->toArray();
                            $tempRow['value'] = $field->type === 'fileinput'
                                ? (! empty($activeField->value) ? url(Storage::url($this->extractFilePath($activeField->getRawOriginal('value')))) : '')
                                : (is_array($activeField->value) ? $activeField->value : json_decode($activeField->value, true));
                            $tempRow['custom_field_value'] = $activeField->toArray();
                            unset($tempRow['custom_field_value']['custom_field']);
                            $tempRow['language_id'] = $activeField->language_id;
                            $tempRow['translated_selected_values'] = $this->resolveTranslatedSelectedValues($field, $activeField);
                            $response[$key]['translated_custom_fields'][] = $tempRow;
                        }

                        // All translated custom fields
                        foreach ($fieldValues as $fieldValue) {
                            if ($fieldValue->relationLoaded('custom_field') && ! empty($fieldValue->custom_field)) {
                                $field = $fieldValue->custom_field;
                                $tempRow = $field->toArray();
                                // $tempRow['value'] = $field->type === "fileinput"
                                //     ? (!empty($fieldValue->value) ? url(Storage::url($fieldValue->value)) : '')
                                //     : (is_array($fieldValue->value) ? $fieldValue->value : json_decode($fieldValue->value, true));

                                if ($field->type === 'fileinput') {
                                    if (! empty($fieldValue->value)) {
                                        $filePath = $this->extractFilePath($fieldValue->getRawOriginal('value'));
                                        $value = !empty($filePath) ? url(Storage::url($filePath)) : '';
                                    } else {
                                        $value = '';
                                    }
                                } else {
                                    $value = is_array($fieldValue->value)
                                        ? $fieldValue->value
                                        : json_decode($fieldValue->value, true);
                                }

                                $tempRow['value'] = $value;

                                $tempRow['custom_field_value'] = $tempRow;
                                unset($tempRow['custom_field_value']['custom_field']);
                                $tempRow['language_id'] = $fieldValue->language_id;
                                $tempRow['translated_selected_values'] = $this->resolveTranslatedSelectedValues($field, $fieldValue);
                                $response[$key]['all_translated_custom_fields'][] = $tempRow;
                            }
                        }
                    }

                    unset($response[$key]['item_custom_field_values']);
                }

                // Item Offers, Reports, Purchases, Job Applications
                $response[$key]['is_already_offered'] = $collection->relationLoaded('item_offers') && Auth::check()
                    ? $collection->item_offers->where('item_id', $collection->id)->where('buyer_id', Auth::id())->count() > 0
                    : false;

                $response[$key]['is_already_reported'] = $collection->relationLoaded('user_reports') && Auth::check()
                    ? $collection->user_reports->where('user_id', Auth::id())->count() > 0
                    : false;

                $response[$key]['is_purchased'] = Auth::check() && $collection->sold_to == Auth::id() ? 1 : 0;

                $response[$key]['is_already_job_applied'] = $collection->relationLoaded('job_applications') && Auth::check()
                    ? $collection->job_applications->where('item_id', $collection->id)->where('user_id', Auth::id())->count() > 0
                    : false;
                
                $seoTranslationArray = array();
                if(isset($response[$key]['seo_detail']) && isset($response[$key]['seo_detail']['translations'])){
                    $seoTranslationData = $response[$key]['seo_detail']['translations'];
                    if(!empty($seoTranslationData)){
                        unset($response[$key]['seo_detail']['translations']);
                        foreach ($seoTranslationData as $translationKey => $value) {
                            $seoTranslationArray[$value['language_id']]['language_id'] = $value['language_id'];
                            $seoTranslationArray[$value['language_id']][$value['key']] = $value['value'];
                        }
                        $response[$key]['seo_detail']['translations'] = $seoTranslationArray;
                    }
                }
            }

            // Featured and normal rows
            $featuredRows = [];
            $normalRows = [];
            foreach ($response as $value) {
                $value['is_feature'] ? $featuredRows[] = $value : $normalRows[] = $value;
            }

            $response = array_merge($featuredRows, $normalRows);
            $totalCount = count($response);

            if ($this->resource instanceof AbstractPaginator) {
                return [
                    ...$this->resource->toArray(),
                    'data' => $response,
                    'total_item_count' => $totalCount,
                ];
            }

            return $response;
        } catch (Throwable $th) {
            throw $th;
        }
    }

    /**
     * Extract a single file path from a raw value.
     * Handles both old JSON-encoded data and plain path strings.
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

    public function resolveTranslatedSelectedValues($field, $fieldValue)
    {
        $type = $field->type ?? null;
        $values = $fieldValue->value ?? null;
        $allPossibleValues = $field->values ?? [];
        $selected = [];

        $contentLangCode = request()->header('Content-Language') ?? app()->getLocale();
        $currentLanguage = Language::where('code', $contentLangCode)->first();
        $currentLangId = $currentLanguage->id ?? 1;

        $translatedValues = [];
        if (! empty($field->translations)) {
            $valueTrans = collect($field->translations)->where('language_id', $currentLangId)->where('key', 'value')->first();
            $translatedValues = $valueTrans['value'] ?? [];
            if (is_string($translatedValues)) {
                $translatedValues = json_decode($translatedValues, true) ?? [];
            }
        }

        if (in_array($type, ['checkbox', 'radio', 'dropdown'])) {
            $actualValues = is_array($values) ? $values : json_decode($values, true);
            if (! is_array($actualValues)) {
                $actualValues = [$actualValues];
            }

            foreach ($actualValues as $val) {
                $index = array_search($val, $allPossibleValues);
                $translatedVal = (is_array($translatedValues) && $index !== false && isset($translatedValues[$index]))
                    ? $translatedValues[$index]
                    : $val;

                $selected[] = $translatedVal;
            }
        } elseif (in_array($type, ['textbox', 'number'])) {
            $actualValue = is_array($values) ? ($values[0] ?? '') : $values;
            $selected[] = $actualValue;
        }

        return $selected;
    }
}
