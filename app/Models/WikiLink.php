<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiLink extends Model
{
    protected $table = 'wiki_links';

    protected $fillable = [
        'from_article_id', 'to_article_id', 'to_namespace', 'to_slug', 'locale',
    ];

    public function fromArticle()
    {
        return $this->belongsTo(WikiArticle::class, 'from_article_id');
    }

    public function toArticle()
    {
        return $this->belongsTo(WikiArticle::class, 'to_article_id');
    }

    public function isRedLink(): bool
    {
        return $this->to_article_id === null;
    }
}
