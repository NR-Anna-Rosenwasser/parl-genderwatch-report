<?php

namespace Database\Seeders;

use App\Models\ParlGroup;
use App\Helpers\Webservice;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ParlGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing parl_groups table...');
        ParlGroup::truncate();

        $this->command->info('Querying Webservice for parl_groups...');
        $svc = new Webservice();
        $parlGroups = $svc->query(
            model: "ParlGroup",
            filter: "Language eq 'DE'",
            top: 1000,
            select: "*"
        );

        $this->command->info('Seeding parl_groups...');
        foreach ($parlGroups as $parlGroup) {
            $this->command->info("Seeding parl_group: {$parlGroup['ParlGroupName']}");
            ParlGroup::create([
                'ExternalId' => $parlGroup["ID"],
                'number' => $parlGroup["ParlGroupNumber"],
                'isActive' => $parlGroup["IsActive"],
                'code' => $parlGroup["ParlGroupCode"],
                'name' => $parlGroup["ParlGroupName"],
                'abbreviation' => $parlGroup["ParlGroupAbbreviation"],
                'nameUsedSince' => $svc->parseODataDate($parlGroup["NameUsedSince"]),
                'modified' => $svc->parseODataDate($parlGroup["Modified"]),
                'colour' => $parlGroup["ParlGroupColour"],
            ]);
        }
    }
}
