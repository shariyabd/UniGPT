<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\FlashcardDeck;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds flashcard decks + cards. The demo student gets hand-crafted CS decks so
 * the feature looks real on the demo login; a sample of bulk students get one
 * generic deck each tied to a course they're enrolled in.
 */
class FlashcardSeeder extends Seeder
{
    /**
     * Hand-crafted decks for the demo student: [title, [[front, back], ...]].
     */
    private const DEMO_DECKS = [
        ['Data Structures — Core', [
            ['What is the time complexity of binary search?', 'O(log n) on a sorted array.'],
            ['Define a stack.', 'A LIFO (last-in, first-out) linear data structure.'],
            ['Queue vs. stack?', 'A queue is FIFO; a stack is LIFO.'],
            ['What is a hash collision?', 'When two keys hash to the same bucket; resolved via chaining or open addressing.'],
            ['Average lookup cost in a balanced BST?', 'O(log n).'],
            ['What is amortized analysis?', 'Averaging the cost of operations over a sequence to bound worst-case totals.'],
        ]],
        ['OS — Scheduling', [
            ['What does a CPU scheduler do?', 'Decides which ready process runs next on the CPU.'],
            ['Define starvation.', 'A process waits indefinitely because higher-priority processes keep preempting it.'],
            ['Round-robin scheduling?', 'Each process gets a fixed time quantum in cyclic order.'],
            ['What is a context switch?', 'Saving one process state and restoring another so the CPU can switch tasks.'],
            ['Preemptive vs. non-preemptive?', 'Preemptive can interrupt a running process; non-preemptive runs to completion/blocking.'],
        ]],
        ['Databases — SQL Joins', [
            ['What does an INNER JOIN return?', 'Only rows with matching keys in both tables.'],
            ['LEFT JOIN?', 'All rows from the left table plus matches from the right (NULLs where none).'],
            ['What is a foreign key?', 'A column referencing the primary key of another table to enforce referential integrity.'],
            ['Purpose of an index?', 'Speeds up lookups/filters at the cost of extra write and storage overhead.'],
            ['What is normalization?', 'Organizing tables to reduce redundancy and improve integrity.'],
        ]],
    ];

    public function run(): void
    {
        $now = Carbon::now();
        $demo = User::where('email', 'student@university.edu')->first();

        if ($demo) {
            $demoCourseId = $demo->enrolledCourses()->value('courses.id');

            foreach (self::DEMO_DECKS as [$title, $cards]) {
                $deck = FlashcardDeck::firstOrCreate(
                    ['user_id' => $demo->id, 'title' => $title],
                    ['course_id' => $demoCourseId, 'description' => 'Demo study deck.', 'source' => 'manual'],
                );

                if ($deck->cards()->exists()) {
                    continue; // idempotent — don't duplicate cards on re-seed
                }

                $position = 0;
                foreach ($cards as [$front, $back]) {
                    $deck->cards()->create(['front' => $front, 'back' => $back, 'position' => $position++]);
                }
            }
        }

        $this->seedBulkStudents($now);

        $this->command->info('   ✓ Flashcard decks seeded');
    }

    private function seedBulkStudents(Carbon $now): void
    {
        $limit = (int) config('seeder.flashcard_students', 60);
        $demoEmails = (array) config('seeder.demo_emails', []);

        $students = User::whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->whereNotIn('email', $demoEmails)
            ->whereDoesntHave('flashcardDecks')
            ->with(['enrolledCourses' => fn ($q) => $q->select('courses.id', 'courses.code', 'courses.name')])
            ->limit($limit)
            ->get();

        foreach ($students as $student) {
            $course = $student->enrolledCourses->first();
            if (! $course instanceof Course) {
                continue;
            }

            $deckId = DB::table('flashcard_decks')->insertGetId([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'title' => $course->code.' — Revision',
                'description' => 'Key terms and definitions for '.$course->name.'.',
                'source' => 'manual',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $cards = [];
            for ($i = 1; $i <= 5; $i++) {
                $cards[] = [
                    'deck_id' => $deckId,
                    'front' => "Key term #{$i} in {$course->code}",
                    'back' => "Definition and example for key term #{$i} in {$course->name}.",
                    'position' => $i - 1,
                    'ease_factor' => 2.50,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('flashcards')->insert($cards);
        }
    }
}
