<?php
use Illuminate\Support\Facades\Route;

Route::prefix("transcripts")->group(base_path('routes/v1/transcripts.php'));
Route::prefix("members")->group(base_path('routes/v1/members.php'));
