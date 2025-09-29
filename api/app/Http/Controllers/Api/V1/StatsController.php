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
}
