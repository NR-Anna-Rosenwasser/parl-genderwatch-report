<?php
use App\Models\Council;
use App\Models\ParlSession;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\StatsController;

Route::get(
    "basic-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new StatsController())->basicDistribution($parl_session);
    }
)->name('api.v1.stats.basicDistribution');

Route::get(
    "thematic-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new StatsController())->thematicDistribution($parl_session);
    }
)->name('api.v1.stats.thematicDistribution');

Route::get(
    "cantonal-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new StatsController())->cantonalDistribution($parl_session);
    })->name('api.v1.stats.cantonalDistribution');
