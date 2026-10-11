<?php

namespace App\Http\Controllers;

use App\Models\AiBabyVideo;
use App\Models\AiVideoCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AiBabyVideoController extends Controller
{
    public function index(Request $request)
    {
        $perPage    = (int) $request->input('per_page', 10);
        $search     = (string) $request->input('search', '');
        $categoryId = (string) $request->input('category_id', '');

        $categories = AiVideoCategory::where('status', 1)->orderBy('category_name')->get();

        $query = AiBabyVideo::with('category');

        if ($categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ai_prompt', 'like', "%{$search}%")
                    ->orWhere('video_title', 'like', "%{$search}%");
            });
        }

        $videos = $query->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html'  => view('ai_baby_video.videos.table', compact('videos'))->render(),
                'total' => $videos->total(),
            ]);
        }

        return view('ai_baby_video.videos.index', compact('categories', 'videos', 'perPage', 'search', 'categoryId'));
    }

    public function create()
    {
        // Show all categories (including inactive) so videos can be added to any category.
        $categories = AiVideoCategory::orderBy('category_name')->get();
        return view('ai_baby_video.videos.form', compact('categories'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'category_id'     => 'required|exists:ai_baby_video_categories,id',
                'video_title'     => 'required|string|max:255',
                'video_path'      => 'required|mimes:mp4,mov,avi,wmv|max:51200',
                'video_thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'ai_prompt'       => 'nullable|string|max:2990',
                'name_change'     => 'boolean',
            ]);

            $category = AiVideoCategory::findOrFail($request->category_id);

            $videoPath     = null;
            $thumbnailPath = null;

            if ($request->hasFile('video_path')) {
                $file     = $request->file('video_path');
                $videoDestPath = public_path('upload/AI Baby Video/' . $category->category_name . '/video');

                if (!File::exists($videoDestPath)) {
                    File::makeDirectory($videoDestPath, 0777, true);
                }

                $videoPath = $this->uniqueFilename($videoDestPath, $file->getClientOriginalName());
                $file->move($videoDestPath, $videoPath);
            }

            if ($request->hasFile('video_thumbnail')) {
                $thumbFile     = $request->file('video_thumbnail');
                $thumbDestPath = public_path('upload/AI Baby Video/' . $category->category_name . '/video thumbanail');

                if (!File::exists($thumbDestPath)) {
                    File::makeDirectory($thumbDestPath, 0777, true);
                }

                $thumbnailPath = $this->uniqueFilename($thumbDestPath, $thumbFile->getClientOriginalName());
                $thumbFile->move($thumbDestPath, $thumbnailPath);
            }

            AiBabyVideo::create([
                'category_id'     => $request->category_id,
                'video_title'     => $request->video_title,
                'video_path'      => $videoPath,
                'video_thumbnail' => $thumbnailPath,
                'ai_prompt'       => $request->ai_prompt,
                'name_change'     => $request->has('name_change') ? 1 : 0,
            ]);

            $category->sort_order = 0;
            $category->save();

            return redirect()->route('ai-baby-video.videos.index')->with('success', 'Video added successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $errors, 'errors' => $e->errors()], 422);
            }
            return redirect()->back()->withInput()->with('error', $errors)->withErrors($e->errors());

        } catch (\Exception $e) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Something went wrong while adding the video. Please try again.'], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Something went wrong while adding the video. Please try again.');
        }
    }

    public function edit($id)
    {
        $video = AiBabyVideo::findOrFail($id);
        // Show all categories (including inactive) so the current one always appears and videos can be added to any category.
        $categories = AiVideoCategory::orderBy('category_name')->get();
        return view('ai_baby_video.videos.form', compact('video', 'categories'));
    }

    public function update(Request $request, $id)
    {
      try {
        $video = AiBabyVideo::findOrFail($id);

        $request->validate([
            'category_id'     => 'required|exists:ai_baby_video_categories,id',
            'video_title'     => 'required|string|max:255',
            'video_path'      => 'nullable|mimes:mp4,mov,avi,wmv|max:51200',
            'video_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'ai_prompt'       => 'nullable|string|max:2990',
            'name_change'     => 'boolean',
        ]);

        $category = AiVideoCategory::findOrFail($request->category_id);

        $videoPath = $video->video_path;
        $thumbnailPath = $video->video_thumbnail;
        $basePathLegacy = 'upload/AI Baby Video/' . $category->category_name . '/';

        if ($request->hasFile('video_path')) {
            // Delete old video
            if ($video->video_path) {
                if (file_exists(public_path($video->video_path))) {
                    unlink(public_path($video->video_path));
                } elseif (file_exists(public_path($basePathLegacy . $video->video_path))) {
                    unlink(public_path($basePathLegacy . $video->video_path));
                } elseif (file_exists(public_path('upload/AI Baby Video/' . $video->category->category_name . '/video/' . $video->video_path))) {
                    unlink(public_path('upload/AI Baby Video/' . $video->category->category_name . '/video/' . $video->video_path));
                }
            }

            $file = $request->file('video_path');
            $videoDestPath = public_path('upload/AI Baby Video/' . $category->category_name . '/video');

            if (!File::exists($videoDestPath)) {
                File::makeDirectory($videoDestPath, 0777, true);
            }

            $videoPath = $this->uniqueFilename($videoDestPath, $file->getClientOriginalName());
            $file->move($videoDestPath, $videoPath);
        } elseif ($request->has('remove_video') && $request->remove_video == '1') {
            if ($video->video_path) {
                if (file_exists(public_path($video->video_path))) {
                    unlink(public_path($video->video_path));
                } elseif (file_exists(public_path($basePathLegacy . $video->video_path))) {
                    unlink(public_path($basePathLegacy . $video->video_path));
                } elseif (file_exists(public_path('upload/AI Baby Video/' . $video->category->category_name . '/video/' . $video->video_path))) {
                    unlink(public_path('upload/AI Baby Video/' . $video->category->category_name . '/video/' . $video->video_path));
                }
            }
            $videoPath = null;
        }

        if ($request->hasFile('video_thumbnail')) {
            // Delete old thumbnail
            if ($video->video_thumbnail) {
                if (file_exists(public_path($video->video_thumbnail))) {
                    unlink(public_path($video->video_thumbnail));
                } elseif (file_exists(public_path($basePathLegacy . $video->video_thumbnail))) {
                    unlink(public_path($basePathLegacy . $video->video_thumbnail));
                } elseif (file_exists(public_path('upload/AI Baby Video/' . $video->category->category_name . '/video thumbanail/' . $video->video_thumbnail))) {
                    unlink(public_path('upload/AI Baby Video/' . $video->category->category_name . '/video thumbanail/' . $video->video_thumbnail));
                }
            }

            $thumbFile = $request->file('video_thumbnail');
            $thumbDestPath = public_path('upload/AI Baby Video/' . $category->category_name . '/video thumbanail');

            if (!File::exists($thumbDestPath)) {
                File::makeDirectory($thumbDestPath, 0777, true);
            }

            $thumbnailPath = $this->uniqueFilename($thumbDestPath, $thumbFile->getClientOriginalName());
            $thumbFile->move($thumbDestPath, $thumbnailPath);
        } elseif ($request->has('remove_thumbnail') && $request->remove_thumbnail == '1') {
            if ($video->video_thumbnail) {
                if (file_exists(public_path($video->video_thumbnail))) {
                    unlink(public_path($video->video_thumbnail));
                } elseif (file_exists(public_path($basePathLegacy . $video->video_thumbnail))) {
                    unlink(public_path($basePathLegacy . $video->video_thumbnail));
                } elseif (file_exists(public_path('upload/AI Baby Video/' . $video->category->category_name . '/video thumbanail/' . $video->video_thumbnail))) {
                    unlink(public_path('upload/AI Baby Video/' . $video->category->category_name . '/video thumbanail/' . $video->video_thumbnail));
                }
            }
            $thumbnailPath = null;
        }

        $video->update([
            'category_id'     => $request->category_id,
            'video_title'     => $request->video_title,
            'video_path'      => $videoPath,
            'video_thumbnail' => $thumbnailPath,
            'ai_prompt'       => $request->ai_prompt,
            'name_change'     => $request->has('name_change') ? 1 : 0,
        ]);

        // Reset sort_order to 0 so this category floats to the top of the API response
        $category->sort_order = 0;
        $category->save();

        return redirect()->route('ai-baby-video.videos.index')->with('success', 'Video updated successfully!');

      } catch (\Illuminate\Validation\ValidationException $e) {
          $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
          if ($request->ajax() || $request->expectsJson()) {
              return response()->json(['success' => false, 'message' => $errors, 'errors' => $e->errors()], 422);
          }
          return redirect()->back()->withInput()->with('error', $errors)->withErrors($e->errors());

      } catch (\Exception $e) {
          if ($request->ajax() || $request->expectsJson()) {
              return response()->json(['success' => false, 'message' => 'Something went wrong while updating the video. Please try again.'], 500);
          }
          return redirect()->back()->withInput()->with('error', 'Something went wrong while updating the video. Please try again.');
      }
    }

    public function destroy($id)
    {
        $video = AiBabyVideo::findOrFail($id);
        $category = AiVideoCategory::find($video->category_id);
        $categoryName = $category ? $category->category_name : '';

        $basePathLegacy = 'upload/AI Baby Video/' . $categoryName . '/';
        $videoDir = public_path('upload/AI Baby Video/' . $categoryName . '/video');
        $thumbDir = public_path('upload/AI Baby Video/' . $categoryName . '/video thumbanail');

        if ($video->video_path) {
            if (file_exists(public_path($video->video_path))) {
                unlink(public_path($video->video_path));
            } elseif (file_exists(public_path($basePathLegacy . $video->video_path))) {
                unlink(public_path($basePathLegacy . $video->video_path));
            } elseif (file_exists($videoDir . '/' . $video->video_path)) {
                unlink($videoDir . '/' . $video->video_path);
            }
        }

        if ($video->video_thumbnail) {
            if (file_exists(public_path($video->video_thumbnail))) {
                unlink(public_path($video->video_thumbnail));
            } elseif (file_exists(public_path($basePathLegacy . $video->video_thumbnail))) {
                unlink(public_path($basePathLegacy . $video->video_thumbnail));
            } elseif (file_exists($thumbDir . '/' . $video->video_thumbnail)) {
                unlink($thumbDir . '/' . $video->video_thumbnail);
            }
        }

        // Check and delete empty video directory
        if (is_dir($videoDir) && count(glob($videoDir . '/*')) === 0) {
            rmdir($videoDir);
        }

        // Check and delete empty thumbnail directory
        if (is_dir($thumbDir) && count(glob($thumbDir . '/*')) === 0) {
            rmdir($thumbDir);
        }

        $video->delete();

        return redirect()->route('ai-baby-video.videos.index')->with('success', 'Video deleted successfully!');
    }
}
