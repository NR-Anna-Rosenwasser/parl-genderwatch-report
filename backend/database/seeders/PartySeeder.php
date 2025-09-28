<?php

namespace Database\Seeders;

use App\Models\Party;
use Illuminate\Database\Seeder;
use App\Helpers\Webservice;

use function Laravel\Prompts\select;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PartySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing parties table...');
        Party::truncate();

        $this->command->info('Querying Webservice for count of parties...');
        $svc = new Webservice();
        $partyCount = $svc->query(
            model: "Party",
            countOnly: true,
            filter: "Language eq 'de'",
            top: 0
        );
        $this->command->info("Found {$partyCount} active parties.");
        $batches = ceil($partyCount / 100);
        $this->command->info("Processing {$batches} batches of 100 parties...");
        for ($i = 0; $i < $batches; $i++) {
            $this->command->info("Processing batch " . ($i + 1) . " of {$batches}");
            $parties = $svc->query(
                model: "Party",
                skip: $i * 100,
                filter: "Language eq 'de'",
                top: 100
            );
            foreach ($parties as $party) {
                $this->command->info("Seeding party: {$party['PartyName']}");
                Party::create([
                    'ExternalID' => $party["ID"],
                    "name" => $party["PartyName"],
                    "abbreviation" => $party["PartyAbbreviation"],
                ]);
            }
        }
    }
}
