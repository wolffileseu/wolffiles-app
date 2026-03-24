<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrivacyExportController extends Controller
{
    public function download(Request $request)
    {
        $path = cache()->get('data_export_' . auth()->id());

        if (!$path || !file_exists($path)) {
            return redirect()->route('settings.privacy')->with('error', __('messages.export_not_ready'));
        }

        auth()->user()->update(['data_export_ready' => 0]);

        return response()->download($path, 'meine-wolffiles-daten.zip')->deleteFileAfterSend();
    }
}
