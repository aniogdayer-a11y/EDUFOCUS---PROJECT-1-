<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonReadProgressController extends Controller
{
    public function store(Request $request, int $lesson): JsonResponse
    {
        $record = $request->user()->lessons()->findOrFail($lesson);
        $data = $request->validate([
            'page_number' => ['required', 'integer', 'min:1', 'max:10000'],
            'total_pages' => ['required', 'integer', 'min:1', 'max:10000', 'gte:page_number'],
        ]);

        $progress = DB::transaction(function () use ($record, $data): int {
            DB::table('lesson_read_pages')->insertOrIgnore([
                'lesson_id' => $record->id,
                'page_number' => $data['page_number'],
                'total_pages' => $data['total_pages'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pagesRead = DB::table('lesson_read_pages')
                ->where('lesson_id', $record->id)
                ->count();
            $trackedProgress = (int) round(($pagesRead / $data['total_pages']) * 100);

            /** @var Lesson $lockedLesson */
            $lockedLesson = Lesson::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $lockedLesson->progress = max($lockedLesson->progress, $trackedProgress);
            $lockedLesson->save();

            return $lockedLesson->progress;
        });

        return response()->json([
            'lessonId' => $record->id,
            'pageNumber' => $data['page_number'],
            'totalPages' => $data['total_pages'],
            'progress' => $progress,
        ]);
    }
}
