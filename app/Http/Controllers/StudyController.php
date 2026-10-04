<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StudyStreakService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudyController extends Controller
{
    public function dashboard(Request $request, StudyStreakService $streaks)
    {
        $lessons = $request->user()->lessons();
        $studyStats = $streaks->summary($request->user());

        return view('dashboard', [
            ...$studyStats,
            'totalLessons' => (clone $lessons)->count(),
            'completedLessons' => (clone $lessons)->where('progress', 100)->count(),
            'averageProgress' => (int) round((clone $lessons)->avg('progress') ?? 0),
            'recentLessons' => (clone $lessons)->latest('updated_at')->limit(3)->get(),
            'lessons' => (clone $lessons)->orderBy('title')->get(),
            'decks' => $request->user()->studyDecks()->with('lessons:id,title,subject')->orderBy('name')->get(),
        ]);
    }

    public function profile(Request $request, StudyStreakService $streaks)
    {
        return view('profile', [
            'user' => $request->user(),
            ...$streaks->summary($request->user()),
            'totalLessons' => $request->user()->lessons()->count(),
            'completedLessons' => $request->user()->lessons()->where('progress', 100)->count(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'learning_styles_present' => ['sometimes', 'in:1'],
            'learning_styles' => ['sometimes', 'array', 'max:4'],
            'learning_styles.*' => ['string', 'distinct', Rule::in(['Visual', 'Flashcards', 'Reading', 'Quiz'])],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
        ]);
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        if ($request->has('learning_styles_present') || array_key_exists('learning_styles', $data)) {
            $user->learning_styles = $data['learning_styles'] ?? [];
        }
        $oldAvatar = $user->avatar_path;
        $newAvatar = null;
        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars/'.$user->id, 'local');
            abort_unless($newAvatar, 500, 'The profile picture could not be saved. Please try again.');
            $user->avatar_path = $newAvatar;
        }
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        try {
            $user->save();
        } catch (\Throwable $error) {
            if ($newAvatar) {
                Storage::disk('local')->delete($newAvatar);
            }
            throw $error;
        }
        if ($newAvatar && $oldAvatar) {
            Storage::disk('local')->delete($oldAvatar);
        }

        return to_route('profile')->with('success', 'Your profile has been updated.');
    }

    public function photo(Request $request)
    {
        $path = $request->user()->avatar_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
