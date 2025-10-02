<?php

namespace Database\Seeders;

use App\Models\Canton;
use App\Models\Council;
use App\Models\Member;
use App\Models\ParlGroup;
use App\Models\Party;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing members table...');
        Member::truncate();

        $this->command->info('Querying Webservice for count of members...');
        $svc = new \App\Helpers\Webservice();
        $memberCount = $svc->query(
            model: "MemberCouncil",
            countOnly: true,
            filter: "Language eq 'DE'",
            top: 0
        );

        $this->command->info("Found {$memberCount} members.");

        $batches = ceil($memberCount / 100);
        $this->command->info("Processing {$batches} batches of 100 members...");
        for ($i = 0; $i < $batches; $i++) {
            $this->command->info("Processing batch " . ($i + 1) . " of {$batches}");
            $members = $svc->query(
                model: "MemberCouncil",
                skip: $i * 100,
                filter: "Language eq 'DE'",
                top: 100
            );

            foreach ($members as $member) {
                $this->command->info("Seeding member: {$member['FirstName']} {$member['LastName']} (external ID: {$member['ID']})");
                $member = Member::create([
                    "externalID" => $member['ID'],
                    "externalPersonId" => $member['PersonNumber'],
                    "isActive" => $member['Active'],
                    "firstName" => $member['FirstName'],
                    "lastName" => $member['LastName'],
                    "genderAsString" => $member['GenderAsString'],
                    "dateOfBirth" => $svc->parseODataDate($member['DateOfBirth']),
                    "dateJoining" => $svc->parseODataDate($member['DateJoining']),
                    "dateLeaving" => $svc->parseODataDate($member['DateLeaving']),
                    "dateElection" => $svc->parseODataDate($member['DateElection']),
                    "party_id" => Party::where('externalId', $member['Party'])->first()?->id ?? Party::where('name', 'parteilos')->first()?->id,
                    "parl_group_id" => ParlGroup::where('number', $member['ParlGroupNumber'])->first()?->id ?? null,
                    "canton_id" => Canton::where("externalId", $member['Canton'])->first()?->id,
                    "council_id" => Council::where("externalId", $member['Council'])->first()?->id,
                ]);
            }
        }
    }
}
