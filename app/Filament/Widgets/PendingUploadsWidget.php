<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use App\Models\File;
use App\Services\FileUploadService;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Forms;

class PendingUploadsWidget extends BaseWidget
{
    protected static ?string $heading = 'Pending Uploads';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(File::query()->where('status', 'pending')->latest())
            ->columns([
                TextColumn::make('title')->limit(40),
                TextColumn::make('category.name'),
                TextColumn::make('user.name')->label('Uploader'),
                TextColumn::make('file_size_formatted')->label('Size'),
                TextColumn::make('game')->badge(),
                IconColumn::make('virus_clean')->boolean()->label('Scan'),
                TextColumn::make('created_at')->since(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (File $record) {
                        app(FileUploadService::class)->approve($record, auth()->id());
                        Notification::make()->title('Approved!')->success()->send();
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->schema([Textarea::make('reason')->required()])
                    ->action(function (File $record, array $data) {
                        app(FileUploadService::class)->reject($record, auth()->id(), $data['reason']);
                        Notification::make()->title('Rejected.')->warning()->send();
                    }),
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (File $record) => route('filament.admin.resources.files.edit', $record)),
            ])
            ->paginated([5]);
    }
}
