<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

class StudyStreakService
{
    /**
     * @return array{completedSessions: int, currentStreak: int, longestStreak: int}
     */
    public function summary(User $user): array
    {
        $dates = $user->focusSessions()
            ->select('study_date')
            ->distinct()
            ->orderBy('study_date')
            ->pluck('study_date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->startOfDay())
            ->unique()
            ->values();

        $longestStreak = 0;
        $runLength = 0;
        $previousDate = null;
        foreach ($dates as $date) {
            $runLength = $previousDate && $previousDate->addDay()->equalTo($date) ? $runLength + 1 : 1;
            $longestStreak = max($longestStreak, $runLength);
            $previousDate = $date;
        }

        $currentStreak = 0;
        $expectedDate = CarbonImmutable::today();
        if ($dates->isNotEmpty() && $dates->last()->lt($expectedDate)) {
            $expectedDate = $expectedDate->subDay();
        }
        foreach ($dates->reverse() as $date) {
            if (!$date->equalTo($expectedDate)) {
                break;
            }
            $currentStreak++;
            $expectedDate = $expectedDate->subDay();
        }

        return [
            'completedSessions' => $user->focusSessions()->count(),
            'currentStreak' => $currentStreak,
            'longestStreak' => $longestStreak,
        ];
    }
}
