<?php

namespace App\Console\Commands;

use App\Models\NdaTemplate;
use Illuminate\Console\Command;

class SeedNdaTemplates extends Command
{
    protected $signature = 'nda:seed-templates';

    protected $description = 'Legt die NDA-Vertragsvorlagen an oder erzeugt eine neue Version bei Aenderungen.';

    public function handle(): int
    {
        $sources = [
            'de' => ['path' => resource_path('nda-templates/de.md'), 'name' => 'Ehrenamts- und Verschwiegenheitsvereinbarung'],
            'en' => ['path' => resource_path('nda-templates/en.md'), 'name' => 'Volunteer and Confidentiality Agreement'],
        ];

        foreach ($sources as $locale => $source) {
            if (! is_file($source['path'])) {
                $this->error('Fehlt: ' . $source['path']);

                return self::FAILURE;
            }

            $body = file_get_contents($source['path']);

            $latest = NdaTemplate::query()
                ->where('locale', $locale)
                ->orderByDesc('version')
                ->first();

            if ($latest !== null && $latest->body === $body) {
                if (! $latest->is_active) {
                    $latest->update(['is_active' => true]);
                    $this->line($locale . ': v' . $latest->version . ' wieder aktiviert.');
                } else {
                    $this->line($locale . ': unveraendert (v' . $latest->version . ').');
                }

                continue;
            }

            $version = $latest === null ? 1 : $latest->version + 1;

            NdaTemplate::query()
                ->where('locale', $locale)
                ->update(['is_active' => false]);

            NdaTemplate::create([
                'name' => $source['name'],
                'locale' => $locale,
                'version' => $version,
                'body' => $body,
                'is_active' => true,
            ]);

            $this->info($locale . ': v' . $version . ' angelegt und aktiviert.');
        }

        return self::SUCCESS;
    }
}
