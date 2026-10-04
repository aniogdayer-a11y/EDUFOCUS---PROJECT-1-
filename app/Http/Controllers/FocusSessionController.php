<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StudyStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FocusSessionController extends Controller
{
    public function store(Request $request, StudyStreakService $streaks): JsonResponse
    {
        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
        ]);
        /** @var User $user */
        $user = $request->user();
        $now = now();

        DB::table('study_sessions')->insertOrIgnore([
            'user_id' => $user->id,
            'completion_key' => $data['completion_key'],
            'study_date' => $now->toDateString(),
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json($streaks->summary($user));
    }
}
