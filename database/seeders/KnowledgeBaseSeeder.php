<?php

namespace Database\Seeders;

use App\Domain\Chat\Document\Services\DocumentProcessingService;
use App\Domain\User\Models\User;
use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding knowledge base documents...');

        $admin = User::where('email', 'admin@university.edu')->first();
        if (! $admin) {
            $this->command->warn('   Demo admin missing; run RBACSeeder first.');

            return;
        }

        $documents = [
            [
                'title' => 'Student Attendance Policy',
                'category' => 'Policy',
                'text' => 'The university attendance policy requires students to attend at least 75 percent of all scheduled classes. '
                    .'Students who fall below the 75 percent attendance threshold may be barred from sitting final examinations. '
                    .'Medical absences require a doctor certificate submitted within seven days of the absence. '
                    .'Repeated unexcused absence can lead to course withdrawal and academic probation.',
            ],
            [
                'title' => 'Grading and Assessment Handbook',
                'category' => 'Handbook',
                'text' => 'The passing grade for all undergraduate courses is 50 percent. '
                    .'Final grades are composed of 40 percent continuous coursework and 60 percent final examination. '
                    .'Grade points are assigned as follows: A equals 4.0, B equals 3.0, C equals 2.0, D equals 1.0. '
                    .'Students scoring below 50 percent must retake the course in the next available semester.',
            ],
            [
                'title' => 'Library Borrowing Rules',
                'category' => 'Policy',
                'text' => 'Students may borrow up to five books at a time for a period of fourteen days. '
                    .'Late returns incur a fine of fifty cents per day per book. '
                    .'Reference books and journals cannot be borrowed and must be used inside the library. '
                    .'The library is open from 8 AM to 10 PM on weekdays and 9 AM to 5 PM on weekends.',
            ],
            [
                'title' => 'Academic Integrity Guidelines',
                'category' => 'Policy',
                'text' => 'All submitted work must be the student own original work. '
                    .'Plagiarism, including copying from other students or online sources without citation, is a serious offence. '
                    .'First violations result in a zero for the assignment; repeated violations may lead to suspension. '
                    .'Proper citation using the prescribed referencing style is required for all academic writing.',
            ],
        ];

        $processor = app(DocumentProcessingService::class);

        foreach ($documents as $data) {
            if (Document::where('title', $data['title'])->exists()) {
                continue;
            }

            $path = 'documents/'.Str::uuid()->toString().'.txt';
            Storage::disk('local')->put($path, $data['text']);

            $document = Document::create([
                'title' => $data['title'],
                'description' => $data['title'].' — official university document.',
                'department_id' => $admin->department_id,
                'category' => $data['category'],
                'file_type' => 'txt',
                'file_path' => $path,
                'original_filename' => Str::slug($data['title']).'.txt',
                'file_size' => strlen($data['text']),
                'visibility' => 'students',
                'status' => DocumentStatus::PROCESSING,
                'uploaded_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            $processor->process($document);
        }

        $this->command->info('   ✓ Knowledge base seeded ('.count($documents).' documents indexed)');
    }
}
