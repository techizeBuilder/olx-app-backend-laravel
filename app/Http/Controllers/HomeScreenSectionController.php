<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomeScreenSection;
use App\Models\PopularCategory;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Throwable;

class HomeScreenSectionController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['home-screen-section-list', 'home-screen-section-update']);

        $sections = HomeScreenSection::orderBy('sequence')->get();
        $popularCategories = PopularCategory::with('category')->orderBy('sequence')->get();
        $categories = Category::whereNotIn('id', $popularCategories->pluck('category_id'))->get();

        return view('home_screen_section.index', compact('sections', 'popularCategories', 'categories'));
    }

    public function toggleSection(Request $request)
    {
        ResponseService::noPermissionThenSendJson('home-screen-section-update');

        try {
            $request->validate([
                'id' => 'required|exists:home_screen_sections,id',
                'is_active' => 'required|boolean',
            ]);

            HomeScreenSection::where('id', $request->id)->update([
                'is_active' => $request->is_active,
            ]);

            ResponseService::successResponse('Section Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function storePopularCategory(Request $request)
    {
        ResponseService::noPermissionThenSendJson('home-screen-section-update');

        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id|unique:popular_categories,category_id',
            ]);

            $maxSequence = PopularCategory::max('sequence') ?? 0;

            PopularCategory::create([
                'category_id' => $request->category_id,
                'sequence' => $maxSequence + 1,
            ]);

            ResponseService::successResponse('Category Added Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function deletePopularCategory($id)
    {
        ResponseService::noPermissionThenSendJson('home-screen-section-update');

        try {
            PopularCategory::findOrFail($id)->delete();
            ResponseService::successResponse('Category Removed Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function popularCategoriesOrder()
    {
        ResponseService::noAnyPermissionThenRedirect(['home-screen-section-list', 'home-screen-section-update']);

        $popularCategories = PopularCategory::with('category')->orderBy('sequence')->get();

        return view('home_screen_section.popular-categories-order', compact('popularCategories'));
    }

    public function updatePopularCategoryOrder(Request $request)
    {
        ResponseService::noPermissionThenSendJson('home-screen-section-update');

        $request->validate([
            'order' => 'required',
        ]);

        try {
            $order = json_decode($request->input('order'), true);
            $data = [];
            foreach ($order as $index => $id) {
                $data[] = [
                    'id' => $id,
                    'sequence' => $index + 1,
                ];
            }
            PopularCategory::upsert($data, ['id'], ['sequence']);
            ResponseService::successResponse('Order Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }
}
