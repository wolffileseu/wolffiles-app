<?php

declare(strict_types=1);

namespace Database\Factories\Etui;

use App\Models\Etui\EtuiProject;
use App\Models\Etui\EtuiRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EtuiRevision>
 */
class EtuiRevisionFactory extends Factory
{
    protected $model = EtuiRevision::class;

    public function definition(): array
    {
        return [
            'project_id' => EtuiProject::factory(),
            'content' => 'menuDef { name "'.fake()->word().'" }',
        ];
    }
}
