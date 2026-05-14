<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiRedirect extends Model
{
    protected $table = 'wiki_redirects';

    protected $fillable = ['namespace', 'from_slug', 'to_article_id', 'created_by'];

    public function target()
    {
        return $this->belongsTo(WikiArticle::class, 'to_article_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
