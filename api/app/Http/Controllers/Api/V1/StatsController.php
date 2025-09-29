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
            ],
        );
        if (!isset($validated['format'])) {
            $validated['format'] = 'json';
        }
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
            "male" => [
                "percentageCount" => $maleTranscripts->count() / ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) * 100,
                "percentageDuration" => $maleTranscripts->sum('duration') / ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1) * 100,
            ],
            "female" => [
                "percentageCount" => $femaleTranscripts->count() / ($allTranscripts->count() > 0 ? $allTranscripts->count() : 1) * 100,
                "percentageDuration" => $femaleTranscripts->sum('duration') / ($allTranscripts->sum('duration') > 0 ? $allTranscripts->sum('duration') : 1) * 100,
            ]
        ];

        if ($validated['format'] === 'csv') {
            return response()->streamDownload(
                function () use ($data) {
                    $csv = fopen('php://output', 'w');
                    fputcsv($csv, ['Metric', 'Male', 'Female']);
                    fputcsv($csv, ['Count', $data['male']['percentageCount'], $data['female']['percentageCount']]);
                    fputcsv($csv, ['Duration (seconds)', $data['male']['percentageDuration'], $data['female']['percentageDuration']]);
                    fclose($csv);
                },
                'basic_distribution_' . ($council->abbreviation ?? 'all') . '_' . $session->externalId . '.csv',
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="basic_distribution.csv"',
                ]
            );
        }

        return response()->json([
            'duration' => [
                'male' => $data['male']['percentageDuration'],
                'female' => $data['female']['percentageDuration'],
            ],
            'count' => [
                'male' => $data['male']['percentageCount'],
                'female' => $data['female']['percentageCount']
            ]
        ]);
    }

    public function thematicDistribution(ParlSession $session)
    {
        $validated = request()->validate(
            rules: [
                'council' => 'nullable|exists:councils,abbreviation',
                'format' => 'in:json,csv',
                'metric' => 'in:count,duration',
            ],
        );
        if (!isset($validated['format'])) {
            $validated['format'] = 'json';
        }
        if (isset($validated['council'])) {
            $council = Council::where('abbreviation', $validated['council'])->first();
        } else {
            $council = null;
        }
        $metric = $validated['metric'];

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
            $totalTranscripts = collect();
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
                $totalTranscripts->push(...$transcripts->filter(function ($transcript) {
                    return $transcript->member && in_array($transcript->member->genderAsString, ['m', 'f']);
                }));
            }

            $data[$tag->name] = [
                'male' => [
                    'count' => $maleTranscripts->count() / $totalTranscripts->count() * 100 ?? 0,
                    'duration' => $maleTranscripts->sum('duration') / $totalTranscripts->sum('duration') * 100 ?? 0,
                ],
                'female' => [
                    'count' => $femaleTranscripts->count() / $totalTranscripts->count() * 100 ?? 0,
                    'duration' => $femaleTranscripts->sum('duration') / $totalTranscripts->sum('duration') * 100 ?? 0,
                ],
            ];
        }
        if ($validated['format'] === 'csv') {
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
}
