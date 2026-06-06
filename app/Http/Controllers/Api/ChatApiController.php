<?php

namespace App\Http\Controllers\Api;

use App\Models\BlockUser;
use App\Models\Chat;
use App\Models\Item;
use App\Models\ItemOffer;
use App\Services\CurrencyFormatterService;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Chat */
class ChatApiController extends BaseApiController
{
    private function getOtherUserRelation(string $type): string
    {
        return $type === 'seller' ? 'buyer' : 'seller';
    }

    /** Create Item Offer */
    public function createItemOffer(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'amount' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $item = Item::approved()->notOwner()->find($request->item_id);
            if(empty($item)){
                ResponseService::validationError(__('No Item Found'));
            }
            $itemOffer = ItemOffer::firstOrNew([
                'item_id' => $request->item_id,
                'buyer_id' => Auth::user()->id,
                'seller_id' => $item->user_id,
            ]);

            $itemOffer->deleted_by_seller_at = null;
            $itemOffer->deleted_by_buyer_at = null;

            if (!$itemOffer->exists && $request->has('amount')) {
                $itemOffer->amount = $request->amount;
            }

            $itemOffer->save();

            $itemOffer = $itemOffer->load('seller:id,name,profile', 'buyer:id,name,profile', 'item:id,name,description,price,currency_id', 'item.currency');
            $formatter = app(currencyFormatterService::class);
            $offerCurrency = $itemOffer->item?->currency;
            $formattedOfferAmount = $formatter->formatPrice($itemOffer->amount, $offerCurrency);
            $formattedItemPrice = $formatter->formatPrice($item->price, $offerCurrency);

            $fcmMsg = [
                'user_id' => $itemOffer->buyer->id,
                'user_name' => $itemOffer->buyer->name,
                'user_profile' => $itemOffer->buyer->profile,
                'user_type' => 'Buyer',
                'item_id' => $itemOffer->item->id,
                'item_name' => $itemOffer->item->name,
                'item_image' => $itemOffer->item->image,
                'item_price' => $itemOffer->item->price,
                'item_offer_id' => $itemOffer->id,
                'item_offer_amount' => $itemOffer->amount,
                'item_formatted_amount' => $formattedOfferAmount,
                'item_formatted_price' => $formattedItemPrice
            ];
            unset($fcmMsg['message_type']);
            if ($request->has('amount') && $request->amount != 0) {
                $message = 'new offer is created by buyer';
                NotificationService::dispatchChunkedNotifications(
                    'New Offer',
                    $message,
                    'offer',
                    $fcmMsg,
                    false,
                    array($item->user->id)
                );
            }

            // Add Formatted Item offer amount and item price
            $itemOffer->item_offer_formatted_amount = $formattedOfferAmount;
            $itemOffer->item_formatted_price = $formattedItemPrice;
            ResponseService::successResponse(__('Advertisement Offer Created Successfully'), $itemOffer);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> createItemOffer');
            ResponseService::errorResponse();
        }
    }

    /** Get Item Offer List */
    public function getItemOfferList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:seller,buyer',
            'search' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $authId = Auth::id();

            $baseQuery = Item::query();

            // Apply search filter
            $searchQuery = $request->input('search', '');
            if (!empty($searchQuery)) {
                $baseQuery->where(function ($q) use ($searchQuery, $request) {
                    $q->whereHas('item_offers', function ($subQ) use ($searchQuery, $request) {
                        $subQ->whereHas($this->getOtherUserRelation($request->type), function ($subQ) use ($searchQuery) {
                            $subQ->where('name', 'like', '%' . $searchQuery . '%');
                        });
                    })
                    ->orWhere('id', 'like', '%' . $searchQuery . '%')->orWhere('name', 'like', '%' . $searchQuery . '%');;
                });
            }

            $itemData = $baseQuery->whereHas('item_offers', function($subQuery) use($request, $authId){
                $subQuery->when($request->type === 'seller',
                    fn($q) => $q->where('seller_id', $authId)
                                ->whereNull('deleted_by_seller_at')
                                ->whereHas('buyer'),
                    fn($q) => $q->where('buyer_id', $authId)
                                ->whereNull('deleted_by_buyer_at')
                                ->whereHas('seller')
                );
            })->with(['item_offers' => function($subQuery) use($request, $authId) {
                $subQuery->when($request->type === 'seller',
                    fn($q) => $q->where('seller_id', $authId)
                                ->whereNull('deleted_by_seller_at')
                                ->whereHas('buyer')
                                ->with('buyer:id,name,profile'),
                    fn($q) => $q->where('buyer_id', $authId)
                                ->whereNull('deleted_by_buyer_at')
                                ->whereHas('seller')
                                ->with('seller:id,name,profile')
                )->when($request->type === 'seller',
                    fn($q) => $q->withCount(['sellerChat as unread_chat_count' => function($q) use($authId) {
                                    $q->where('is_read', 0)->where('sender_id', '!=', $authId);
                                }]),
                    fn($q) => $q->withCount(['buyerChat as unread_chat_count' => function($q) use($authId) {
                                    $q->where('is_read', 0)->where('sender_id', '!=', $authId);
                                }])
                )->withMax('chat', 'created_at');
            }])->select('id', 'name', 'price')
                ->orderByDesc(
                    Chat::select('created_at')
                        ->whereIn('item_offer_id',
                            ItemOffer::select('id')
                                ->whereColumn('item_id', 'items.id')
                                ->when($request->type === 'seller',
                                    fn($q) => $q->where('seller_id', $authId)->whereNull('deleted_by_seller_at'),
                                    fn($q) => $q->where('buyer_id', $authId)->whereNull('deleted_by_buyer_at')
                                )
                        )
                        ->latest('created_at')
                        ->limit(1)
                )
                ->paginate();

            $itemData->getCollection()->transform(function($data) use($request) {
                $totalOtherUsers = 0;
                if ($request->type === 'seller') {
                    $otherUserQuery = $data->item_offers->filter(fn($offer) => $offer->buyer !== null);
                    $totalOtherUsers = $otherUserQuery->count();
                    $otherUser = $otherUserQuery
                        ->take(6)
                        ->map(function($offer) {
                            $buyerData = $offer->buyer->toArray();
                            $buyerData['offer_id'] = $offer->id;
                            return $buyerData;
                        })->values();
                } else {
                    $otherUserQuery = $data->item_offers->filter(fn($offer) => $offer->seller !== null);
                    $totalOtherUsers = $otherUserQuery->count();
                    $otherUser = $otherUserQuery
                        ->take(6)
                        ->map(function($offer) {
                            $sellerData = $offer->seller->toArray();
                            $sellerData['offer_id'] = $offer->id;
                            return $sellerData;
                        })->values();
                }
                return [
                    'id'                 => $data->id,
                    'name'               => $data->name,
                    'price'              => $data->price,
                    'image'              => $data->image,
                    'last_offer_updated' => $data->item_offers->max('chat_max_created_at') ?? $data->item_offers->max('updated_at'),
                    'unread_chat_count'  => $data->item_offers->sum('unread_chat_count'),
                    'other_users'        => $otherUser,
                    'total_other_users'  => $totalOtherUsers ?? 0,
                ];
            });

            $filtered = $itemData->getCollection()->filter(fn($item) => $item['other_users']->isNotEmpty())->values();
            $itemData->setCollection($filtered);

            return ResponseService::successResponse(
                __('Item Chat List Fetched Successfully'),
                $itemData
            );

        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, 'API Controller -> getItemChatList');
            return ResponseService::errorResponse('Something went wrong');
        }
    }

    /** Get Chat List */
    public function getChatList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:seller,buyer',
            'item_id' => 'nullable|required_if:type,seller|exists:items,id',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $authId = Auth::id();

            // Blocked By Current User
            $authUserBlockList  = BlockUser::where('user_id', $authId)->pluck('blocked_user_id');
            $otherUserBlockList = BlockUser::where('blocked_user_id', $authId)->pluck('user_id');

            if($request->item_id){
                $baseQuery = ItemOffer::where('item_id', $request->item_id);
            }else{
                $baseQuery = ItemOffer::query();
            }
            
            // Apply search filter
            $searchQuery = $request->input('search', '');
            if (!empty($searchQuery)) {
                $baseQuery->where(function ($q) use ($searchQuery, $request) {
                    $q->whereHas($this->getOtherUserRelation($request->type), function ($subQ) use ($searchQuery) {
                        $subQ->where('name', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhereHas('item', function ($subQ) use ($searchQuery) {
                        $subQ->where('name', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhereHas('chat', function ($subQ) use ($searchQuery) {
                        $subQ->where('message', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhere('id', 'like', '%' . $searchQuery . '%');
                });
            }

            $query = $baseQuery->with([
                'seller:id,name,profile',
                'buyer:id,name,profile',
                'item' => function ($q) {
                    $q->with([
                        'currency:id,iso_code,symbol,symbol_position',
                        'category:id,name,image,is_job_category,price_optional',
                    ]);
                },
                'item.review' => function ($q) use ($authId) {
                    $q->where('buyer_id', $authId);
                },
            ])
            ->whereHas('buyer', fn($q) => $q->whereNull('deleted_at'))
            ->whereHas('seller', fn($q) => $q->whereNull('deleted_at'))
            ->when($request->type === 'seller',
                fn($q) => $q->whereNull('deleted_by_seller_at'),
                fn($q) => $q->whereNull('deleted_by_buyer_at')
            )
            ->when($request->type === 'seller',
                fn($q) => $q->withCount([
                    'sellerChat as unread_chat_count' => function ($q) use ($authId) {
                        $q->where('is_read', 0)
                          ->where('sender_id', '!=', $authId);
                    },
                ]),
                fn($q) => $q->withCount([
                    'buyerChat as unread_chat_count' => function ($q) use ($authId) {
                        $q->where('is_read', 0)
                          ->where('sender_id', '!=', $authId);
                    },
                ])
            );

            if ($request->type === 'seller') {
                $query->where('seller_id', $authId);
            } else {
                $query->where('buyer_id', $authId);
            }

            $clearedColumn = $request->type === 'seller' ? 'cleared_by_seller_at' : 'cleared_by_buyer_at';

            $totalUnreadChatCount = (clone $query)->get()->sum('unread_chat_count');

            $itemOffers = $query
                ->addSelect([
                    'last_chat_time' => Chat::selectRaw('COALESCE(MAX(chats.created_at), item_offers.updated_at)')
                        ->whereColumn('item_offer_id', 'item_offers.id')
                        ->where(function ($q) use ($clearedColumn) {
                            $q->whereColumn('chats.created_at', '>', 'item_offers.' . $clearedColumn)
                              ->orWhereNull('item_offers.' . $clearedColumn);
                        })
                        ->limit(1)
                ])
                ->addSelect([
                    'last_chat_message' => Chat::select('message')
                        ->whereColumn('item_offer_id', 'item_offers.id')
                        ->where(function ($q) use ($clearedColumn) {
                            $q->whereColumn('chats.created_at', '>', 'item_offers.' . $clearedColumn)
                              ->orWhereNull('item_offers.' . $clearedColumn);
                        })
                        ->latest()
                        ->limit(1)
                ])
                ->orderByRaw('CASE WHEN unread_chat_count > 0 THEN 0 ELSE 1 END')
                ->orderByDesc('last_chat_time')
                ->orderByDesc('id')
                ->paginate();

            $formatter = app(CurrencyFormatterService::class);

            $itemOffers->getCollection()->transform(function ($offer) use (
                $request,
                $authId,
                $authUserBlockList,
                $otherUserBlockList,
                $formatter
            ) {
                $userBlocked = $request->type === 'seller'
                    ? $authUserBlockList->contains($offer->buyer_id) || $otherUserBlockList->contains($offer->seller_id)
                    : $authUserBlockList->contains($offer->seller_id) || $otherUserBlockList->contains($offer->buyer_id);

                $isMyUserBlockedByOthers = $request->type === 'seller'
                    ? $otherUserBlockList->contains($offer->buyer_id) || $authUserBlockList->contains($offer->seller_id)
                    : $otherUserBlockList->contains($offer->seller_id) || $authUserBlockList->contains($offer->buyer_id);

                $offer->user_blocked = $userBlocked;
                $offer->is_my_user_blocked = $isMyUserBlockedByOthers;

                $lastChatTime = $offer->last_chat_time ?? $offer->updated_at;
                $offer->last_message_time = $lastChatTime ? Carbon::parse($lastChatTime) : null;
                unset($offer->last_chat_time);

                if ($offer->item) {
                    $item = $offer->item;
                    $currency = $item->currency;

                    $item->is_purchased = $item->sold_to == $authId ? 1 : 0;
                    $item->setRelation('review', optional($item->review)->first());

                    $item->formatted_price = $formatter->formatPrice($item->price, $currency);
                    $item->formatted_min_salary = $formatter->formatPrice($item->min_salary, $currency);
                    $item->formatted_max_salary = $formatter->formatPrice($item->max_salary, $currency);
                    $item->formatted_salary_range = $formatter->formatSalaryRange(
                        $item->min_salary,
                        $item->max_salary,
                        $currency
                    );
                }

                $offerCurrency = $offer->item?->currency;
                $offer->formatted_amount = $formatter->formatPrice($offer->amount, $offerCurrency);

                unset($offer->chat);

                return $offer;
            });

            return ResponseService::successResponse(
                __('Chat List Fetched Successfully'),
                $itemOffers,
                ['total_unread_chat_count' => $totalUnreadChatCount]
            );

        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getChatList');
            return ResponseService::errorResponse('Something went wrong');
        }
    }

    /** Send Message */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_offer_id' => 'required|integer',
            'message' => (! $request->file('file') && ! $request->file('audio')) ? 'required' : 'nullable',
            'file' => 'nullable|mimes:jpg,jpeg,png|max:7168',
            'audio' => 'nullable|mimetypes:audio/mpeg,audio/ogg,video/mp4,audio/x-wav,text/plain|max:7168',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $authUserBlockList = BlockUser::where('user_id', $user->id)->get();
            $otherUserBlockList = BlockUser::where('blocked_user_id', $user->id)->get();

            $itemOffer = ItemOffer::with('item')->findOrFail($request->item_offer_id);
            if ($itemOffer->seller_id == $user->id) {
                $blockStatus = $authUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->seller_id && $data->blocked_user_id == $itemOffer->buyer_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because You have blocked this user'),array('key' => 'blocked_by_user'));
                }

                $blockStatus = $otherUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->buyer_id && $data->blocked_user_id == $itemOffer->seller_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'),array('key' => 'blocked_by_other_user'));
                }
            } else {
                $blockStatus = $authUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->buyer_id && $data->blocked_user_id == $itemOffer->seller_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because You have blocked this user'),array('key' => 'blocked_by_user'));
                }

                $blockStatus = $otherUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->seller_id && $data->blocked_user_id == $itemOffer->buyer_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'),array('key' => 'blocked_by_other_user'));
                }
            }
            if ($itemOffer->deleted_by_seller_at || $itemOffer->deleted_by_buyer_at) {
                $itemOffer->update([
                    'deleted_by_seller_at' => null,
                    'deleted_by_buyer_at' => null,
                ]);
            }

            $chat = Chat::create([
                'sender_id' => Auth::user()->id,
                'item_offer_id' => $request->item_offer_id,
                'message' => $request->message,
                'file' => $request->hasFile('file') ? FileService::compressAndUpload($request->file('file'), 'chat') : '',
                'audio' => $request->hasFile('audio') ? FileService::compressAndUpload($request->file('audio'), 'chat') : '',
                'is_read' => 0,
            ]);

            if ($itemOffer->seller_id == $user->id) {
                $receiver_id = $itemOffer->buyer_id;
                $userType = 'Seller';
            } else {
                $receiver_id = $itemOffer->seller_id;
                $userType = 'Buyer';
            }
            $notificationPayload = $chat->toArray();

            $unreadMessagesCount = Chat::where('item_offer_id', $itemOffer->id)
                ->where('is_read', 0)
                ->count();
            $formatter = app(CurrencyFormatterService::class);
            $offerCurrency = $itemOffer->item?->currency;
            $formattedOfferAmount = $formatter->formatPrice($itemOffer->amount, $offerCurrency);
            $formattedItemPrice = $formatter->formatPrice($itemOffer->item->price, $offerCurrency);

            $fcmMsg = [
                ...$notificationPayload,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_profile' => $user->profile,
                'user_type' => $userType,
                'item_id' => $itemOffer->item->id,
                'item_name' => $itemOffer->item->name,
                'item_image' => $itemOffer->item->image,
                'item_price' => $itemOffer->item->price,
                'item_offer_id' => $itemOffer->id,
                'item_offer_amount' => $itemOffer->amount,
                'type' => $notificationPayload['message_type'],
                'message_type_temp' => $notificationPayload['message_type'],
                'unread_count' => $unreadMessagesCount,
                'item_formatted_amount' => $formattedOfferAmount,
                'item_formatted_price' => $formattedItemPrice
            ];
            unset($fcmMsg['message_type']);
            $displayMessage = $request->message;
            if (empty($displayMessage)) {
                if ($request->hasFile('file')) {
                    $mime = $request->file('file')->getMimeType();

                    if (str_contains($mime, 'image')) {
                        $displayMessage = '📷 Sent you an image';
                    } elseif (str_contains($mime, 'pdf')) {
                        $displayMessage = '📄 Sent you a PDF file';
                    } elseif (str_contains($mime, 'word')) {
                        $displayMessage = '📘 Sent you a document';
                    } elseif (str_contains($mime, 'text')) {
                        $displayMessage = '📄 Sent you a text file';
                    } else {
                        $displayMessage = '📎 Sent you a file';
                    }
                } elseif ($request->hasFile('audio')) {
                    $displayMessage = '🎤 Sent you an audio message';
                } else {
                    $displayMessage = '💬 Sent you a message';
                }
            }
            DB::commit();

            $notificationTitle = ($user->name ?? 'User') . ' • ' . ($itemOffer->item->name ?? 'Item');
            NotificationService::dispatchChunkedNotifications(
                $notificationTitle,
                $displayMessage,
                'chat',
                $fcmMsg,
                false,
                array($receiver_id),
                true
            );

            // APP side Request to return its custom client id passed in payload 
            $chat->client_id = $request->client_id ?? null;
            ResponseService::successResponse(__('Message Fetched Successfully'), $chat);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> sendMessage');
            ResponseService::errorResponse();
        }
    }

    /** Get Chat Messages */
    public function getChatMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_offer_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $itemOffer = ItemOffer::owner()->findOrFail($request->item_offer_id);
            $authUserId = Auth::user()->id;

            $clearedAt = null;
            if ($itemOffer->seller_id == $authUserId) {
                $clearedAt = $itemOffer->cleared_by_seller_at;
            } elseif ($itemOffer->buyer_id == $authUserId) {
                $clearedAt = $itemOffer->cleared_by_buyer_at;
            }

            $chat = Chat::where('item_offer_id', $itemOffer->id)
                ->where(function ($query) use ($authUserId) {
                    $query->where('sender_id', '!=', $authUserId)
                        ->orWhere(function ($q) use ($authUserId) {
                            $q->where('sender_id', $authUserId)
                                ->whereNull('deleted_by_sender_at');
                        });
                })
                ->when($clearedAt, function ($query) use ($clearedAt) {
                    $query->where('created_at', '>', $clearedAt);
                })
                ->orderBy('created_at', 'DESC')
                ->paginate();

            Chat::where('item_offer_id', $itemOffer->id)
                ->where('sender_id', '!=', $authUserId)
                ->whereIn('id', $chat->pluck('id'))
                ->update(['is_read' => '1']);

            ResponseService::successResponse(__('Messages Fetched Successfully'), $chat);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getChatMessages');
            ResponseService::errorResponse();
        }
    }

    /** Delete Chat */
    public function deleteChat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_offer_id' => 'required|array',
            'item_offer_id.*' => 'exists:item_offers,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $authUserId = Auth::id();
            $ids = $request->item_offer_id;

            $baseQuery = ItemOffer::owner()->whereIn('id', $ids);

            if (!$baseQuery->exists()) {
                return ResponseService::errorResponse(__('No chat found'));
            }

            // Update seller records
            $baseQuery->clone()
                ->where('seller_id', $authUserId)
                ->update([
                    'deleted_by_seller_at' => now(),
                    'cleared_by_seller_at' => now(),
                ]);

            // Update buyer records
            $baseQuery->clone()
                ->where('buyer_id', $authUserId)
                ->update([
                    'deleted_by_buyer_at' => now(),
                    'cleared_by_buyer_at' => now(),
                ]);

            DB::commit();

            return ResponseService::successResponse(__('Chat Deleted Successfully'));

        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> deleteChat');
            return ResponseService::errorResponse();
        }
    }

    /** Delete Chat Messages */
    public function deleteChatMessages(Request $request)
    {
        try {

            // $messageIds = $request->message_ids;

            // if (is_string($messageIds)) {
            //     $decoded = json_decode($messageIds, true);

            //     if (json_last_error() === JSON_ERROR_NONE) {
            //         $request->merge([
            //             'message_ids' => $decoded
            //         ]);
            //     }
            // }

            $validator = Validator::make($request->all(), [
                'message_ids'   => 'required|array|min:1',
                'message_ids.*' => 'integer|exists:chats,id',
                'item_offer_id' => 'required|integer|exists:item_offers,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            DB::beginTransaction();

            $userId = Auth::id();

            $itemOffer = ItemOffer::where('id', $request->item_offer_id)
                ->where(function ($q) use ($userId) {
                    $q->where('seller_id', $userId)
                        ->orWhere('buyer_id', $userId);
                })
                ->first();

            if (!$itemOffer) {
                return ResponseService::errorResponse(__('Invalid item offer'));
            }

            $messages = Chat::where('item_offer_id', $itemOffer->id)
                ->whereIn('id', $request->message_ids)
                ->where('sender_id', $userId)
                ->get();

            if ($messages->isEmpty()) {
                return ResponseService::errorResponse(__('You can only delete your own messages'));
            }

            $deletedCount = Chat::where('item_offer_id', $itemOffer->id)
                ->whereIn('id', $request->message_ids)
                ->where('sender_id', $userId)
                ->whereNull('deleted_by_sender_at')
                ->update(['deleted_by_sender_at' => now()]);

            DB::commit();

            return ResponseService::successResponse(
                __('Messages Deleted Successfully'),
                [
                    'deleted_count' => $deletedCount,
                    'deleted_ids'   => $messages->pluck('id')->toArray()
                ]
            );

        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> deleteChatMessages');
            return ResponseService::errorResponse(__('Something went wrong'));
        }
    }
}
