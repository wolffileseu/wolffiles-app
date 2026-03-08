<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProfileField extends Model {
    protected $fillable = ['label','key','type','placeholder','options','is_required','is_active','show_on_profile','sort_order'];
    protected $casts = ['options'=>'array','is_required'=>'boolean','is_active'=>'boolean','show_on_profile'=>'boolean'];
}
