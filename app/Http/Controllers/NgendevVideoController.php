<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\File;
use App\Models\NgendevVideo;
use App\Models\NgendevVideoCategory;

class NgendevVideoController extends Controller
{
    public function index(Request $request)
    {
        $perPage    = (int) $request->input('per_page', 10);
        $search     = (string) $request->input('search', '');
        $categoryId = (string) $request->input('category_id', '');

        $categories = NgendevVideoCategory::orderBy('created_at', 'desc')->get();

        $query = NgendevVideo::with('category')
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

        $videos = $query->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html'  => view('ngendev_video.table', compact('videos'))->render(),
                'total' => $videos->total(),
            ]);
        }

        return view('ngendev_video.index', compact('categories', 'videos', 'perPage', 'search', 'categoryId'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'category_id'     => 'required|exists:ngendev_video_categories,id',
                'ai_prompt'       => 'required|string|max:2990',
                'ai_model'        => 'nullable|string|max:255',
                'video'           => 'required|file|mimes:mp4,mkv,avi,mov|max:50000',
                'video_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10000',
                'no_of_video'     => 'nullable|integer|min:1',
                'name_change'     => 'boolean',
                'image_hint'      => 'nullable|string|max:255|required_if:name_change,1',
            ], [
                'image_hint.required_if' => 'Image Hint is required when Name Change is enabled.',
            ]);

            $videoName     = null;
            $thumbnailName = null;

            if ($request->hasFile('video')) {
                $category     = NgendevVideoCategory::findOrFail($request->category_id);
                $destinationPath = public_path('upload/ngendev/videos/' . $category->category_name . '/category_video');

                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }

                $videoName = $this->uniqueFilename($destinationPath, $request->file('video')->getClientOriginalName());
                $request->file('video')->move($destinationPath, $videoName);
            }

            if ($request->hasFile('video_thumbnail')) {
                $category     = NgendevVideoCategory::findOrFail($request->category_id);
                $destinationThumbPath = public_path('upload/ngendev/videos/' . $category->category_name . '/video_thumbnail');

                if (!File::exists($destinationThumbPath)) {
                    File::makeDirectory($destinationThumbPath, 0777, true, true);
                }

                $thumbnailName = $this->uniqueFilename($destinationThumbPath, $request->file('video_thumbnail')->getClientOriginalName());
                $request->file('video_thumbnail')->move($destinationThumbPath, $thumbnailName);
            }

            $isNameChange = $request->has('name_change') ? 1 : 0;

            NgendevVideo::create([
                'category_id'     => $request->category_id,
                'ai_prompt'       => $request->ai_prompt,
                'ai_model'        => $request->ai_model,
                'no_of_video'     => $request->no_of_video ?? 1,
                'name_change'     => $isNameChange,
                'image_hint'      => $isNameChange ? $request->image_hint : null,
                'video_path'      => $videoName,
                'video_thumbnail' => $thumbnailName,
            ]);

            return $request->ajax()
                ? response()->json(['success' => true, 'message' => 'Ngendev video added successfully!'])
                : redirect()->route('ngendev-videos.index')->with('success', 'Ngendev video added successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errors], 422);
            }
            return redirect()->back()->withInput()->withErrors($e->errors());

        } catch (\Exception $e) {
            $msg = 'Something went wrong while adding the video. Please try again.';
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
                'category_id'     => 'required|exists:ngendev_video_categories,id',
                'ai_prompt'       => 'required|string|max:2990',
                'ai_model'        => 'nullable|string|max:255',
                'video'           => 'nullable|file|mimes:mp4,mkv,avi,mov|max:50000',
                'video_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10000',
                'no_of_video'     => 'required|integer|min:1',
                'name_change'     => 'boolean',
                'image_hint'      => 'nullable|string|max:255|required_if:name_change,1',
            ], [
                'image_hint.required_if' => 'Image Hint is required when Name Change is enabled.',
            ]);

            $video = NgendevVideo::with('category')->findOrFail($id);
            $oldCategory = $video->category ?? NgendevVideoCategory::find($video->category_id);
            $oldCategoryName = $oldCategory ? $oldCategory->category_name : null;

            $newCategory = NgendevVideoCategory::findOrFail($request->category_id);
            $newCategoryName = $newCategory->category_name;

            $isCategoryChanged = ($oldCategoryName !== null && $oldCategoryName !== $newCategoryName);

            $videoPath     = $video->video_path;
            $thumbnailPath = $video->video_thumbnail;

            $newVideoFolder = public_path('upload/ngendev/videos/' . $newCategoryName . '/category_video');
            $newThumbFolder = public_path('upload/ngendev/videos/' . $newCategoryName . '/video_thumbnail');

            // 1. Handle Video
            if ($request->hasFile('video')) {
                if ($videoPath && $oldCategoryName) {
                    $oldFilePath = public_path('upload/ngendev/videos/' . $oldCategoryName . '/category_video/' . $videoPath);
                    if (File::exists($oldFilePath)) {
                        File::delete($oldFilePath);
                    }
                }

                if (!File::exists($newVideoFolder)) {
                    File::makeDirectory($newVideoFolder, 0777, true, true);
                }

                $videoPath = $this->uniqueFilename($newVideoFolder, $request->file('video')->getClientOriginalName());
                $request->file('video')->move($newVideoFolder, $videoPath);
            } elseif ($isCategoryChanged && $videoPath && $oldCategoryName) {
                $oldFilePath = public_path('upload/ngendev/videos/' . $oldCategoryName . '/category_video/' . $videoPath);
                if (File::exists($oldFilePath)) {
                    if (!File::exists($newVideoFolder)) {
                        File::makeDirectory($newVideoFolder, 0777, true, true);
                    }
                    $newVideoFilename = $this->uniqueFilename($newVideoFolder, $videoPath);
                    File::move($oldFilePath, $newVideoFolder . '/' . $newVideoFilename);
                    $videoPath = $newVideoFilename;
                }
            }

            // 2. Handle Thumbnail
            if ($request->hasFile('video_thumbnail')) {
                if ($thumbnailPath && $oldCategoryName) {
                    $oldThumbFilePath = public_path('upload/ngendev/videos/' . $oldCategoryName . '/video_thumbnail/' . $thumbnailPath);
                    if (File::exists($oldThumbFilePath)) {
                        File::delete($oldThumbFilePath);
                    }
                }

                if (!File::exists($newThumbFolder)) {
                    File::makeDirectory($newThumbFolder, 0777, true, true);
                }

                $thumbnailPath = $this->uniqueFilename($newThumbFolder, $request->file('video_thumbnail')->getClientOriginalName());
                $request->file('video_thumbnail')->move($newThumbFolder, $thumbnailPath);
            } elseif ($isCategoryChanged && $thumbnailPath && $oldCategoryName) {
                $oldThumbFilePath = public_path('upload/ngendev/videos/' . $oldCategoryName . '/video_thumbnail/' . $thumbnailPath);
                if (File::exists($oldThumbFilePath)) {
                    if (!File::exists($newThumbFolder)) {
                        File::makeDirectory($newThumbFolder, 0777, true, true);
                    }
                    $newThumbFilename = $this->uniqueFilename($newThumbFolder, $thumbnailPath);
                    File::move($oldThumbFilePath, $newThumbFolder . '/' . $newThumbFilename);
                    $thumbnailPath = $newThumbFilename;
                }
            }

            $isNameChange = $request->has('name_change') ? 1 : 0;

            $video->update([
                'category_id'     => $request->category_id,
                'ai_prompt'       => $request->ai_prompt,
                'ai_model'        => $request->ai_model,
                'no_of_video'     => $request->no_of_video,
                'name_change'     => $isNameChange,
                'image_hint'      => $isNameChange ? $request->image_hint : null,
                'video_path'      => $videoPath,
                'video_thumbnail' => $thumbnailPath,
            ]);

            return $request->ajax()
                ? response()->json(['success' => true, 'message' => 'Ngendev video updated successfully!'])
                : redirect()->route('ngendev-videos.index')->with('success', 'Ngendev video updated successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errors], 422);
            }
            return redirect()->back()->withInput()->withErrors($e->errors());

        } catch (\Exception $e) {
            $msg = 'Something went wrong while updating the video. Please try again.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }
    }

    public function destroy($id)
    {
        $video = NgendevVideo::with('category')->findOrFail($id);
        $category = $video->category ?? NgendevVideoCategory::find($video->category_id);

        if ($category && $video->video_path) {
            $videoFilePath = public_path('upload/ngendev/videos/' . $category->category_name . '/category_video/' . $video->video_path);
            if (File::exists($videoFilePath)) {
                File::delete($videoFilePath);
            }
        }

        if ($category && $video->video_thumbnail) {
            $thumbnailFilePath = public_path('upload/ngendev/videos/' . $category->category_name . '/video_thumbnail/' . $video->video_thumbnail);
            if (File::exists($thumbnailFilePath)) {
                File::delete($thumbnailFilePath);
            }
        }

        $video->delete();

        return redirect()->route('ngendev-videos.index')->with('success', 'Selected video deleted successfully!');
    }

    public function indexing(Request $request)
    {
        $categoryId = $request->get('category_id');

        if (!$categoryId) {
            return response()->json(['videos' => []]);
        }

        $videos = NgendevVideo::with('category')->where('category_id', $categoryId)->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        if ($videos->count() > 0 && $videos->where('sort_order', 0)->count() > 0) {
            foreach ($videos as $index => $video) {
                if ($video->sort_order == 0) {
                    $video->update(['sort_order' => $index + 1]);
                }
            }
            $videos = NgendevVideo::with('category')->where('category_id', $categoryId)->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();
        }

        $transformedVideos = $videos->map(function ($video) {
            $videoData = $video->toArray();

            if ($video->video_path && $video->category) {
                $videoData['video_url'] = asset('upload/ngendev/videos/' . $video->category->category_name . '/category_video/' . $video->video_path);
            } else {
                $videoData['video_url'] = null;
            }

            if ($video->video_thumbnail && $video->category) {
                $videoData['thumbnail_url'] = asset('upload/ngendev/videos/' . $video->category->category_name . '/video_thumbnail/' . $video->video_thumbnail);
            } else {
                $videoData['thumbnail_url'] = null;
            }

            return $videoData;
        });

        return response()->json(['videos' => $transformedVideos]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:ngendev_video_categories,id',
            'order' => 'required|array',
            'order.*.id' => 'required|exists:ngendev_videos,id',
            'order.*.sort_order' => 'required|integer|min:1',
        ]);

        $categoryId = $request->category_id;
        $orderData = $request->order;

        foreach ($orderData as $item) {
            NgendevVideo::where('id', $item['id'])
                ->where('category_id', $categoryId)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true, 'message' => 'Video order updated successfully!']);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:ngendev_videos,id',
            'status' => 'required|boolean',
        ]);

        $video = NgendevVideo::findOrFail($request->id);
        $video->status = $request->status ? 1 : 0;
        $video->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully!']);
    }

    public function bulkToggleNameChange(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:ngendev_video_categories,id',
            'name_change' => 'required|in:0,1',
        ]);

        $value = (int) $request->name_change;
        $updateData = ['name_change' => $value];
        if (!$value) {
            $updateData['image_hint'] = null;
        }
        $count = NgendevVideo::where('category_id', $request->category_id)
            ->update($updateData);

        return response()->json([
            'success'       => true,
            'message'       => "Updated {$count} video(s) — name_change set to " . ($value ? 'true' : 'false') . '.',
            'updated_count' => $count,
            'name_change'   => (bool) $value,
        ]);
    }

    public function categoryNameChangeStats(Request $request)
    {
        $stats = NgendevVideo::selectRaw('category_id, SUM(name_change) as true_count, COUNT(*) as total')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        return response()->json(['stats' => $stats]);
    }
}
