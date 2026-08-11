<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SmsInactivityNudgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsInboundController extends Controller
{
    /**
     * Africa's Talking inbound SMS webhook — handles opt-out keyword.
     */
    public function handle(Request $request, SmsInactivityNudgeService $nudges)
    {
        $from = (string) ($request->input('from') ?? $request->input('phoneNumber') ?? '');
        $text = (string) ($request->input('text') ?? $request->input('message') ?? '');

        if ($from === '' || $text === '') {
            return response()->json(['success' => false, 'message' => 'Missing from/text'], 422);
        }

        $optedOut = $nudges->handleInboundOptOut($from, $text);

        Log::info('Inbound SMS processed', [
            'from' => $from,
            'opted_out' => $optedOut,
        ]);

        return response()->json([
            'success' => true,
            'opted_out' => $optedOut,
        ]);
    }
}
