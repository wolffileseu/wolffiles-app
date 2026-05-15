<?php

declare(strict_types=1);

namespace Database\Factories\Etui;

use App\Models\Etui\EtuiFile;
use App\Models\Etui\EtuiMod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EtuiFile>
 */
class EtuiFileFactory extends Factory
{
    protected $model = EtuiFile::class;

    public function definition(): array
    {
        $content = 'menuDef { name "'.fake()->word().'" }';

        return [
            'mod_id' => EtuiMod::factory(),
            'name' => fake()->word().'.menu',
            'content' => $content,
            'source' => 'user',
            'uploader_id' => null,
            'is_public' => true,
            // hash auto-recomputed by EtuiFile::booted on save; populating here
            // satisfies the NOT NULL on first insert before that hook fires.
            'hash' => hash('sha256', $content),
            'parse_status' => 'pending',
            'parse_error_count' => 0,
            'parsed_at' => null,
        ];
    }

    public function stock(): static
    {
        return $this->state(fn () => ['source' => 'stock']);
    }
}
