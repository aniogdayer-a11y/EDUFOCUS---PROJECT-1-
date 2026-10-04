@extends('layouts.site', ['workspace' => true])
@section('title', 'My lessons')
@section('content')
<div class="site-container study-page">
    <div class="study-page-heading"><div><p class="site-eyebrow">LESS CLUTTER. MORE CLARITY.</p><h1>My Lessons</h1><p>Your lessons, organized. Your progress, in one place.</p></div><a class="site-button" href="#upload"><span aria-hidden="true">+</span> Upload Lesson</a></div>
    @include('partials.feedback')
    <section class="study-deck-library" aria-labelledby="study-decks-heading">
        <div class="study-panel-heading"><div><p class="site-eyebrow">PICK EXACTLY WHAT YOU STUDY</p><h2 id="study-decks-heading">Study decks</h2></div></div>
        @if($allLessons->isEmpty())
            <p class="study-deck-empty">Upload PDFs to create a deck from the files you choose.</p>
        @else
            <details class="study-panel study-deck-editor">
                <summary class="site-button site-button--small">Create a deck <span aria-hidden="true">+</span></summary>
                <form method="POST" action="{{ route('study-decks.store') }}" class="study-form">
                    @csrf
                    <label for="new-deck-name">Deck name<input id="new-deck-name" name="name" value="{{ old('name') }}" maxlength="100" placeholder="e.g. Biology midterm" required></label>
                    <fieldset class="study-deck-files">
                        <legend>Choose PDFs for this deck</legend>
                        @foreach($allLessons as $lesson)
                            <label><input type="checkbox" name="lesson_ids[]" value="{{ $lesson->id }}" @checked(in_array($lesson->id, old('lesson_ids', [])))><span>{{ $lesson->title }}<small>{{ $lesson->subject }}</small></span></label>
                        @endforeach
                    </fieldset>
                    <button class="site-button" type="submit">Save deck <span aria-hidden="true">✓</span></button>
                </form>
            </details>
            @if($decks->isNotEmpty())
                <div class="study-deck-grid">
                    @foreach($decks as $deck)
                        <details class="study-panel study-deck-card">
                            <summary><span class="study-deck-icon" aria-hidden="true">▤</span><span><strong>{{ $deck->name }}</strong><small>{{ $deck->lessons->count() }} {{ Str::plural('PDF', $deck->lessons->count()) }}</small></span><span aria-hidden="true">↗</span></summary>
                            <ul class="study-deck-members">@foreach($deck->lessons as $lesson)<li>{{ $lesson->title }} <small>· {{ $lesson->subject }}</small></li>@endforeach</ul>
                            <form method="POST" action="{{ route('study-decks.update', $deck) }}" class="study-form study-deck-edit-form">
                                @csrf @method('PUT')
                                <label for="deck-name-{{ $deck->id }}">Deck name<input id="deck-name-{{ $deck->id }}" name="name" value="{{ $deck->name }}" maxlength="100" required></label>
                                <fieldset class="study-deck-files">
                                    <legend>PDFs in this deck</legend>
                                    @foreach($allLessons as $lesson)
                                        <label><input type="checkbox" name="lesson_ids[]" value="{{ $lesson->id }}" @checked($deck->lessons->contains('id', $lesson->id))><span>{{ $lesson->title }}<small>{{ $lesson->subject }}</small></span></label>
                                    @endforeach
                                </fieldset>
                                <button class="site-button site-button--small" type="submit">Save changes</button>
                            </form>
                            <form method="POST" action="{{ route('study-decks.destroy', $deck) }}" class="study-deck-delete-form">
                                @csrf @method('DELETE')
                                <button class="site-text-link" type="submit">Delete deck</button>
                            </form>
                        </details>
                    @endforeach
                </div>
            @endif
        @endif
    </section>
    <div class="study-library-grid">
        <section aria-label="Your lessons">
            <form class="study-search" method="GET" action="{{ route('lessons.index') }}"><label class="auth-sr-only" for="lesson-search">Search lessons</label><input type="search" name="q" id="lesson-search" placeholder="Search title or subject…" value="{{ request('q') }}" maxlength="100"><label class="auth-sr-only" for="lesson-status">Filter by progress</label><select id="lesson-status" name="status"><option value="">All lessons</option>@foreach(['new' => 'Not started', 'in-progress' => 'In progress', 'completed' => 'Completed'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select><button class="site-button site-button--small" type="submit">Search</button></form>
            <p class="study-results">{{ $lessons->total() }} {{ Str::plural('lesson', $lessons->total()) }} @if(request('q') || request('status'))<a href="{{ route('lessons.index') }}">Clear filters</a>@endif</p>
            <div class="study-table-wrap">
                <table class="study-lessons-table">
                    <caption class="auth-sr-only">My lessons with category, progress, and actions</caption>
                    <thead><tr><th scope="col">Lesson</th><th scope="col">Category</th><th scope="col">Progress</th><th scope="col">Actions</th></tr></thead>
                    <tbody>
                        @forelse($lessons as $lesson)
                            <tr>
                                <th scope="row"><a href="{{ route('lessons.show', $lesson) }}">{{ $lesson->title }}</a><small>PDF LESSON</small></th>
                                <td data-label="Category"><span class="site-tag">{{ $lesson->subject }}</span></td>
                                <td data-label="Progress"><div class="study-table-progress"><progress value="{{ $lesson->progress }}" max="100" aria-label="{{ $lesson->title }} progress">{{ $lesson->progress }}%</progress><span>{{ $lesson->progress }}%</span></div></td>
                                <td data-label="Actions"><div class="study-row-actions"><a href="{{ route('lessons.show', $lesson) }}">View<span class="auth-sr-only"> {{ $lesson->title }}</span></a><a href="{{ route('lessons.show', $lesson) }}#edit-lesson">Edit<span class="auth-sr-only"> {{ $lesson->title }}</span></a><details class="study-row-delete"><summary>Delete<span class="auth-sr-only"> {{ $lesson->title }}</span></summary><form method="POST" action="{{ route('lessons.destroy', $lesson) }}">@csrf @method('DELETE')<p>Delete “{{ $lesson->title }}” and its PDF? This cannot be undone.</p><button type="submit" class="site-button site-button--small">Confirm delete</button></form></details></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="study-empty"><span class="study-empty-icon" aria-hidden="true">▤</span><h2>{{ request('q') || request('status') ? 'No matching lessons.' : 'A fresh page awaits.' }}</h2><p>{{ request('q') || request('status') ? 'Try another title, category, or progress filter.' : 'Upload a PDF to begin building your study library.' }}</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($lessons->hasPages())<nav class="study-pagination" aria-label="Lesson pages">@if($lessons->previousPageUrl())<a class="site-button site-button--outline site-button--small" href="{{ $lessons->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $lessons->currentPage() }} of {{ $lessons->lastPage() }}</span>@if($lessons->nextPageUrl())<a class="site-button site-button--outline site-button--small" href="{{ $lessons->nextPageUrl() }}">Next →</a>@endif</nav>@endif
        </section>
        <aside class="study-panel study-upload" id="upload"><p class="site-eyebrow">ADD A NEW CHAPTER</p><h2>Upload a lesson</h2><p>Bring your own PDF. Keep your notes and progress together.</p>
            <form method="POST" action="{{ route('lessons.store') }}" enctype="multipart/form-data" class="study-form">
                @csrf
                <label for="title">Lesson title<input id="title" name="title" value="{{ old('title') }}" placeholder="e.g. Introduction to Biology" maxlength="255" required></label>
                <label for="subject">Category<input id="subject" name="subject" value="{{ old('subject') }}" placeholder="e.g. Networking" maxlength="100" required></label>
                <label for="pdf" class="study-file-label"><span class="study-empty-icon" aria-hidden="true">↑</span>Choose your PDF<input id="pdf" type="file" name="pdf" accept="application/pdf,.pdf" aria-describedby="pdf-help" required><small id="pdf-help">PDF only · Up to 2 MB</small></label>
                <label for="notes">Study notes <span class="study-optional">(optional)</span><textarea id="notes" name="notes" rows="3" maxlength="10000" placeholder="What would you like to learn?">{{ old('notes') }}</textarea></label>
                <button type="submit" class="site-button">Add to my library <span aria-hidden="true">+</span></button>
            </form>
        </aside>
    </div>
</div>
@endsection
