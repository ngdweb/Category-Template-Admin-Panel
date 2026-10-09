<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use App\Models\NgendevImage;
use App\Models\NgendevCategory;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class NgendevImageController extends Controller
{
    public function index(Request $request)
    {
        $perPage    = (int) $request->input('per_page', 10);
        $search     = (string) $request->input('search', '');
        $categoryId = (string) $request->input('category_id', '');

        $categories = NgendevCategory::orderBy('created_at', 'desc')->get();

        $query = NgendevImage::with('category')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc');

        if ($categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ai_prompt', 'like', "%{$search}%")
                  ->orWhere('ai_model', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($q2) use ($search) {
                      $q2->where('category_name', 'like', "%{$search}%");
                  });
            });
        }

        $images = $query->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html'  => view('ngendev.images.table', compact('images'))->render(),
                'total' => $images->total(),
            ]);
        }

        return view('ngendev.images.index', compact('categories', 'images', 'perPage', 'search', 'categoryId'));
    }


    public function store(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:ngendev_categories,id',
                'ai_prompt'   => 'required|string|max:2990',
                'ai_model'    => 'nullable|string|max:255',
                'image'       => 'required|image|mimes:webp|max:10000',
                'no_of_image' => 'required|integer|min:1',
                'image_hint'  => 'nullable|string|max:255|required_if:name_change,1',
            ], [
                'image_hint.required_if' => 'Image Hint is required when Name Change is enabled.',
            ]);

            $imageName = null;

            if ($request->hasFile('image')) {
                $category        = NgendevCategory::findOrFail($request->category_id);
                $destinationPath = public_path('upload/ngendev/images/' . $category->category_name . '/category_image');

                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }

                $imageName = $this->uniqueFilename($destinationPath, $request->file('image')->getClientOriginalName());
                $request->file('image')->move($destinationPath, $imageName);
            }

            $isNameChange = $request->has('name_change') ? 1 : 0;

            NgendevImage::create([
                'category_id' => $request->category_id,
                'ai_prompt'   => $request->ai_prompt,
                'ai_model'    => $request->ai_model,
                'no_of_image' => $request->no_of_image,
                'name_change' => $isNameChange,
                'image_hint'  => $isNameChange ? $request->image_hint : null,
                'image_path'  => $imageName,
            ]);

            return $request->ajax()
                ? response()->json(['success' => true, 'message' => 'Ngendev image added successfully!'])
                : redirect()->route('ngendev.images.index')->with('success', 'Ngendev image added successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errors], 422);
            }
            return redirect()->back()->withInput()->withErrors($e->errors());

        } catch (\Exception $e) {
            $msg = 'Something went wrong while adding the image. Please try again.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:ngendev_categories,id',
                'ai_prompt'   => 'required|string|max:2990',
                'ai_model'    => 'nullable|string|max:255',
                'image'       => 'nullable|image|mimes:webp|max:4096',
                'no_of_image' => 'required|integer|min:1',
                'name_change' => 'boolean',
                'image_hint'  => 'nullable|string|max:255|required_if:name_change,1',
            ], [
                'image_hint.required_if' => 'Image Hint is required when Name Change is enabled.',
            ]);

            $image = NgendevImage::with('category')->findOrFail($id);
            $oldCategory = $image->category ?? NgendevCategory::find($image->category_id);
            $oldCategoryName = $oldCategory ? $oldCategory->category_name : null;

            $newCategory = NgendevCategory::findOrFail($request->category_id);
            $newCategoryName = $newCategory->category_name;

            $isCategoryChanged = ($oldCategoryName !== null && $oldCategoryName !== $newCategoryName);
            $imagePath = $image->image_path;
            $newImageFolder = public_path('upload/ngendev/images/' . $newCategoryName . '/category_image');

            if ($request->hasFile('image')) {
                if ($imagePath && $oldCategoryName) {
                    $oldPath = public_path('upload/ngendev/images/' . $oldCategoryName . '/category_image/' . $imagePath);
                    if (File::exists($oldPath)) {
                        File::delete($oldPath);
                    }
                }

                if (!File::exists($newImageFolder)) {
                    File::makeDirectory($newImageFolder, 0777, true, true);
                }

                $imagePath = $this->uniqueFilename($newImageFolder, $request->file('image')->getClientOriginalName());
                $request->file('image')->move($newImageFolder, $imagePath);
            } elseif ($isCategoryChanged && $imagePath && $oldCategoryName) {
                $oldPath = public_path('upload/ngendev/images/' . $oldCategoryName . '/category_image/' . $imagePath);
                if (File::exists($oldPath)) {
                    if (!File::exists($newImageFolder)) {
                        File::makeDirectory($newImageFolder, 0777, true, true);
                    }
                    $newImageFilename = $this->uniqueFilename($newImageFolder, $imagePath);
                    File::move($oldPath, $newImageFolder . '/' . $newImageFilename);
                    $imagePath = $newImageFilename;
                }
            }

            $isNameChange = $request->has('name_change') ? 1 : 0;

            $image->update([
                'category_id' => $request->category_id,
                'ai_prompt'   => $request->ai_prompt,
                'ai_model'    => $request->ai_model,
                'no_of_image' => $request->no_of_image,
                'name_change' => $isNameChange,
                'image_hint'  => $isNameChange ? $request->image_hint : null,
                'image_path'  => $imagePath,
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Ngendev image updated successfully!']);
            }

            return redirect()->route('ngendev.images.index')->with('success', 'Ngendev image updated successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errors], 422);
            }
            return redirect()->back()->withInput()->withErrors($e->errors());

        } catch (\Exception $e) {
            $msg = 'Something went wrong while updating the image. Please try again.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }
    }

    public function destroy($id)
    {
        $image = NgendevImage::with('category')->findOrFail($id);
        $category = $image->category ?? NgendevCategory::find($image->category_id);

        if ($category && $image->image_path) {
            $filePath = public_path('upload/ngendev/images/' . $category->category_name . '/category_image/' . $image->image_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $image->delete();

        return redirect()->route('ngendev.images.index')->with('success', 'Selected image deleted successfully!');
    }

    public function indexing(Request $request)
    {
        $categoryId = $request->get('category_id');

        if (!$categoryId) {
            return response()->json(['images' => []]);
        }

        $images = NgendevImage::with('category')->where('category_id', $categoryId)->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        // If no images have sort_order set, initialize them
        if ($images->count() > 0 && $images->where('sort_order', 0)->count() > 0) {
            foreach ($images as $index => $image) {
                if ($image->sort_order == 0) {
                    $image->update(['sort_order' => $index + 1]);
                }
            }
            // Refresh the collection
            $images = NgendevImage::with('category')->where('category_id', $categoryId)->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();
        }

        // Transform the images to include proper image URLs
        $transformedImages = $images->map(function ($image) {
            $imageData = $image->toArray();

            // Build the correct image URL
            if ($image->image_path && $image->category) {
                $catName  = rawurlencode($image->category->category_name);
                $imgPath  = rawurlencode($image->image_path);
                $imageData['image_url'] = asset("upload/ngendev/images/{$catName}/category_image/{$imgPath}");
            } else {
                $imageData['image_url'] = null;
            }

            return $imageData;
        });

        return response()->json(['images' => $transformedImages]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:ngendev_categories,id',
            'order' => 'required|array',
            'order.*.id' => 'required|exists:ngendev_images,id',
            'order.*.sort_order' => 'required|integer|min:1',
        ]);

        $categoryId = $request->category_id;
        $orderData = $request->order;

        foreach ($orderData as $item) {
            NgendevImage::where('id', $item['id'])
                ->where('category_id', $categoryId)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true, 'message' => 'Image order updated successfully!']);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:ngendev_images,id',
            'status' => 'required|boolean',
        ]);

        $image = NgendevImage::findOrFail($request->id);
        $image->status = $request->status ? 1 : 0;
        $image->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully!']);
    }

    public function bulkToggleNameChange(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:ngendev_categories,id',
            'name_change' => 'required|in:0,1',
        ]);

        $value = (int) $request->name_change;
        $updateData = ['name_change' => $value];
        if (!$value) {
            $updateData['image_hint'] = null;
        }
        $count = NgendevImage::where('category_id', $request->category_id)
            ->update($updateData);

        return response()->json([
            'success'       => true,
            'message'       => "Updated {$count} image(s) — name_change set to " . ($value ? 'true' : 'false') . '.',
            'updated_count' => $count,
            'name_change'   => (bool) $value,
        ]);
    }

    public function categoryNameChangeStats(Request $request)
    {
        $stats = NgendevImage::selectRaw('category_id, SUM(name_change) as true_count, COUNT(*) as total')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        return response()->json(['stats' => $stats]);
    }

    public function addSortOrderColumn()
    {
        $tableName = 'ngendev_images';
        $columnName = 'sort_order';

        // Check if column exists
        $columnExists = Schema::hasColumn($tableName, $columnName);

        if (!$columnExists) {
            // Add the column dynamically
            DB::statement("ALTER TABLE {$tableName} ADD COLUMN {$columnName} INT DEFAULT 0 AFTER ai_model");
        }

        // Fill sort_order per category
        $categories = \App\Models\NgendevCategory::all();

        foreach ($categories as $category) {
            $images = \App\Models\NgendevImage::where('category_id', $category->id)->orderBy('id', 'asc')->get();

            $i = 1;
            foreach ($images as $image) {
                $image->sort_order = $i;
                $image->save();
                $i++;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'sort_order column added and populated successfully for all images.',
        ]);
    }
}
