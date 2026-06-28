<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Emmadonjo\Naija\Naija;
use Emmadonjo\Naija\Exception\StateException;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    public function states()
    {
        $states = Cache::rememberForever('nigeria_states', function () {
            return collect(Naija::states())
                ->map(fn ($s, $key) => [
                    'key'  => $key,
                    'name' => $s['name'],
                ])
                ->values()
                ->sortBy('name')
                ->values()
                ->toArray();
        });

        return response()->json(['success' => true, 'data' => $states]);
    }

    public function lgas(string $state)
    {
        $key = strtolower(trim(str_replace([' ', '-'], '_', $state)));

        $cacheKey = "nigeria_lgas_{$key}";

        try {
            $lgas = Cache::rememberForever($cacheKey, function () use ($key) {
                $stateData = Naija::state($key);
                $attrs = $stateData->getAttributes();

                return collect($attrs['lga'] ?? [])
                    ->map(fn ($lga) => $lga['name'])
                    ->values()
                    ->sort()
                    ->values()
                    ->toArray();
            });

            return response()->json(['success' => true, 'data' => $lgas]);
        } catch (StateException $e) {
            return response()->json(['success' => false, 'message' => 'State not found.'], 404);
        }
    }
}
