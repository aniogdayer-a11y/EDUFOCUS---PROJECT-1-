@extends('layouts.site', ['workspace' => true])
@section('title', 'Dashboard')
@section('content')
<div class="site-container study-page" id="dashboard-home" data-study-user="{{ auth()->id() }}" data-focus-session-url="{{ route('focus-sessions.store') }}" data-completed-sessions="{{ $completedSessions }}" data-current-streak="{{ $currentStreak }}" data-longest-streak="{{ $longestStreak }}">
    <div class="study-page-heading"><div><p class="site-eyebrow">YOUR PERSONAL STUDY SPACE</p><h1>Hello, {{ auth()->user()->name }}! <span class="study-wave" aria-hidden="true">👋</span></h1><p>A little focus today. A little more progress tomorrow.</p></div></div>
    @include('partials.feedback')
    <div class="study-stats">
        <article><span class="site-eyebrow">STUDY STREAK</span><strong data-current-streak-value>{{ $currentStreak }}</strong><span data-current-streak-label>{{ \Illuminate\Support\Str::plural('day', $currentStreak) }} in a row</span></article>
        <article><span class="site-eyebrow">XP</span><strong aria-label="XP not available">—</strong><span>Rewards coming soon</span></article>
        <article><span class="site-eyebrow">LESSONS</span><strong>{{ $totalLessons }}</strong><span>In your library</span></article>
        <article><span class="site-eyebrow">QUIZ AVERAGE</span><strong aria-label="No quiz results">—</strong><span>No quiz results yet</span></article>
    </div>
    <div class="study-upload-action"><a class="site-button" href="{{ route('lessons.index') }}#upload"><span aria-hidden="true">+</span> Upload New Lesson</a></div>
    <div class="study-dashboard-grid study-dashboard-overview">
        <div class="study-section-stack">
            <section class="study-panel">
                <div class="study-panel-heading"><h2>Continue Learning</h2><a class="site-text-link" href="{{ route('lessons.index') }}">My lessons ↗</a></div>
                @forelse ($recentLessons as $lesson)
                    <article class="study-continue-card"><p class="site-eyebrow">{{ $lesson->subject }}</p><h3>{{ $lesson->title }}</h3><div class="study-progress-label"><progress data-lesson-progress="{{ $lesson->id }}" value="{{ $lesson->progress }}" max="100" aria-label="{{ $lesson->title }} progress">{{ $lesson->progress }}%</progress><strong data-lesson-progress-label="{{ $lesson->id }}">{{ $lesson->progress }}%</strong></div><a class="site-button site-button--small site-button--outline" href="{{ route('lessons.show', $lesson) }}">{{ $lesson->progress === 100 ? 'Review' : 'Continue' }} <span aria-hidden="true">→</span></a></article>
                @empty
                    <div class="study-empty"><span class="study-empty-icon" aria-hidden="true">▤</span><h3>Your next chapter is waiting.</h3><p>Upload your first PDF lesson to start learning and tracking your progress.</p><a class="site-text-link" href="{{ route('lessons.index') }}#upload">Add your first lesson +</a></div>
                @endforelse
            </section>
            <section class="study-panel"><div class="study-panel-heading"><h2>Knowledge Gaps</h2><span aria-hidden="true">?</span></div><div class="study-section-empty"><h3>Discover what needs another look.</h3><p>Topic scores will appear here when quizzes are available. Lesson progress measures how much you’ve studied, not your quiz score.</p><span class="site-tag">AWAITING QUIZ RESULTS</span></div></section>
        </div>
        <aside class="study-section-stack">
            <section class="study-timer study-panel" data-pomodoro>
                <p class="site-eyebrow">TODAY’S STUDY</p>
                <div class="study-panel-heading"><h2>Pomodoro Timer</h2><span aria-hidden="true">◷</span></div>
                <div class="study-timer-modes" role="group" aria-label="Timer mode"><button type="button" data-timer-mode="focus" aria-pressed="true">Focus</button><button type="button" data-timer-mode="break" aria-pressed="false">Short break</button></div>
                <p class="study-timer-clock" role="timer" aria-label="Time remaining" data-timer-display>25:00</p>
                <p class="study-timer-message" data-timer-message role="status">Time to focus. One thing at a time.</p>
                <div class="site-actions"><button type="button" class="site-button" data-timer-start>Start Focus Session</button><button type="button" class="study-reset" data-timer-reset aria-label="Reset timer">↺</button></div>
                <dialog class="study-style-dialog" data-style-dialog aria-labelledby="study-style-title">
                    <form method="dialog" data-style-form>
                        <p class="site-eyebrow">MAKE THIS SESSION YOURS</p>
                        <h2 id="study-style-title">Choose a learning style</h2>
                        <p>Choose one PDF or a saved deck, then pick a study format.</p>
                        @if($lessons->isNotEmpty())
                            <label class="study-style-lesson-label" for="study-style-source">PDF or deck
                                <select id="study-style-source" data-style-lesson required>
                                    <optgroup label="Individual PDFs">
                                        @foreach($lessons as $lesson)
                                            <option value="lesson:{{ $lesson->id }}" data-title="{{ $lesson->title }}" data-files="{{ collect([['id' => $lesson->id, 'title' => $lesson->title, 'url' => route('lessons.read', $lesson), 'progressUrl' => route('lessons.read-progress', $lesson)]])->toJson() }}">{{ $lesson->title }} · {{ $lesson->subject }}</option>
                                        @endforeach
                                    </optgroup>
                                    @if($decks->isNotEmpty())
                                        <optgroup label="Saved decks">
                                            @foreach($decks as $deck)
                                                @php
                                                    $deckFiles = $deck->lessons->map(fn($lesson) => ['id' => $lesson->id, 'title' => $lesson->title, 'url' => route('lessons.read', $lesson), 'progressUrl' => route('lessons.read-progress', $lesson)]);
                                                @endphp
                                                <option value="deck:{{ $deck->id }}" data-title="{{ $deck->name }}" data-files="{{ $deckFiles->toJson() }}">{{ $deck->name }} · {{ $deck->lessons->count() }} {{ Str::plural('PDF', $deck->lessons->count()) }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                            </label>
                        @else
                            <p class="study-style-hint">Upload a PDF lesson before starting a study session.</p>
                        @endif
                        <fieldset class="study-style-options">
                            <legend class="auth-sr-only">Learning style for this focus session</legend>
                            @foreach((auth()->user()->learning_styles ?: ['Visual', 'Flashcards', 'Reading', 'Quiz']) as $style)
                                <label><input type="radio" name="learning-style" value="{{ $style }}" @checked($loop->first) required><span>{{ $style }}</span></label>
                            @endforeach
                        </fieldset>
                        @if($lessons->isNotEmpty())
                            <div class="site-actions"><button class="site-button" type="submit" value="start" data-style-confirm>Start focus session</button><button class="site-button site-button--outline" type="submit" value="cancel">Cancel</button></div>
                        @else
                            <a class="site-button" href="{{ route('lessons.index') }}#upload">Upload a lesson <span aria-hidden="true">↗</span></a>
                            <button class="site-button site-button--outline" type="submit" value="cancel">Close</button>
                        @endif
                    </form>
                </dialog>
                <p class="study-timer-caption">25 minutes of focus · 5 minutes of rest</p>
                <p class="study-timer-caption"><span data-session-count>{{ $completedSessions }}</span> completed focus sessions</p>
                <noscript><p>Enable JavaScript to use the focus timer.</p></noscript>
            </section>
            <section class="study-panel"><div class="study-panel-heading"><h2>Upcoming Reviews</h2><span aria-hidden="true">↻</span></div><div class="study-section-empty"><h3>You’re all clear for now.</h3><p>Scheduled flashcard, quiz, and concept reviews will appear here when review tools are available.</p></div></section>
        </aside>
    </div>
    <section class="study-panel study-achievements"><div class="study-panel-heading"><h2>Recent Achievements</h2><span class="site-tag">{{ $totalLessons > 0 ? '1 EARNED' : 'YOUR NEXT MILESTONES' }}</span></div><div class="study-badge-grid">
        <article class="study-badge {{ $totalLessons > 0 ? 'is-earned' : '' }}"><span aria-hidden="true">🏆</span><div><h3>First Lesson</h3><p>{{ $totalLessons > 0 ? 'Earned · You added your first lesson.' : 'Upload your first lesson to earn this badge.' }}</p></div></article>
        <article class="study-badge {{ $longestStreak >= 5 ? 'is-earned' : '' }}" data-five-day-streak><span aria-hidden="true">🔥</span><div><h3>5 Day Streak</h3><p>{{ $longestStreak >= 5 ? 'Earned · You studied five days in a row.' : 'Locked · Study five days in a row to earn this badge.' }}</p></div></article>
        <article class="study-badge"><span aria-hidden="true">⭐</span><div><h3>Quiz Master</h3><p>Locked · Quiz achievements coming soon.</p></div></article>
    </div></section>
</div>
<section class="study-reading-pane study-study-overlay" data-study-overlay data-reading-pane hidden aria-label="Study material">
    <div class="study-reading-heading">
        <h2 data-reading-title>Study session</h2>
        <button type="button" class="study-reading-close" data-reading-close aria-label="Close study view">×</button>
    </div>
    <div class="study-reading-toolbar">
        <button type="button" class="study-reading-nav" data-reading-previous disabled>← Previous PDF</button>
        <p class="study-reading-timer" aria-label="Focus timer remaining"><span aria-hidden="true">FOCUS</span><strong data-reading-clock>25:00</strong></p>
        <p class="study-reading-position" data-reading-position></p>
        <button type="button" class="study-reading-nav" data-reading-next disabled>Next PDF →</button>
    </div>
    <div class="study-pdf-reader" data-pdf-reader hidden>
        <div class="study-pdf-toolbar">
            <button type="button" class="study-reading-nav" data-page-previous disabled>← Previous page</button>
            <p data-page-position role="status">Page 0 of 0</p>
            <p data-page-progress>Lesson progress: 0%</p>
            <button type="button" class="study-reading-nav" data-page-next disabled>Next page →</button>
        </div>
        <div class="study-pdf-canvas-wrap" data-pdf-canvas-wrap><canvas data-pdf-canvas aria-label="Current PDF page"></canvas></div>
    </div>
    <div class="study-generated-material" data-study-material hidden aria-live="polite"></div>
</section>
@include('partials.about-section')
@endsection
