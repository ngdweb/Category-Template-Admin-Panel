<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NgendevVideoCategory;
use App\Models\NgendevVideo;
use Illuminate\Http\Request;
use App\Models\AiVideoNgdSetting;

class NgendevVideoApiController extends Controller
{
    public function getAiVideoCategories()
    {
        $ngdAiSetting = AiVideoNgdSetting::first();
        $ngdAiModel = $ngdAiSetting ? $ngdAiSetting->model : null;
        $coupleActive = $ngdAiSetting ? $ngdAiSetting->couple_active : 1;

        $query = NgendevVideoCategory::select('id', 'category_name', 'sort_order', 'type')
            ->where('status', 1);

        if (!$coupleActive) {
            $query->where('type', '!=', 'Couple');
        }

        $categories = $query->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        if ($categories->isEmpty()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'No categories found',
                    'model' => $ngdAiModel,
                    'data' => [],
                ],
                404,
            );
        }

        // Map categories with videos
        $categories = $categories->map(function ($category) {
            $encodedCategory = str_replace(' ', '%20', $category->category_name);

            $videos = NgendevVideo::where('category_id', $category->id)
                ->where('status', 1)
                ->select('id', 'ai_prompt', 'video_thumbnail', 'video_path', 'no_of_video', 'name_change', 'image_hint')
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->slice(-4)
                ->values();

            $videos->transform(function ($video) use ($encodedCategory) {
                $video->video_thumbnail_full_url = $video->video_thumbnail ? asset('upload/ngendev/videos/' . rawurlencode(str_replace('%20', ' ', $encodedCategory)) . '/video_thumbnail/' . rawurlencode($video->video_thumbnail)) : null;
                $video->video_thumbnail = $video->video_thumbnail ? "ngendev/videos/{$encodedCategory}/video_thumbnail/{$video->video_thumbnail}" : null;
                $video->category_video = $video->video_path ? "ngendev/videos/{$encodedCategory}/category_video/{$video->video_path}" : null;
                $video->category_video_full_url = $video->category_video ? asset('upload/ngendev/videos/' . rawurlencode(str_replace('%20', ' ', $encodedCategory)) . '/category_video/' . rawurlencode($video->video_path)) : null;
                $isNameChange = (bool) $video->name_change;
                $hint = $video->image_hint;
                $video->name_change = $isNameChange;
                if ($isNameChange) {
                    $video->image_hint = $hint;
                } else {
                    unset($video->image_hint);
                }
                unset($video->video_path);
                return $video;
            });

            return [
                'category_id' => $category->id,
                'category_name' => $category->category_name,
                'items' => $videos,
            ];
        })->filter(function ($cat) {
            return $cat['items']->isNotEmpty();
        })->values();

        // Separate Exclusive category
        $exclusive = $categories->firstWhere('category_name', 'Exclusive');
        if ($exclusive) {
            $categories = $categories->reject(function ($cat) {
                return $cat['category_name'] === 'Exclusive'; });
        }

        // Separate Trending category
        $trending = $categories->firstWhere('category_name', 'Trending');
        if ($trending) {
            $categories = $categories->reject(function ($cat) {
                return $cat['category_name'] === 'Trending'; });
        }

        // Latest category: last record from all other categories
        $latestVideos = $categories
            ->filter(function ($cat) {
                return $cat['items']->isNotEmpty(); })
            ->map(function ($cat) {
                return $cat['items']->last(); }) // explicitly last record
            ->filter()
            ->values();

        if ($exclusive && $exclusive['items']->isNotEmpty()) {
            $exclusiveLastRecord = $exclusive['items']->last(); // explicitly last record
            $latestVideos->push($exclusiveLastRecord);
        }

        if ($trending && $trending['items']->isNotEmpty()) {
            $trendingLastRecord = $trending['items']->last(); // explicitly last record
            $latestVideos->push($trendingLastRecord);
        }

        $latestCategory = [
            'category_id' => 0,
            'category_name' => 'Latest',
            'items' => $latestVideos,
        ];

        // Reassemble categories: Exclusive -> Trending -> Latest -> Rest
        $sortedCategories = collect([]);

        if ($exclusive) {
            $sortedCategories->push($exclusive);
        }

        if ($trending) {
            $sortedCategories->push($trending);
        }

        $sortedCategories->push($latestCategory);

        // Merge remaining categories
        $sortedCategories = $sortedCategories->merge($categories);

        return response()->json([
            'status' => true,
            'message' => 'Categories fetched successfully',
            'model' => $ngdAiModel,
            'data' => $sortedCategories->values(),
        ]);
    }

    public function getAiVideoByCategoryId(Request $request)
    {
        $data = $request->json()->all();

        $validator = \Validator::make($data, [
            'category_id' => 'required',
        ], [
            'category_id.required' => 'category_id is required',
        ]);

        $ngdAiSetting = AiVideoNgdSetting::first();
        $ngdAiModel = $ngdAiSetting ? $ngdAiSetting->model : null;

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'model' => $ngdAiModel,
                'data' => [],
            ], 422);
        }

        /**
         * ===========================
         * LATEST CATEGORY (ID = 0)
         * ===========================
         */
        if ($data['category_id'] == 0) {

            $coupleActive = $ngdAiSetting ? $ngdAiSetting->couple_active : 1;

            $query = NgendevVideoCategory::where('status', 1);

            if (!$coupleActive) {
                $query->where('type', '!=', 'Couple');
            }

            $categories = $query->orderBy('sort_order', 'asc')
                ->orderBy('id', 'desc')
                ->get();

            // Separate Exclusive and Trending categories
            $exclusiveCategory = $categories->firstWhere('category_name', 'Exclusive');
            $categories = $categories->reject(function ($cat) {
                return $cat->category_name === 'Exclusive'; });

            $trendingCategory = $categories->firstWhere('category_name', 'Trending');
            $categories = $categories->reject(function ($cat) {
                return $cat->category_name === 'Trending'; });

            $latestVideos = collect();

            // Get first video from each remaining category (same order as getAiVideoCategories)
            foreach ($categories as $category) {
                $latestVideo = NgendevVideo::where('category_id', $category->id)
                    ->where('status', 1)
                    ->select('id', 'ai_prompt', 'video_thumbnail', 'video_path', 'category_id', 'no_of_video', 'name_change', 'image_hint')
                    ->orderBy('sort_order', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($latestVideo) {
                    $encodedCategory = str_replace(' ', '%20', $category->category_name);
                    $isNameChange = (bool) $latestVideo->name_change;
                    $itemData = [
                        'id' => $latestVideo->id,
                        'ai_prompt' => $latestVideo->ai_prompt,
                        'no_of_video' => $latestVideo->no_of_video,
                        'name_change' => $isNameChange,
                        'video_thumbnail' => $latestVideo->video_thumbnail
                            ? "ngendev/videos/{$encodedCategory}/video_thumbnail/{$latestVideo->video_thumbnail}"
                            : null,
                        'video_thumbnail_full_url' => $latestVideo->video_thumbnail ? asset('upload/ngendev/videos/' . rawurlencode($category->category_name) . '/video_thumbnail/' . rawurlencode($latestVideo->video_thumbnail)) : null,
                        'category_video' => $latestVideo->video_path
                            ? "ngendev/videos/{$encodedCategory}/category_video/{$latestVideo->video_path}"
                            : null,
                        'category_video_full_url' => $latestVideo->video_path ? asset('upload/ngendev/videos/' . rawurlencode($category->category_name) . '/category_video/' . rawurlencode($latestVideo->video_path)) : null,
                    ];
                    if ($isNameChange) {
                        $itemData['image_hint'] = $latestVideo->image_hint;
                    }
                    $latestVideos->push($itemData);
                }
            }

            // Add Exclusive first record
            if ($exclusiveCategory) {
                $exclusiveVideo = NgendevVideo::where('category_id', $exclusiveCategory->id)
                    ->where('status', 1)
                    ->select('id', 'ai_prompt', 'video_thumbnail', 'video_path', 'category_id', 'no_of_video', 'name_change', 'image_hint')
                    ->orderBy('sort_order', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($exclusiveVideo) {
                    $encodedExclusive = str_replace(' ', '%20', $exclusiveCategory->category_name);
                    $isNameChange = (bool) $exclusiveVideo->name_change;
                    $itemData = [
                        'id' => $exclusiveVideo->id,
                        'ai_prompt' => $exclusiveVideo->ai_prompt,
                        'no_of_video' => $exclusiveVideo->no_of_video,
                        'name_change' => $isNameChange,
                        'video_thumbnail' => $exclusiveVideo->video_thumbnail
                            ? "ngendev/videos/{$encodedExclusive}/video_thumbnail/{$exclusiveVideo->video_thumbnail}"
                            : null,
                        'video_thumbnail_full_url' => $exclusiveVideo->video_thumbnail ? asset('upload/ngendev/videos/' . rawurlencode($exclusiveCategory->category_name) . '/video_thumbnail/' . rawurlencode($exclusiveVideo->video_thumbnail)) : null,
                        'category_video' => $exclusiveVideo->video_path
                            ? "ngendev/videos/{$encodedExclusive}/category_video/{$exclusiveVideo->video_path}"
                            : null,
                        'category_video_full_url' => $exclusiveVideo->video_path ? asset('upload/ngendev/videos/' . rawurlencode($exclusiveCategory->category_name) . '/category_video/' . rawurlencode($exclusiveVideo->video_path)) : null,
                    ];
                    if ($isNameChange) {
                        $itemData['image_hint'] = $exclusiveVideo->image_hint;
                    }
                    $latestVideos->push($itemData);
                }
            }

            // Add Trending first record
            if ($trendingCategory) {
                $trendingVideo = NgendevVideo::where('category_id', $trendingCategory->id)
                    ->where('status', 1)
                    ->select('id', 'ai_prompt', 'video_thumbnail', 'video_path', 'category_id', 'no_of_video', 'name_change', 'image_hint')
                    ->orderBy('sort_order', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($trendingVideo) {
                    $encodedTrending = str_replace(' ', '%20', $trendingCategory->category_name);
                    $isNameChange = (bool) $trendingVideo->name_change;
                    $itemData = [
                        'id' => $trendingVideo->id,
                        'ai_prompt' => $trendingVideo->ai_prompt,
                        'no_of_video' => $trendingVideo->no_of_video,
                        'name_change' => $isNameChange,
                        'video_thumbnail' => $trendingVideo->video_thumbnail
                            ? "ngendev/videos/{$encodedTrending}/video_thumbnail/{$trendingVideo->video_thumbnail}"
                            : null,
                        'video_thumbnail_full_url' => $trendingVideo->video_thumbnail ? asset('upload/ngendev/videos/' . rawurlencode($trendingCategory->category_name) . '/video_thumbnail/' . rawurlencode($trendingVideo->video_thumbnail)) : null,
                        'category_video' => $trendingVideo->video_path
                            ? "ngendev/videos/{$encodedTrending}/category_video/{$trendingVideo->video_path}"
                            : null,
                        'category_video_full_url' => $trendingVideo->video_path ? asset('upload/ngendev/videos/' . rawurlencode($trendingCategory->category_name) . '/category_video/' . rawurlencode($trendingVideo->video_path)) : null,
                    ];
                    if ($isNameChange) {
                        $itemData['image_hint'] = $trendingVideo->image_hint;
                    }
                    $latestVideos->push($itemData);
                }
            }

            $latestVideos = $latestVideos->values();

            return response()->json([
                'status' => true,
                'message' => 'Latest videos fetched successfully',
                'model' => $ngdAiModel,
                'data' => $latestVideos,
            ]);
        }

        /**
         * ===========================
         * SINGLE CATEGORY
         * ===========================
         */
        $category = NgendevVideoCategory::where('id', $data['category_id'])
            ->where('status', 1)
            ->first();

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
                'model' => $ngdAiModel,
                'data' => [],
            ], 404);
        }

        $encodedCategory = str_replace(' ', '%20', $category->category_name);

        $videos = NgendevVideo::where('category_id', $data['category_id'])
            ->where('status', 1)
            ->select('id', 'video_thumbnail', 'video_path', 'ai_prompt', 'no_of_video', 'name_change', 'image_hint')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($videos->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No videos found for this category',
                'model' => $ngdAiModel,
                'data' => [],
            ], 404);
        }

        $videos->transform(function ($video) use ($encodedCategory, $category) {
            $isNameChange = (bool) $video->name_change;
            $itemData = [
                'id' => $video->id,
                'ai_prompt' => $video->ai_prompt,
                'no_of_video' => $video->no_of_video,
                'name_change' => $isNameChange,
                'video_thumbnail' => $video->video_thumbnail
                    ? "ngendev/videos/{$encodedCategory}/video_thumbnail/{$video->video_thumbnail}"
                    : null,
                'video_thumbnail_full_url' => $video->video_thumbnail
                    ? asset('upload/ngendev/videos/' . rawurlencode($category->category_name) . '/video_thumbnail/' . rawurlencode($video->video_thumbnail))
                    : null,
                'category_video' => $video->video_path
                    ? "ngendev/videos/{$encodedCategory}/category_video/{$video->video_path}"
                    : null,
                'category_video_full_url' => $video->video_path
                    ? asset('upload/ngendev/videos/' . rawurlencode($category->category_name) . '/category_video/' . rawurlencode($video->video_path))
                    : null,
            ];
            if ($isNameChange) {
                $itemData['image_hint'] = $video->image_hint;
            }
            return $itemData;
        });

        return response()->json([
            'status' => true,
            'message' => 'Videos fetched successfully',
            'model' => $ngdAiModel,
            'data' => $videos,
        ]);
    }

    public function getAllCategoryNames()
    {
        $categories = NgendevVideoCategory::select('id', 'category_name')
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No categories found',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Categories fetched successfully',
            'data' => $categories,
        ]);
    }
}
