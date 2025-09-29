<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Council;
use App\Models\ParlSession;
use App\Models\Transcript;

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
                "count" => $maleTranscripts->count(),
                "duration" => $maleTranscripts->sum('duration'),
            ],
            "female" => [
                "count" => $femaleTranscripts->count(),
                "duration" => $femaleTranscripts->sum('duration'),
            ],
            "total" => [
                "count" => $allTranscripts->count(),
                "duration" => $allTranscripts->sum('duration'),
            ],
        ];

        if ($validated['format'] === 'csv') {
            return response()->streamDownload(
                function () use ($data) {
                    $csv = fopen('php://output', 'w');
                    fputcsv($csv, ['Metric', 'Male', 'Female', 'Total']);
                    fputcsv($csv, ['Count', $data['male']['count'], $data['female']['count'], $data['total']['count']]);
                    fputcsv($csv, ['Duration (seconds)', $data['male']['duration'], $data['female']['duration'], $data['total']['duration']]);
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
                'male' => $data['male']['duration'],
                'female' => $data['female']['duration'],
                'total' => $data['total']['duration'],
                'percentageMale' => $data['total']['duration'] > 0 ? ($data['male']['duration'] / $data['total']['duration']) * 100 : 0,
                'percentageFemale' => $data['total']['duration'] > 0 ? ($data['female']['duration'] / $data['total']['duration']) * 100 : 0
            ],
            'count' => [
                'male' => $data['male']['count'],
                'female' => $data['female']['count'],
                'total' => $data['total']['count'],
                'percentageMale' => $data['total']['count'] > 0 ? ($data['male']['count'] / $data['total']['count']) * 100 : 0,
                'percentageFemale' => $data['total']['count'] > 0 ? ($data['female']['count'] / $data['total']['count']) * 100 : 0
            ]
        ]);
    }
}
