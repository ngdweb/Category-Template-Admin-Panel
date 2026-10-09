<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NgendevCategory;
use App\Models\NgendevImage;
use Illuminate\Http\Request;
use App\Models\AiImageNgdSetting;

class NgendevCategoryApiController extends Controller
{
    public function getCategories()
    {
        $ngdAiSetting = AiImageNgdSetting::first();
        $ngdAiModel = $ngdAiSetting ? $ngdAiSetting->model : null;
        $coupleActive = $ngdAiSetting ? $ngdAiSetting->couple_active : 1;

        $query = NgendevCategory::select('id', 'category_name', 'sort_order', 'type')
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

        // Map categories with images
        $categories = $categories->map(function ($category) {
            $encodedCategory = str_replace(' ', '%20', $category->category_name);

            $images = NgendevImage::where('category_id', $category->id)
                ->where('status', 1)
                ->select('id', 'ai_prompt', 'image_path', 'no_of_image', 'name_change', 'image_hint') // removed sort_order
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->slice(-4)
                ->values();

            $images->transform(function ($image) use ($encodedCategory) {
                $image->category_image = $image->image_path ? "ngendev/images/{$encodedCategory}/category_image/{$image->image_path}" : null;
                $image->category_image_full_url = $image->category_image ? asset('upload/ngendev/images/' . rawurlencode(str_replace('%20', ' ', $encodedCategory)) . '/category_image/' . rawurlencode($image->image_path)) : null;
                $isNameChange = (bool) $image->name_change;
                $hint = $image->image_hint;
                $image->name_change = $isNameChange;
                if ($isNameChange) {
                    $image->image_hint = $hint;
                } else {
                    unset($image->image_hint);
                }
                unset($image->image_path);
                return $image;
            });

            return [
                'category_id' => $category->id,
                'category_name' => $category->category_name,
                'items' => $images,
            ];
        })->filter(function ($cat) {
            return $cat['items']->isNotEmpty();
        })->values();

        // Separate Exclusive category
        $exclusive = $categories->firstWhere('category_name', 'Exclusive');
        if ($exclusive) {
            $categories = $categories->reject(function($cat) { return $cat['category_name'] === 'Exclusive'; })->values();
        }

        // Separate Trending category
        $trending = $categories->firstWhere('category_name', 'Trending');
        if ($trending) {
            $categories = $categories->reject(function($cat) { return $cat['category_name'] === 'Trending'; })->values();
        }

        // Latest category: last record from all other categories
        $latestImages = $categories
            ->filter(function($cat) { return $cat['items']->isNotEmpty(); })
            ->map(function($cat) { return $cat['items']->last(); }) // explicitly last record
            ->filter()
            ->values();

        // 🔥 Add Exclusive's last record at the end of Latest category
        if ($exclusive && $exclusive['items']->isNotEmpty()) {
            $exclusiveLastRecord = $exclusive['items']->last(); // explicitly last record
            $latestImages->push($exclusiveLastRecord);
        }

        // 🔥 Add Trending's last record at the end of Latest category
        if ($trending && $trending['items']->isNotEmpty()) {
            $trendingLastRecord = $trending['items']->last(); // explicitly last record
            $latestImages->push($trendingLastRecord);
        }

        $latestCategory = [
            'category_id' => 0,
            'category_name' => 'Latest',
            'items' => $latestImages,
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

        // Append remaining categories (concat preserves existing keys, merge would overwrite them)
        $sortedCategories = $sortedCategories->concat($categories);

        return response()->json([
            'status' => true,
            'message' => 'Categories fetched successfully',
            'model' => $ngdAiModel,
            'data' => $sortedCategories->values(),
        ]);
    }

    public function getAiImageByCategoryId(Request $request)
    {
        $data = $request->json()->all();

        $validator = \Validator::make($data, [
            'category_id' => 'required',
        ], [
            'category_id.required' => 'category_id is required',
        ]);

        $ngdAiSetting = AiImageNgdSetting::first();
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

            $query = NgendevCategory::where('status', 1);

            if (!$coupleActive) {
                $query->where('type', '!=', 'Couple');
            }

            $categories = $query->orderBy('sort_order', 'asc')
                ->orderBy('id', 'desc')
                ->get();

            // Separate Exclusive and Trending categories to match getCategories ordering
            $exclusiveCategory = $categories->firstWhere('category_name', 'Exclusive');
            $categories = $categories->reject(function ($cat) {
                return $cat->category_name === 'Exclusive'; });

            $trendingCategory = $categories->firstWhere('category_name', 'Trending');
            $categories = $categories->reject(function ($cat) {
                return $cat->category_name === 'Trending'; });

            $latestImages = collect();

            // Get first image from each remaining category (same order as getCategories)
            foreach ($categories as $category) {
                $latestImage = NgendevImage::where('category_id', $category->id)
                    ->where('status', 1)
                    ->select('id', 'ai_prompt', 'image_path', 'category_id', 'no_of_image', 'name_change', 'image_hint')
                    ->orderBy('sort_order', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($latestImage) {
                    $encodedCategory = str_replace(' ', '%20', $category->category_name);
                    $category_image_path = $latestImage->image_path
                        ? "ngendev/images/{$encodedCategory}/category_image/{$latestImage->image_path}"
                        : null;
                    $isNameChange = (bool) $latestImage->name_change;
                    $itemData = [
                        'id' => $latestImage->id,
                        'ai_prompt' => $this->buildFullPrompt($latestImage->ai_prompt),
                        'no_of_image' => $latestImage->no_of_image,
                        'name_change' => $isNameChange,
                        'category_image' => $category_image_path,
                        'category_image_full_url' => $category_image_path ? asset('upload/ngendev/images/' . rawurlencode($category->category_name) . '/category_image/' . rawurlencode($latestImage->image_path)) : null,
                    ];
                    if ($isNameChange) {
                        $itemData['image_hint'] = $latestImage->image_hint;
                    }
                    $latestImages->push($itemData);
                }
            }

            // Add Exclusive first record
            if ($exclusiveCategory) {
                $exclusiveImage = NgendevImage::where('category_id', $exclusiveCategory->id)
                    ->where('status', 1)
                    ->select('id', 'ai_prompt', 'image_path', 'category_id', 'no_of_image', 'name_change', 'image_hint')
                    ->orderBy('sort_order', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($exclusiveImage) {
                    $encodedExclusive = str_replace(' ', '%20', $exclusiveCategory->category_name);
                    $exclusive_image_path = $exclusiveImage->image_path
                        ? "ngendev/images/{$encodedExclusive}/category_image/{$exclusiveImage->image_path}"
                        : null;
                    $isNameChange = (bool) $exclusiveImage->name_change;
                    $itemData = [
                        'id' => $exclusiveImage->id,
                        'ai_prompt' => $this->buildFullPrompt($exclusiveImage->ai_prompt),
                        'no_of_image' => $exclusiveImage->no_of_image,
                        'name_change' => $isNameChange,
                        'category_image' => $exclusive_image_path,
                        'category_image_full_url' => $exclusive_image_path ? asset('upload/ngendev/images/' . rawurlencode($exclusiveCategory->category_name) . '/category_image/' . rawurlencode($exclusiveImage->image_path)) : null,
                    ];
                    if ($isNameChange) {
                        $itemData['image_hint'] = $exclusiveImage->image_hint;
                    }
                    $latestImages->push($itemData);
                }
            }

            // Add Trending first record
            if ($trendingCategory) {
                $trendingImage = NgendevImage::where('category_id', $trendingCategory->id)
                    ->where('status', 1)
                    ->select('id', 'ai_prompt', 'image_path', 'category_id', 'no_of_image', 'name_change', 'image_hint')
                    ->orderBy('sort_order', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($trendingImage) {
                    $encodedTrending = str_replace(' ', '%20', $trendingCategory->category_name);
                    $trending_image_path = $trendingImage->image_path
                        ? "ngendev/images/{$encodedTrending}/category_image/{$trendingImage->image_path}"
                        : null;
                    $isNameChange = (bool) $trendingImage->name_change;
                    $itemData = [
                        'id' => $trendingImage->id,
                        'ai_prompt' => $this->buildFullPrompt($trendingImage->ai_prompt),
                        'no_of_image' => $trendingImage->no_of_image,
                        'name_change' => $isNameChange,
                        'category_image' => $trending_image_path,
                        'category_image_full_url' => $trending_image_path ? asset('upload/ngendev/images/' . rawurlencode($trendingCategory->category_name) . '/category_image/' . rawurlencode($trendingImage->image_path)) : null,
                    ];
                    if ($isNameChange) {
                        $itemData['image_hint'] = $trendingImage->image_hint;
                    }
                    $latestImages->push($itemData);
                }
            }

            $latestImages = $latestImages->values();

            return response()->json([
                'status' => true,
                'message' => 'Latest images fetched successfully',
                'model' => $ngdAiModel,
                'data' => $latestImages,
            ]);
        }

        /**
         * ===========================
         * SINGLE CATEGORY
         * ===========================
         */
        $category = NgendevCategory::where('id', $data['category_id'])
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

        $images = NgendevImage::where('category_id', $data['category_id'])
            ->where('status', 1)
            ->select('id', 'image_path', 'ai_prompt', 'no_of_image', 'name_change', 'image_hint')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($images->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No images found for this category',
                'model' => $ngdAiModel,
                'data' => [],
            ], 404);
        }

        $images->transform(function($image) use ($encodedCategory, $category) {
            $isNameChange = (bool) $image->name_change;
            $itemData = [
                'id' => $image->id,
                'ai_prompt' => $this->buildFullPrompt($image->ai_prompt),
                'no_of_image' => $image->no_of_image,
                'name_change' => $isNameChange,
                'category_image' => $image->image_path
                    ? "ngendev/images/{$encodedCategory}/category_image/{$image->image_path}"
                    : null,
                'category_image_full_url' => $image->image_path
                    ? asset('upload/ngendev/images/' . rawurlencode($category->category_name) . '/category_image/' . rawurlencode($image->image_path))
                    : null,
            ];
            if ($isNameChange) {
                $itemData['image_hint'] = $image->image_hint;
            }
            return $itemData;
        });

        return response()->json([
            'status' => true,
            'message' => 'Images fetched successfully',
            'model' => $ngdAiModel,
            'data' => $images,
        ]);
    }
    private function buildFullPrompt($aiPrompt)
    {
        $defaultPrompt = 'Ultra-photorealistic portrait of the exact same person as the reference image. Preserve identical facial identity, bone structure, proportions, and all features (eyes, nose, mouth, jawline, skin detail) with zero modification. Maintain the original expression, emotion, and mood. No beautification or facial alteration. Natural lighting, lifelike skin texture, high dynamic range, and maximum identity fidelity.';
        $userLimit = 2990 - strlen($defaultPrompt);

        return (strlen($aiPrompt) <= $userLimit)
            ? trim($aiPrompt . ' ' . $defaultPrompt)
            : $aiPrompt;
    }

    public function getAllCategoryNames()
    {
        $categories = NgendevCategory::select('id', 'category_name')
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
