<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiTalkMessage extends Model
{
    protected $table = 'wiki_talk_messages';

    protected $fillable = [
        'wiki_talk_thread_id', 'user_id', 'wikitext', 'content_html', 'reply_to_id',
    ];

    public function thread()
    {
        return $this->belongsTo(WikiTalkThread::class, 'wiki_talk_thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(WikiTalkMessage::class, 'reply_to_id');
    }

    public function replies()
    {
        return $this->hasMany(WikiTalkMessage::class, 'reply_to_id')->orderBy('created_at');
    }
}
