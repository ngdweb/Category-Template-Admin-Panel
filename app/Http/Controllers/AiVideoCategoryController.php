<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiVideoCategory;
use Illuminate\Support\Facades\File;

class AiVideoCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = intval($request->input('per_page', 10));
        $search = $request->input('search', '');

        $query = AiVideoCategory::query();

        if ($search) {
            $query->where('category_name', 'like', '%' . $search . '%');
        }

        $categories = $query->orderBy('sort_order', 'asc')->orderBy('updated_at', 'desc')->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html'  => view('ai_baby_video.categories.table', compact('categories'))->render(),
                'total' => $categories->total(),
            ]);
        }

        return view('ai_baby_video.categories.index', compact('categories', 'perPage', 'search'));
    }

    public function create()
    {
        return view('ai_baby_video.categories.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255|unique:ai_baby_video_categories,category_name',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10000',
            'description' => 'nullable|string',
            'trending' => 'boolean',
            'status' => 'boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('category_image')) {
            $file = $request->file('category_image');
            $path = public_path('upload/AI Baby Video/' . $request->category_name . '/category thumbanail');
            if (!File::exists($path)) {
                File::makeDirectory($path, 0777, true);
            }
            $imagePath = $this->uniqueFilename($path, $file->getClientOriginalName());
            $file->move($path, $imagePath);
        }

        $category = AiVideoCategory::create([
            'category_name' => $request->category_name,
            'category_image' => $imagePath,
            'description' => $request->description,
            'trending' => $request->has('trending') ? 1 : 0,
            'status' => $request->has('status') ? 1 : 0,
            'sort_order' => 0,
        ]);

        if ($request->boolean('notify_after_save')) {
            session()->flash('notify_project_ids', $request->input('firebase_project_ids', []));
            return redirect()->route('notifications.form', ['module' => 'ai_baby_video', 'id' => $category->id]);
        }

        return redirect()->route('ai-baby-video.categories.index')->with('success', 'Category added successfully!');
    }

    public function edit($id)
    {
        $category = AiVideoCategory::findOrFail($id);
        return view('ai_baby_video.categories.form', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = AiVideoCategory::findOrFail($id);

        $request->validate([
            'category_name' => 'required|string|max:255|unique:ai_baby_video_categories,category_name,' . $id,
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10000',
            'description' => 'nullable|string',
            'trending' => 'boolean',
            'status' => 'boolean',
        ]);

        $imagePath = $category->category_image;
        if ($request->hasFile('category_image')) {
            // Delete old
            if ($category->category_image) {
                if (file_exists(public_path($category->category_image))) {
                    unlink(public_path($category->category_image));
                } elseif (file_exists(public_path('upload/AI Baby Video/' . $category->category_name . '/category thumbanail/' . $category->category_image))) {
                    unlink(public_path('upload/AI Baby Video/' . $category->category_name . '/category thumbanail/' . $category->category_image));
                } elseif (file_exists(public_path('upload/AI Baby Video/Category/' . $category->category_image))) {
                    unlink(public_path('upload/AI Baby Video/Category/' . $category->category_image));
                }
            }

            $file = $request->file('category_image');
            $path = public_path('upload/AI Baby Video/' . $request->category_name . '/category thumbanail');
            if (!File::exists($path)) {
                File::makeDirectory($path, 0777, true);
            }
            $imagePath = $this->uniqueFilename($path, $file->getClientOriginalName());
            $file->move($path, $imagePath);
        }

        $category->update([
            'category_name' => $request->category_name,
            'category_image' => $imagePath,
            'description' => $request->description,
            'trending' => $request->has('trending') ? 1 : 0,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('ai-baby-video.categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy($id)
    {
        $category = AiVideoCategory::findOrFail($id);

        // Delete Image
        if ($category->category_image) {
            if (file_exists(public_path($category->category_image))) {
                unlink(public_path($category->category_image));
            } elseif (file_exists(public_path('upload/AI Baby Video/' . $category->category_name . '/category thumbanail/' . $category->category_image))) {
                unlink(public_path('upload/AI Baby Video/' . $category->category_name . '/category thumbanail/' . $category->category_image));
            } elseif (file_exists(public_path('upload/AI Baby Video/Category/' . $category->category_image))) {
                unlink(public_path('upload/AI Baby Video/Category/' . $category->category_image));
            }
        }

        // We should also delete related AiBabyVideo items if any
        \App\Models\AiBabyVideo::where('category_id', $category->id)->delete();

        // Remove the Baby AI Home Slider entry that points to this category
        \App\Models\BabyAiHomeSlider::deleteBySource('video', $category->id);

        $category->delete();

        return redirect()->route('ai-baby-video.categories.index')->with('success', 'Category and all videos deleted successfully!');
    }

    public function toggleStatus(Request $request)
    {
        $category = AiVideoCategory::findOrFail($request->id);
        $category->status = $request->status ? 1 : 0;
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully!'
        ]);
    }

    public function indexing(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('ai-baby-video.categories.index');
        }

        $categories = AiVideoCategory::orderBy('sort_order', 'asc')->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'categories' => $categories
        ]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:ai_baby_video_categories,id',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->order as $item) {
            AiVideoCategory::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category order updated successfully!',
            'redirect_url' => route('ai-baby-video.categories.index'),
        ]);
    }
}
