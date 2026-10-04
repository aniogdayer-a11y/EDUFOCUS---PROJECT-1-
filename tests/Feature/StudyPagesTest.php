<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_and_protected_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Interactive Learning')
            ->assertSee('How EduFocus works')
            ->assertSee('id="home"', false)
            ->assertSee('id="features"', false)
            ->assertSee('id="about"', false)
            ->assertSee(route('home').'#home', false)
            ->assertSee(route('home').'#features', false)
            ->assertSee(route('home').'#about', false)
            ->assertSee('What is')
            ->assertSee('OUR SOLUTION');
        $this->get('/about')->assertRedirect(route('home').'#about');
        foreach (['/dashboard', '/lessons', '/profile'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
        $this->postJson(route('focus-sessions.store'), ['completion_key' => (string) Str::uuid()])
            ->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Time to focus')
            ->assertSee('id="dashboard-home"', false)
            ->assertSee('id="about"', false)
            ->assertSee(route('dashboard').'#dashboard-home', false)
            ->assertSee(route('dashboard').'#about', false)
            ->assertSee('Choose a learning style')
            ->assertSee('value="Visual"', false)
            ->assertSee('value="Flashcards"', false)
            ->assertSee('value="Reading"', false)
            ->assertSee('value="Quiz"', false)
            ->assertSee('data-study-overlay', false)
            ->assertSee('data-reading-pane', false)
            ->assertSee('data-pdf-reader', false)
            ->assertSee('data-pdf-canvas', false)
            ->assertSee('data-page-progress', false)
            ->assertSee('data-reading-clock', false)
            ->assertSee('data-study-material', false)
            ->assertSee('What is')
            ->assertDontSee('>Dashboard</a>', false)
            ->assertViewHas('totalLessons', 0);
        $this->get('/lessons')->assertOk()->assertSee('Upload a lesson');
        $this->get('/profile')->assertOk()->assertSee('Save profile');
    }

    public function test_a_student_can_upload_edit_download_and_delete_their_lesson(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/lessons', [
            'title' => 'Cell Biology', 'subject' => 'Science', 'notes' => 'Review cell structures.',
            'pdf' => UploadedFile::fake()->createWithContent('cells.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"),
        ]);
        $lesson = $user->lessons()->sole();
        $response->assertRedirect(route('lessons.show', $lesson));
        Storage::disk('local')->assertExists($lesson->file_path);
        $this->get(route('lessons.show', $lesson))->assertOk()->assertSee('Cell Biology');
        $this->get(route('lessons.read', $lesson))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="lesson-'.$lesson->id.'.pdf"');
        $this->get(route('lessons.download', $lesson))->assertOk()->assertDownload('lesson-'.$lesson->id.'.pdf');
        $this->patch(route('lessons.update', $lesson), [
            'title' => 'Cell Biology Review', 'subject' => 'Science', 'notes' => 'Finished the reading.', 'progress' => 100,
        ])->assertRedirect(route('lessons.show', $lesson));
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => 'Cell Biology Review', 'progress' => 100]);
        $this->get('/dashboard')->assertViewHas('completedLessons', 1)->assertViewHas('averageProgress', 100);
        $this->delete(route('lessons.destroy', $lesson))->assertRedirect(route('lessons.index'));
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        Storage::disk('local')->assertMissing($lesson->file_path);
    }

    public function test_focus_session_can_select_an_owned_pdf_for_study_formats(): void
    {
        $student = User::factory()->create();
        $lesson = $student->lessons()->create([
            'title' => 'Cell Biology',
            'subject' => 'Science',
            'file_path' => 'lessons/cell-biology.pdf',
        ]);
        $otherStudent = User::factory()->create();
        $otherLesson = $otherStudent->lessons()->create([
            'title' => 'Private lesson',
            'subject' => 'Math',
            'file_path' => 'lessons/private.pdf',
        ]);

        $this->actingAs($student)->get('/dashboard')
            ->assertOk()
            ->assertSee('Choose one PDF or a saved deck, then pick a study format.', false)
            ->assertSee('data-title="Cell Biology"', false)
            ->assertSee('data-files=', false)
            ->assertSee(str_replace('/', '\\/', route('lessons.read', $lesson)), false)
            ->assertDontSee('Private lesson');
        $this->assertNotSame($lesson->id, $otherLesson->id);
    }

    public function test_reading_pdf_pages_updates_lesson_progress_once_and_checks_ownership(): void
    {
        $student = User::factory()->create();
        $lesson = $student->lessons()->create([
            'title' => 'Cell Biology',
            'subject' => 'Science',
            'file_path' => 'lessons/cell-biology.pdf',
        ]);
        $privateLesson = User::factory()->create()->lessons()->create([
            'title' => 'Private lesson',
            'subject' => 'Science',
            'file_path' => 'lessons/private.pdf',
        ]);

        $this->actingAs($student)
            ->postJson(route('lessons.read-progress', $lesson), ['page_number' => 1, 'total_pages' => 4])
            ->assertOk()
            ->assertJson(['lessonId' => $lesson->id, 'pageNumber' => 1, 'totalPages' => 4, 'progress' => 25]);
        $this->postJson(route('lessons.read-progress', $lesson), ['page_number' => 1, 'total_pages' => 4])
            ->assertOk()
            ->assertJson(['progress' => 25]);
        $this->postJson(route('lessons.read-progress', $lesson), ['page_number' => 4, 'total_pages' => 4])
            ->assertOk()
            ->assertJson(['progress' => 50]);
        $this->postJson(route('lessons.read-progress', $lesson), ['page_number' => 5, 'total_pages' => 4])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('total_pages');
        $this->postJson(route('lessons.read-progress', $privateLesson), ['page_number' => 1, 'total_pages' => 4])
            ->assertNotFound();

        $this->assertDatabaseCount('lesson_read_pages', 2);
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'progress' => 50]);
        $this->assertDatabaseHas('lessons', ['id' => $privateLesson->id, 'progress' => 0]);
    }

    public function test_completed_focus_sessions_are_saved_once_and_update_consecutive_day_streaks(): void
    {
        $student = User::factory()->create();
        try {
            Carbon::setTestNow('2026-10-01 23:59:00');
            $firstKey = (string) Str::uuid();
            $this->actingAs($student)->postJson(route('focus-sessions.store'), ['completion_key' => $firstKey])
                ->assertOk()
                ->assertJson(['completedSessions' => 1, 'currentStreak' => 1, 'longestStreak' => 1]);
            $this->postJson(route('focus-sessions.store'), ['completion_key' => $firstKey])
                ->assertOk()
                ->assertJson(['completedSessions' => 1, 'currentStreak' => 1, 'longestStreak' => 1]);
            $this->assertDatabaseCount('study_sessions', 1);

            Carbon::setTestNow('2026-10-02 12:00:00');
            $this->postJson(route('focus-sessions.store'), ['completion_key' => (string) Str::uuid()])
                ->assertOk()
                ->assertJson(['completedSessions' => 2, 'currentStreak' => 2, 'longestStreak' => 2]);

            Carbon::setTestNow('2026-10-04 12:00:00');
            $this->postJson(route('focus-sessions.store'), ['completion_key' => (string) Str::uuid()])
                ->assertOk()
                ->assertJson(['completedSessions' => 3, 'currentStreak' => 1, 'longestStreak' => 2]);

            $this->get('/dashboard')->assertViewHas('currentStreak', 1)->assertViewHas('longestStreak', 2);
            $this->get('/profile')->assertOk()->assertSee('Completed Focus Sessions')->assertSee('>3</span>', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_students_can_create_update_and_delete_named_pdf_study_decks(): void
    {
        $student = User::factory()->create();
        $first = $student->lessons()->create(['title' => 'Biology', 'subject' => 'Science', 'file_path' => 'biology.pdf']);
        $second = $student->lessons()->create(['title' => 'Chemistry', 'subject' => 'Science', 'file_path' => 'chemistry.pdf']);
        $otherStudent = User::factory()->create();
        $privateLesson = $otherStudent->lessons()->create(['title' => 'Private', 'subject' => 'Math', 'file_path' => 'private.pdf']);

        $this->actingAs($student)->post(route('study-decks.store'), [
            'name' => 'Science Review',
            'lesson_ids' => [$first->id, $second->id],
        ])->assertRedirect(route('lessons.index'));

        $deck = $student->studyDecks()->with('lessons')->sole();
        $this->assertSame([$first->id, $second->id], $deck->lessons->pluck('id')->all());
        $this->assertDatabaseHas('study_deck_lesson', ['study_deck_id' => $deck->id, 'lesson_id' => $second->id]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Science Review')
            ->assertSee('deck:'.$deck->id, false)
            ->assertSee(str_replace('/', '\\/', route('lessons.read', $first)), false)
            ->assertSee(str_replace('/', '\\/', route('lessons.read', $second)), false)
            ->assertDontSee('Private', false);

        $this->put(route('study-decks.update', $deck), [
            'name' => 'Biology Review',
            'lesson_ids' => [$first->id],
        ])->assertRedirect(route('lessons.index'));
        $this->assertSame('Biology Review', $deck->fresh()->name);
        $this->assertSame([$first->id], $deck->fresh()->lessons->pluck('id')->all());

        $this->delete(route('study-decks.destroy', $deck))->assertRedirect(route('lessons.index'));
        $this->assertDatabaseMissing('study_decks', ['id' => $deck->id]);
        $this->assertDatabaseMissing('study_deck_lesson', ['study_deck_id' => $deck->id]);
    }

    public function test_students_cannot_add_another_students_lesson_to_a_deck(): void
    {
        $student = User::factory()->create();
        $privateLesson = User::factory()->create()->lessons()->create([
            'title' => 'Private lesson',
            'subject' => 'Math',
            'file_path' => 'private.pdf',
        ]);

        $this->actingAs($student)->post(route('study-decks.store'), [
            'name' => 'Not mine',
            'lesson_ids' => [$privateLesson->id],
        ])->assertSessionHasErrors('lesson_ids');
        $this->assertDatabaseCount('study_decks', 0);
    }

    public function test_students_cannot_access_other_students_lessons(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $lesson = $owner->lessons()->create([
            'title' => 'Private lesson', 'subject' => 'Math', 'file_path' => 'lessons/private.pdf', 'progress' => 80,
        ]);
        $other = User::factory()->create();
        $this->actingAs($other);
        $this->get('/lessons')->assertDontSee('Private lesson');
        $this->get('/dashboard')->assertViewHas('totalLessons', 0)->assertViewHas('averageProgress', 0);
        $this->get(route('lessons.show', $lesson))->assertNotFound();
        $this->get(route('lessons.read', $lesson))->assertNotFound();
        $this->get(route('lessons.download', $lesson))->assertNotFound();
        $this->patch(route('lessons.update', $lesson), ['progress' => 100])->assertNotFound();
        $this->delete(route('lessons.destroy', $lesson))->assertNotFound();
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'progress' => 80]);
    }

    public function test_invalid_uploads_and_out_of_range_progress_are_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post('/lessons', [
            'title' => 'Not a PDF', 'subject' => 'Test', 'pdf' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('pdf');
        $this->post('/lessons', [
            'title' => 'Large PDF', 'subject' => 'Test', 'pdf' => UploadedFile::fake()->create('large.pdf', 2049, 'application/pdf'),
        ])->assertSessionHasErrors('pdf');
        $this->assertDatabaseCount('lessons', 0);
        $lesson = $user->lessons()->create(['title' => 'Math', 'subject' => 'Math', 'file_path' => 'test.pdf']);
        $this->patch(route('lessons.update', $lesson), ['title' => 'Math', 'subject' => 'Math', 'progress' => 101])
            ->assertSessionHasErrors('progress');
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'progress' => 0]);
    }

    public function test_search_filters_and_dashboard_totals_use_saved_progress(): void
    {
        $user = User::factory()->create();
        foreach ([['Algebra', 'Math', 100], ['Geometry', 'Math', 50], ['Cells', 'Science', 0]] as [$title, $subject, $progress]) {
            $user->lessons()->create(compact('title', 'subject', 'progress') + ['file_path' => 'test.pdf']);
        }
        $filteredLessons = $this->actingAs($user)->get('/lessons?q=Math&status=completed');
        $filteredLessons->assertSee('href="'.route('lessons.show', $user->lessons()->where('title', 'Algebra')->first()).'"', false)
            ->assertDontSee('href="'.route('lessons.show', $user->lessons()->where('title', 'Geometry')->first()).'"', false)
            ->assertDontSee('href="'.route('lessons.show', $user->lessons()->where('title', 'Cells')->first()).'"', false);
        $this->get('/dashboard')->assertViewHas('totalLessons', 3)->assertViewHas('completedLessons', 1)->assertViewHas('averageProgress', 50);
    }

    public function test_profile_updates_only_the_signed_in_student_and_requires_unique_email(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($student)->patch('/profile', ['name' => 'Updated Student', 'email' => $other->email])
            ->assertSessionHasErrors('email');
        $this->patch('/profile', ['name' => 'Updated Student', 'email' => 'updated@example.test'])
            ->assertRedirect(route('profile'));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'name' => 'Updated Student', 'email' => 'updated@example.test', 'email_verified_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $other->id, 'email' => $other->email]);
    }

    public function test_learning_preferences_can_be_saved_cleared_and_validated(): void
    {
        $student = User::factory()->create();
        $details = ['name' => $student->name, 'email' => $student->email, 'learning_styles_present' => '1'];
        $this->actingAs($student)->patch('/profile', $details + ['learning_styles' => ['Visual', 'Quiz']])
            ->assertRedirect(route('profile'));
        $this->assertSame(['Visual', 'Quiz'], $student->fresh()->learning_styles);
        $this->patch('/profile', $details + ['learning_styles' => ['Unknown']])->assertSessionHasErrors('learning_styles.0');
        $this->assertSame(['Visual', 'Quiz'], $student->fresh()->learning_styles);
        $this->patch('/profile', $details)->assertRedirect(route('profile'));
        $this->assertSame([], $student->fresh()->learning_styles);
    }

    public function test_profile_pictures_are_private_and_replaced_files_are_removed(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        $other = User::factory()->create();
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $details = ['name' => $student->name, 'email' => $student->email];
        $this->get('/profile/photo')->assertRedirect(route('login'));
        $this->actingAs($student)->post('/profile', $details + ['_method' => 'PATCH', 'avatar' => UploadedFile::fake()->createWithContent('avatar.png', $image)])
            ->assertRedirect(route('profile'));
        $oldPath = $student->fresh()->avatar_path;
        Storage::disk('local')->assertExists($oldPath);
        $this->get('/profile/photo')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($other)->get('/profile/photo')->assertNotFound();
        $this->actingAs($student)->post('/profile', $details + ['_method' => 'PATCH', 'avatar' => UploadedFile::fake()->createWithContent('replacement.png', $image)])
            ->assertRedirect(route('profile'));
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($student->fresh()->avatar_path);
        $this->post('/profile', $details + ['_method' => 'PATCH', 'avatar' => UploadedFile::fake()->createWithContent('avatar.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')])
            ->assertSessionHasErrors('avatar');
    }
}
