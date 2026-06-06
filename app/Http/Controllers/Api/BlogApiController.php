<?php

namespace App\Http\Controllers\Api;

use App\Models\Blog;
use App\Models\Language;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Blog */
class BlogApiController extends BaseApiController
{
    /** Get Blogs */
    public function getBlog(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'nullable|integer|exists:categories,id',
                'blog_id' => 'nullable|integer|exists:blogs,id',
                'sort_by' => 'nullable|in:new-to-old,old-to-new,popular',
                'views' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            if ($request->views == 1) {
                if (! empty($request->id)) {
                    Blog::where('blogs.id', $request->id)->increment('views');
                } elseif (! empty($request->slug)) {
                    Blog::where('slug', $request->slug)->increment('views');
                } else {
                    return ResponseService::errorResponse(__('ID or Slug is required to increment views'));
                }
            }
            $blogs = Blog::with('translations', 'seoDetail.translations')->when(! empty($request->id), static function ($q) use ($request) {
                $q->where('blogs.id', $request->id);
                Blog::where('blogs.id', $request->id);
            })
                ->when(! empty($request->slug), function ($q) use ($request) {
                    $q->where('slug', $request->slug);
                    Blog::where('slug', $request->slug);
                })
                ->when(! empty($request->sort_by), function ($q) use ($request) {
                    if ($request->sort_by === 'new-to-old') {
                        $q->orderByDesc('created_at');
                    } elseif ($request->sort_by === 'old-to-new') {
                        $q->orderBy('created_at');
                    } elseif ($request->sort_by === 'popular') {
                        $q->orderByDesc('views');
                    }
                })
                ->when(! empty($request->tag), function ($q) use ($request) {
                    $q->where(function ($query) use ($request) {
                        $query->where('tags', 'like', '%' . $request->tag . '%')
                            ->orWhereHas('translations', function ($translationQuery) use ($request) {
                                $translationQuery->where('key', 'tags')->where('value', 'like', '%' . $request->tag . '%');
                            });
                    });
                })
                ->paginate();

            $otherBlogs = [];
            if (! empty($request->id) || ! empty($request->slug)) {
                $otherBlogs = Blog::with('translations', 'seoDetail.translations')
                    ->when(! empty($request->id), function ($q) use ($request) {
                        $q->where('blogs.id', '!=', $request->id);
                    })
                    ->when(! empty($request->slug), function ($q) use ($request) {
                        $q->where('slug', '!=', $request->slug);
                    })
                    ->orderByDesc('id')
                    ->limit(3)
                    ->get();
            }

            ResponseService::successResponse(__('Blogs fetched successfully'), $blogs, ['other_blogs' => $otherBlogs]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getBlog');
            ResponseService::errorResponse(__('Failed to fetch blogs'));
        }
    }

    /** Get All Blog Tags */
    public function getAllBlogTags()
    {
        try {
            $languageCode = request()->header('Content-Language') ?? app()->getLocale();

            $language = Language::select(['id', 'code', 'name'])
                ->where('code', $languageCode)
                ->first();

            if (! $language) {
                return ResponseService::errorResponse('Invalid language code');
            }

            $tagsMap = [];

            Blog::with(['translations' => function ($q) use ($language) {
                $q->where('language_id', $language->id);
            }])->chunk(100, function ($blogs) use (&$tagsMap) {
                foreach ($blogs as $blog) {
                    $defaultTagsRaw = $blog->tags;
                    $defaultTags = [];
                    if (! empty($defaultTagsRaw)) {
                        if (is_string($defaultTagsRaw)) {
                            $decoded = json_decode($defaultTagsRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && ! empty($decoded)) {
                                $defaultTags = is_array($decoded) ? $decoded : [$decoded];
                            } else {
                                $defaultTags = array_map('trim', explode(',', $defaultTagsRaw));
                            }
                        } elseif (is_array($defaultTagsRaw)) {
                            $defaultTags = $defaultTagsRaw;
                        }
                    }
                    $translatedTagsRaw = $blog->translations->where('key', 'tags')->first()?->value;
                    $translatedTags = [];
                    if (! empty($translatedTagsRaw)) {
                        if (is_string($translatedTagsRaw)) {
                            $decoded = json_decode($translatedTagsRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && ! empty($decoded)) {
                                $translatedTags = is_array($decoded) ? $decoded : [$decoded];
                            } else {
                                $translatedTags = array_map('trim', explode(',', $translatedTagsRaw));
                            }
                        } elseif (is_array($translatedTagsRaw)) {
                            $translatedTags = $translatedTagsRaw;
                        }
                    }
                    foreach ($defaultTags as $index => $defaultTag) {
                        $translated = $translatedTags[$index] ?? $defaultTag;
                        $tagsMap[$defaultTag] = $translated;
                    }
                }
            });
            $result = [];
            foreach ($tagsMap as $defaultTag => $translatedTag) {
                $result[] = [
                    'label' => $translatedTag,
                    'value' => $defaultTag,
                ];
            }

            ResponseService::successResponse('Blog Tags Retrieved Successfully', array_values($result));
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getAllBlogTags');

            return ResponseService::errorResponse('Failed to fetch Tags');
        }
    }

    /** Get Blogs Slug */
    public function getBlogsSlug(Request $request)
    {
        try {
            $blogs = Blog::without('translations')
                ->select('id', 'slug', 'updated_at')
                ->get()
                ->each->setAppends([]);

            if ($blogs->isEmpty()) {
                return ResponseService::errorResponse(__('No active Blogs found.'));
            }

            return ResponseService::successResponse(__('Active Blogs slugs fetched successfully.'), $blogs);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategoriesSlug');
            ResponseService::errorResponse();
        }
    }
}
