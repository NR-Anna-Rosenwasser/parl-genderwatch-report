<?php

namespace Database\Seeders;

use App\Models\Canton;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CantonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing cantons table...');
        Canton::truncate();

        $this->command->info('Fetching cantons from webservice...');
        $cantons = (new \App\Helpers\Webservice())->query(
            model: 'Canton',
            filter: "Language eq 'DE'",
            top: 1000
        );

        $this->command->info('Seeding cantons...');
        foreach ($cantons as $canton) {
            $this->command->info("Seeding canton {$canton['CantonName']}");
            Canton::create([
                "externalID" => $canton['ID'],
                "name" => $canton['CantonName'],
                "number" => $canton['CantonNumber'],
                "abbreviation" => $canton['CantonAbbreviation']
            ]);
        }
    }
}
