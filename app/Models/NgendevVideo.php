<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NgendevVideo extends Model
{
    use HasFactory;

    protected $table = 'ngendev_videos';

    protected $fillable = ['category_id', 'video_thumbnail', 'video_path', 'ai_prompt', 'ai_model', 'sort_order', 'no_of_video', 'name_change', 'image_hint', 'status'];

    public function category()
    {
        return $this->belongsTo(NgendevVideoCategory::class, 'category_id');
    }
}
