<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BannerItem;
use App\Models\Category;
use App\Models\Item;
use App\Services\BootstrapTableService;
use App\Services\FileService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BannerAdController extends Controller
{
    private string $uploadFolder = 'banner-ads';

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['banner-ad-list', 'banner-ad-create', 'banner-ad-update', 'banner-ad-delete']);

        return view('banner-ads.index');
    }

    /**
     * Datatable feed for the Banner Ads list.
     */
    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('banner-ad-list');

        $offset = $request->input('offset', 0);
        $limit  = $request->input('limit', 10);
        // Newest banner first by default.
        $sort   = $request->input('sort', 'id');
        $order  = $request->input('order', 'DESC');

        $sql = Banner::with('bannerItems');

        if (! empty($request->search)) {
            $sql = $sql->search($request->search);
        }

        // Column filters (Platform / Page / Layout / Ad Type)
        foreach (['platform', 'page', 'layout'] as $filter) {
            if ($request->filled($filter)) {
                $sql->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('ad_type')) {
            $adType = $request->input('ad_type');
            $sql->whereHas('bannerItems', fn($q) => $q->where('ad_type', $adType));
        }

        $total = $sql->count();

        $result = $sql->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($result as $row) {
            $tempRow = $row->toArray();

            $tempRow['platform_label'] = $row->platform_label;
            $tempRow['page_label']     = $row->page_label;
            $tempRow['layout_label']   = $row->layout_label;
            $tempRow['ad_type_label']  = $row->ad_type_label;
            $tempRow['images']         = $row->bannerItems->pluck('image')->toArray();

            $operate = '';
            if (Auth::user()->can('banner-ad-update')) {
                $operate .= BootstrapTableService::editButton(route('banner-ads.edit', $row->id));
            }
            if (Auth::user()->can('banner-ad-delete')) {
                $operate .= BootstrapTableService::deleteButton(route('banner-ads.destroy', $row->id));
            }
            $tempRow['operate'] = $operate;

            $rows[] = $tempRow;
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('banner-ad-create');

        return view('banner-ads.create', [
            'categories' => Category::where('status', 1)->get(),
            'items'      => Item::where('status', 'approved')->select('id', 'name')->get(),
            'sections'   => $this->placementSections(),
        ]);
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('banner-ad-create');

        $validator = Validator::make($this->validationData($request), $this->rules(true));
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $banner = Banner::create([
                'platform' => $request->platform,
                'page'     => $request->page,
                'layout'   => $request->layout,
                'sequence' => $request->input('sequence', Banner::max('sequence') + 1),
                'status'   => 1,
            ]);

            foreach ($this->normalisedBanners($request) as $position => $data) {
                BannerItem::create([
                    'banner_id'     => $banner->id,
                    'image'         => FileService::compressAndUpload($data['image'], $this->uploadFolder),
                    'ad_type'       => $data['ad_type'],
                    'category_id'   => $data['ad_type'] === 'category' ? $data['category_id'] : null,
                    'item_id'       => $data['ad_type'] === 'advertisement' ? $data['item_id'] : null,
                    'external_link' => $data['ad_type'] === 'external_link' ? $data['external_link'] : null,
                    'position'      => $position + 1,
                ]);
            }

            DB::commit();
            ResponseService::successResponse('Banner Ad created successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'BannerAdController -> store');
            ResponseService::errorResponse();
        }
    }

    public function edit($id)
    {
        ResponseService::noPermissionThenRedirect('banner-ad-update');

        return view('banner-ads.edit', [
            'banner'     => Banner::with('bannerItems')->findOrFail($id),
            'categories' => Category::where('status', 1)->get(),
            'items'      => Item::where('status', 'approved')->select('id', 'name')->get(),
            'sections'   => $this->placementSections(),
        ]);
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('banner-ad-update');

        $banner = Banner::with('bannerItems')->findOrFail($id);

        // On edit the image is optional — the existing one is kept when no new file is sent.
        $validator = Validator::make($this->validationData($request), $this->rules(false));
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $banner->update([
                'platform' => $request->platform,
                'page'     => $request->page,
                'layout'   => $request->layout,
                'sequence' => $request->input('sequence', $banner->sequence),
                'status'   => $request->input('status', $banner->status),
            ]);

            $existing = $banner->bannerItems->keyBy('position');
            $keptIds  = [];

            foreach ($this->normalisedBanners($request) as $index => $data) {
                $position = $index + 1;
                $current  = $existing->get($position);

                $image = $current?->getRawOriginal('image');
                if (! empty($data['image'])) {
                    $image = FileService::compressAndUpload($data['image'], $this->uploadFolder);
                    if ($current) {
                        Storage::disk('public')->delete($current->getRawOriginal('image'));
                    }
                }

                if (empty($image)) {
                    DB::rollBack();
                    ResponseService::validationError("Banner {$position} image is required.");

                    return;
                }

                $payload = [
                    'banner_id'     => $banner->id,
                    'image'         => $image,
                    'ad_type'       => $data['ad_type'],
                    'category_id'   => $data['ad_type'] === 'category' ? $data['category_id'] : null,
                    'item_id'       => $data['ad_type'] === 'advertisement' ? $data['item_id'] : null,
                    'external_link' => $data['ad_type'] === 'external_link' ? $data['external_link'] : null,
                    'position'      => $position,
                ];

                if ($current) {
                    $current->update($payload);
                    $keptIds[] = $current->id;
                } else {
                    $keptIds[] = BannerItem::create($payload)->id;
                }
            }

            // Switching Dual -> Single leaves an orphan second image; drop it.
            foreach ($banner->bannerItems as $item) {
                if (! in_array($item->id, $keptIds, true)) {
                    Storage::disk('public')->delete($item->getRawOriginal('image'));
                    $item->delete();
                }
            }

            DB::commit();
            ResponseService::successResponse('Banner Ad updated successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'BannerAdController -> update');
            ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('banner-ad-delete');

        try {
            $banner = Banner::with('bannerItems')->findOrFail($id);

            foreach ($banner->bannerItems as $item) {
                Storage::disk('public')->delete($item->getRawOriginal('image'));
            }

            $banner->delete(); // banner_items cascade
            ResponseService::successResponse('Banner Ad deleted successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'BannerAdController -> destroy');
            ResponseService::errorResponse();
        }
    }

    /**
     * Validation rules. Images are required on create, optional on update.
     */
    private function rules(bool $imageRequired): array
    {
        $image = ($imageRequired ? 'required' : 'nullable') . '|image|mimes:jpg,jpeg,png|max:8192';

        return [
            'platform' => 'required|in:' . implode(',', Banner::PLATFORMS),
            'page'     => 'required|in:' . implode(',', Banner::PAGES),
            'layout'   => 'required|in:' . implode(',', Banner::LAYOUTS),
            'sequence' => 'nullable|integer|min:0',

            'banners'                 => 'required|array|min:1|max:2',
            'banners.*.image'         => $image,
            'banners.*.ad_type'       => 'required|in:' . implode(',', BannerItem::AD_TYPES),
            'banners.*.category_id'   => 'required_if:banners.*.ad_type,category|nullable|exists:categories,id',
            'banners.*.item_id'       => 'required_if:banners.*.ad_type,advertisement|nullable|exists:items,id',
            'banners.*.external_link' => 'required_if:banners.*.ad_type,external_link|nullable|url|max:191',
        ];
    }

    /**
     * Keep only the banner slots the chosen layout uses (2 for dual, 1 for single),
     * pairing each with its uploaded file.
     *
     * The form always renders both slots, so the unused one still posts its ad_type
     * — plus any file left over from switching dual -> single. Trimming here means
     * validation never demands an image for a slot outside the chosen layout.
     */
    private function normalisedBanners(Request $request): array
    {
        $expected = $request->input('layout') === 'dual' ? 2 : 1;

        $banners = array_slice(array_values((array) $request->input('banners', [])), 0, $expected);
        $files   = array_values((array) $request->file('banners', []));

        foreach ($banners as $i => &$banner) {
            $banner['image'] = $files[$i]['image'] ?? null;
        }

        return $banners;
    }

    /**
     * Validation payload built by hand — mutating the Request is unreliable because
     * Laravel caches its converted files the first time they are read.
     */
    private function validationData(Request $request): array
    {
        return [
            'platform' => $request->input('platform'),
            'page'     => $request->input('page'),
            'layout'   => $request->input('layout'),
            'sequence' => $request->input('sequence'),
            'banners'  => $this->normalisedBanners($request),
        ];
    }

    /**
     * Sections shown in the "Banner Placement" step. `Banner Ad (New)` is the
     * draggable one; the rest are the fixed homepage sections it sits among.
     */
    private function placementSections(): array
    {
        $featureTitles = DB::table('feature_sections')->orderBy('sequence')->pluck('title')->filter()->values();

        $sections = [];
        foreach (DB::table('home_screen_sections')->where('is_active', 1)->orderBy('sequence')->pluck('section_type') as $type) {
            if ($type === 'featured_section') {
                // A featured_section row stands for every configured feature section.
                foreach ($featureTitles as $title) {
                    $sections[] = $title;
                }
                continue;
            }

            $sections[] = ucwords(str_replace('_', ' ', $type));
        }

        // The banner is the only draggable entry; it starts right after the slider.
        $sliderIndex = array_search('Slider', $sections, true);
        $insertAt    = $sliderIndex === false ? 0 : $sliderIndex + 1;
        array_splice($sections, $insertAt, 0, ['Banner Ad (New)']);

        $sections[] = 'All Advertisement';

        return $sections;
    }
}
