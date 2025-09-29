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
                'council' => 'nullable|exists:councils,abbreviation'
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

        $maleTranscriptDuration = $maleTranscripts->sum('duration');
        $femaleTranscriptDuration = $femaleTranscripts->sum('duration');
        $totalTranscriptDuration = $allTranscripts->sum('duration');

        return response()->json([
            'duration' => [
                'male' => $maleTranscriptDuration,
                'female' => $femaleTranscriptDuration,
                'total' => $totalTranscriptDuration,
                'percentageMale' => $totalTranscriptDuration > 0 ? ($maleTranscriptDuration / $totalTranscriptDuration) * 100 : 0,
                'percentageFemale' => $totalTranscriptDuration > 0 ? ($femaleTranscriptDuration / $totalTranscriptDuration) * 100 : 0
            ],
            'count' => [
                'male' => $maleTranscripts->count(),
                'female' => $femaleTranscripts->count(),
                'total' => $allTranscripts->count(),
                'percentageMale' => $allTranscripts->count() > 0 ? ($maleTranscripts->count() / $allTranscripts->count()) * 100 : 0,
                'percentageFemale' => $allTranscripts->count() > 0 ? ($femaleTranscripts->count() / $allTranscripts->count()) * 100 : 0
            ]
        ]);
    }
}
