<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tag;
use App\Models\Council;
use App\Models\Transcript;
use App\Models\ParlSession;
use App\Http\Controllers\Controller;
use App\Models\Business;

class StatsController extends Controller
{
    private function validateApiRequest($request)
    {
        $validated = $request->validate(
            rules: [
                'council' => 'nullable|exists:councils,abbreviation',
                'include_presidency' => 'boolean',
                'include_federal_council' => 'boolean',
                'format' => 'in:json,csv',
                'metric' => 'in:count,duration',
                'percentages' => 'boolean',
            ],
        );

        return [
            "format" => $validated['format'] ?? 'json',
            "percentages" => $validated['percentages'] ?? true,
            "council" => $validated['council'] ?? null,
            "metric" => $validated['metric'] ?? 'duration',
            "include_presidency" => $validated['include_presidency'] ?? false,
            "include_federal_council" => $validated['include_federal_council'] ?? true,
        ];
    }

    private function buildTranscriptQuery($session_id, bool $includePresidency = false, bool $includeFederalCouncil = true, string $council = null)
    {
        $transcriptQuery = Transcript::where('parl_session_id', $session_id);
        if (!$includePresidency) {
            // Filter transcript to explude where function contains "P-", "1VP-" or "2VP-"
            $transcriptQuery = $transcriptQuery->whereNotLike('function', 'P-%')->whereNotLike('function', '1VP-%')->whereNotLike('function', '2VP-%');
        }

        if (!$includeFederalCouncil) {
            // Filter transcript to exclude where function contains "BR-" or "BPR-" or "VPBR-"
            $transcriptQuery = $transcriptQuery->whereNotLike('function', 'BR-%')->whereNotLike('function', 'BPR-%')->whereNotLike('function', 'VPBR-%');
        }
        if ($council) {
            $council = Council::where('abbreviation', $council)->first();
            if ($council) {
                $transcriptQuery = $transcriptQuery->where('council_id', $council->id);
            }
        }
        return $transcriptQuery;
    }

    private function splitMaleAndFemale($transcripts)
    {
        $maleTranscripts = $transcripts->filter(function ($transcript) {
            return $transcript->member && $transcript->member->genderAsString === 'm';
        });

        $femaleTranscripts = $transcripts->filter(function ($transcript) {
            return $transcript->member && $transcript->member->genderAsString === 'f';
        });

        $allTranscripts = $transcripts->filter(function ($transcript) {
            return $transcript->member && in_array($transcript->member->genderAsString, ['m', 'f']);
        });

        return [
            "male" => $maleTranscripts,
            "female" => $femaleTranscripts,
            "all" => $allTranscripts
        ];
    }

    private function calculateValues($maleTranscripts, $femaleTranscripts, $allTranscripts, $metric, $percentages)
    {
        if ($metric === 'count') {
            $male = $maleTranscripts->count();
            $female = $femaleTranscripts->count();
            $all = $allTranscripts->count();
        } else {
            $male = $maleTranscripts->sum('duration');
            $female = $femaleTranscripts->sum('duration');
            $all = $allTranscripts->sum('duration');
        }
        return [
            "male" => $percentages ? $male / ($all > 0 ? $all : 1) * 100 : $male,
            "female" => $percentages ? $female / ($all > 0 ? $all : 1) * 100 : $female,
            "all" => $percentages ? 100 : $all
        ];
    }

    private function makeFileName($prefix, $councilAbbreviation, $sessionExternalId, $metric, $percentages)
    {
        return
            $prefix
            . "_council_" . ($councilAbbreviation ?? 'all')
            . '_session_' . $sessionExternalId
            . '_metric_' . ($metric ?? 'count')
            . '_percentages_' . ($percentages ? 'true' : 'false')
            . '.csv';
    }

    public function basicDistribution(ParlSession $session)
    {
        $validated = $this->validateApiRequest(request());
        $transcripts = $this->buildTranscriptQuery(
            session_id: $session->id,
            includePresidency: $validated['include_presidency'],
            includeFederalCouncil: $validated['include_federal_council'],
            council: $validated['council']
        )->with('member')->get();

        $split = $this->splitMaleAndFemale($transcripts);

        $values = $this->calculateValues($split['male'], $split['female'], $split['all'], $validated['metric'], $validated['percentages']);

        if ($validated['format'] === 'csv') {
            return response()->streamDownload(
                function () use ($values) {
                    $csv = fopen('php://output', 'w');
                    fputcsv($csv, ['Male', 'Female']);
                    fputcsv($csv, [$values['male'], $values['female']]);
                },
                $this->makeFileName('basic_distribution', $validated['council'], $session->externalId, $validated['metric'], $validated['percentages']),
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="basic_distribution.csv"',
                ]
            );
        }

