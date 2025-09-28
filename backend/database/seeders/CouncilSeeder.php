<?php

namespace Database\Seeders;

use App\Models\Council;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouncilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing councils table...');
        Council::truncate();

        $this->command->info('Fetching councils from webservice...');
        $councils = (new \App\Helpers\Webservice())->query(
            model: 'Council',
            filter: "Language eq 'DE'",
            top: 1000
        );

        $this->command->info('Seeding councils...');
        foreach ($councils as $council) {
            $this->command->info("Seeding council {$council['CouncilName']} ({$council['ID']})");
            Council::create([
                "externalID" => $council['ID'],
                "name" => $council['CouncilName'],
                "abbreviation" => $council['CouncilAbbreviation']
            ]);
        }

        $this->command->info('Seeding Federal Council (Bundesrat)...');
        Council::firstOrCreate([
            'externalID' => 99,
            'name' => 'Bundesrat',
            'abbreviation' => 'BR',
        ]);

        $this->command->info('Seeding Federal Chancellor (Bundeskanzler*in)...');
        Council::firstOrCreate([
            'externalID' => 98,
            'name' => 'Bundeskanzler*in',
            'abbreviation' => 'BK',
        ]);
    }
}
