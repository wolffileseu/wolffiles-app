<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $category
 * @property string|null $icon
 * @property string|null $content
 * @property bool $is_pinned
 * @property int $sort_order
 */
class KnowledgeBase extends Model
{
    protected $table = 'knowledge_base';

    protected $fillable = [
        'title', 'slug', 'category', 'icon', 'content',
        'is_pinned', 'sort_order', 'updated_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function categories(): array
    {
        return [
            'system' => '🔧 System Doku',
            'features' => '🗺️ Feature Doku',
            'changelog' => '📝 Changelog / Dev Notes',
            'troubleshooting' => '🚨 Troubleshooting',
        ];
    }
}
