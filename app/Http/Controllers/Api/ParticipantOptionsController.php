<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ParticipantProfile;

/** The participant form (questions, answers, conditions) for any app to render. */
class ParticipantOptionsController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => ParticipantProfile::catalogue()]);
    }
}
