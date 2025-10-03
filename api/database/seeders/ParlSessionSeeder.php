<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Helpers\Webservice;
use App\Models\Member;
use App\Models\ParlSession;

class ParlSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing parl_sessions table...');
        ParlSession::truncate();

        $this->command->info('Querying Webservice for parl_sessions...');
        $svc = new Webservice();
        $sessions = $svc->query(
            model: "Session",
            filter: "Language eq 'de'",
            top: 5000,
            select: "*"
        );

        foreach ($sessions as $session) {
            $this->command->info("Seeding session: {$session['SessionName']}");
            $createdSession = ParlSession::create([
                'ExternalID' => $session["ID"],
                'number' => $session["SessionNumber"],
                "name" => $session["SessionName"],
                "abbreviation" => $session["Abbreviation"],
                "startDate" => $svc->parseODataDate($session["StartDate"]),
                "endDate" => $svc->parseODataDate($session["EndDate"]),
                "title" => $session["Title"],
                "type" => $session["Type"],
                "typeName" => $session["TypeName"],
                "modified" => $svc->parseODataDate($session["Modified"]),
                "legislativePeriodNumber" => $session["LegislativePeriodNumber"],
            ]);

            $this->command->info("Seeding members for session: {$session['SessionName']}");
            $uuids = [];
            $formattedStartDate = $createdSession->startDate->format('Y-m-d\TH:i:s');
            $formattedEndDate = $createdSession->endDate->format('Y-m-d\TH:i:s');
            $members = $svc->query(
                model: "MemberCouncilHistory",
                filter: "DateJoining lt datetime'$formattedEndDate' and (DateLeaving gt datetime'$formattedStartDate' or (DateLeaving eq null and Active eq true)) and Language eq 'DE'",
                top: 5000,
                select: "*",
                countOnly: false
            )["results"];

            foreach ($members as $member) {
                if (in_array($member["ID"], $uuids)) {
                    continue;
                }
                $uuids[] = $member["ID"];
                $memberModel = Member::where("externalPersonId", $member["PersonNumber"])->first();
                if (!$memberModel) {
                    $this->command->error("Member with externalPersonId {$member['PersonNumber']} not found. Skipping...");
                    die();
                }
                $this->command->info("Attaching member: {$member['FirstName']} {$member['LastName']} to Session {$session['SessionName']}");
                $createdSession->members()->attach(Member::where("externalPersonId", $member["PersonNumber"])->first());
            }
        }
    }
}
