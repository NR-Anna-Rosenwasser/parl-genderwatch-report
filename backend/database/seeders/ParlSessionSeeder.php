<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Helpers\Webservice;
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
            ParlSession::create([
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
        }
    }
}