        return response()->json($values);
    }

    public function thematicDistribution(ParlSession $session)
    {
        $validated = $this->validateApiRequest(request());
        $transcripts = $this->buildTranscriptQuery(
            session_id: $session->id,
            includePresidency: $validated['include_presidency'],
            includeFederalCouncil: $validated['include_federal_council'],
            council: $validated['council']
        )->with('member', 'business', 'business.tags')->get();

        $grouped = $transcripts->groupBy(function ($transcript) {
            return $transcript->business->tags->pluck('name')->toArray();
        });

        $data = [];
        foreach ($grouped as $theme => $transcripts) {
            $split = $this->splitMaleAndFemale($transcripts);
            $values = $this->calculateValues($split['male'], $split['female'], $split['all'], $validated['metric'], $validated['percentages']);
            $data[$theme] = $values;
        }


        if ($validated['format'] === 'csv') {
            return response()->streamDownload(
                function () use ($data) {
                    $csv = fopen('php://output', 'w');
                    $headers = ['Tag', 'Male', 'Female'];
                    fputcsv($csv, $headers);
                    foreach ($data as $tag => $values) {
                        fputcsv($csv, [
                            $tag,
                            $values['male'],
                            $values['female'],
                        ]);
                    }
                    fclose($csv);
                },
                $this->makeFileName('thematic_distribution', $validated['council'], $session->externalId, $validated['metric'], $validated['percentages'])
            );
        }

        return response()->json($data);
    }

    public function cantonalDistribution(ParlSession $session)
    {
        $validated = request()->validate(
            rules: [
                'format' => 'in:json,csv',
                'council' => 'exists:councils,abbreviation',
                'metric' => 'in:count,duration',
                'percentages' => 'boolean',
            ],
        );
        $format = $validated['format'] ?? 'json';
        $percentages = $validated['percentages'] ?? true;
        $metric = $validated['metric'] ?? 'duration';

        $transcripts = Transcript::where('parl_session_id', $session->id)->with('member', 'member.canton')->whereHas('member')->whereHas('member.canton');
        if (isset($validated['council'])) {
            $council = Council::where('abbreviation', $validated['council'])->first();
            $transcripts = $transcripts->where('council_id', $council->id);
        }
        $transcripts = $transcripts->get();

        $grouped = $transcripts->groupBy(function ($transcript) {
            return $transcript->member?->canton?->abbreviation ?? 'Unknown';
        });
        // Sum metric by canton and gender
        $data = [];
        foreach ($grouped as $canton => $transcripts) {
            $maleTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && $transcript->member->genderAsString === 'm';
            });
            $femaleTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && $transcript->member->genderAsString === 'f';
            });
            $allTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && in_array($transcript->member->genderAsString, ['m', 'f']);
            });
            $data[$canton] = [
                "male" => $percentages ? ($metric === 'count' ? $maleTranscripts->count() : $maleTranscripts->sum('duration')) / ($metric === 'count' ? ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) : ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1)) * 100 : ($metric === 'count' ? $maleTranscripts->count() : $maleTranscripts->sum('duration')),
                "female" => $percentages ? ($metric === 'count' ? $femaleTranscripts->count() : $femaleTranscripts->sum('duration')) / ($metric === 'count' ? ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) : ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1)) * 100 : ($metric === 'count' ? $femaleTranscripts->count() : $femaleTranscripts->sum('duration')),
            ];
        }
        ksort($data);
        if ($format === 'csv') {
            return response()->streamDownload(
                function () use ($data, $metric) {
                    $csv = fopen('php://output', 'w');
                    $headers = ['Canton', "male", "female"];
                    fputcsv($csv, $headers);
                    foreach ($data as $canton => $values) {
                        fputcsv($csv, [
                            $canton,
                            $values['male'],
                            $values['female'],
                        ]);
                    }
                    fclose($csv);
                },
                'cantonal_distribution.csv'
            );
        }

        return response()->json($data);
    }

    public function ageDistribution(ParlSession $session)
    {
        $validated = request()->validate(
            rules: [
                'format' => 'in:json,csv',
                'council' => 'exists:councils,abbreviation',
                'metric' => 'in:count,duration',
                'percentages' => 'boolean',
            ],
        );
        $format = $validated['format'] ?? 'json';
        $metric = $validated['metric'] ?? 'duration';
        $percentages = $validated['percentages'] ?? true;

        $ageBuckets = [
            'u35' => [0, 35],
            '36-45' => [36, 45],
            '46-55' => [46, 55],
            '56-65' => [56, 65],
            '66+' => [66, 2000],
        ];

        $transcripts = Transcript::where('parl_session_id', $session->id)->with('member')->whereHas('member', function ($query) use ($ageBuckets) {
            $query->whereNotNull('dateOfBirth');
        });

        if (isset($validated['council'])) {
            $council = Council::where('abbreviation', $validated['council'])->first();
            $transcripts = $transcripts->where('council_id', $council->id);
        }
        $transcripts = $transcripts->get();
        $sessionDate = $session->startDate;
        $grouped = [];
        foreach ($ageBuckets as $bucket => [$min, $max]) {
            $grouped[$bucket] = $transcripts->filter(function ($transcript) use ($sessionDate, $min, $max) {
                if (!$transcript->member || !$transcript->member->dateOfBirth) {
                    return false;
                }
                $age = $sessionDate->diffInYears($transcript->member->dateOfBirth) * -1;
                return $age >= $min && $age <= $max;
            });
        }
        $data = [];
        foreach ($grouped as $bucket => $transcripts) {
            $maleTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && $transcript->member->genderAsString === 'm';
            });
            $femaleTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && $transcript->member->genderAsString === 'f';
            });
            $allTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && in_array($transcript->member->genderAsString, ['m', 'f']);
            });
            if ($metric === 'count') {
                $data[$bucket] = [
                    "male" => $percentages ? $maleTranscripts->count() / ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) * 100 : $maleTranscripts->count(),
                    "female" => $percentages ? $femaleTranscripts->count() / ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) * 100 : $femaleTranscripts->count(),
                ];
            } else {
                $data[$bucket] = [
                    "male" => $percentages ? $maleTranscripts->sum('duration') / ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1) * 100 : $maleTranscripts->sum('duration'),
                    "female" => $percentages ? $femaleTranscripts->sum('duration') / ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1) * 100 : $femaleTranscripts->sum('duration'),
                ];
            }
        }
        if ($format === 'csv') {
            return response()->streamDownload(
                function () use ($data, $metric) {
                    $csv = fopen('php://output', 'w');
                    $headers = ['Age Bucket', 'Male', 'Female'];
                    fputcsv($csv, $headers);
                    foreach ($data as $bucket => $values) {
                        fputcsv($csv, [
                            $bucket,
                            $values['male'],
                            $values['female'],
                        ]);
                    }
                    fclose($csv);
                },
                'age_distribution.csv'
            );
        }

        return response()->json($data);
    }

    public function groupDistribution(ParlSession $session)
    {
        $validated = request()->validate(
            rules: [
                'format' => 'in:json,csv',
                'council' => 'nullable|exists:councils,abbreviation',
                'metric' => 'in:count,duration',
                'percentages' => 'boolean',
            ],
        );
        $format = $validated['format'] ?? 'json';
        $percentages = $validated['percentages'] ?? true;
        $council = Council::where('abbreviation', $validated['council'])->first() ?? null;
        $metric = $validated['metric'] ?? 'duration';

        $transcripts = Transcript::where('parl_session_id', $session->id)->with('member', 'member.parlGroup')->whereHas('member', function ($query) {
            $query->whereNotNull('parl_group_id');
        });
        if ($council ?? false) {
            $transcripts = $transcripts->where('council_id', $council->id);
        }
        $transcripts = $transcripts->get();

        $grouped = $transcripts->groupBy(function ($transcript) {
            return $transcript->member?->parlGroup?->name;
        });
        $data = [];
        foreach ($grouped as $group => $transcripts) {
            $maleTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && $transcript->member->genderAsString === 'm';
            });
            $femaleTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && $transcript->member->genderAsString === 'f';
            });
            $allTranscripts = $transcripts->filter(function ($transcript) {
                return $transcript->member && in_array($transcript->member->genderAsString, ['m', 'f']);
            });
            $data[$group] = [
                "male" => $percentages ? ($metric === 'count' ? $maleTranscripts->count() : $maleTranscripts->sum('duration')) / ($metric === 'count' ? ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) : ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1)) * 100 : ($metric === 'count' ? $maleTranscripts->count() : $maleTranscripts->sum('duration')),
                "female" => $percentages ? ($metric === 'count' ? $femaleTranscripts->count() : $femaleTranscripts->sum('duration')) / ($metric === 'count' ? ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) : ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1)) * 100 : ($metric === 'count' ? $femaleTranscripts->count() : $femaleTranscripts->sum('duration')),
            ];
        }
        ksort($data);
        if ($format === 'csv') {
            return response()->streamDownload(
                function () use ($data, $metric) {
                    $csv = fopen('php://output', 'w');
                    $headers = ['Parliamentary Group', 'Male', 'Female'];
                    fputcsv($csv, $headers);
                    foreach ($data as $group => $values) {
                        fputcsv($csv, [
                            $group,
                            $values['male'],
                            $values['female']
                        ]);
                    }
                    fclose($csv);
                },
                'group_distribution.csv'
            );
        }
        return response()->json($data);
    }
}
