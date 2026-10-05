<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TopSliderCategory;
use App\Models\TopSliderItem;
use App\Models\NgendevCategory;
use App\Models\NgendevVideoCategory;
use Illuminate\Support\Facades\File;

class TopSliderCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = intval($request->input('per_page', 10));
        $search = $request->input('search', '');
        $fileType = $request->input('file_type', '');

        $query = TopSliderCategory::query();

        if ($search) {
            $query->where('category_name', 'like', '%' . $search . '%');
        }
        
        if ($fileType) {
            $query->where('file_type', $fileType);
        }

        $categories = $query->orderBy('sort_order', 'asc')
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('top_slider.categories.index', compact('categories', 'perPage', 'search', 'fileType'));
    }

    public function create()
    {
        $imageCategories = NgendevCategory::select('id', 'category_name')->orderBy('category_name')->get();
        $videoCategories = NgendevVideoCategory::select('id', 'category_name')->orderBy('category_name')->get();
        return view('top_slider.categories.form', compact('imageCategories', 'videoCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer',
            'title'           => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'file_type'       => 'required|in:image,video',
            'image'           => 'nullable|image|mimes:webp|max:10000',
            'video'           => 'nullable|mimes:mp4,mov,ogg,qt|max:50000',
            'video_thumbnail' => 'nullable|image|mimes:webp|max:10000',
        ]);

        $category = new TopSliderCategory();
        $category->category_id = $request->category_id;

        
        $category->title = $request->title;
        $category->description = $request->description;
        $category->file_type = $request->file_type;
        $category->top_slider_is_on = $request->has('top_slider_is_on') ? 1 : 0;

        $category->save();

        $basePath = 'upload/top_slider/categories/' . $category->category_name;

        if ($request->file_type == 'image') {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = public_path($basePath);
                if (!File::exists($path)) File::makeDirectory($path, 0777, true);
                $filename = $this->uniqueFilename($path, $file->getClientOriginalName());
                $file->move($path, $filename);
                $category->image = $filename;
            }
        } elseif ($request->file_type == 'video') {
            if ($request->hasFile('video')) {
                $file = $request->file('video');
                $path = public_path($basePath);
                if (!File::exists($path)) File::makeDirectory($path, 0777, true);
                $filename = $this->uniqueFilename($path, $file->getClientOriginalName());
                $file->move($path, $filename);
                $category->video = $filename;
            }
            if ($request->hasFile('video_thumbnail')) {
                $file = $request->file('video_thumbnail');
                $path = public_path($basePath);
                if (!File::exists($path)) File::makeDirectory($path, 0777, true);
                $filename = $this->uniqueFilename($path, $file->getClientOriginalName());
                $file->move($path, $filename);
                $category->video_thumbnail = $filename;
            }
        }

        $category->save();

        return redirect()->route('top-slider.categories.index')->with('success', 'Category created successfully!');
    }

    public function edit($id)
    {
        $category = TopSliderCategory::findOrFail($id);
        $imageCategories = NgendevCategory::select('id', 'category_name')->orderBy('category_name')->get();
        $videoCategories = NgendevVideoCategory::select('id', 'category_name')->orderBy('category_name')->get();
        return view('top_slider.categories.form', compact('category', 'imageCategories', 'videoCategories'));
    }

    public function update(Request $request, $id)
    {
        $category = TopSliderCategory::findOrFail($id);

        $request->validate([
            'category_id' => 'required|integer',
            'title'           => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'file_type'       => 'required|in:image,video',
            'image'           => 'nullable|image|mimes:webp|max:10000',
            'video'           => 'nullable|mimes:mp4,mov,ogg,qt|max:50000',
            'video_thumbnail' => 'nullable|image|mimes:webp|max:10000',
        ]);

        $category->category_id = $request->category_id;


        $category->title = $request->title;
        $category->description = $request->description;
        $category->file_type = $request->file_type;
        $category->top_slider_is_on = $request->has('top_slider_is_on') ? 1 : 0;

        $basePath = 'upload/top_slider/categories/' . $category->category_name;

        if ($request->file_type == 'image') {
            if ($request->hasFile('image')) {
                if ($category->image && file_exists(public_path('upload/top_slider/categories/' . $category->category_name . '/' . $category->image))) {
                    unlink(public_path('upload/top_slider/categories/' . $category->category_name . '/' . $category->image));
                }
                $file = $request->file('image');
                $path = public_path($basePath);
                if (!File::exists($path)) File::makeDirectory($path, 0777, true);
                $filename = $this->uniqueFilename($path, $file->getClientOriginalName());
                $file->move($path, $filename);
                $category->image = $filename;
            }
        } elseif ($request->file_type == 'video') {
            if ($request->hasFile('video')) {
                if ($category->video && file_exists(public_path('upload/top_slider/categories/' . $category->category_name . '/' . $category->video))) {
                    unlink(public_path('upload/top_slider/categories/' . $category->category_name . '/' . $category->video));
                }
                $file = $request->file('video');
                $path = public_path($basePath);
                if (!File::exists($path)) File::makeDirectory($path, 0777, true);
                $filename = $this->uniqueFilename($path, $file->getClientOriginalName());
                $file->move($path, $filename);
                $category->video = $filename;
            }
            if ($request->hasFile('video_thumbnail')) {
                if ($category->video_thumbnail && file_exists(public_path('upload/top_slider/categories/' . $category->category_name . '/' . $category->video_thumbnail))) {
                    unlink(public_path('upload/top_slider/categories/' . $category->category_name . '/' . $category->video_thumbnail));
                }
                $file = $request->file('video_thumbnail');
                $path = public_path($basePath);
                if (!File::exists($path)) File::makeDirectory($path, 0777, true);
                $filename = $this->uniqueFilename($path, $file->getClientOriginalName());
                $file->move($path, $filename);
                $category->video_thumbnail = $filename;
            }
        }

        $category->save();

        return redirect()->route('top-slider.categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy($id)
    {
        $category = TopSliderCategory::findOrFail($id);
        
        // Delete category folder from filesystem
        $catBasePath = public_path('upload/top_slider/categories/' . $category->category_name);
        if (File::exists($catBasePath)) {
            File::deleteDirectory($catBasePath);
        }

        // Delete related items folder from filesystem
        $itemBasePath = public_path('upload/top_slider/items/' . $category->category_name);
        if (File::exists($itemBasePath)) {
            File::deleteDirectory($itemBasePath);
        }

        // Delete items from database
        TopSliderItem::where('top_slider_category_id', $id)->delete();
        
        // Delete category
        $category->delete();

        return redirect()->route('top-slider.categories.index')->with('success', 'Category and all its related items and files deleted successfully!');
    }

    
    public function toggleTopSliderStatus(Request $request)
    {
        $category = TopSliderCategory::findOrFail($request->id);
        $category->top_slider_is_on = $request->top_slider_is_on ? 1 : 0;
        $category->save();

        return response()->json(['success' => true, 'message' => 'Top Slider Status updated successfully!']);
    }

    public function indexing(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('top-slider.categories.index');
        }
        $categories = TopSliderCategory::orderBy('sort_order', 'asc')->orderBy('updated_at', 'desc')->get();
        $categories->each->append('category_name');
        return response()->json(['categories' => $categories]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:top_slider_categories,id',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);
        foreach ($request->order as $item) {
            TopSliderCategory::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }
        return response()->json(['success' => true, 'message' => 'Order updated successfully!']);
    }
}
