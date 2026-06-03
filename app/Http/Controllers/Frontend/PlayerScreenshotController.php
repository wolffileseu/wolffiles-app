<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tracker\PlayerScreenshot;
use App\Models\Tracker\TrackerPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlayerScreenshotController extends Controller
{
    const MAX_SCREENSHOTS_PER_PLAYER = 12;
    const MAX_FILE_SIZE = 5242880; // 5 MB
    const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** Gate: only the player owner (or admin) can manage screenshots. */
    private function gate(TrackerPlayer $player): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        $isOwner = $player->claimed_by_user_id === $user->id;
        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('admin');
        abort_unless($isOwner || $isAdmin, 403, 'You do not own this player.');
    }

    /** Upload one or more screenshots. */
    public function upload(Request $request, TrackerPlayer $player)
    {
        $this->gate($player);

        $request->validate([
            'screenshots'   => 'required|array|min:1|max:6',
            'screenshots.*' => 'required|file|image|max:5120|mimes:jpg,jpeg,png,webp,gif',
        ]);

        // Enforce total cap
        $existing = $player->screenshots()->count();
        $remaining = self::MAX_SCREENSHOTS_PER_PLAYER - $existing;
        if ($remaining <= 0) {
            return back()->with('error', __('You have reached the limit of :max screenshots.', ['max' => self::MAX_SCREENSHOTS_PER_PLAYER]));
        }

        $files = array_slice($request->file('screenshots'), 0, $remaining);
        $uploaded = 0;
        $nextOrder = ($player->screenshots()->max('sort_order') ?? -1) + 1;

        foreach ($files as $file) {
            try {
                $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
                $key = 'player-screenshots/' . $player->id . '/' . Str::uuid()->toString() . '.' . strtolower($ext);

                $stream = fopen($file->getRealPath(), 'r');
                Storage::disk('s3')->put($key, $stream, 'public');
                if (is_resource($stream)) fclose($stream);

                // Try to determine image dimensions
                [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

                PlayerScreenshot::create([
                    'player_id'           => $player->id,
                    'uploaded_by_user_id' => auth()->id(),
                    'file_path'           => $key,
                    'file_size'           => $file->getSize(),
                    'mime_type'           => $file->getMimeType(),
                    'width'               => $width,
                    'height'              => $height,
                    'is_public'           => true,
                    'sort_order'          => $nextOrder++,
                ]);
                $uploaded++;
            } catch (\Throwable $e) {
                \Log::warning('Player screenshot upload failed', [
                    'player_id' => $player->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        if ($uploaded === 0) {
            return back()->with('error', __('No screenshots were uploaded.'));
        }
        return back()->with('success', __(':n screenshot(s) uploaded.', ['n' => $uploaded]));
    }

    /** Update title/caption/is_public. */
    public function update(Request $request, TrackerPlayer $player, PlayerScreenshot $screenshot)
    {
        $this->gate($player);
        abort_unless($screenshot->player_id === $player->id, 404);

        $data = $request->validate([
            'title'     => 'nullable|string|max:200',
            'caption'   => 'nullable|string|max:1000',
            'is_public' => 'nullable|boolean',
        ]);
        $data['is_public'] = $request->boolean('is_public');
        $screenshot->update($data);
        return back()->with('success', __('Screenshot updated.'));
    }

    /** Delete a screenshot (DB row + S3 object). */
    public function destroy(TrackerPlayer $player, PlayerScreenshot $screenshot)
    {
        $this->gate($player);
        abort_unless($screenshot->player_id === $player->id, 404);

        try {
            if ($screenshot->file_path) {
                Storage::disk('s3')->delete($screenshot->file_path);
            }
        } catch (\Throwable $e) {
            \Log::warning('S3 delete failed for screenshot', [
                'screenshot_id' => $screenshot->id,
                'error'         => $e->getMessage(),
            ]);
        }

        $screenshot->delete();
        return back()->with('success', __('Screenshot deleted.'));
    }

    /** Reorder screenshots. Expects array of IDs in desired order. */
    public function reorder(Request $request, TrackerPlayer $player)
    {
        $this->gate($player);

        $ids = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:tracker_player_screenshots,id',
        ])['ids'];

        foreach ($ids as $order => $id) {
            PlayerScreenshot::where('id', $id)
                ->where('player_id', $player->id)
                ->update(['sort_order' => $order]);
        }
        return response()->json(['ok' => true]);
    }
}
