<?php

namespace App\Http\Controllers;

use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(private AlertService $alerts)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->alerts->pending($request->user()));
    }

    public function dismiss(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:191'],
        ]);

        $this->alerts->dismiss($request->user(), $data['key']);

        return response()->json($this->alerts->pending($request->user()));
    }

    public function dismissAll(Request $request): JsonResponse
    {
        $this->alerts->dismissAll($request->user());

        return response()->json($this->alerts->pending($request->user()));
    }
}
