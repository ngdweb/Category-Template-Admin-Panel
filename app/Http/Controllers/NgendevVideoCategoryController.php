<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\NgendevVideoCategory;
use App\Models\NgendevVideo;
use App\Models\AiVideoNgdSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class NgendevVideoCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = (string) $request->input('search', '');

        $query = NgendevVideoCategory::query();

        if ($search !== '') {
            $query->where('category_name', 'like', '%' . $search . '%');
        }

        $categories = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        $coupleActive = AiVideoNgdSetting::value('couple_active');

        if ($request->ajax()) {
            return response()->json([
                'html'  => view('ngendev_video_category.table', compact('categories'))->render(),
                'total' => $categories->total(),
            ]);
        }

        return view('ngendev_video_category.index', compact('categories', 'perPage', 'search', 'coupleActive'));
    }

    public function create()
    {
        $coupleActive = (bool) (AiVideoNgdSetting::value('couple_active') ?? 0);
        return view('ngendev_video_category.form', compact('coupleActive'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255|unique:ngendev_video_categories,category_name',
            'status' => 'boolean',
            'type' => 'required|in:Solo,Couple',
            'category_image' => 'required',
            'category_image.*' => 'image|mimes:jpg,jpeg,png,webp|max:10000',
        ]);

        $categoryFolder = $request->category_name;
        $destinationPath = public_path("upload/ngendev/videos/{$categoryFolder}/category_thumbnail_image");

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $images = [];

        if ($request->hasFile('category_image')) {
            foreach ($request->file('category_image') as $file) {
                $fileName = $this->uniqueFilename($destinationPath, $file->getClientOriginalName());
                $file->move($destinationPath, $fileName);
                $images[] = $fileName;
            }
        }

        $status = $request->has('status') ? 1 : 0;
        if ($request->type === 'Solo') {
            $status = 1;
        } elseif ($request->type === 'Couple') {
            $coupleActive = AiVideoNgdSetting::value('couple_active');
            if (!$coupleActive) {
                $status = 0;
            }
        }

        $category = NgendevVideoCategory::create([
            'category_name' => $request->category_name,
            'status' => $status,
            'type' => $request->type,
            'category_image' => json_encode($images),
            'sort_order' => 0,
        ]);

        if ($request->boolean('notify_after_save')) {
            if ($request->boolean('notify_inline')) {
                $request->validate([
                    'notif_title'        => 'required|string|max:191',
                    'notif_description'  => 'nullable|string|max:2990',
                    'notif_image_url'    => 'nullable|url|max:500',
                    'notif_scheduled_at' => 'nullable|date',
                ]);

                $scheduledAt = $request->filled('notif_scheduled_at')
                    ? \Illuminate\Support\Carbon::parse($request->notif_scheduled_at)
                    : \Illuminate\Support\Carbon::now()->addMinutes(
                        (int) \App\Models\NotificationSetting::current()->default_delay_minutes
                      );

                if ($scheduledAt->isPast()) {
                    $scheduledAt = \Illuminate\Support\Carbon::now()->addSeconds(30);
                }

                $targetIds = $request->filled('firebase_project_ids')
                    ? array_values(array_map('intval', (array) $request->firebase_project_ids))
                    : null;

                \App\Models\ScheduledNotification::create([
                    'module'             => 'ngendev_video',
                    'type'               => 'vid',
                    'category_id'        => $category->id,
                    'title'              => $request->notif_title,
                    'description'        => $request->notif_description,
                    'image_url'          => $request->filled('notif_image_url') ? trim($request->notif_image_url) : null,
                    'click_action'       => 'OPEN_NGENDEV_VIDEO_CATEGORY',
                    'screen'             => 'ngendev_video_category_detail',
                    'extra_data'         => [
                        'category_name' => $category->category_name,
                        'deep_link'     => 'app://ngendev_video/category/' . $category->id,
                    ],
                    'target_project_ids' => $targetIds,
                    'scheduled_at'       => $scheduledAt,
                    'status'             => 'pending',
                ]);

                $delay = max(1, (int) \Illuminate\Support\Carbon::now()->diffInSeconds($scheduledAt, false));
                \App\Support\BackgroundProcess::spawnArtisan('notifications:dispatch-due --delay=' . $delay);
                Cache::put('notifications_dispatcher_last_run', time(), 600);

                return redirect()->route('ngendev-video-categories.index')->with('success', 'Category added! Notification scheduled for ' . $scheduledAt->format('d M Y, h:i A') . '.');
            }

            session()->flash('notify_project_ids', $request->input('firebase_project_ids', []));
            return redirect()->route('notifications.form', ['module' => 'ngendev_video', 'id' => $category->id]);
        }

        return redirect()->route('ngendev-video-categories.index')->with('success', 'Category added successfully!');
    }

    public function edit($id)
    {
        $category = NgendevVideoCategory::findOrFail($id);
        $coupleActive = (bool) (AiVideoNgdSetting::value('couple_active') ?? 0);
        return view('ngendev_video_category.form', compact('category', 'coupleActive'));
    }

    public function update(Request $request, $id)
    {
        $category = NgendevVideoCategory::findOrFail($id);

        $request->validate([
            'category_name' => 'required|string|max:255|unique:ngendev_video_categories,category_name,' . $id,
            'status' => 'boolean',
            'type' => 'required|in:Solo,Couple',
            'category_image.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10000',
        ]);

        $oldName = $category->category_name;
        $newName = $request->category_name;

        $oldFolder = public_path('upload/ngendev/videos/' . $oldName);
        $newFolder = public_path('upload/ngendev/videos/' . $newName);

        if ($oldName !== $newName && File::exists($oldFolder)) {
            File::move($oldFolder, $newFolder);
        } elseif (!File::exists($newFolder)) {
            File::makeDirectory($newFolder, 0777, true);
        }

        $destinationPath = $newFolder . '/category_thumbnail_image';
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        if ($request->hasFile('category_image')) {
            if ($category->category_image) {
                $oldImages = json_decode($category->category_image, true);
                foreach ((array) $oldImages as $img) {
                    $oldFile = $destinationPath . '/' . $img;
                    if (File::exists($oldFile)) {
                        File::delete($oldFile);
                    }
                }
            }

            $images = [];
            foreach ($request->file('category_image') as $file) {
                $fileName = $this->uniqueFilename($destinationPath, $file->getClientOriginalName());
                $file->move($destinationPath, $fileName);
                $images[] = $fileName;
            }
        } else {
            $images = $category->category_image ? json_decode($category->category_image, true) : [];
        }

        $status = $request->has('status') ? $request->status : 0;
        if ($request->type === 'Solo') {
            $status = 1;
        } elseif ($request->type === 'Couple') {
            $coupleActive = AiVideoNgdSetting::value('couple_active');
            if (!$coupleActive) {
                $status = 0;
            }
        }

        $category->update([
            'category_name' => $newName,
            'status' => $status,
            'type' => $request->type,
            'category_image' => json_encode($images),
        ]);

        return redirect()->route('ngendev-video-categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy($id)
    {
        $category = NgendevVideoCategory::with('videos')->findOrFail($id);

        $categoryFolder = $category->category_name;
        $folderPath = public_path("upload/ngendev/videos/{$categoryFolder}");

        if (File::exists($folderPath)) {
            File::deleteDirectory($folderPath);
        }

        if ($category->videos) {
            foreach ($category->videos as $video) {
                $video->delete();
            }
        }

        // Remove Top Slider categories (video) that point to this Ngendev video category
        \App\Models\TopSliderCategory::deleteBySource('video', $category->id);

        $category->delete();

        return redirect()->route('ngendev-video-categories.index')->with('success', 'Category deleted successfully!');
    }

    public function indexing(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('ngendev-video-categories.index');
        }

        $categories = NgendevVideoCategory::orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get();

        if ($categories->count() > 0 && $categories->where('sort_order', 0)->count() > 0) {
            foreach ($categories as $index => $category) {
                if ($category->sort_order == 0) {
                    $category->update(['sort_order' => $index + 1]);
                }
            }
            $categories = NgendevVideoCategory::orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get();
        }

        return response()->json([
            'categories' => $categories
        ]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:ngendev_video_categories,id',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->order as $item) {
            NgendevVideoCategory::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category order updated successfully!',
            'redirect_url' => route('ngendev-video-categories.index'),
        ]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:ngendev_video_categories,id',
            'status' => 'required|boolean',
        ]);

        $category = NgendevVideoCategory::find($request->id);

        if ($category->type === 'Couple' && $request->status == 1) {
            $coupleActive = AiVideoNgdSetting::value('couple_active');
            if (!$coupleActive) {
                return response()->json(['success' => false, 'message' => 'Cannot activate category because global Couple Status is OFF!']);
            }
        }

        $category->status = $request->status;
        $category->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully!']);
    }

    public function updateType(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:ngendev_video_categories,id',
            'type' => 'required|in:Solo,Couple',
        ]);

        $category = NgendevVideoCategory::find($request->id);
        $category->type = $request->type;

        if ($request->type === 'Solo') {
            $category->status = 1;
        } elseif ($request->type === 'Couple') {
            $coupleActive = AiVideoNgdSetting::value('couple_active');
            $category->status = $coupleActive ? 1 : 0;
        }

        $category->save();

        return response()->json(['success' => true, 'message' => 'Category type updated successfully!']);
    }

    public function updateCoupleStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $setting = AiVideoNgdSetting::first() ?? new AiVideoNgdSetting();

        $setting->couple_active = (int) $request->status;
        $setting->save();

        NgendevVideoCategory::where('type', 'Couple')->update(['status' => (int) $request->status]);

        return response()->json(['success' => true, 'message' => 'Global couple status updated successfully!']);
    }
}
