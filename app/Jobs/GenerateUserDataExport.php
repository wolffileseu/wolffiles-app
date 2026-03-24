<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;

class GenerateUserDataExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $data = [
            'profile' => [
                'username'   => $this->user->name,
                'email'      => $this->user->email,
                'registered' => $this->user->created_at?->toDateTimeString(),
            ],
            'uploads'      => $this->user->files()->select('id','title','description','created_at','download_count','file_size')->get()->toArray(),
            'forum_posts'  => method_exists($this->user, 'forumPosts') ? $this->user->forumPosts()->select('id','content','created_at')->get()->toArray() : [],
            'messages_sent'=> method_exists($this->user, 'sentMessages') ? $this->user->sentMessages()->select('id','subject','body','created_at')->get()->toArray() : [],
        ];

        $tmpDir  = storage_path('app/exports');
        $tmpJson = $tmpDir . '/data.json';
        $zipPath = $tmpDir . '/export_' . $this->user->id . '.zip';

        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        file_put_contents($tmpJson, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($tmpJson, 'wolffiles_daten.json');
        $zip->close();
        @unlink($tmpJson);

        cache()->put('data_export_' . $this->user->id, $zipPath, now()->addMinutes(30));
        $this->user->update(['data_export_ready' => 1]);
    }
}
