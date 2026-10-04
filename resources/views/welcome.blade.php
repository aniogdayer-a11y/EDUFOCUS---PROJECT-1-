@extends('layouts.site')
@section('title', 'Home')
@section('content')
    <section class="site-hero site-container" id="home">
        <div class="site-hero-copy">
            <p class="site-eyebrow"><span class="site-square"></span> YOUR NEXT CHAPTER STARTS HERE</p>
            <h1>EduFocus<span>Interactive Learning<br>for Better Focus<span class="site-caret" aria-hidden="true">_</span></span></h1>
            <p class="site-lead">Transform your PDF lessons into short, interactive and easier-to-study learning materials.</p>
            <div class="site-actions">
                <a class="site-button site-button--outline" href="#how-it-works">Learn More <span aria-hidden="true">↓</span></a>
            </div>
            <div class="site-hero-note"><span aria-hidden="true">✛</span> Less overwhelm. More little wins.</div>
        </div>
        <div class="site-hero-art">
            <div class="site-window-bar"><span aria-hidden="true">■ ■ ▪</span><span>STUDY_BUDDY.EXE</span><span aria-hidden="true">↗</span></div>
            <div class="site-buddy-caption"><span>ONE LESSON AT A TIME.</span><p>Your focus era<br>starts here.</p></div>
            @include('auth.study-scene')
        </div>
    </section>
    <div class="site-ticker" aria-label="Study approach"><div class="site-container"><span>SHORTER LESSONS</span><span aria-hidden="true">✛</span><span>ACTIVE LEARNING</span><span aria-hidden="true">✛</span><span>BETTER FOCUS</span><span aria-hidden="true">✛</span><span>YOUR OWN PACE</span></div></div>

    <section class="site-section site-container" id="how-it-works">
        <div class="site-section-heading"><div><p class="site-eyebrow">01 / THE STUDY ROUTINE</p><h2>How EduFocus works</h2></div><p>From a full lesson to a small next step.<br>A simpler way to keep moving forward.</p></div>
        <ol class="site-steps">
            @foreach ([['Upload PDF', 'Bring your lesson notes into one study space.'], ['Analyze Lesson', 'Find the concepts that matter most.'], ['Generate Learning Materials', 'Break the lesson into manageable pieces.'], ['Study & Take Quizzes', 'Practice, recall, and build understanding.'], ['Track Progress', 'See how far your small steps take you.']] as [$heading, $description])
                <li><span class="site-step-number">0{{ $loop->iteration }}</span><h3>{{ $heading }}</h3><p>{{ $description }}</p><span class="site-step-arrow" aria-hidden="true">→</span></li>
            @endforeach
        </ol>
    </section>

    <section class="site-feature-section" id="features">
        <div class="site-container site-section">
            <div class="site-section-heading"><div><p class="site-eyebrow">02 / YOUR STUDY TOOLKIT</p><h2>A little structure.<br>A lot more focus.</h2></div><p>Tools designed around the way you learn,<br>one manageable session at a time.</p></div>
            <div class="site-features">
                @foreach ([['↑', 'PDF Lesson Upload', 'Keep your PDF lessons organized and ready for your next study session.'], ['▤', 'Visual Summaries', 'See the big ideas in shorter, easier-to-follow overviews.'], ['▱', 'Flashcards', 'Turn key concepts into quick moments of active recall.'], ['?', 'Interactive Quizzes', 'Check your understanding and revisit what needs practice.'], ['◷', 'Pomodoro Timer', 'Make room for focused study and well-earned breaks.'], ['↗', 'Adaptive Learning', 'A learning experience designed to grow with your needs.'], ['▥', 'Progress Tracking', 'Keep track of your lessons and celebrate each completed step.'], ['★', 'XP and Achievements', 'Make consistent effort feel rewarding, one milestone at a time.']] as [$symbol, $heading, $description])
                    <article class="site-feature-card"><span class="site-feature-icon" aria-hidden="true">{{ $symbol }}</span><span class="site-card-index">0{{ $loop->iteration }}</span><h3>{{ $heading }}</h3><p>{{ $description }}</p></article>
                @endforeach
            </div>
        </div>
    </section>
    @include('partials.about-section')
@endsection
