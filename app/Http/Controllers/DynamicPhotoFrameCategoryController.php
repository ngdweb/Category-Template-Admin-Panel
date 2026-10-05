<?php

namespace App\Http\Controllers;

use App\Models\DynamicPhotoFrameCategory;
use App\Models\DynamicPhotoFrame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DynamicPhotoFrameCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $search  = $request->input('search', '');

        $query = DynamicPhotoFrameCategory::query();

        if ($search) {
            $query->where('category_name', 'like', '%' . $search . '%');
        }

        $categories = $query->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html'  => view('dynamic_photo_frame.categories.table', compact('categories'))->render(),
                'total' => $categories->total(),
            ]);
        }

        return view('dynamic_photo_frame.categories.index', compact('categories', 'perPage', 'search'));
    }

    public function create()
    {
        return view('dynamic_photo_frame.categories.form');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'category_name' => 'required|string|max:255|unique:dynamic_photo_frame_categories,category_name',
                'image'         => 'nullable|image|mimes:webp|max:10000',
            ]);

            $category = new DynamicPhotoFrameCategory();
            $category->category_name = $request->category_name;
            $category->status        = 1;
            $category->save();

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = public_path('upload/dynamic_photo_frame/' . $category->category_name . '/category image');
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0777, true);
                }
                $filename = $this->uniqueFilename($path, $file->getClientOriginalName());
                $file->move($path, $filename);
                $category->image = $filename;
                $category->save();
            }

            return redirect()->route('dynamic-photo-frame.categories.index')->with('success', 'Dynamic Photo Frame Category created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
            return redirect()->back()->withInput()->with('error', $errors)->withErrors($e->errors());

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to save category: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $category = DynamicPhotoFrameCategory::findOrFail($id);
        return view('dynamic_photo_frame.categories.form', compact('category'));
    }

    public function update(Request $request, $id)
    {
        try {
            $category = DynamicPhotoFrameCategory::findOrFail($id);

            $request->validate([
                'category_name' => 'required|string|max:255|unique:dynamic_photo_frame_categories,category_name,' . $category->id,
                'image'         => 'nullable|image|mimes:webp|max:10000',
            ]);

            $oldName = $category->category_name;
            $category->category_name = $request->category_name;

            if ($oldName !== $category->category_name) {
                $oldFolder = public_path('upload/dynamic_photo_frame/' . $oldName);
                $newFolder = public_path('upload/dynamic_photo_frame/' . $category->category_name);
                if (File::exists($oldFolder) && !File::exists($newFolder)) {
                    @rename($oldFolder, $newFolder);
                }
            }

            if ($request->hasFile('image')) {
                $oldImagePath = public_path('upload/dynamic_photo_frame/' . $category->category_name . '/category image/' . $category->image);
                if ($category->image && file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }

                $file = $request->file('image');
                $path = public_path('upload/dynamic_photo_frame/' . $category->category_name . '/category image');
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0777, true);
                }
                $filename = $this->uniqueFilename($path, $file->getClientOriginalName());
                $file->move($path, $filename);
                $category->image = $filename;
            }

            $category->save();

            $this->rmIfEmpty(public_path('upload/dynamic_photo_frame/' . $category->category_name . '/category image'));
            $this->rmIfEmpty(public_path('upload/dynamic_photo_frame/' . $category->category_name));

            return redirect()->route('dynamic-photo-frame.categories.index')->with('success', 'Dynamic Photo Frame Category updated successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
            return redirect()->back()->withInput()->with('error', $errors)->withErrors($e->errors());

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $category = DynamicPhotoFrameCategory::findOrFail($id);

        $folder = public_path('upload/dynamic_photo_frame/' . $category->category_name);
        if (File::exists($folder)) {
            File::deleteDirectory($folder);
        }

        DynamicPhotoFrame::where('dynamic_photo_frame_category_id', $id)->delete();

        // Remove the Baby AI Home Slider entry that points to this category
        \App\Models\BabyAiHomeSlider::deleteBySource('dynamic_frame', $id);

        $category->delete();

        return redirect()->route('dynamic-photo-frame.categories.index')->with('success', 'Dynamic Photo Frame Category and all its frames deleted successfully!');
    }

    private function rmIfEmpty(string $folder): void
    {
        if (!is_dir($folder)) return;
        $items = @scandir($folder);
        $items = array_diff($items ?: [], ['.', '..']);
        if (empty($items)) {
            @rmdir($folder);
        }
    }

}
