@extends('layouts.site', ['workspace' => true])
@section('title', $lesson->title)
@section('content')
<div class="site-container study-page">
    <a class="site-text-link" href="{{ route('lessons.index') }}">← Back to your library</a>
    <div class="study-page-heading"><div><p class="site-eyebrow">{{ $lesson->subject }}</p><h1>{{ $lesson->title }}</h1><p>Added {{ $lesson->created_at->format('M j, Y') }} · Your own pace, your own progress.</p></div><a class="site-button" href="{{ route('lessons.download', $lesson) }}">Download PDF <span aria-hidden="true">↓</span></a></div>
    @include('partials.feedback')
    <div class="study-library-grid">
        <section class="study-panel"><h2>Edit lesson & track progress</h2><p>Update your notes and set how much of this lesson you’ve studied.</p>
            <form class="study-form" id="edit-lesson" method="POST" action="{{ route('lessons.update', $lesson) }}">@csrf @method('PATCH')
                <label for="title">Lesson title<input id="title" name="title" value="{{ old('title', $lesson->title) }}" maxlength="255" required></label>
                <label for="subject">Subject<input id="subject" name="subject" value="{{ old('subject', $lesson->subject) }}" maxlength="100" required></label>
                <label for="notes">Study notes<textarea id="notes" name="notes" rows="8" maxlength="10000" placeholder="Key ideas, questions, or what you want to review…">{{ old('notes', $lesson->notes) }}</textarea></label>
                <label for="progress">Progress (%)<input type="number" id="progress" name="progress" min="0" max="100" step="1" value="{{ old('progress', $lesson->progress) }}" aria-describedby="progress-help" required><small id="progress-help">Set 100 when you’ve finished studying this lesson.</small></label>
                <button class="site-button" type="submit">Save changes <span aria-hidden="true">✓</span></button>
            </form>
        </section>
        <aside><section class="study-panel"><p class="site-eyebrow">YOUR PROGRESS</p><p class="study-big-number">{{ $lesson->progress }}<small>%</small></p><progress value="{{ $lesson->progress }}" max="100" aria-label="Lesson progress">{{ $lesson->progress }}%</progress><p>{{ $lesson->progress === 100 ? 'One more chapter completed. Nice work.' : 'Every bit of focused study adds up.' }}</p><a class="site-text-link" href="{{ route('dashboard') }}">Start a focus session →</a></section>
            <section class="study-panel study-delete"><h2>Remove this lesson</h2><p>This deletes the uploaded PDF, your notes, and its progress.</p><details><summary>Delete lesson</summary><form method="POST" action="{{ route('lessons.destroy', $lesson) }}">@csrf @method('DELETE')<p>Delete “{{ $lesson->title }}”? This cannot be undone.</p><button type="submit" class="site-button site-button--outline">Yes, delete lesson</button></form></details></section>
        </aside>
    </div>
</div>
@endsection
