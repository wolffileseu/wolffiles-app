<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiRevision extends Model
{
    protected $fillable = [
        'wiki_article_id', 'user_id', 'title', 'content', 'change_summary', 'revision_number',
    ];

    public function article()
    {
        return $this->belongsTo(WikiArticle::class, 'wiki_article_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
