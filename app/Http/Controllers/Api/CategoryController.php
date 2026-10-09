<?php

namespace App\Http\Controllers\Api;
use App\Models\AiImageCategory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subcategory;
use App\Models\AiImageBabyPhotoSetting;

class CategoryController extends Controller
{
    public function getAllCategories()
    {
        // Get Baby AI setting
        $babyAiSetting = AiImageBabyPhotoSetting::first();
        $babyAiModel = $babyAiSetting ? $babyAiSetting->model : null;

        // Get only active categories from AiImageCategory table
        $activeCategories = AiImageCategory::where('status', 1)->pluck('name');

        $response = [];

        foreach ($activeCategories as $category) {
            $subcategories = Subcategory::where('category_name', $category)
                ->orderBy('id', 'desc')
                ->get(['id', 'title', 'category_name', 'category_thumbnail_image']);

            $subcategories->transform(function ($subcat) {
                $categoryName = trim($subcat->category_name);
                $subcatTitle = trim($subcat->title);

                $thumbnailPath = $subcat->category_thumbnail_image ? "{$categoryName}/{$subcatTitle}/category_thumbnail/{$subcat->category_thumbnail_image}" : null;

                $thumbnailPathArr = explode('/', $thumbnailPath);
                $encodedThumbnailPath = implode('/', array_map('rawurlencode', $thumbnailPathArr));
                return [
                    'id' => $subcat->id,
                    'title' => $subcat->title,
                    'thumbnail' => $thumbnailPath,
                    'thumbnail_full_url' => $thumbnailPath ? asset('upload/' . $encodedThumbnailPath) : null,
                ];
            });

            if ($subcategories->isNotEmpty()) {
                $response[] = [
                    'category_name' => $category,
                    'subcategories' => $subcategories,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Categories retrieved successfully',
            'model' => $babyAiModel,
            'data' => $response,
        ]);
    }

    public function getAllCategoriesV2()
    {
        // Baby AI model
        $babyAiSetting = AiImageBabyPhotoSetting::first();
        $babyAiModel = $babyAiSetting ? $babyAiSetting->model : null;

        // Active categories
        $activeCategories = AiImageCategory::where('status', 1)->pluck('name');

        $response = [];

        foreach ($activeCategories as $category) {

            $subcategories = Subcategory::where('category_name', $category)
                ->orderBy('id', 'desc')
                ->get([
                    'id',
                    'title',
                    'category_name',
                    'category_thumbnail_image',
                    'images'
                ]);

            $formattedSubcategories = $subcategories->map(function ($subcat) {

                $categoryName = trim($subcat->category_name);
                $subcatTitle = trim($subcat->title);

                // Thumbnail path
                $thumbnailPath = $subcat->category_thumbnail_image
                    ? "{$categoryName}/{$subcatTitle}/category_thumbnail/{$subcat->category_thumbnail_image}"
                    : null;

                // Decode images
                $images = json_decode($subcat->images, true) ?? [];

                // Total images count
                $totalCount = count($images);

                // Random image
                $subcategoryImage = null;
                if (!empty($images)) {
                    $randomImage = collect($images)->random();
                    if (isset($randomImage['file'])) {
                        $subcategoryImage = "{$categoryName}/{$subcatTitle}/{$randomImage['file']}";
                    }
                }

                $thumbnailPathArr = $thumbnailPath ? explode('/', $thumbnailPath) : [];
                $encodedThumbnailPath = $thumbnailPath ? implode('/', array_map('rawurlencode', $thumbnailPathArr)) : null;

                $subImageArr = $subcategoryImage ? explode('/', $subcategoryImage) : [];
                $encodedSubcategoryImage = $subcategoryImage ? implode('/', array_map('rawurlencode', $subImageArr)) : null;

                return [
                    'id' => $subcat->id,
                    'title' => $subcat->title,
                    'thumbnail' => $thumbnailPath,
                    'thumbnail_full_url' => $thumbnailPath ? asset('upload/' . $encodedThumbnailPath) : null,
                    'total_count' => $totalCount,
                    'subcategories_image' => $subcategoryImage,
                    'subcategories_image_full_url' => $subcategoryImage ? asset('upload/' . $encodedSubcategoryImage) : null,
                ];
            });

            if ($formattedSubcategories->isNotEmpty()) {
                $response[] = [
                    'category_name' => $category,
                    'subcategories' => $formattedSubcategories,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Categories retrieved successfully',
            'model' => $babyAiModel,
            'data' => $response,
        ]);
    }

    public function getAllCategoriesV3()
    {
        // Baby AI model
        $babyAiSetting = AiImageBabyPhotoSetting::first();
        $babyAiModel = $babyAiSetting ? $babyAiSetting->model : null;

        // Active categories
        $activeCategories = AiImageCategory::where('status', 1)->pluck('name');

        $response = [];

        foreach ($activeCategories as $category) {

            $subcategories = Subcategory::where('category_name', $category)
                ->where('status', 1) // Published only — exclude Draft subcategories
                ->orderBy('id', 'desc')
                // Limit to 6 records
                ->limit(4)
                ->get([
                    'id',
                    'title',
                    'category_name',
                    'category_thumbnail_image',
                    'images'
                ]);

            $formattedSubcategories = $subcategories->map(function ($subcat) {

                $categoryName = trim($subcat->category_name);
                $subcatTitle = trim($subcat->title);

                // Thumbnail path
                $thumbnailPath = $subcat->category_thumbnail_image
                    ? "{$categoryName}/{$subcatTitle}/category_thumbnail/{$subcat->category_thumbnail_image}"
                    : null;

                // Decode images
                $images = json_decode($subcat->images, true) ?? [];
                $formattedImages = [];

                foreach ($images as $img) {
                    $file = $img['file'] ?? null;
                    $prompt = $img['prompt'] ?? '';
                    $imageTitle = $img['image_title'] ?? '';
                    $nameChange = isset($img['name_change']) ? (bool) $img['name_change'] : false;

                    if ($file) {
                        $urlPath = "{$categoryName}/{$subcatTitle}/{$file}";
                        $urlPathArr = explode('/', $urlPath);
                        $encodedUrlPath = implode('/', array_map('rawurlencode', $urlPathArr));

                        $formattedImages[] = [
                            'url' => $urlPath,
                            'url_full_url' => asset('upload/' . $encodedUrlPath),
                            'prompt' => $prompt,
                            'image_title' => $imageTitle,
                            'name_change' => $nameChange,
                        ];
                    }
                }

                if ($subcatTitle === 'Baby Month Milestone') {
                    $uniqueMonthImages = [];
                    foreach ($formattedImages as $img) {
                        if (preg_match('/^(\d+)\s*Month/i', $img['image_title'], $matches)) {
                            $month = (int) $matches[1];
                            $uniqueMonthImages[$month] = $img;
                        }
                    }
                    ksort($uniqueMonthImages);
                    $formattedImages = array_values($uniqueMonthImages);
                } else {
                    // Limit to last 6 images
                    $formattedImages = array_slice($formattedImages, -4);
                }

                $thumbnailPathArr = $thumbnailPath ? explode('/', $thumbnailPath) : [];
                $encodedThumbnailPath = $thumbnailPath ? implode('/', array_map('rawurlencode', $thumbnailPathArr)) : null;

                return [
                    'id' => $subcat->id,
                    'title' => $subcat->title,
                    'thumbnail' => $thumbnailPath,
                    'thumbnail_full_url' => $thumbnailPath ? asset('upload/' . $encodedThumbnailPath) : null,
                    'images' => $formattedImages,
                ];


            });


            if ($formattedSubcategories->isNotEmpty()) {
                $response = array_merge($response, $formattedSubcategories->toArray());
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Categories retrieved successfully',
            'model' => $babyAiModel,
            'data' => $response,
        ]);
    }

    public function getSubcategoriesByCategory(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'main_category' => 'required|string',
            'category_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        $mainCategory = $request->main_category;
        $subCategoryName = $request->category_name;

        // Check if the main category is active
        $category = AiImageCategory::where('name', $mainCategory)->first();

        if (!$category || $category->status != 1) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'This category is not active',
                    'main_category' => $mainCategory,
                    'subcategories' => [],
                ],
                403,
            );
        }

