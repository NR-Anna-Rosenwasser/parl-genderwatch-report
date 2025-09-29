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
    public function basicDistribution(ParlSession $session)
    {
        $validated = request()->validate(
            rules: [
                'council' => 'nullable|exists:councils,abbreviation',
                'format' => 'in:json,csv',
                'metric' => 'in:count,duration',
                'percentages' => 'boolean',
            ],
        );
        $format = $validated['format'] ?? 'json';
        $percentages = $validated['percentages'] ?? true;
        $council = $validated['council'] ?? null;
        $metric = $validated['metric'] ?? 'duration';

        if (isset($validated['council'])) {
            $council = Council::where('abbreviation', $validated['council'])->first();
        }
        $transcripts = Transcript::where('parl_session_id', $session->id)->with('member');
        if ($council ?? false) {
            $transcripts = $transcripts->where('council_id', $council->id);
        }
        $transcripts = $transcripts->get();

        $maleTranscripts = $transcripts->filter(function ($transcript) {
            return $transcript->member && $transcript->member->genderAsString === 'm';
        });

        $femaleTranscripts = $transcripts->filter(function ($transcript) {
            return $transcript->member && $transcript->member->genderAsString === 'f';
        });

        $allTranscripts = $transcripts->filter(function ($transcript) {
            return $transcript->member && in_array($transcript->member->genderAsString, ['m', 'f']);
        });

        $data = [
            "male" => $percentages ? ($metric === 'count' ? $maleTranscripts->count() : $maleTranscripts->sum('duration')) / ($metric === 'count' ? ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) : ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1)) * 100 : ($metric === 'count' ? $maleTranscripts->count() : $maleTranscripts->sum('duration')),
            "female" => $percentages ? ($metric === 'count' ? $femaleTranscripts->count() : $femaleTranscripts->sum('duration')) / ($metric === 'count' ? ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) : ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1)) * 100 : ($metric === 'count' ? $femaleTranscripts->count() : $femaleTranscripts->sum('duration')),
        ];

        if ($format === 'csv') {
            return response()->streamDownload(
                function () use ($data) {
                    $csv = fopen('php://output', 'w');
                    fputcsv($csv, ['Male', 'Female']);
                    fputcsv($csv, [$data['male'], $data['female']]);
                },
                'basic_distribution_' . ($council->abbreviation ?? 'all') . '_' . $session->externalId . '.csv',
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="basic_distribution.csv"',
                ]
            );
        }

        return response()->json($data);
    }

    public function thematicDistribution(ParlSession $session)
    {
        $validated = request()->validate(
            rules: [
                'council' => 'nullable|exists:councils,abbreviation',
                'format' => 'in:json,csv',
                'metric' => 'in:count,duration',
                'percentages' => 'boolean',
            ],
        );
        $format = $validated['format'] ?? 'json';
        $percentages = $validated['percentages'] ?? true;
        $council = Council::where('abbreviation', $validated['council'])->first() ?? null;
        $metric = $validated['metric'] ?? 'duration';

        $tags = Tag::whereHas('businesses', function ($query) use ($session, $council) {
            // Query businesses where has transcripts in the given session and council if provided
            $query->whereHas('transcripts', function ($query) use ($session, $council) {
                $query->where('parl_session_id', $session->id);
                if ($council) {
                    $query->where('council_id', $council->id);
                }
            });
        });
        $data = [];
        foreach ($tags->get() as $tag) {
            $businesses = $tag->businesses()->whereHas('transcripts', function ($query) use ($session, $council) {
                $query->where('parl_session_id', $session->id);
                if ($council) {
                    $query->where('council_id', $council->id);
                }
            })->get();
            $maleTranscripts = collect();
            $femaleTranscripts = collect();
            $allTranscripts = collect();
            foreach ($businesses as $business) {
                $transcripts = $business->transcripts()->where('parl_session_id', $session->id);
                if ($council) {
                    $transcripts = $transcripts->where('council_id', $council->id);
                }
                $transcripts = $transcripts->with('member')->get();
                $maleTranscripts->push(...$transcripts->filter(function ($transcript) {
                    return $transcript->member && $transcript->member->genderAsString === 'm';
                }));
                $femaleTranscripts->push(...$transcripts->filter(function ($transcript) {
                    return $transcript->member && $transcript->member->genderAsString === 'f';
                }));
                $allTranscripts->push(...$transcripts->filter(function ($transcript) {
                    return $transcript->member && in_array($transcript->member->genderAsString, ['m', 'f']);
                }));
            }

            $data[$tag->name] = [
                "male" => $percentages ? ($metric === 'count' ? $maleTranscripts->count() : $maleTranscripts->sum('duration')) / ($metric === 'count' ? ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) : ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1)) * 100 : ($metric === 'count' ? $maleTranscripts->count() : $maleTranscripts->sum('duration')),
                "female" => $percentages ? ($metric === 'count' ? $femaleTranscripts->count() : $femaleTranscripts->sum('duration')) / ($metric === 'count' ? ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) : ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1)) * 100 : ($metric === 'count' ? $femaleTranscripts->count() : $femaleTranscripts->sum('duration')),
            ];
        }
        if ($format === 'csv') {
            return response()->streamDownload(
                function () use ($data, $metric) {
                    $csv = fopen('php://output', 'w');
                    $headers = ['Tag'];
                    if ($metric === 'count') {
                        $headers = array_merge($headers, ['Male Count', 'Female Count']);
                    } else {
                        $headers = array_merge($headers, ['Male Duration', 'Female Duration']);
                    }
                    fputcsv($csv, $headers);
                    foreach ($data as $tag => $values) {
                        if ($metric === 'count') {
                            fputcsv($csv, [
                                $tag,
                                $values['male']['count'],
                                $values['female']['count'],
                            ]);
                        } else {
                            fputcsv($csv, [
                                $tag,
                                $values['male']['duration'],
                                $values['female']['duration'],
                            ]);
                        }
                    }
                    fclose($csv);
                },
                'thematic_distribution.csv'
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
}
