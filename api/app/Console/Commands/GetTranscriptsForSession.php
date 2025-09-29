<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Council;
use App\Models\Member;
use App\Models\ParlSession;
use Illuminate\Console\Command;
use Illuminate\Contracts\Session\Session;

use function Laravel\Prompts\select;

class GetTranscriptsForSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transcripts:get {--batchSize=20}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get transcripts for a specific parliamentary session and store them in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $session = select('Select a parliamentary session:', ParlSession::orderBy("id", "desc")->get()->pluck('name', 'id'));
        $session = ParlSession::find($session);

        $this->info("Querying Webservice for transcript count for session: {$session->name}");
        $ws = new \App\Helpers\Webservice();
        $transcriptCount = $ws->query(
            model: "Transcript",
            countOnly: true,
            //  and IdSession eq '5209'
            filter: "IdSession eq '{$session->externalId}' and Language eq 'DE' and not substringof('VP-F', SpeakerFunction) and not substringof('VP-M', SpeakerFunction) and SpeakerFunction ne 'P-M' and SpeakerFunction ne 'P-F'",
            top: 0,
            select: false
        );
        $batches = ceil($transcriptCount / $this->option('batchSize'));
        $this->info("Found {$transcriptCount} transcripts for session: {$session->name} ({$batches} batches of {$this->option('batchSize')})");

        for ($i = 0; $i < $batches; $i++) {
            $this->info("Processing batch " . ($i + 1) . " of {$batches}");
            $transcripts = $ws->query(
                model: "Transcript",
                skip: $i * $this->option('batchSize'),
                filter: "IdSession eq '{$session->externalId}' and Language eq 'DE' and not substringof('VP-F', SpeakerFunction) and not substringof('VP-M', SpeakerFunction) and SpeakerFunction ne 'P-M' and SpeakerFunction ne 'P-F'",
                top: $this->option('batchSize'),
                orderby: "ID asc",
                countOnly: false,
            );

            foreach ($transcripts as $transcript) {
                $start = isset($transcript['Start']) ? $ws->parseODataDate($transcript['Start']) : null;
                $end = isset($transcript['End']) ? $ws->parseODataDate($transcript['End']) : null;
                $duration = (isset($start, $end)) ? $end->getTimestamp() - $start->getTimestamp() : null;
                $transcriptData = [
                    'externalId' => $transcript['ID'],
                    'text' => $transcript['Text'],
                    "start" => $start,
                    "end" => $end,
                    "duration" => $duration,
                    "languageOfText" => $transcript['LanguageOfText'],
                    "parl_session_id" => $session->id,
                    "council_id" => Council::where('abbreviation', $transcript['MeetingCouncilAbbreviation'] . "R")->first()?->id,
                    "member_id" => Member::where('externalId', $transcript['PersonNumber'])->first()?->id,
                ];

                $insertedTranscript = \App\Models\Transcript::updateOrCreate(
                    ['externalId' => $transcriptData['externalId']],
                    $transcriptData
                );

                if (isset($transcript["IdSubject"])) {
                    $this->info("Transcript with ID {$insertedTranscript->externalId} has an associated subject ID: {$transcript["IdSubject"]}");
                    $this->info("Querying Webservice SubjectBusiness for transcript ID {$insertedTranscript->externalId}");
                    $subjectBusiness = $ws->query(
                        model: "SubjectBusiness",
                        filter: "IdSubject eq {$transcript["IdSubject"]}L",
                        top: 1,
                        orderby: null,
                        countOnly: false,
                        select: null,
                        skip: 0
                    );
                    if (count($subjectBusiness) == 0) {
                        $this->info("No SubjectBusiness found for subject ID {$transcript["IdSubject"]}");
                        continue;
                    }
                    $this->info("Found SubjectBusiness for subject ID {$transcript["IdSubject"]}, querying Webservice for Business with ID {$subjectBusiness[0]["BusinessNumber"]}");
                    $business = $ws->query(
                        model: "Business",
                        filter: "ID eq {$subjectBusiness[0]["BusinessNumber"]}L",
                        top: 1,
                        orderby: null,
                        countOnly: false,
                        select: null,
                        skip: 0
                    );
                    if (count($business) == 0) {
                        $this->info("No Business found for business ID {$subjectBusiness[0]["BusinessNumber"]}");
                        continue;
                    }
                    $this->info("Found Business with ID {$business[0]["ID"]}, checking if it exists in local database or creating it");
                    $localBusinessExists = Business::where('externalId', $business[0]['ID'])->first();
                    if ($localBusinessExists) {
                        $this->info("Business with external ID {$business[0]['ID']} already exists in local database, skipping creation.");
                        $this->info("Attaching business ID {$business[0]['ID']} to transcript ID {$insertedTranscript->externalId}");
                        $insertedTranscript->business_id = $localBusinessExists->id;
                        $insertedTranscript->save();
                        continue;
                    }
                    $localBusiness = Business::firstOrCreate(
                        ['externalId' => $business[0]['ID']],
                        [
                            'externalId' => $business[0]['ID'],
                            'shortNumber' => $business[0]['BusinessShortNumber'],
                            'type' => $business[0]['BusinessType'],
                            'typeName' => $business[0]['BusinessTypeName'],
                            'typeAbbreviation' => $business[0]['BusinessTypeAbbreviation'],
                            'title' => $business[0]['Title'],
                            'description' => $business[0]['Description']
                        ]
                    );
                    $this->info("Attaching business ID {$localBusiness->id} to transcript ID {$insertedTranscript->externalId}");
                    $insertedTranscript->business_id = $localBusiness->id;
                    $insertedTranscript->save();
                    if (!empty($business[0]['TagNames'])) {
                        $tags = explode('|', $business[0]['TagNames']);
                        $localBusiness->tags()->attach(
                            collect($tags)->map(function ($tag) {
                                return \App\Models\Tag::firstOrCreate(['name' => $tag])->id;
                            })->toArray()
                        );
                    }
                }

                $this->info("Stored/Updated transcript with ID {$insertedTranscript->externalId}");
            }
        }
    }
}
