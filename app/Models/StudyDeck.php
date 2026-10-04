<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudyDeck extends Model
{
    protected $fillable = ['name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'study_deck_lesson')->withTimestamps();
    }
}
