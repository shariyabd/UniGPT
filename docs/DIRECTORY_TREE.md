# UniGPT — Directory Structure

> Annotated map of the codebase as it actually exists (2026-06-18). Legend:
> ✅ implemented · ⬜ empty/scaffold-only · ⚠️ present but unused.
> For the *why* behind this layout, see [PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md).

The project follows a **Domain-Driven Design** layout: HTTP is a thin layer over
**domain services**, which use **Eloquent models** and **infrastructure** adapters.

```
uni-chat/
├── app/
│   ├── Console/Commands/                 ✅ SendAssignmentReminders (assignments:remind)
│   │
│   ├── Enums/                            ✅ Type-safe constants
│   │   ├── Permission.php                ✅ 46 permission slugs (categorized + helpers; +view/post/moderate discussions)
│   │   ├── UserRole.php                  ✅ admin | faculty | student (lowercase-backed)
│   │   ├── ChatMode.php                  ✅ general/academic/research/exam_prep/assignment_help/career_guidance
│   │   ├── NotificationType.php          ✅ grade/material/assignment/submission/enrollment/exam/announcement/system
│   │   ├── AttendanceStatus.php          ✅ present/absent/late/excused
│   │   ├── ExamType.php                  ✅ midterm/final/quiz/practical
│   │   ├── TaskPriority.php              ✅ low/medium/high
│   │   ├── DocumentStatus.php            ✅ pending/processing/processed/failed/approved/rejected
│   │   ├── ConfidenceLevel.php           ✅ very_low … very_high (maps a 0–1 score)
│   │   └── Language.php                  ✅ en/ar/es/fr/de/zh/ja
│   │
│   ├── Http/
│   │   ├── Controllers/                  ✅ Thin controllers (38 total)
│   │   │   ├── Auth/                      AuthenticationController, PasswordResetController
│   │   │   ├── Student/                   Dashboard, Chat, SavedAnswer, Registration, Assignment, Note (+ocr()), Task,
│   │   │   │                              StudyPlanner, LearningAnalytics, Flashcard, Leaderboard
│   │   │   ├── Faculty/                   Dashboard, Course, CourseMaterial, AIAssistant, Attendance, Analytics, Assignment, Grading
│   │   │   ├── Community/                 DiscussionController (course/section discussion feed)
│   │   │   ├── Admin/                     Dashboard, UserManagement, Role, Document, Analytics, Monitor, Settings,
│   │   │   │                              Course, Section, Term, Department, Exam, Announcement, DiscussionModeration
│   │   │   ├── Api/                       ⬜ effectively empty (no separate REST API)
│   │   │   ├── NotificationController.php ✅ in-app notifications (poll/read/delete)
│   │   │   └── LegalController.php        ✅ terms / privacy pages
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php         ✅ role gate (`role:`) + force-logout deactivated users
│   │   │   ├── PermissionMiddleware.php   ✅ permission gate (`permission:`)
│   │   │   └── HandleInertiaRequests.php  ✅ shares real auth.user + notifications + flash + Ziggy
│   │   ├── Requests/{Admin,Faculty,Student}/  ✅ ~28 permission-aware Form Requests
│   │   └── Resources/                     (API resources)
│   │
│   ├── Domain/                           🎯 Business logic (DDD bounded contexts)
│   │   ├── User/
│   │   │   ├── Models/User.php            ✅ THE User model (RBAC helpers live here, not app/Models)
│   │   │   ├── Services/UserManagementService.php  ✅
│   │   │   ├── Contracts/ Policies/ Providers/     ✅
│   │   ├── Academic/Services/             ✅ Course, CourseManagement, Enrollment, Grading, Submission,
│   │   │   │                                 Attendance, Exam, Transcript, Calendar, Term,
│   │   │   │                                 StudyPlanner, Flashcard services
│   │   │   ├── Rules/ ValueObjects/       ⬜ empty (logic inlined in services)
│   │   ├── Community/Services/DiscussionService.php  ✅ NEW — course/section discussion feed (posts/comments/reactions/reports)
│   │   ├── Chat/
│   │   │   ├── Services/                  ✅ ChatService, RagChatService, TeachingAssistantService, OcrService
│   │   │   ├── Document/                  ✅ DocumentService, DocumentProcessingService, ChunkingService
│   │   │   ├── Contracts/ DataObjects/ Support/  ✅ AIProviderInterface, ChatResult DTO, AIProviderManager
│   │   │   ├── Models/                    ⬜ (chat models live in app/Models)
│   │   │   └── Memory/                    ⬜ empty (no long-term memory/summarization yet)
│   │   ├── RAG/
│   │   │   ├── Embeddings/EmbeddingService.php    ✅
│   │   │   ├── Retrieval/RetrievalService.php     ✅ cosine similarity in PHP over MySQL vectors
│   │   │   ├── Citations/CitationService.php      ✅
│   │   │   ├── Contracts/                 ✅
│   │   │   └── Prompts/                   ⬜ empty
│   │   ├── Notification/Services/NotificationService.php  ✅
│   │   └── Analytics/Services/            ✅ AnalyticsService, FacultyAnalyticsService,
│   │       │                                 LearningAnalyticsService, LeaderboardService
│   │
│   ├── Infrastructure/                   🔌 External-edge adapters
│   │   ├── AI/                            ✅ OpenAiProvider, MockProvider, AIProviderManager
│   │   ├── FileStorage/                   ✅ DocumentStorageService, DocumentTextExtractor
│   │   ├── Repositories/                  ✅ EloquentUserRepository
│   │   ├── VectorDB/                      ⬜ empty (MySQL is the vector store)
│   │   └── Speech/                        ⬜ empty (no STT/TTS)
│   │
│   ├── Models/                           ✅ Eloquent models (most domain entities)
│   │   ├── Course, Section, Term, Department
│   │   ├── CourseMaterial, Assignment, AssignmentSubmission, AttendanceRecord, Exam
│   │   ├── Document, DocumentChunk, Embedding, DocumentApproval
│   │   ├── ChatSession, ChatMessage, MessageCitation, SavedAnswer
│   │   ├── Role, Permission, RoleUser, PermissionRole
│   │   ├── Notification, Note, Task, ActivityLog, Setting
│   │   ├── FlashcardDeck, Flashcard              ✅ spaced-repetition study decks
│   │   ├── Post, PostComment, PostReaction, PostReport  ✅ Community discussion feed
│   │   └── Concerns/BelongsToSection.php  ✅ shared trait (section-scoped models)
│   │
│   ├── Jobs/ProcessDocumentJob.php       ✅ queued RAG ingest (extract→chunk→embed)
│   ├── Policies/                         ✅ ChatSession, Course, Document, SavedAnswer
│   ├── Services/ActivityLogger.php       ✅ audit trail writer
│   ├── Providers/  Support/  Livewire/   ⚠️ Livewire dirs exist but are unused
│
├── bootstrap/app.php                     ✅ registers web.php + `role`/`permission` middleware aliases
│
├── config/
│   ├── ai.php                            ✅ provider/model/embedding config
│   ├── rag.php                           ✅ chunking/retrieval/citation config
│   ├── permissions.php                   ⚠️ legacy soft map (enum is source of truth)
│   ├── vector.php                        ⚠️ Pinecone/FAISS/Chroma config — unused (MySQL store)
│   └── … (standard Laravel configs)
│
├── database/
│   ├── migrations/                       ✅ ~40 migrations (RBAC, academic, RAG, terms/sections, etc.)
│   ├── seeders/                          ✅ DatabaseSeeder → RBACSeeder, AcademicSeeder, KnowledgeBaseSeeder,
│   │                                        ClassTestAttemptSeeder, FlashcardSeeder, LeaderboardSeeder, DiscussionSeeder
│   └── factories/
│
├── resources/
│   ├── js/
│   │   ├── app.js                        ✅ Inertia + Vue 3 + Ziggy bootstrap; vue-toastification
│   │   ├── pages/                        ✅ Inertia pages (~45)
│   │   │   ├── Landing.vue, Dashboard.vue
│   │   │   ├── Auth/Login.vue
│   │   │   ├── Admin/                     15 pages (Users, Roles, Courses, Terms, Departments,
│   │   │   │                              Documents, Approvals, Analytics, Monitor, AISettings,
│   │   │   │                              Announcements, Exams, DiscussionReports, …)
│   │   │   ├── Faculty/                   8 pages (Dashboard, CourseDetail, AIAssistant,
│   │   │   │                              Attendance, Analytics, Exams, Grading, ArchivedChats)
│   │   │   ├── Student/                   22 pages (Dashboard, Chat, Materials, Registration,
│   │   │   │                              Assignments, Attendance, Transcript, Exams, Calendar,
│   │   │   │                              Notes, Tasks, Roadmap, SavedAnswers, Documents,
│   │   │   │                              StudyPlanner, LearningAnalytics, Flashcards/{Index,Study},
│   │   │   │                              Leaderboard, …)
│   │   │   ├── Community/Discussions.vue   ✅ course/section discussion feed
│   │   │   └── Notifications/Index.vue
│   │   ├── components/                    ✅ ui/ (Badge, Card, …), Chat/, landing/, shared widgets,
│   │   │                                     charts/StatChart.vue (Chart.js/vue-chartjs)
│   │   ├── Layouts/AppLayout.vue          ✅ authenticated shell + role-aware nav
│   │   ├── composables/                   ✅ usePermissions, useConfirm, useReveal, useTheme
│   │   └── tests/                         ✅ Vitest specs
│   ├── css/app.css                        ✅ Tailwind
│   └── views/app.blade.php                ✅ single Inertia root view (@routes for Ziggy)
│
├── routes/
│   ├── web.php                            ✅ THE route file (public + student + faculty + admin)
│   ├── api.php                            ⬜ effectively empty
│   └── student.php / faculty.php / admin.php  ⚠️ unregistered dead stubs — editing them does nothing
│
├── storage/   public/build/   tests/{Feature,Unit}
│
└── Docs: README.md · PROJECT_ANALYSIS.md · PROJECT_STATUS.md · DIRECTORY_TREE.md · CLAUDE.md
```

---

## Where do I put a new feature?

| You're adding… | Put it in |
|---|---|
| A new page | `resources/js/pages/{Role}/Name.vue` + a route in `routes/web.php` |
| A new endpoint | A thin method on the matching `app/Http/Controllers/{Role}/*Controller` |
| Validation | `app/Http/Requests/{Role}/*Request` (permission-aware `authorize()`) |
| Business logic | A **service** under `app/Domain/{Context}/Services/` |
| A DB entity | Migration in `database/migrations/` + model in `app/Models/` (or `app/Domain/*/Models`) |
| Per-record access rule | A policy in `app/Policies/` |
| A new permission | A case in `app/Enums/Permission.php` + grant it in `RBACSeeder` |
| An external integration | An adapter under `app/Infrastructure/` behind a `Contracts/` interface |

> **Rule 0:** a rename/change isn't done until *every* usage site is updated
> (routes, Vue `route()` calls, props, services, models, migrations, tests). See
> [CLAUDE.md](CLAUDE.md).
</content>
