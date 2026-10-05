<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use App\Models\AiVideoSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use File;

class SubcategoryController extends Controller
{
    private function getModel(Request $request)
    {
        return $request->get('origin') === 'video' ? AiVideoSubcategory::class : Subcategory::class;
    }

    public function index(Request $request)
    {
        $model = $this->getModel($request);
        $subcategories = $model::latest()->paginate(10);
        return view('category-template.index', compact('subcategories'));
    }

    public function form(Request $request, $id = null)
    {
        $model = $this->getModel($request);
        $subcategory = $id ? $model::findOrFail($id) : new $model();

        $videoCategories = \App\Models\AiVideoCategory::where('status', 1)->pluck('category_name')->toArray();
        $imageCategories = \App\Models\AiImageCategory::pluck('name')->toArray();
        $hardcodedCategories = ['Newborn Baby', 'Baby Bumps', 'Toddler Photoshoot', 'Festival Photoshoot', 'Birthday Photo', 'Unique Style', 'Invitation card'];

        // If it's video origin, only show video categories
        if ($request->get('origin') === 'video') {
            $categories = $videoCategories;
        } else {
            // Otherwise show image categories (allowing for hardcoded ones for now)
            $categories = array_unique(array_merge($hardcodedCategories, $imageCategories));
        }

        if (!$id && $request->has('category_name')) {
            $subcategory->category_name = $request->get('category_name');
        }

        return view('category-template.form', compact('subcategory', 'categories'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_thumbnail_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $model = $this->getModel($request);
        $subcategory = $id ? $model::findOrFail($id) : new $model();
        $oldTitle = $id ? $subcategory->title : null;
        $oldThumbnail = $id ? $subcategory->category_thumbnail_image : null;
        $oldCategory = $id ? $subcategory->category_name : null;

        $subcategory->category_name = $request->category_name;
        $subcategory->title = $request->title;
        $subcategory->description = $request->description;
        $subcategory->trending = $request->has('trending') ? 1 : 0;

        $is_video = $request->get('origin') === 'video';
        $pathPrefix = $is_video ? 'upload/AI Baby Video/' : 'upload/';

        $categoryFolder = $request->category_name;
        $subcategoryFolder = $subcategory->title;

        // Handle folder rename if category or title changed
        if ($id && $oldTitle && ($oldTitle !== $subcategory->title || $oldCategory !== $request->category_name)) {
            $oldFolder = public_path($pathPrefix . $oldCategory . '/' . $oldTitle);
            $newFolder = public_path($pathPrefix . $categoryFolder . '/' . $subcategoryFolder);
            $newFolderParent = dirname($newFolder);

            if (!is_dir($newFolderParent)) {
                mkdir($newFolderParent, 0755, true);
            }

            if (file_exists($oldFolder)) {
                rename($oldFolder, $newFolder);
            }
        }

        // Create thumbnail folder if it doesn't exist
        $thumbFolder = public_path("{$pathPrefix}{$categoryFolder}/{$subcategoryFolder}/category_thumbnail");
        if (!is_dir($thumbFolder)) {
            mkdir($thumbFolder, 0755, true);
        }

        // Handle thumbnail image upload
        if ($request->hasFile('category_thumbnail_image')) {
            $file = $request->file('category_thumbnail_image');
            $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $generatedName = Str::slug($baseName) ?: 'thumbnail';
            $originalName = $generatedName . '-' . time() . '-' . Str::random(6) . '.' . $extension;

            // Remove old thumbnail if exists
            if ($id && $oldThumbnail) {
                $oldThumbPath = public_path($pathPrefix . $oldCategory . '/' . $oldTitle . '/category_thumbnail/' . $oldThumbnail);
                if (file_exists($oldThumbPath)) {
                    unlink($oldThumbPath);
                }
            }

            $file->move($thumbFolder, $originalName);
            $subcategory->category_thumbnail_image = $originalName;
        } elseif ($request->remove_thumbnail && $id) {
            // Remove thumbnail if checkbox is checked
            if ($subcategory->category_thumbnail_image) {
                $currentThumbPath = public_path($pathPrefix . $categoryFolder . '/' . $subcategoryFolder . '/category_thumbnail/' . $subcategory->category_thumbnail_image);
                if (file_exists($currentThumbPath)) {
                    unlink($currentThumbPath);
                }
            }
            $subcategory->category_thumbnail_image = null;
        }

        if ($request->get('origin') === 'video') {
            $subcategory->videos = $subcategory->videos ?? json_encode([]);
        } else {
            $subcategory->images = $subcategory->images ?? json_encode([]);
        }
        $subcategory->save();

        Cache::forget('sidebar.subcategories_grouped');
        Cache::forget('sidebar.ai_image_categories');

        $redirectParams = ['id' => $subcategory->id];
        if ($request->has('origin')) {
            $redirectParams['origin'] = $request->origin;
        }

        return redirect()
            ->route('subcategories.show', $redirectParams)
            ->with('success', $id ? 'Subcategory updated!' : 'Subcategory created!');
    }

    public function show(Request $request, $id)
    {
        $model = $this->getModel($request);
        $subcategory = $model::findOrFail($id);

        $is_video = $request->get('origin') === 'video';
        $pathPrefix = $is_video ? 'AI Baby Video/' : '';
        $dataJson = $is_video ? $subcategory->videos : $subcategory->images;
        $imagesArray = json_decode($dataJson, true) ?? [];

        $imageUrls = array_map(function ($img) use ($subcategory, $pathPrefix, $is_video) {
            $basePath = 'upload/' . $pathPrefix . $subcategory->category_name . '/' . $subcategory->title . '/';
            $fileUrl = $is_video ? asset($basePath . 'video/' . ($img['file'] ?? '')) : asset($basePath . ($img['file'] ?? ''));
            return [
                'url' => $fileUrl,
                'prompt' => $img['prompt'] ?? '',
                'image_title' => $img['image_title'] ?? '',
                'name_change' => $img['name_change'] ?? false,
            ];
        }, $imagesArray);

        return view('category-template.show', compact('subcategory', 'imageUrls'));
    }

    public function destroy(Request $request, $id)
    {
        $model = $this->getModel($request);
        $subcategory = $model::findOrFail($id);
        $is_video = $request->get('origin') === 'video';
        $pathPrefix = $is_video ? 'upload/AI Baby Video/' : 'upload/';
        $folderPath = public_path($pathPrefix . $subcategory->category_name . '/' . $subcategory->title);
        if (File::exists($folderPath)) {
            File::deleteDirectory($folderPath);
        }

        // Remove the Baby AI Home Slider entry that points to this image subcategory
        if (!$is_video) {
            \App\Models\BabyAiHomeSlider::deleteBySource('image', $subcategory->id);
        }

        $subcategory->delete();

        Cache::forget('sidebar.subcategories_grouped');
        Cache::forget('sidebar.ai_image_categories');

        $redirectParams = [];
        if ($request->has('origin')) {
            $redirectParams['origin'] = $request->origin;
        }

        $type = $request->get('origin') === 'video' ? 'videos' : 'images';
        return redirect()->route('subcategories.index', $redirectParams)->with('success', "Subcategory and its {$type} deleted successfully!");
    }

    public function addDetailsForm(Request $request, $id)
    {
        $model = $this->getModel($request);
        $subcategory = $model::findOrFail($id);
        return view('category-template.addDetailsForm', compact('subcategory'));
    }

    // public function saveDetails(Request $request, $id)
    // {
    //     $subcategory = Subcategory::findOrFail($id);

    //     $request->validate([
    //         'category_name' => 'required|string|max:255',
    //         'title' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'category_thumbnail_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
    //         'images.*' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
    //         'prompts.*' => 'nullable|string|max:2555',
    //         'image_title.*' => 'nullable|string|max:255',
    //         'name_change_row.*' => 'nullable|boolean',
    //         'existing_prompts.*' => 'nullable|string|max:2555',
    //         'existing_image_title.*' => 'nullable|string|max:255',
    //         'remove_images' => 'nullable|array',
    //         'replace_images' => 'nullable|array',
    //     ]);

    //     $oldTitle = $subcategory->title;
    //     $oldCategory = $subcategory->category_name;
    //     $oldThumbnail = $subcategory->category_thumbnail_image;

    //     $subcategory->category_name = $request->category_name;
    //     $subcategory->title = $request->title;
    //     $subcategory->description = $request->description;

    //     $categoryFolder = $subcategory->category_name;
    //     $subcategoryFolder = $subcategory->title;

    //     if (($oldCategory !== $categoryFolder || $oldTitle !== $subcategoryFolder) && $oldCategory && $oldTitle) {
    //         $oldFolder = public_path('upload/' . $oldCategory . '/' . $oldTitle);
    //         $newFolder = public_path('upload/' . $categoryFolder . '/' . $subcategoryFolder);
    //         $newFolderParent = dirname($newFolder);
    //         if (!is_dir($newFolderParent)) {
    //             mkdir($newFolderParent, 0755, true);
    //         }
    //         if (file_exists($oldFolder)) {
    //             rename($oldFolder, $newFolder);
    //         }
    //     }

    //     $thumbFolder = public_path("upload/{$categoryFolder}/{$subcategoryFolder}/category_thumbnail");
    //     if (!is_dir($thumbFolder)) {
    //         mkdir($thumbFolder, 0755, true);
    //     }

    //     if ($request->hasFile('category_thumbnail_image')) {
    //         $file = $request->file('category_thumbnail_image');
    //         $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //         $extension = $file->getClientOriginalExtension();
    //         $generatedName = Str::slug($baseName) ?: 'thumbnail';
    //         $originalName = $generatedName . '-' . time() . '-' . Str::random(6) . '.' . $extension;

    //         // Remove old thumbnail
    //         if ($oldThumbnail) {
    //             $oldThumbPath = public_path('upload/' . $categoryFolder . '/' . $subcategoryFolder . '/category_thumbnail/' . $oldThumbnail);
    //             if (file_exists($oldThumbPath)) {
    //                 unlink($oldThumbPath);
    //             }
    //         }

    //         $file->move($thumbFolder, $originalName);
    //         $subcategory->category_thumbnail_image = $originalName;
    //     } elseif ($request->remove_thumbnail) {
    //         if ($subcategory->category_thumbnail_image) {
    //             $currentThumbPath = public_path('upload/' . $categoryFolder . '/' . $subcategoryFolder . '/category_thumbnail/' . $subcategory->category_thumbnail_image);
    //             if (file_exists($currentThumbPath)) {
    //                 unlink($currentThumbPath);
    //             }
    //         }
    //         $subcategory->category_thumbnail_image = null;
    //     }

    //     $uploadFolder = public_path("upload/{$categoryFolder}/{$subcategoryFolder}");
    //     if (!is_dir($uploadFolder)) {
    //         mkdir($uploadFolder, 0755, true);
    //     }

    //     $imagesData = $subcategory->images ? json_decode($subcategory->images, true) : [];

    //     if ($request->remove_images) {
    //         foreach ($imagesData as $key => $img) {
    //             if (in_array($img['file'], $request->remove_images)) {
    //                 $filePath = $uploadFolder . '/' . $img['file'];
    //                 if (file_exists($filePath)) {
    //                     unlink($filePath);
    //                 }
    //                 unset($imagesData[$key]);
    //             }
    //         }
    //         $imagesData = array_values($imagesData);
    //     }

    //     if ($imagesData && is_array($imagesData)) {
    //         foreach ($imagesData as $index => &$img) {
    //             $fileName = $img['file'];
    //             if ($request->existing_prompts[$fileName] ?? false) {
    //                 $img['prompt'] = $request->existing_prompts[$fileName];
    //             }
    //             if ($request->existing_image_title[$fileName] ?? false) {
    //                 $img['image_title'] = $request->existing_image_title[$fileName];
    //             }
    //         }
    //     }

    //     if ($request->replace_images && is_array($request->replace_images)) {
    //         foreach ($request->replace_images as $oldFileName => $newFile) {
    //             if ($newFile && $newFile->isValid()) {
    //                 foreach ($imagesData as &$img) {
    //                     if ($img['file'] === $oldFileName) {
    //                         $oldPath = $uploadFolder . '/' . $oldFileName;
    //                         if (file_exists($oldPath)) {
    //                             unlink($oldPath);
    //                         }
    //                         $newFileName = $newFile->getClientOriginalName();
    //                         $newFile->move($uploadFolder, $newFileName);
    //                         $img['file'] = $newFileName;
    //                         break;
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     if ($request->hasFile('images')) {
    //         foreach ($request->file('images') as $index => $file) {
    //             $originalName = $file->getClientOriginalName();
    //             $prompt = $request->prompts[$index] ?? '';
    //             $imageTitle = $request->image_title[$index] ?? '';
    //             $nameChange = $request->name_change_row[$index] ?? false;

    //             $file->move($uploadFolder, $originalName);

    //             $imagesData[] = [
    //                 'file' => $originalName,
    //                 'prompt' => $prompt,
    //                 'image_title' => $imageTitle,
    //                 'name_change' => $nameChange ? true : false,
    //             ];
    //         }
    //     }

    //     $subcategory->images = !empty($imagesData) ? json_encode($imagesData) : null;
    //     $subcategory->save();

    //     return redirect()->route('subcategories.show', $subcategory->id)->with('success', 'Subcategory images & details saved successfully!');
    // }

    public function saveDetails(Request $request, $id)
    {
      try {
        $model = $this->getModel($request);
        $subcategory = $model::findOrFail($id);

        $is_video = $request->get('origin') === 'video';
        $pathPrefix = $is_video ? 'upload/AI Baby Video/' : 'upload/';

        $titleField = $is_video ? 'video_title' : 'image_title';
        $existingTitleField = $is_video ? 'existing_video_title' : 'existing_image_title';

        $rules = [
            'description' => 'nullable|string',
            'category_thumbnail_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
            'images.*' => $is_video ? 'nullable|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv|max:51200' : 'nullable|image|mimes:webp|max:10240',
            'replace_images.*' => $is_video ? 'nullable' : 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:10240',
            'prompts.*' => 'nullable|string|max:2990',
            $titleField . '.*' => 'nullable|string|max:255',
            'name_change_row.*' => 'nullable|boolean',
            'existing_prompts.*' => 'nullable|string|max:2990',
            $existingTitleField . '.*' => 'nullable|string|max:255',
            'remove_images' => 'nullable|array',
        ];

        $request->validate($rules);

        // Keep track of old values for safe cleanup
        $oldCategory = $subcategory->getOriginal('category_name');
        $oldTitle = $subcategory->getOriginal('title');
        $oldThumbnail = $subcategory->getOriginal('category_thumbnail_image');

        // Update simple fields
        $subcategory->description = $request->description;
        $subcategory->trending = $request->has('trending') ? 1 : 0;

        // Update Title
        if ($request->has('title')) {
            $subcategory->title = $request->title;
        }
        $subcategory->save(); // Save immediately to update model state for folder logic

        // Current resolved folders (New Values)
        $categoryFolder = $subcategory->category_name;
        $subcategoryFolder = $subcategory->title;

        // Handle Folder Rename Logic for saveDetails
        if ($oldTitle && $oldTitle !== $subcategory->title) {
            $oldFolder = public_path($pathPrefix . $oldCategory . '/' . $oldTitle);
            $newFolder = public_path($pathPrefix . $categoryFolder . '/' . $subcategoryFolder);
            $newFolderParent = dirname($newFolder);

            if (!is_dir($newFolderParent)) {
                @mkdir($newFolderParent, 0755, true);
            }

            if (file_exists($oldFolder)) {
                rename($oldFolder, $newFolder);
            }
        }

        // Base folders (New Paths)
        $baseFolder = public_path("{$pathPrefix}{$categoryFolder}/{$subcategoryFolder}");
        $catThumbFolder = public_path("{$pathPrefix}{$categoryFolder}/{$subcategoryFolder}/category_thumbnail");

        // Video specific folders
        $videoFolder = $is_video ? public_path("{$pathPrefix}{$categoryFolder}/{$subcategoryFolder}/video") : $baseFolder;
        $videoThumbFolder = $is_video ? public_path("{$pathPrefix}{$categoryFolder}/{$subcategoryFolder}/video thumbnail") : $baseFolder;

        // Ensure folders exist
        if (!is_dir($baseFolder)) {
            @mkdir($baseFolder, 0755, true);
        }
        if (!is_dir($catThumbFolder)) {
            @mkdir($catThumbFolder, 0755, true);
        }
        if ($is_video) {
            if (!is_dir($videoFolder)) {
                @mkdir($videoFolder, 0755, true);
            }
            if (!is_dir($videoThumbFolder)) {
                @mkdir($videoThumbFolder, 0755, true);
            }
        }

        // === THUMBNAIL: Auto-replace old on upload ===
        if ($request->hasFile('category_thumbnail_image')) {
            $file = $request->file('category_thumbnail_image');
            if ($file->isValid()) {
                $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $generated = \Illuminate\Support\Str::slug($baseName) ?: 'thumbnail';
                $newFileName = $generated . '-' . time() . '-' . \Illuminate\Support\Str::random(6) . '.' . $extension;

                // Delete any old thumbnail in both the previous and current paths
                if (!empty($oldThumbnail)) {
                    $oldThumbInOldPath = public_path("{$pathPrefix}{$oldCategory}/{$oldTitle}/category_thumbnail/{$oldThumbnail}");
                    $oldThumbInNewPath = public_path("{$pathPrefix}{$categoryFolder}/{$subcategoryFolder}/category_thumbnail/{$oldThumbnail}");

                    if (file_exists($oldThumbInOldPath)) {
                        @unlink($oldThumbInOldPath);
                    }
                    if (file_exists($oldThumbInNewPath)) {
                        @unlink($oldThumbInNewPath);
                    }
                }

                // Move new thumbnail
                $file->move($catThumbFolder, $newFileName);
                $subcategory->category_thumbnail_image = $newFileName;
            }
        } elseif ($request->filled('remove_thumbnail')) {
            if (!empty($oldThumbnail)) {
                $oldThumbInNewPath = public_path("{$pathPrefix}{$categoryFolder}/{$subcategoryFolder}/category_thumbnail/{$oldThumbnail}");
                if (file_exists($oldThumbInNewPath)) {
                    @unlink($oldThumbInNewPath);
                }
            }
            $subcategory->category_thumbnail_image = null;
        }

        // === GALLERY IMAGES ===
        $dataJson = $is_video ? $subcategory->videos : $subcategory->images;
        $imagesData = $dataJson ? json_decode($dataJson, true) : [];

        // Remove selected gallery images
        if ($request->filled('remove_images')) {
            foreach ($imagesData as $key => $img) {
                if (in_array($img['file'], $request->remove_images)) {

                    if ($is_video) {
                        $path = $videoFolder . '/' . $img['file'];
                    } else {
                        $path = $baseFolder . '/' . $img['file'];
                    }

                    if (file_exists($path)) {
                        @unlink($path);
                    }

                    if (isset($img['thumbnail']) && $img['thumbnail']) {
                        if ($is_video) {
                            $thumbPath = $videoThumbFolder . '/' . $img['thumbnail'];
                        } else {
                            $thumbPath = $baseFolder . '/' . $img['thumbnail'];
                        }

                        if (file_exists($thumbPath)) {
                            @unlink($thumbPath);
                        }
                    }
                    unset($imagesData[$key]);
                }
            }
            $imagesData = array_values($imagesData);
        }

        // Update existing gallery image meta
        if (is_array($imagesData)) {
            foreach ($imagesData as &$img) {
                $fileName = $img['file'];
                if (isset($request->existing_prompts[$fileName])) {
                    $img['prompt'] = $request->existing_prompts[$fileName];
                }

                // Use the correct field for title
                if (isset($request->$existingTitleField[$fileName])) {
                    $img[$titleField] = $request->$existingTitleField[$fileName];
                }

                $img['name_change'] = isset($request->existing_name_change[$fileName]);
            }
            unset($img);
        }

        // Replace existing gallery images (file swap)
        if ($request->hasFile('replace_images')) {
            foreach ($request->file('replace_images') as $oldFileName => $newFile) {
                if (!$newFile || !$newFile->isValid()) continue;
                foreach ($imagesData as &$img) {
                    if ($img['file'] !== $oldFileName) continue;
                    $oldPath = $is_video ? ($videoFolder . '/' . $oldFileName) : ($baseFolder . '/' . $oldFileName);
                    if (file_exists($oldPath)) @unlink($oldPath);
                    $baseName  = pathinfo($newFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $ext       = $newFile->getClientOriginalExtension();
                    $newFName  = \Illuminate\Support\Str::slug($baseName) . '-' . time() . '-' . \Illuminate\Support\Str::random(6) . '.' . $ext;
                    $is_video ? $newFile->move($videoFolder, $newFName) : $newFile->move($baseFolder, $newFName);
                    $img['file'] = $newFName;
                    break;
                }
                unset($img);
            }
        }

        // Add new gallery images
        if ($request->hasFile('images')) {
            $thumbnails = $request->input('video_thumbnails', []);

            foreach ($request->file('images') as $index => $imgFile) {
                if (!$imgFile || !$imgFile->isValid())
                    continue;

                $baseName  = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $imgFile->getClientOriginalExtension();
                $originalName = \Illuminate\Support\Str::slug($baseName) . '-' . time() . '-' . \Illuminate\Support\Str::random(6) . '.' . $extension;
                $prompt = $request->prompts[$index] ?? '';
                // Get title from the dynamic field
                $titles = $request->$titleField;
                $imageTitle = $titles[$index] ?? '';
                $nameChange = $request->name_change_row[$index] ?? false;

                if ($is_video) {
                    $imgFile->move($videoFolder, $originalName);
                } else {
                    $imgFile->move($baseFolder, $originalName);
                }

                $thumbnailName = null;
                // Process thumbnail if exists (for videos)
                if ($is_video && isset($thumbnails[$index]) && !empty($thumbnails[$index])) {
                    $base64Image = $thumbnails[$index];
                    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                        $data = substr($base64Image, strpos($base64Image, ',') + 1);
                        $type = strtolower($type[1]); // jpg, png, etc
                        $data = base64_decode($data);

                        if ($data !== false) {
                            $thumbFileName = pathinfo($originalName, PATHINFO_FILENAME) . '_thumb.' . $type;
                            $thumbPath = $videoThumbFolder . '/' . $thumbFileName;
                            file_put_contents($thumbPath, $data);
                            $thumbnailName = $thumbFileName;
                        }
                    }
                }

                $imagesData[] = [
                    'file' => $originalName,
                    'thumbnail' => $thumbnailName, // Add thumbnail field
                    'prompt' => $prompt,
                    $titleField => $imageTitle,
                    'name_change' => $nameChange ? true : false,
                ];
            }
        }

        // Add images from URLs (Generate option)
        if ($request->filled('image_urls')) {
            foreach ($request->image_urls as $index => $url) {
                if (empty(trim($url))) continue;
                try {
                    $response  = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
                    if (!$response->successful()) continue;
                    $urlPath   = parse_url($url, PHP_URL_PATH);
                    $urlExt    = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
                    $extension = in_array($urlExt, ['jpg', 'jpeg', 'png', 'webp']) ? $urlExt : 'jpg';
                    $fileName  = 'gen-' . time() . '-' . \Illuminate\Support\Str::random(6) . '.' . $extension;
                    file_put_contents($baseFolder . '/' . $fileName, $response->body());
                    $imagesData[] = [
                        'file'       => $fileName,
                        'thumbnail'  => null,
                        'prompt'     => $request->url_prompts[$index] ?? '',
                        $titleField  => $request->url_image_titles[$index] ?? '',
                        'name_change' => false,
                    ];
                } catch (\Exception $e) {
                    // skip invalid URL
                }
            }
        }

        $finalJson = !empty($imagesData) ? json_encode(array_values($imagesData)) : null;

        if ($is_video) {
            $subcategory->videos = $finalJson;
        } else {
            $subcategory->images = $finalJson;
        }

        $subcategory->save();

        $redirectParams = ['id' => $subcategory->id];
        if ($request->has('origin')) {
            $redirectParams['origin'] = $request->origin;
        }

        return redirect()
            ->route('subcategories.show', $redirectParams)
            ->with('success', 'Subcategory details saved. Thumbnail updated.');

      } catch (\Illuminate\Validation\ValidationException $e) {
          $errors = collect($e->errors())->map(fn($msgs) => $msgs[0])->values()->implode(' | ');
          return redirect()->back()->withInput()->with('error', $errors)->withErrors($e->errors());

      } catch (\Exception $e) {
          return redirect()->back()->withInput()->with('error', 'Something went wrong while saving details. Please try again.');
      }
    }



    public function deleteImage(Request $request, $subcategoryId, $file)
    {
        $model = $this->getModel($request);
        $subcategory = $model::findOrFail($subcategoryId);
        $is_video = $request->get('origin') === 'video';
        $pathPrefix = $is_video ? 'upload/AI Baby Video/' : 'upload/';

        $dataJson = $is_video ? $subcategory->videos : $subcategory->images;
        $images = json_decode($dataJson, true) ?? [];

        $found = false;
        foreach ($images as $key => $img) {
            if ($img['file'] === $file) {
                if ($is_video) {
                    $imagePath = public_path($pathPrefix . $subcategory->category_name . '/' . $subcategory->title . '/video/' . $file);
                } else {
                    $imagePath = public_path($pathPrefix . $subcategory->category_name . '/' . $subcategory->title . '/' . $file);
                }

                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                }
                if (isset($img['thumbnail']) && $img['thumbnail']) {
                    if ($is_video) {
                        $thumbPath = public_path($pathPrefix . $subcategory->category_name . '/' . $subcategory->title . '/video thumbnail/' . $img['thumbnail']);
                    } else {
                        $thumbPath = public_path($pathPrefix . $subcategory->category_name . '/' . $subcategory->title . '/' . $img['thumbnail']);
                    }

                    if (File::exists($thumbPath)) {
                        File::delete($thumbPath);
                    }
                }
                unset($images[$key]);
                $found = true;
                break;
            }
        }

        $redirectParams = ['id' => $subcategory->id];
        if ($request->has('origin')) {
            $redirectParams['origin'] = $request->origin;
        }

        if ($found) {
            $newJson = json_encode(array_values($images));
            if ($is_video) {
                $subcategory->videos = $newJson;
            } else {
                $subcategory->images = $newJson;
            }
            $subcategory->save();
            $type = $request->get('origin') === 'video' ? 'Video' : 'Image';
            return redirect()->route('subcategories.show', $redirectParams)->with('success', "{$type} deleted successfully!");
        }

        $type = $request->get('origin') === 'video' ? 'Video' : 'Image';
        return redirect()->route('subcategories.show', $redirectParams)->with('error', "{$type} not found!");
    }

    public function updateStatus(Request $request)
    {
        $model = $this->getModel($request);
        $subcategory = $model::findOrFail($request->id);
        $subcategory->trending = $request->trending;
        $subcategory->save();

        return response()->json(['success' => true]);
    }
}
