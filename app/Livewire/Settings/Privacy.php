<?php

namespace App\Livewire\Settings;

use App\Actions\DeleteUserAccount;
use App\Jobs\GenerateUserDataExport;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Privacy extends Component
{
    public string $deleteConfirm   = '';
    public bool   $showDeleteModal = false;
    public bool   $exportRequested = false;
    public bool   $exportReady     = false;

    public function mount(): void
    {
        $this->exportReady = (bool) auth()->user()->data_export_ready;
    }

    public function requestExport(): void
    {
        $user = auth()->user();
        $user->update(['data_export_ready' => 0]);
        GenerateUserDataExport::dispatchSync($user);
        $this->exportReady     = (bool) $user->fresh()->data_export_ready;
        $this->exportRequested = !$this->exportReady;
        session()->flash('export_info', __('messages.export_requested'));
    }

    public function downloadExport()
    {
        $path = cache()->get('data_export_' . auth()->id());
        if ($path && file_exists($path)) {
            auth()->user()->update(['data_export_ready' => 0]);
            return response()->download($path, 'meine-wolffiles-daten.zip')->deleteFileAfterSend();
        }
        session()->flash('error', __('messages.export_not_ready'));
    }

    public function deleteAccount(): void
    {
        $this->validate(
            ['deleteConfirm' => 'required|in:LOESCHEN'],
            ['deleteConfirm.in' => __('messages.type_delete_to_confirm')]
        );
        $user = auth()->user();
        Auth::logout();
        app(DeleteUserAccount::class)->execute($user);
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.privacy')
            ->layout('components.layouts.app', ['title' => __('messages.privacy_settings')]);
    }
}
