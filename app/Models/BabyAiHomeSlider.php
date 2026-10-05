<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BabyAiHomeSlider extends Model
{
    use HasFactory;

    protected $table = 'baby_ai_home_sliders';

    protected $fillable = [
        'source_type',
        'source_id',
        'title',
        'description',
        'image',
        'video',
        'video_thumbnail',
        'is_on',
        'status',
        'sort_order',
    ];

    public function imageSubcategory()
    {
        return $this->belongsTo(Subcategory::class, 'source_id', 'id');
    }

    public function videoCategory()
    {
        return $this->belongsTo(AiVideoCategory::class, 'source_id', 'id');
    }

    public function dynamicFrameCategory()
    {
        return $this->belongsTo(DynamicPhotoFrameCategory::class, 'source_id', 'id');
    }

    public function getSourceNameAttribute()
    {
        if ($this->source_type === 'image' && $this->imageSubcategory) {
            return $this->imageSubcategory->title;
        }
        if ($this->source_type === 'video' && $this->videoCategory) {
            return $this->videoCategory->category_name;
        }
        if ($this->source_type === 'dynamic_frame' && $this->dynamicFrameCategory) {
            return $this->dynamicFrameCategory->category_name;
        }
        return 'Unknown';
    }

    // Remove any home-slider entries (and their files) that point to a deleted source category.
    public static function deleteBySource(string $sourceType, $sourceId): void
    {
        $sliders = self::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();

        foreach ($sliders as $slider) {
            $folder = public_path('upload/baby_ai_home_slider/' . $slider->source_type);
            foreach ([$slider->image, $slider->video, $slider->video_thumbnail] as $f) {
                if ($f && file_exists($folder . '/' . $f)) {
                    @unlink($folder . '/' . $f);
                }
            }
            $slider->delete();
        }
    }
}
