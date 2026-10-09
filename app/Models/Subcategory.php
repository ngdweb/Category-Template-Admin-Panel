<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['category_name', 'title', 'image', 'description', 'image_title', 'name_change', 'trending', 'status'];
    use HasFactory;
}
