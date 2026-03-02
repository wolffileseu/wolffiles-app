<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingCriteria extends Model
{
    protected $table = 'rating_criteria';
    protected $fillable = ['name', 'name_translations', 'sort_order', 'is_active'];
    protected $casts = ['name_translations' => 'array', 'is_active' => 'boolean'];
}
