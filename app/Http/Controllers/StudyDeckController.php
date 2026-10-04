<?php

namespace App\Http\Controllers;

use App\Models\StudyDeck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudyDeckController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('study_decks', 'name')->where('user_id', $request->user()->id)],
            'lesson_ids' => ['required', 'array', 'min:1'],
            'lesson_ids.*' => ['required', 'integer', 'distinct', 'exists:lessons,id'],
        ]);
        $lessonIds = $this->ownedLessonIds($request, $data['lesson_ids']);

        $deck = DB::transaction(function () use ($request, $data, $lessonIds) {
            $deck = $request->user()->studyDecks()->create(['name' => $data['name']]);
            $deck->lessons()->sync($lessonIds);

            return $deck;
        });

        return to_route('lessons.index')->with('success', 'Study deck created.');
    }

    public function update(Request $request, StudyDeck $deck)
    {
        abort_unless($deck->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('study_decks', 'name')->where('user_id', $request->user()->id)->ignore($deck->id)],
            'lesson_ids' => ['required', 'array', 'min:1'],
            'lesson_ids.*' => ['required', 'integer', 'distinct', 'exists:lessons,id'],
        ]);
        $lessonIds = $this->ownedLessonIds($request, $data['lesson_ids']);

        DB::transaction(function () use ($deck, $data, $lessonIds) {
            $deck->update(['name' => $data['name']]);
            $deck->lessons()->sync($lessonIds);
        });

        return to_route('lessons.index')->with('success', 'Study deck updated.');
    }

    public function destroy(Request $request, StudyDeck $deck)
    {
        abort_unless($deck->user_id === $request->user()->id, 404);
        $deck->delete();

        return to_route('lessons.index')->with('success', 'Study deck deleted.');
    }

    private function ownedLessonIds(Request $request, array $lessonIds): array
    {
        $ownedIds = $request->user()->lessons()->whereKey($lessonIds)->pluck('id')->all();
        if (count($ownedIds) !== count($lessonIds)) {
            throw ValidationException::withMessages([
                'lesson_ids' => 'Choose only lessons from your own library.',
            ]);
        }

        return $ownedIds;
    }
}
