<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lesson extends Model
{
    protected $fillable = ['title', 'subject', 'notes', 'file_path', 'progress'];

    protected function casts(): array
    {
        return ['progress' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studyDecks(): BelongsToMany
    {
        return $this->belongsToMany(StudyDeck::class, 'study_deck_lesson')->withTimestamps();
    }
}
