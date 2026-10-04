<article class="study-lesson-card">
    <div class="study-lesson-top"><span class="study-file-icon" aria-hidden="true">PDF</span><span class="site-tag">{{ $lesson->progress === 100 ? 'Completed' : ($lesson->progress > 0 ? 'In progress' : 'Ready to start') }}</span></div>
    <p class="site-eyebrow">{{ $lesson->subject }}</p><h3><a href="{{ route('lessons.show', $lesson) }}">{{ $lesson->title }}</a></h3>
    <div class="study-progress-label"><span>Your progress</span><strong>{{ $lesson->progress }}%</strong></div>
    <progress value="{{ $lesson->progress }}" max="100" aria-label="{{ $lesson->title }} progress">{{ $lesson->progress }}%</progress>
    <a class="site-text-link" href="{{ route('lessons.show', $lesson) }}">{{ $lesson->progress === 100 ? 'Review lesson' : 'Open lesson' }} <span aria-hidden="true">→</span></a>
</article>
