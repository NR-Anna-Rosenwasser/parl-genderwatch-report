<?php
use Illuminate\Support\Facades\Route;
use App\Models\ParlSession;
use App\Http\Controllers\Api\V1\TranscriptStatsController;

Route::get(
    "basic-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new TranscriptStatsController())->basicDistribution($parl_session);
    }
)->name('api.v1.stats.transcripts.basicDistribution');

Route::get(
    "thematic-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new TranscriptStatsController())->thematicDistribution($parl_session);
    }
)->name('api.v1.stats.transcripts.thematicDistribution');

Route::get(
    "cantonal-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new TranscriptStatsController())->cantonalDistribution($parl_session);
    }
)->name('api.v1.stats.transcripts.cantonalDistribution');

Route::get(
    "age-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new TranscriptStatsController())->ageDistribution($parl_session);
    }
)->name('api.v1.stats.transcripts.ageDistribution');

Route::get(
    "group-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new TranscriptStatsController())->groupDistribution($parl_session);
    }
)->name('api.v1.stats.transcripts.groupDistribution');