        // Get Baby AI setting
        $babyAiSetting = AiImageBabyPhotoSetting::first();
        $babyAiModel = $babyAiSetting ? $babyAiSetting->model : null;

        // Get subcategory
        $subcategories = Subcategory::where('category_name', $mainCategory)
            ->where('title', $subCategoryName)
            ->get(['id', 'title', 'description', 'images', 'category_name']);

        if ($subcategories->isEmpty()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'No data found for the given main category and subcategory',
                    'model' => $babyAiModel,
                    'main_category' => $mainCategory,
                    'category_name' => $subCategoryName,
                    'subcategories' => [],
                ],
                200,
            );
        }

        // Format images including image_title and name_change
        $subcategories->transform(function ($subcat) {
            $images = json_decode($subcat->images, true) ?? [];
            $formattedImages = [];

            $categoryName = trim($subcat->category_name);
            $subcatTitle = trim($subcat->title);

            foreach ($images as $img) {
                $file = $img['file'] ?? null;
                $prompt = $img['prompt'] ?? '';
                $imageTitle = $img['image_title'] ?? '';
                $nameChange = isset($img['name_change']) ? (bool) $img['name_change'] : false;

                if ($file) {
                    $urlPath = "{$categoryName}/{$subcatTitle}/{$file}";
                    $urlPathArr = explode('/', $urlPath);
                    $encodedUrlPath = implode('/', array_map('rawurlencode', $urlPathArr));

                    $formattedImages[] = [
                        'url' => $urlPath,
                        'url_full_url' => asset('upload/' . $encodedUrlPath),
                        'prompt' => $prompt,
                        'image_title' => $imageTitle,
                        'name_change' => $nameChange,
                    ];
                }
            }

            $subcat->images = $formattedImages;
            unset($subcat->category_name);

            return $subcat;
        });

        return response()->json([
            'status' => true,
            'message' => 'Subcategories retrieved successfully',
            'model' => $babyAiModel,
            'main_category' => $mainCategory,
            'category_name' => $subCategoryName,
            'subcategories' => $subcategories,
        ]);
    }

    public function getSubcategoriesByCategoryid(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'sub_category_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        $subCategoryId = $request->sub_category_id;

        // Get subcategory
        $subCategory = Subcategory::where('id', $subCategoryId)->first(['id', 'title', 'description', 'images', 'category_name']);

        if (!$subCategory) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'No data found for the given sub category id',
                    'sub_category_id' => $subCategoryId,
                    'data' => [],
                ],
                200,
            );
        }

        $mainCategory = $subCategory->category_name;
        $subCategoryName = $subCategory->title;

        // Check if the main category is active
        $category = AiImageCategory::where('name', $mainCategory)->first();

        if (!$category || $category->status != 1) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'This category is not active',
                    'main_category' => $mainCategory,
                    'data' => [],
                ],
                403,
            );
        }

        // Get Baby AI setting
        $babyAiSetting = AiImageBabyPhotoSetting::first();
        $babyAiModel = $babyAiSetting ? $babyAiSetting->model : null;

        // Format images
        $images = json_decode($subCategory->images, true) ?? [];
        $formattedImages = [];

        $categoryName = trim($subCategory->category_name);
        $subcatTitle = trim($subCategory->title);

        foreach ($images as $img) {
            $file = $img['file'] ?? null;
            $prompt = $img['prompt'] ?? '';
            $imageTitle = $img['image_title'] ?? '';
            $nameChange = isset($img['name_change']) ? (bool) $img['name_change'] : false;

            if ($file) {
                $urlPath = "{$categoryName}/{$subcatTitle}/{$file}";
                $urlPathArr = explode('/', $urlPath);
                $encodedUrlPath = implode('/', array_map('rawurlencode', $urlPathArr));

                $formattedImages[] = [
                    'url' => $urlPath,
                    'url_full_url' => asset('upload/' . $encodedUrlPath),
                    'prompt' => $prompt,
                    'image_title' => $imageTitle,
                    'name_change' => $nameChange,
                ];
            }
        }

        $subCategory->images = $formattedImages;
        unset($subCategory->category_name);

        return response()->json([
            'status' => true,
            'message' => 'Subcategories retrieved successfully',
            'model' => $babyAiModel,
            'main_category' => $mainCategory,
            'category_name' => $subCategoryName,
            'subcategories' => [$subCategory],
        ]);
    }
    public function trendingv2()
    {
        $trendingSubcategories = Subcategory::where('trending', 1)->get();

        $data = $trendingSubcategories->map(function ($subcat) {
            $categoryName = trim($subcat->category_name);
            $subcatTitle = trim($subcat->title);
            $thumbnailPath = $subcat->category_thumbnail_image
                ? "{$categoryName}/{$subcatTitle}/category_thumbnail/{$subcat->category_thumbnail_image}"
                : null;

            $thumbnailPathArr = $thumbnailPath ? explode('/', $thumbnailPath) : [];
            $encodedThumbnailPath = $thumbnailPath ? implode('/', array_map('rawurlencode', $thumbnailPathArr)) : null;

            return [
                'id' => $subcat->id,
                'main_category_name' => $categoryName,
                'name' => $subcatTitle,
                'thumbnail' => $thumbnailPath,
                'thumbnail_full_url' => $thumbnailPath ? asset('upload/' . $encodedThumbnailPath) : null,
                'description' => $subcat->description,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Trending subcategories retrieved successfully',
            'data' => $data,
        ]);
    }
    public function getAllCategoryNames()
    {
        $activeCategories = AiImageCategory::where('status', 1)->pluck('name');

        $categories = Subcategory::select('id', 'title as category_name')
            ->whereIn('category_name', $activeCategories)
            ->orderBy('id', 'desc')
            ->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No subcategories found',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Subcategories fetched successfully',
            'data' => $categories,
        ]);
    }
}
