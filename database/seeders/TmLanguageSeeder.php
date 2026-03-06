<?php
namespace Database\Seeders;
use App\Models\TmLanguage;
use Illuminate\Database\Seeder;

class TmLanguageSeeder extends Seeder {
    public function run(): void {
        $langs = [
            ['code'=>'de','name'=>'Deutsch',   'flag'=>'🇩🇪','sort_order'=>1],
            ['code'=>'fr','name'=>'Français',  'flag'=>'🇫🇷','sort_order'=>2],
            ['code'=>'nl','name'=>'Nederlands','flag'=>'🇳🇱','sort_order'=>3],
            ['code'=>'pl','name'=>'Polski',    'flag'=>'🇵🇱','sort_order'=>4],
            ['code'=>'tr','name'=>'Türkçe',    'flag'=>'🇹🇷','sort_order'=>5],
        ];
        foreach ($langs as $l) TmLanguage::updateOrCreate(['code'=>$l['code']], $l);
        $this->command->info('Sprachen geseedet.');
    }
}
