<?php
namespace App\Filament\Pages;

use Artisan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManageAppRelease extends Page
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-arrow-down-tray';
    protected static string | \UnitEnum | null $navigationGroup = 'Clans';
    protected static ?string $navigationLabel = 'Tool Release';
    protected static ?int    $navigationSort  = 3;
    protected string  $view            = 'filament.pages.manage-app-release';

    public string $version      = '';
    public string $changelog    = '';
    public bool   $force_update = false;

    public function mount(): void
    {
        $config = config('clan-tool');
        $this->version      = $config['version']      ?? '1.0.0';
        $this->changelog    = $config['changelog']    ?? '';
        $this->force_update = $config['force_update'] ?? false;
    }

    public function save(): void
    {
        $path = config_path('clan-tool.php');

        $content = "<?php\nreturn [\n" .
            "    'version'      => '{$this->version}',\n" .
            "    'download_url' => 'https://github.com/wolffileseu/clan-news-tool/releases/latest/download/ClanNewsTool.exe',\n" .
            "    'changelog'    => '{$this->changelog}',\n" .
            "    'force_update' => " . ($this->force_update ? 'true' : 'false') . ",\n" .
            "];\n";

        file_put_contents($path, $content);

        Artisan::call('config:clear');

        Notification::make()
            ->title('Release gespeichert!')
            ->success()
            ->send();
    }

    protected function getFormSchema(): array
    {
        return [];
    }
}
