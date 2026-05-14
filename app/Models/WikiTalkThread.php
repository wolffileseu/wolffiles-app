<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiTalkThread extends Model
{
    protected $table = 'wiki_talk_threads';

    protected $fillable = [
        'wiki_article_id', 'title', 'created_by', 'is_resolved', 'is_pinned', 'last_reply_at',
    ];

    protected $casts = [
        'is_resolved'   => 'boolean',
        'is_pinned'     => 'boolean',
        'last_reply_at' => 'datetime',
    ];

    public function article()
    {
        return $this->belongsTo(WikiArticle::class, 'wiki_article_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages()
    {
        return $this->hasMany(WikiTalkMessage::class)->orderBy('created_at');
    }

    public function topMessages()
    {
        return $this->hasMany(WikiTalkMessage::class)->whereNull('reply_to_id')->orderBy('created_at');
    }
}
