@extends('layouts.site', ['workspace' => true])
@section('title', 'My profile')
@section('content')
<div class="site-container study-page" data-study-user="{{ auth()->id() }}" data-completed-sessions="{{ $completedSessions }}" data-current-streak="{{ $currentStreak }}" data-longest-streak="{{ $longestStreak }}">
    <div class="study-page-heading"><div><p class="site-eyebrow">YOUR SPACE, YOUR PACE</p><h1>My Profile</h1><p>A study space that feels like you.</p></div><a class="site-text-link" href="{{ route('dashboard') }}">Back to dashboard ↗</a></div>
    @include('partials.feedback')
    <div class="study-profile-grid study-profile-overview">
        <section class="study-panel study-profile-details">
            <div class="study-profile-identity">
                @if($user->avatar_path)
                    <img class="study-avatar study-avatar--photo" src="{{ route('profile.photo', ['v' => $user->updated_at->timestamp]) }}" alt="{{ $user->name }}’s profile picture" width="96" height="96">
                @else
                    <div class="study-avatar" role="img" aria-label="Profile picture placeholder">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                @endif
                <div><span class="site-tag">STUDENT</span><p>Member since {{ $user->created_at->format('F Y') }}</p></div>
            </div>
            <dl class="study-account-details"><div><dt>Name</dt><dd>{{ $user->name }}</dd></div><div><dt>Email</dt><dd><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></dd></div></dl>
            <h2 class="study-preferences-heading">Preferred Learning Style</h2>
            <div class="study-preference-tags">@foreach(['Visual', 'Flashcards', 'Reading', 'Quiz'] as $style)<span class="study-preference-tag {{ in_array($style, $user->learning_styles ?? []) ? 'is-selected' : '' }}">@if(in_array($style, $user->learning_styles ?? []))<span aria-hidden="true">✓ </span>@endif{{ $style }}<span class="auth-sr-only">{{ in_array($style, $user->learning_styles ?? []) ? ', selected' : ', not selected' }}</span></span>@endforeach</div>
            @if(empty($user->learning_styles))<p class="study-preference-help">Choose your preferences using Edit Profile.</p>@endif
        </section>
        <section class="study-panel study-statistics"><p class="site-eyebrow">EVERY SMALL STEP COUNTS</p><h2>Study Statistics</h2><dl>
            <div><dt>Total Lessons</dt><dd>{{ $totalLessons }}</dd></div>
            <div><dt>Total XP</dt><dd>—<small>Coming soon</small></dd></div>
            <div><dt>Study Streak</dt><dd><span data-current-streak-value>{{ $currentStreak }}</span><small data-current-streak-label>{{ \Illuminate\Support\Str::plural('day', $currentStreak) }} in a row</small></dd></div>
            <div><dt>Completed Focus Sessions</dt><dd><span data-session-count>{{ $completedSessions }}</span><small>Across your account</small></dd></div>
            <div><dt>Badges Earned</dt><dd>{{ $totalLessons > 0 ? 1 : 0 }}<small>{{ $totalLessons > 0 ? 'First Lesson' : 'Upload a lesson to start' }}</small></dd></div>
        </dl></section>
    </div>
    <details class="study-profile-editor" id="edit-profile" @if($errors->any()) open @endif>
        <summary class="site-button">Edit Profile <span aria-hidden="true">↗</span></summary>
        <section class="study-panel"><h2>Edit your profile</h2><form class="study-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PATCH')
            <div class="study-edit-grid"><label for="name">Full name<input id="name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" maxlength="255" required></label><label for="email">Email address<input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" maxlength="255" required></label></div>
            <label for="avatar">Profile picture<input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" aria-describedby="avatar-help"><small id="avatar-help">JPG, PNG, or WebP · Up to 2 MB. Leave blank to keep your picture.</small></label>
            <fieldset class="study-preference-inputs"><legend>Preferred Learning Style</legend><input type="hidden" name="learning_styles_present" value="1">@foreach(['Visual', 'Flashcards', 'Reading', 'Quiz'] as $style)<label><input type="checkbox" name="learning_styles[]" value="{{ $style }}" @checked(in_array($style, old('learning_styles', old('learning_styles_present') ? [] : ($user->learning_styles ?? []))))><span>{{ $style }}</span></label>@endforeach</fieldset>
            <button class="site-button" type="submit">Save profile <span aria-hidden="true">✓</span></button>
        </form></section>
    </details>
</div>
@endsection
