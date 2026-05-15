<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Etui\Profiles\EtcProfile;
use App\Etui\Profiles\EtJumpProfile;
use App\Etui\Profiles\EtLegacyProfile;
use App\Etui\Profiles\EtMainProfile;
use App\Etui\Profiles\JaymodProfile;
use App\Etui\Profiles\NitmodProfile;
use App\Etui\Profiles\NoQuarterProfile;
use App\Etui\Profiles\SilentProfile;
use App\Etui\Profiles\TceProfile;
use App\Models\Etui\EtuiMod;
use Illuminate\Database\Seeder;

class EtuiModSeeder extends Seeder
{
    public function run(): void
    {
        $mods = [
            ['slug' => 'etmain',   'name' => 'ET:Main (Stock)',    'parser_profile' => EtMainProfile::class,   'order' => 10],
            ['slug' => 'etlegacy', 'name' => 'ET:Legacy',          'parser_profile' => EtLegacyProfile::class, 'order' => 20],
            ['slug' => 'etjump',   'name' => 'ET:Jump',            'parser_profile' => EtJumpProfile::class,   'order' => 30],
            ['slug' => 'silent',   'name' => 'Silent Mod',         'parser_profile' => SilentProfile::class,   'order' => 40],
            ['slug' => 'jaymod',   'name' => 'Jaymod',             'parser_profile' => JaymodProfile::class,   'order' => 50],
            ['slug' => 'nq',       'name' => 'No Quarter',         'parser_profile' => NoQuarterProfile::class,'order' => 60],
            ['slug' => 'nitmod',   'name' => 'N!tmod',             'parser_profile' => NitmodProfile::class,   'order' => 70],
            ['slug' => 'tce',      'name' => 'True Combat Elite',  'parser_profile' => TceProfile::class,      'order' => 80],
            ['slug' => 'etc',      'name' => 'ETc Modpack',        'parser_profile' => EtcProfile::class,      'order' => 90],
        ];

        foreach ($mods as $mod) {
            // updateOrCreate on slug — re-running the seeder is idempotent and
            // also catches the case where an admin renames a mod by hand.
            EtuiMod::updateOrCreate(
                ['slug' => $mod['slug']],
                $mod + ['is_active' => true],
            );
        }
    }
}
