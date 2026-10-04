<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\PdfLessonService;
use App\Services\VisualLessonService;

class LessonController extends Controller
{
    private function ownedLesson(Request $request, int $lesson): Lesson
    {
        return $request->user()->lessons()->findOrFail($lesson);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:new,in-progress,completed'],
        ]);
        $query = $request->user()->lessons();
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%');
            });
        }
        match ($filters['status'] ?? '') {
            'new' => $query->where('progress', 0),
            'in-progress' => $query->whereBetween('progress', [1, 99]),
            'completed' => $query->where('progress', 100),
            default => null,
        };

        return view('lessons', [
            'lessons' => $query->latest()->paginate(8)->withQueryString(),
            'allLessons' => $request->user()->lessons()->orderBy('title')->get(),
            'decks' => $request->user()->studyDecks()->with('lessons:id,title,subject')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:2048'],
        ]);
        $path = $request->file('pdf')->store('lessons/'.$request->user()->id, 'local');
        abort_unless($path, 500, 'The PDF could not be saved. Please try again.');
        try {
            $lesson = $request->user()->lessons()->create([
                'title' => $data['title'], 'subject' => $data['subject'],
                'notes' => $data['notes'] ?? null, 'file_path' => $path,
            ]);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return to_route('lessons.show', $lesson)->with('success', 'Lesson uploaded. Your next study session is ready.');
    }

    public function show(Request $request, int $lesson)
    {
        return view('lesson', ['lesson' => $this->ownedLesson($request, $lesson)]);
    }

    public function update(Request $request, int $lesson)
    {
        $record = $this->ownedLesson($request, $lesson);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'progress' => ['required', 'integer', 'between:0,100'],
        ]);
        $record->update($data);

        return to_route('lessons.show', $record)->with('success', 'Lesson and progress saved.');
    }

    public function download(Request $request, int $lesson)
    {
        $record = $this->ownedLesson($request, $lesson);
        abort_unless(Storage::disk('local')->exists($record->file_path), 404);

        return Storage::disk('local')->download($record->file_path, 'lesson-'.$record->id.'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function read(Request $request, int $lesson)
    {
        $record = $this->ownedLesson($request, $lesson);
        abort_unless(Storage::disk('local')->exists($record->file_path), 404);

        return response()->file(Storage::disk('local')->path($record->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="lesson-'.$record->id.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Request $request, int $lesson)
    {
        $record = $this->ownedLesson($request, $lesson);
        $path = $record->file_path;
        $record->delete();
        Storage::disk('local')->delete($path);

        return to_route('lessons.index')->with('success', 'Lesson deleted.');
    }
}
