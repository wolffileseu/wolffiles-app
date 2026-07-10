<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use App\Models\File;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUploadsWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Approved Files';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(File::query()->where('status', 'approved')->latest('published_at'))
            ->columns([
                TextColumn::make('title')->limit(50),
                TextColumn::make('category.name')->badge(),
                TextColumn::make('download_count')->label('DLs'),
                TextColumn::make('published_at')->since(),
            ])
            ->paginated([5]);
    }
}
