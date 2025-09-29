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
