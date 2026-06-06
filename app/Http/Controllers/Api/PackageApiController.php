<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\UserPurchasedPackage;
use App\Services\CurrencyFormatterService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/** @tags Package */
class PackageApiController extends BaseApiController
{
    /** Get Package */
    public function getPackage(Request $request)
    {
        $validator = Validator::make($request->toArray(), [
            'category_id' => 'nullable',
            'platform'    => 'nullable|in:android,ios',
            'type'        => 'nullable|in:advertisement,item_listing',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            if ($request->filled('category_id')) {
                $categoryExists = Category::where(['id' => $request->category_id, 'status' => 1])->exists();
                if (!$categoryExists) {
                    ResponseService::errorResponse(__('Category not found.'));
                }
            }

            $packages = Package::with(['translations', 'categories', 'package_categories'])
                ->where('status', 1);

            if ($request->filled('category_id')) {
                $categoryIdsToMatch = [(int) $request->category_id];
                $currentCatId = $request->category_id;

                while ($currentCatId) {
                    $parentId = Category::without('translations')
                        ->where('id', $currentCatId)
                        ->value('parent_category_id');
                    if ($parentId) {
                        $categoryIdsToMatch[] = (int) $parentId;
                        $currentCatId = $parentId;
                    } else {
                        $currentCatId = null;
                    }
                }

                $packages->where(function ($query) use ($categoryIdsToMatch) {
                    $query->whereHas('package_categories', function ($q) use ($categoryIdsToMatch) {
                        $q->whereIn('category_id', $categoryIdsToMatch);
                    });
                });
            }else{
                $packages->where('is_global',1);
            }

            if ($request->platform === 'ios') {
                $packages->whereNotNull('ios_product_id');
            }

            if ($request->filled('type')) {
                $packages->where('type', $request->type);
            }

            if(Auth::check()){
                $packages = $packages->with(['user_purchased_packages' => function($query){
                    $query->onlyActive();
                }])->orderBy('id', 'ASC')->get();
            }else{
                $packages = $packages->orderBy('id', 'ASC')->get();
            }

            $formatter  = app(CurrencyFormatterService::class);
            $iso_code   = Setting::where('name', 'currency_iso_code')->value('value');
            $symbol     = Setting::where('name', 'currency_symbol')->value('value');
            $position   = Setting::where('name', 'currency_symbol_position')->value('value');

            $currency = (object) [
                'iso_code'        => $iso_code,
                'symbol'          => $symbol,
                'symbol_position' => $position,
            ];

            $packages = $packages->map(function ($package) use ($formatter, $currency) {

                // Category data (only for non-global packages)
                if ($package->is_global != 1) {
                    $package['selected_category_ids'] = $package->package_categories->pluck('category_id')->toArray();
                    $package['categories'] = $package->categories->map(fn($cat) => [
                        'id'                 => $cat->id,
                        'name'               => $cat->name,
                        'slug'               => $cat->slug ?? null,
                        'parent_category_id' => $cat->parent_category_id ?? null,
                    ]);
                } else {
                    $package['selected_category_ids'] = [];
                    $package['categories']            = [];
                }

                // key_points
                if (!empty($package->key_points)) {
                    $keyPoints = json_decode($package->key_points, true);
                    $package['key_points'] = (json_last_error() === JSON_ERROR_NONE && is_array($keyPoints))
                        ? $keyPoints
                        : [];
                } else {
                    $package['key_points'] = [];
                }

                // Formatted prices
                $package['formatted_final_price'] = $formatter->formatPrice($package->final_price ?? 0, $currency);
                $package['formatted_price']       = $formatter->formatPrice($package->price ?? $package->final_price ?? 0, $currency);
                $package['user_purchased_packages'] = Auth::check() ? $package->user_purchased_packages : array();

                // Listing duration fallback
                if (empty($package->listing_duration_type) || $package->listing_duration_type === 'package') {
                    $package['listing_duration_type'] = 'package';
                    $package['listing_duration_days'] = !empty($package->listing_duration_days)
                        ? $package->listing_duration_days
                        : 'unlimited';
                }

                return $package;
            });

            ResponseService::successResponse(__('Data Fetched Successfully'), $packages);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getPackage');
            ResponseService::errorResponse();
        }
    }

    /** Assign Free Package */
    public function assignFreePackage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'package_id' => 'required|exists:packages,id',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();

            $package = Package::where(['final_price' => 0, 'id' => $request->package_id, 'status' => 1])->firstOrFail();
            if(collect($package)->isEmpty()) {
                ResponseService::errorResponse(__('Package not found'));
            }
            $activePackage = UserPurchasedPackage::onlyActive()->where(['package_id' => $request->package_id, 'user_id' => Auth::user()->id])->first();
            if (! empty($activePackage)) {
                ResponseService::errorResponse(__('You already have purchased this package'));
            }

            $paymentTransactionData = PaymentTransaction::create([
                'user_id' => $user->id,
                'package_id' => $request->package_id,
                'amount' => 0,
                'original_price' => $package->price,
                'discount_price' => $package->price - $package->final_price,
                'payment_gateway' => 'Free',
                'payment_status' => 'succeed',
                'order_id'=> Str::random(10)
            ]);

            UserPurchasedPackage::create([
                'user_id' => $user->id,
                'package_id' => $request->package_id,
                'start_date' => Carbon::now(),
                'total_limit' => $package->item_limit == 'unlimited' ? null : $package->item_limit,
                'end_date' => $package->duration == 'unlimited' ? null : Carbon::now()->addDays($package->duration),
                'listing_duration_type' => $package->listing_duration_type,
                'listing_duration_days' => $package->listing_duration_days
            ]);

            // Send Notifiation to user for free package assigned
            $title = "Package Assigned";
            $body = 'Free Package hase been assigned to your account successfully.';
            if (!empty($user->id)) {
                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    $title,
                    $body,
                    'payment',
                    ['id' => $paymentTransactionData->id],
                    false,
                    array($user->id),
                    true
                );
            }
            ResponseService::successResponse(__('Package Purchased Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> assignFreePackage');
            ResponseService::errorResponse();
        }
    }

    /** App Payment Status */
    public function appPaymentStatus(Request $request)
    {
        try {
            $paypalInfo = $request->all();
            if (! empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == 'completed') {
                ResponseService::successResponse(__('Your Package will be activated within 10 Minutes'), $paypalInfo['txn_id']);
            } elseif (! empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == 'authorized') {
                ResponseService::successResponse(__('Your Transaction is Completed. Ads wil be credited to your account within 30 minutes.'), $paypalInfo);
            } else {
                ResponseService::errorResponse(__('Payment Cancelled / Declined'), (isset($_GET)) ? $paypalInfo : '');
            }
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> appPaymentStatus');
            ResponseService::errorResponse();
        }
    }

    /** Get User Purchased Packages */
    public function getUserPurchasedPackages(Request $request)
    {
        $validator = Validator::make($request->toArray(), [
            'type' => 'nullable|in:advertisement,item_listing',
            'category_id' => 'nullable|exists:categories,id'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $packages = Package::with(['translations', 'categories', 'package_categories'])
                ->where('status', 1)
                ->with(['user_purchased_packages' => fn($q) => $q->onlyActive()])
                ->withCount(['user_purchased_packages as is_purchased_before_count' => fn($q) => $q->where('user_id', Auth::id())])
                ->whereHas('user_purchased_packages', fn($q) => $q->onlyActive());

            if ($request->filled('type')) {
                $packages->where('type', $request->type);
            }

            if ($request->filled('category_id')) {
                $categoryIdsToMatch = [(int) $request->category_id];
                $currentCatId = $request->category_id;

                while ($currentCatId) {
                    $parentId = Category::without('translations')
                        ->where('id', $currentCatId)
                        ->value('parent_category_id');
                    if ($parentId) {
                        $categoryIdsToMatch[] = (int) $parentId;
                        $currentCatId = $parentId;
                    } else {
                        $currentCatId = null;
                    }
                }

                $packages->where(function ($query) use ($categoryIdsToMatch) {
                    $query->whereHas('package_categories', function ($q) use ($categoryIdsToMatch) {
                        $q->whereIn('category_id', $categoryIdsToMatch);
                    })->orWhere('is_global',1);
                });
            }

            $packages = $packages->orderBy('id', 'ASC')->get();

            $formatter  = app(CurrencyFormatterService::class);
            $iso_code   = Setting::where('name', 'currency_iso_code')->value('value');
            $symbol     = Setting::where('name', 'currency_symbol')->value('value');
            $position   = Setting::where('name', 'currency_symbol_position')->value('value');

            $currency = (object) [
                'iso_code'        => $iso_code,
                'symbol'          => $symbol,
                'symbol_position' => $position,
            ];

            $packages = $packages->map(function ($package) use ($formatter, $currency) {

                $package->is_active           = count($package->user_purchased_packages) > 0;
                $package->is_purchased_before = ($package->is_purchased_before_count ?? 0) > 0;

                // Category data
                if ($package->is_global != 1) {
                    $package['selected_category_ids'] = $package->package_categories->pluck('category_id')->toArray();
                    $package['categories'] = $package->categories->map(fn($cat) => [
                        'id'                 => $cat->id,
                        'name'               => $cat->name,
                        'slug'               => $cat->slug ?? null,
                        'parent_category_id' => $cat->parent_category_id ?? null,
                    ]);
                } else {
                    $package['selected_category_ids'] = [];
                    $package['categories']            = [];
                }

                // key_points
                if (!empty($package->key_points)) {
                    $keyPoints = json_decode($package->key_points, true);
                    $package['key_points'] = (json_last_error() === JSON_ERROR_NONE && is_array($keyPoints))
                        ? $keyPoints
                        : [];
                } else {
                    $package['key_points'] = [];
                }

                // Formatted prices
                $package['formatted_final_price'] = $formatter->formatPrice($package->final_price ?? 0, $currency);
                $package['formatted_price']       = $formatter->formatPrice($package->price ?? $package->final_price ?? 0, $currency);

                // Listing duration fallback
                if (empty($package->listing_duration_type) || $package->listing_duration_type === 'package') {
                    $package['listing_duration_type'] = 'package';
                    $package['listing_duration_days'] = !empty($package->listing_duration_days)
                        ? $package->listing_duration_days
                        : 'unlimited';
                }

                // Purchased package details
                $package->user_purchased_packages = $package->user_purchased_packages->map(function ($purchased) use ($package) {

                    if ($purchased->start_date && $purchased->end_date) {
                        $purchased['duration'] = Carbon::parse($purchased->start_date)
                            ->diffInDays(Carbon::parse($purchased->end_date));
                    } else {
                        $purchased['duration'] = 'unlimited';
                    }

                    $purchased['item_limit']            = $purchased->total_limit;
                    $purchased['listing_duration_type'] = $purchased->listing_duration_type
                        ?? $package->listing_duration_type
                        ?? 'package';

                    $days = $purchased->listing_duration_days ?? $package->listing_duration_days ?? $package->duration;
                    $purchased['listing_duration_days'] = ($days == 0 && $days !== null) ? 'unlimited' : $days;

                    return $purchased;
                });

                return $package;
            });

            ResponseService::successResponse(__('Data Fetched Successfully'), $packages);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getUserPurchasedPackages');
            ResponseService::errorResponse();
        }
    }
}
