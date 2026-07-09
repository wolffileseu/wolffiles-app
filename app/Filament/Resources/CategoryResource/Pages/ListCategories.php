<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // === CSV Export ===
            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $query = $this->getFilteredTableQuery()
                        ->with('parent')
                        ->withCount(['files', 'children'])
                        ->orderBy('type')
                        ->orderBy('parent_id')
                        ->orderBy('sort_order')
                        ->orderBy('id');

                    $filename = 'categories_' . date('Ymd_His') . '.csv';

                    $response = new StreamedResponse(function () use ($query) {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

                        fputcsv($handle, [
                            'id', 'parent_id', 'parent_name', 'name', 'slug', 'type',
                            'icon', 'image', 'sort_order', 'is_active', 'description',
                            'files_count', 'children_count', 'created_at', 'updated_at',
                        ]);

                        $query->chunk(500, function ($categories) use ($handle) {
                            foreach ($categories as $c) {
                                fputcsv($handle, [
                                    $c->id,
                                    $c->parent_id,
                                    $c->parent?->name,
                                    $c->name,
                                    $c->slug,
                                    $c->type,
                                    $c->icon,
                                    $c->image,
                                    $c->sort_order,
                                    $c->is_active ? 'yes' : 'no',
                                    $c->description,
                                    $c->files_count,
                                    $c->children_count,
                                    $c->created_at?->toDateTimeString(),
                                    $c->updated_at?->toDateTimeString(),
                                ]);
                            }
                        });

                        fclose($handle);
                    });

                    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
                    return $response;
                }),

            // === XLSX Export ===
            Action::make('export_xlsx')
                ->label('Export XLSX')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $rows = $this->getFilteredTableQuery()
                        ->with('parent')
                        ->withCount(['files', 'children'])
                        ->orderBy('type')
                        ->orderBy('parent_id')
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get();

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle('Categories');

                    // Header-Zeile
                    $headers = ['ID', 'Parent ID', 'Parent', 'Name', 'Slug', 'Type',
                                'Icon', 'Image', 'Sort', 'Active', 'Description',
                                'Files', 'Sub-Cats', 'Created', 'Updated'];
                    $sheet->fromArray($headers, null, 'A1');

                    // Header-Styling
                    $headerRange = 'A1:O1';
                    $sheet->getStyle($headerRange)->getFont()->setBold(true);
                    $sheet->getStyle($headerRange)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('374151');
                    $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');

                    // Daten-Zeilen
                    $row = 2;
                    foreach ($rows as $c) {
                        $sheet->fromArray([
                            $c->id,
                            $c->parent_id,
                            $c->parent?->name,
                            $c->name,
                            $c->slug,
                            $c->type,
                            $c->icon,
                            $c->image,
                            $c->sort_order,
                            $c->is_active ? 'yes' : 'no',
                            $c->description,
                            $c->files_count,
                            $c->children_count,
                            $c->created_at?->toDateTimeString(),
                            $c->updated_at?->toDateTimeString(),
                        ], null, "A{$row}");
                        $row++;
                    }

                    // Auto-size columns
                    foreach (range('A', 'O') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }

                    // Freeze pane + Auto-Filter
                    $sheet->freezePane('A2');
                    if ($row > 2) {
                        $sheet->setAutoFilter("A1:O" . ($row - 1));
                    }

                    $writer = new Xlsx($spreadsheet);
                    $filename = 'categories_' . date('Ymd_His') . '.xlsx';

                    return response()->streamDownload(
                        function () use ($writer) {
                            $writer->save('php://output');
                        },
                        $filename,
                        [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ]
                    );
                }),

            CreateAction::make(),
        ];
    }
}
