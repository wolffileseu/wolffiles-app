<?php

namespace App\Filament\Widgets;

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
                Tables\Columns\TextColumn::make('title')->limit(50),
                Tables\Columns\TextColumn::make('category.name')->badge(),
                Tables\Columns\TextColumn::make('download_count')->label('DLs'),
                Tables\Columns\TextColumn::make('published_at')->since(),
            ])
            ->paginated([5]);
    }
}
