<?php
use Illuminate\Support\Facades\Route;
use App\Models\ParlSession;
use App\Http\Controllers\Api\V1\MemberStatsController;

Route::get(
    "basic-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new MemberStatsController())->basicDistribution($parl_session);
    }
)->name('api.v1.members.basicDistribution');

Route::get(
    "cantonal-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new MemberStatsController())->cantonalDistribution($parl_session);
    }
)->name('api.v1.members.cantonalDistribution');

Route::get(
    "age-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new MemberStatsController())->ageDistribution($parl_session);
    }
)->name('api.v1.members.ageDistribution');

Route::get(
    "group-distribution/{parl_session:externalId}/",
    function (ParlSession $parl_session) {
        return (new MemberStatsController())->groupDistribution($parl_session);
    }
)->name('api.v1.members.groupDistribution');
