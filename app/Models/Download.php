<?php
// app/Models/Download.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    protected $fillable = ['file_id', 'user_id', 'ip_address', 'user_agent', 'referer'];

    public function file(): BelongsTo { return $this->belongsTo(File::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
