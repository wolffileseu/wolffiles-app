<?php

declare(strict_types=1);

namespace Database\Factories\Etui;

use App\Etui\Profiles\EtMainProfile;
use App\Models\Etui\EtuiMod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EtuiMod>
 */
class EtuiModFactory extends Factory
{
    protected $model = EtuiMod::class;

    public function definition(): array
    {
        return [
            'slug' => 'test_'.fake()->unique()->word(),
            'name' => fake()->words(2, true),
            'description' => null,
            'parser_profile' => EtMainProfile::class,
            'homepage_url' => null,
            'icon_path' => null,
            'order' => 0,
            'is_active' => true,
        ];
    }
}
