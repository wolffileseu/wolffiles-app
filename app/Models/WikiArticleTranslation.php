<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiArticleTranslation extends Model
{
    protected $table = 'wiki_article_translations';

    protected $fillable = [
        'wiki_article_id', 'locale', 'title', 'wikitext', 'content_html', 'updated_by',
    ];

    public function article()
    {
        return $this->belongsTo(WikiArticle::class, 'wiki_article_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
