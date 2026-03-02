<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorialVote extends Model
{
    protected $fillable = ['tutorial_id', 'user_id', 'is_helpful'];

    protected $casts = ['is_helpful' => 'boolean'];

    public function tutorial()
    {
        return $this->belongsTo(Tutorial::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
