<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Enums\DocumentStatus;
use App\Models\Department;
use App\Models\Document;
use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::where('email', 'admin@university.edu')->first();

        if (! $admin) {
            $this->markTestSkipped('Demo admin not seeded; run php artisan db:seed.');
        }

        return $admin;
    }

    public function test_admin_can_view_all_pages(): void
    {
        $admin = $this->admin();

        $uris = [
            '/admin/dashboard', '/admin/users', '/admin/roles', '/admin/analytics', '/admin/settings',
            '/admin/monitor', '/admin/documents', '/admin/documents/upload', '/admin/approvals',
        ];

        foreach ($uris as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }

    public function test_admin_can_update_a_roles_permissions(): void
    {
        $admin = $this->admin();

        $facultyRole = \App\Models\Role::findByEnum(\App\Enums\UserRole::FACULTY);
        $permission = \App\Models\Permission::where('slug', 'view_documents')->first();

        // Set the faculty role to exactly one permission.
        $this->actingAs($admin)
            ->patch("/admin/roles/{$facultyRole->id}/permissions", [
                'permissions' => [$permission->id],
            ])
            ->assertRedirect();

        $this->assertSame(
            [$permission->id],
            $facultyRole->fresh()->permissions->pluck('id')->all(),
        );
    }

    public function test_admin_can_toggle_a_document_bookmark(): void
    {
        $admin = $this->admin();
        $document = Document::first();

        if (! $document) {
            $this->markTestSkipped('No documents seeded.');
        }

        // First toggle bookmarks the document and surfaces it on the library.
        $this->actingAs($admin)
            ->post("/admin/documents/{$document->id}/bookmark")
            ->assertRedirect();
        $this->assertTrue($admin->bookmarkedDocuments()->whereKey($document->id)->exists());

        $libraryDoc = $this->actingAs($admin)
            ->get('/admin/documents')
            ->viewData('page')['props']['documents']['data'] ?? [];
        $found = collect($libraryDoc)->firstWhere('id', $document->id);
        $this->assertNotNull($found);
        $this->assertTrue($found['isBookmarked']);

        // Second toggle removes it.
        $this->actingAs($admin)
            ->post("/admin/documents/{$document->id}/bookmark")
            ->assertRedirect();
        $this->assertFalse($admin->bookmarkedDocuments()->whereKey($document->id)->exists());
    }

    public function test_admin_course_department_filter_matches_across_all_pages(): void
    {
        $admin = $this->admin();

        $course = \App\Models\Course::query()->whereNotNull('department_id')->first();

        if (! $course) {
            $this->markTestSkipped('No departmental courses seeded.');
        }

        $departmentId = $course->department_id;
        $expected = \App\Models\Course::where('department_id', $departmentId)->count();

        $props = $this->actingAs($admin)
            ->get('/admin/courses?department_id='.$departmentId)
            ->viewData('page')['props'];

        // Filtering happens server-side before pagination, so the total reflects
        // every matching course — the bug was the filter only seeing page 1.
        $this->assertSame((string) $departmentId, (string) $props['filters']['department_id']);
        $this->assertSame($expected, $props['courses']['total']);
        foreach ($props['courses']['data'] as $row) {
            $this->assertSame($departmentId, $row['departmentId']);
        }
    }

    public function test_admin_role_permissions_cannot_be_edited(): void
    {
        $admin = $this->admin();

        $adminRole = \App\Models\Role::findByEnum(\App\Enums\UserRole::ADMIN);

        // The admin role is locked to prevent self-lockout.
        $this->actingAs($admin)
            ->patch("/admin/roles/{$adminRole->id}/permissions", ['permissions' => []])
            ->assertForbidden();
    }

    public function test_admin_can_create_and_manage_users(): void
    {
        $admin = $this->admin();
        $department = Department::first();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Test Created User',
            'email' => 'created.user@university.edu',
            'password' => 'password123',
            'role' => 'student',
            'department_id' => $department?->id,
        ])->assertRedirect();

        $user = User::where('email', 'created.user@university.edu')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('student'));

        // Toggle active off
        $this->actingAs($admin)->patch("/admin/users/{$user->id}/toggle-active")->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);

        // Change role to faculty
        $this->actingAs($admin)->patch("/admin/users/{$user->id}/role", ['role' => 'faculty'])->assertRedirect();
        $this->assertTrue($user->fresh()->hasRole('faculty'));
    }

    public function test_admin_can_upload_and_approve_a_document_which_gets_indexed(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $file = UploadedFile::fake()->createWithContent(
            'exam-rules.txt',
            'The final examination duration is three hours. Calculators are not permitted in the mathematics exam. '
            .'Students must arrive at least fifteen minutes before the exam starts.',
        );

        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Examination Rules',
            'category' => 'Policy',
            'visibility' => 'students',
            'file' => $file,
        ])->assertRedirect();

        $document = Document::where('title', 'Examination Rules')->first();
        $this->assertNotNull($document);
        $this->assertSame(DocumentStatus::PENDING->value, $document->status->value);

        // Approve → ProcessDocumentJob runs synchronously (queue=sync) → chunk + embed.
        $this->actingAs($admin)->post("/admin/documents/{$document->id}/approve")->assertRedirect();

        $document->refresh();
        $this->assertSame(DocumentStatus::APPROVED->value, $document->status->value);
        $this->assertGreaterThan(0, $document->chunks()->count());
        $this->assertGreaterThan(0, $document->embeddings()->count());
    }

    public function test_admin_can_reject_a_document(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $file = UploadedFile::fake()->createWithContent('draft.txt', 'Some draft content that needs revision.');
        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Draft To Reject',
            'category' => 'General',
            'visibility' => 'students',
            'file' => $file,
        ])->assertRedirect();

        $document = Document::where('title', 'Draft To Reject')->first();

        $this->actingAs($admin)->post("/admin/documents/{$document->id}/reject", [
            'reason' => 'Needs more detail.',
        ])->assertRedirect();

        $this->assertSame(DocumentStatus::REJECTED->value, $document->fresh()->status->value);
    }

    public function test_admin_can_save_and_test_ai_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch('/admin/settings', [
            'temperature' => 0.5,
            'max_tokens' => 2048,
            'rag_top_k' => 4,
            'rag_similarity_threshold' => 0.6,
            'system_prompt' => 'Be concise.',
        ])->assertRedirect();

        $this->assertEquals(0.5, Setting::get('ai')['temperature']);

        $this->actingAs($admin)->postJson('/admin/settings/test')
            ->assertOk()
            ->assertJsonStructure(['provider', 'available', 'message']);
    }

    public function test_saved_ai_settings_drive_the_runtime_resolver(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch('/admin/settings', [
            'temperature' => 0.3,
            'max_tokens' => 1024,
            'rag_top_k' => 7,
            'rag_similarity_threshold' => 0.42,
            'system_prompt' => 'Always answer in British English.',
        ])->assertRedirect();

        // A freshly resolved resolver must reflect the persisted overrides,
        // proving the admin AI Settings screen actually controls the engine.
        $settings = app(\App\Domain\Chat\Support\AiSettings::class);

        $this->assertSame(0.3, $settings->chatOptions()['temperature']);
        $this->assertSame(1024, $settings->chatOptions()['max_tokens']);
        $this->assertSame(7, $settings->topK());
        $this->assertSame(0.42, $settings->similarityThreshold());
        $this->assertSame('Always answer in British English.', $settings->systemPromptOverride());
    }
}
