<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeepAliveController extends Controller
{
    /**
     * Touch the session to prevent it from expiring.
     *
     * This endpoint is called periodically by the frontend JavaScript
     * to keep the authenticated session alive for standby applications.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->session()->put('last_keep_alive', now()->toIso8601String());

        return response()->json(['status' => 'ok']);
    }
}
