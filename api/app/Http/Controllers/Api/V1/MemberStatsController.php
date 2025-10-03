<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Member;
use App\Models\Council;
use App\Http\Controllers\Controller;

class MemberStatsController extends Controller
{
    private function validateApiRequest($request)
    {
        $validated = $request->validate(
            rules: [
                'council' => 'nullable|exists:councils,abbreviation',
                'format' => 'in:json,csv',
                'percentages' => 'boolean',
            ],
        );

        return [
            "format" => $validated['format'] ?? 'json',
            "percentages" => $validated['percentages'] ?? true,
            "council" => $validated['council'] ?? null,
        ];
    }

    private function buildMemberyQuery($session, string $council = null)
    {
        $session->load('members');
        if ($council) {
            $council = Council::where('abbreviation', $council)->first();
            if ($council) {
                $members = $session->members()->where('council_id', $council->id);
            }
        } else {
            // Filter members where council_id is 1 or 2 (exclude federal council members)
            $members = $session->members()->whereIn('council_id', [1, 2]);
        }
        return $members;
    }

    private function makeFileName($prefix, $councilAbbreviation, $sessionExternalId, $percentages)
    {
        return
            $prefix
            . "_council_" . ($councilAbbreviation ?? 'all')
            . '_session_' . $sessionExternalId
            . '_metric_' . ($metric ?? 'count')
            . '_percentages_' . ($percentages ? 'true' : 'false')
            . '.csv';
    }

    private function calculateGenderDistribution($members, $percentages = true)
    {
        $totalMembers = $members->count();
        $femaleMembers = $members->filter(function ($member) {
            return $member->genderAsString === 'f';
        })->count();
        $maleMembers = $members->filter(function ($member) {
            return $member->genderAsString === 'm';
        })->count();

        $return = [
            "male" => $percentages ? ($maleMembers / $totalMembers * 100) : $maleMembers,
            "female" => $percentages ? ($femaleMembers / $totalMembers * 100) : $femaleMembers,
        ];
        if (!$percentages) {
            $return['total'] = $totalMembers;
        }
        return $return;
    }

    public function basicDistribution($parl_session)
    {
        $validated = $this->validateApiRequest(request());
        $members = $this->buildMemberyQuery($parl_session, $validated['council'])->get();
        $genders = $this->calculateGenderDistribution($members, $validated['percentages']);
        if ($validated['format'] === 'csv') {
            return response()->streamDownload(
                function () use ($genders) {
                    $csv = fopen('php://output', 'w');
                    fputcsv($csv, ['Male', 'Female']);
                    fputcsv($csv, [$genders['male'], $genders['female']]);
                },
                $this->makeFileName('basic_distribution', $validated['council'], $parl_session->externalId, $validated['percentages']),
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="basic_distribution.csv"',
                ]
            );
        }

        return response()->json([
            'session' => $parl_session->name,
            'council' => $validated['council'] ?? 'all',
            'distribution' => $this->calculateGenderDistribution($members, $validated['percentages']),
        ]);
    }

    public function cantonalDistribution($parl_session)
    {
        $validated = $this->validateApiRequest(request());
        $members = $this->buildMemberyQuery($parl_session, $validated['council']);
        $cantons = $members->with('canton')->get()->groupBy('canton.abbreviation');

        foreach ($cantons as $abbreviation => $membersInCanton) {
            $distribution[$abbreviation] = $this->calculateGenderDistribution(collect($membersInCanton), $validated['percentages']);
        }

        if ($validated['format'] === 'csv') {
            return response()->streamDownload(
                function () use ($distribution) {
                    $csv = fopen('php://output', 'w');
                    fputcsv($csv, ['Canton', 'Male', 'Female']);
                    foreach ($distribution as $canton => $data) {
                        fputcsv($csv, [$canton, $data['male'], $data['female']]);
                    }
                },
                $this->makeFileName('cantonal_distribution', $validated['council'], $parl_session->externalId, $validated['percentages']),
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="cantonal_distribution.csv"',
                ]
            );
        }

        return response()->json([
            'session' => $parl_session->name,
            'council' => $validated['council'] ?? 'all',
            'distribution' => $distribution,
        ]);
    }

    public function ageDistribution($parl_session)
    {
        $validated = $this->validateApiRequest(request());

        $ageBuckets = [
            'u35' => [0, 35],
            '36-45' => [36, 45],
            '46-55' => [46, 55],
            '56-65' => [56, 65],
            '66+' => [66, 2000],
        ];
        $members = $this->buildMemberyQuery($parl_session, $validated['council'])->get();
        $distribution = [];
        $sessionDate = $parl_session->startDate;
        foreach ($ageBuckets as $bucketName => [$min, $max]) {
            $membersInBucket = $members->filter(function ($member) use ($sessionDate, $min, $max) {
                if (!$member->dateOfBirth) {
                    return false;
                }
                $age = $sessionDate->diffInYears($member->dateOfBirth) * -1;
                return $age >= $min && $age <= $max;
            });
            $distribution[$bucketName] = $this->calculateGenderDistribution(collect($membersInBucket), $validated['percentages']);
        }
        if ($validated['format'] === 'csv') {
            return response()->streamDownload(
                function () use ($distribution) {
                    $csv = fopen('php://output', 'w');
                    fputcsv($csv, ['Age Bucket', 'Male', 'Female']);
                    foreach ($distribution as $bucket => $data) {
                        fputcsv($csv, [$bucket, $data['male'], $data['female']]);
                    }
                },
                $this->makeFileName('age_distribution', $validated['council'], $parl_session->externalId, $validated['percentages']),
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="age_distribution.csv"',
                ]
            );
        }

        return response()->json([
            'session' => $parl_session->name,
            'council' => $validated['council'] ?? 'all',
            'distribution' => $distribution,
        ]);
    }

    public function groupDistribution($parl_session)
    {
        $validated = $this->validateApiRequest(request());
        $members = $this->buildMemberyQuery($parl_session, $validated['council']);
        $groups = $members->with('parlGroup')->get()->groupBy('parlGroup.name');

        foreach ($groups as $abbreviation => $membersInGroup) {
            $distribution[$abbreviation] = $this->calculateGenderDistribution(collect($membersInGroup), $validated['percentages']);
        }

        if ($validated['format'] === 'csv') {
            return response()->streamDownload(
                function () use ($distribution) {
                    $csv = fopen('php://output', 'w');
                    fputcsv($csv, ['Group', 'Male', 'Female']);
                    foreach ($distribution as $group => $data) {
                        fputcsv($csv, [$group, $data['male'], $data['female']]);
                    }
                },
                $this->makeFileName('group_distribution', $validated['council'], $parl_session->externalId, $validated['percentages']),
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="group_distribution.csv"',
                ]
            );
        }
        return response()->json([
            'session' => $parl_session->name,
            'council' => $validated['council'] ?? 'all',
            'distribution' => $distribution,
        ]);
    }
}
