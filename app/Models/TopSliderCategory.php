<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopSliderCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'thumbnail_image',
        'title',
        'description',
        'file_type',
        'image',
        'video',
        'video_thumbnail',
        'top_slider_is_on',
        'status',
        'sort_order',
    ];
    public function items()
    {
        return $this->hasMany(TopSliderItem::class, 'top_slider_category_id');
    }
    
    public function originalImageCategory()
    {
        return $this->belongsTo(NgendevCategory::class, 'category_id', 'id');
    }

    public function originalVideoCategory()
    {
        return $this->belongsTo(NgendevVideoCategory::class, 'category_id', 'id');
    }
    
    public function getCategoryNameAttribute()
    {
        if ($this->file_type === 'image' && $this->originalImageCategory) {
            return $this->originalImageCategory->category_name;
        } elseif ($this->file_type === 'video' && $this->originalVideoCategory) {
            return $this->originalVideoCategory->category_name;
        }
        return 'Unknown';
    }

    // Remove Top Slider categories (with their items + files) that point to a deleted
    // source Ngendev category. Call this BEFORE deleting the source so category_name resolves.
    public static function deleteBySource(string $fileType, $categoryId): void
    {
        $categories = self::where('file_type', $fileType)
            ->where('category_id', $categoryId)
            ->get();

        foreach ($categories as $category) {
            $name = $category->category_name;

            $catBasePath = public_path('upload/top_slider/categories/' . $name);
            if (\Illuminate\Support\Facades\File::exists($catBasePath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($catBasePath);
            }

            $itemBasePath = public_path('upload/top_slider/items/' . $name);
            if (\Illuminate\Support\Facades\File::exists($itemBasePath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($itemBasePath);
            }

            TopSliderItem::where('top_slider_category_id', $category->id)->delete();
            $category->delete();
        }
    }
}
