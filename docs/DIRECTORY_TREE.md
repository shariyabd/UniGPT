# UniNexus — Directory Structure

> Annotated map of the codebase as it actually exists (2026-07-10). Legend:
> ✅ implemented · ⬜ empty/scaffold-only · ⚠️ present but unused.
> For the *why* behind this layout, see [PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md).

The project follows a **Domain-Driven Design** layout: HTTP is a thin layer over
**domain services**, which use **Eloquent models** and **infrastructure** adapters.

```
uni-chat/
├── app/
│   ├── Console/Commands/                 ✅ SendAssignmentReminders (assignments:remind — in-app nudge + email),
│   │                                        SendWeeklyDigests (digests:send-weekly — scheduled Mon 07:00),
│   │                                        SyncPersonalCorpus (rag:sync-personal — backfills notes/materials into RAG)
│   │
│   ├── Enums/                            ✅ Type-safe constants
│   │   ├── Permission.php                ✅ 46 permission slugs (categorized + helpers; +view/post/moderate discussions)
│   │   ├── UserRole.php                  ✅ admin | faculty | student (lowercase-backed)
│   │   ├── ChatMode.php                  ✅ general/academic/research/exam_prep/assignment_help/career_guidance
│   │   ├── NotificationType.php          ✅ grade/material/assignment/submission/enrollment/exam/class_test/announcement/office_hours/system
│   │   ├── AttendanceStatus.php          ✅ present/absent/late/excused
│   │   ├── ExamType.php                  ✅ midterm/final/quiz/practical
│   │   ├── TaskPriority.php              ✅ low/medium/high
│   │   ├── DocumentStatus.php            ✅ pending/processing/processed/failed/approved/rejected
│   │   ├── ConfidenceLevel.php           ✅ very_low … very_high (maps a 0–1 score)
│   │   └── Language.php                  ✅ en/ar/es/fr/de/zh/ja
│   │
│   ├── Http/
│   │   ├── Controllers/                  ✅ Thin controllers (45+)
│   │   │   ├── Auth/                      AuthenticationController, PasswordResetController
│   │   │   ├── Student/                   Dashboard, Chat (+stream SSE + tool trail), SavedAnswer, Registration,
│   │   │   │                              Assignment (+ peer reviews), Note (+ocr()), Task, StudyPlanner,
│   │   │   │                              LearningAnalytics (+ concept mastery), Flashcard, Leaderboard, ClassTest,
│   │   │   │                              PracticeQuiz (+ from-bank), StudyRoom, OfficeHours, CourseFeedback,
│   │   │   │                              CalendarExport (.ics), FacultyDirectory
│   │   │   ├── Faculty/                   Dashboard, Course, CourseMaterial, AIAssistant (+stream SSE), Attendance,
│   │   │   │                              Analytics, Assignment, Grading (+ draft-grade), ClassTest, OfficeHours,
│   │   │   │                              CourseFeedback, QuestionBank, StudentDirectory
│   │   │   ├── Community/                 DiscussionController (course/section discussion feed)
│   │   │   ├── Messenger/                 MessageController (1:1 chat + study-room message plumbing)
│   │   │   ├── Admin/                     Dashboard, UserManagement, Role, Document, Analytics, AiUsage, Monitor,
│   │   │   │                              Settings, Course, Section, Term, Department, Exam, ExamSecurity,
│   │   │   │                              Announcement, DiscussionModeration, EmailSettings
│   │   │   ├── Api/                       ⬜ effectively empty (no separate REST API)
│   │   │   ├── SearchController.php       ✅ ⌘K global search (semantic + lexical, permission-scoped)
│   │   │   ├── NotificationController.php ✅ in-app notifications (poll/read/delete)
│   │   │   └── LegalController.php        ✅ terms / privacy pages
│   │   ├── Concerns/StreamsServerSentEvents.php  ✅ SSE response + flush helper (streaming chat)
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
│   │   ├── Academic/Services/             ✅ Course, CourseManagement, Enrollment (+ prerequisites/waitlists),
│   │   │   │                                 Grading, Submission, SubmissionSimilarity, SubmissionText,
│   │   │   │                                 PeerReview, CourseFeedback, QuestionBank, Attendance, Exam,
│   │   │   │                                 ExamSecurity, ClassTest, Transcript, Calendar, Term, StudyPlanner,
│   │   │   │                                 Flashcard, PracticeQuiz, OfficeHours, IcsExport services
│   │   │   ├── Rules/ ValueObjects/       ⬜ empty (logic inlined in services)
│   │   ├── Community/Services/            ✅ DiscussionService (section feed) + StudyRoomService (group chats)
│   │   ├── Search/GlobalSearchService.php ✅ ⌘K search: semantic knowledge group + lexical entity groups
│   │   ├── Chat/
│   │   │   ├── Services/                  ✅ ChatService, RagChatService (answer + answerStream + agent loop),
│   │   │   │                                 TeachingAssistantService (+ draftRubricGrade, summarizeFeedback), OcrService
│   │   │   ├── Tools/                     ✅ ChatToolRegistry + 8 agentic chat tools (GetUpcomingDeadlines, ListMyCourses,
│   │   │   │                                 ListOfficeHourSlots, BookOfficeHourSlot, CancelOfficeHourBooking,
│   │   │   │                                 GeneratePracticeQuiz, GenerateFlashcardDeck, CreateStudyTask)
│   │   │   ├── Document/                  ✅ DocumentService, DocumentProcessingService, ChunkingService
│   │   │   ├── Contracts/ DataObjects/ Support/  ✅ AIProviderInterface (chat/chatStream — accept options['tools'] — /embed/extractText),
│   │   │   │                                 ChatToolInterface, ChatResult DTO (+$toolCalls), ToolExecution DTO, AiSettings
│   │   │   ├── Models/                    ⬜ (chat models live in app/Models)
│   │   │   └── Memory/                    ⬜ empty (no long-term memory/summarization yet)
│   │   ├── RAG/
│   │   │   ├── Embeddings/EmbeddingService.php    ✅
│   │   │   ├── Retrieval/RetrievalService.php     ✅ cosine similarity in PHP over MySQL vectors;
│   │   │   │                                         per-user scope = library ∪ own notes ∪ section materials
│   │   │   ├── Ingestion/PersonalCorpusService.php ✅ notes/materials → shadow documents ("chat with my materials")
│   │   │   ├── Citations/CitationService.php      ✅
│   │   │   ├── Support/CorpusVersion.php          ✅ retrieval-cache invalidation
│   │   │   ├── Contracts/                 ✅
│   │   │   └── Prompts/                   ⬜ empty
│   │   ├── Notification/Services/         ✅ NotificationService, EmailDigestService (weekly digest + due-soon emails)
│   │   └── Analytics/Services/            ✅ AnalyticsService, FacultyAnalyticsService, EarlyWarningService
│   │       │                                 (at-risk: 4 signals, high/watch), LearningAnalyticsService,
│   │       │                                 ConceptMasteryService (topic mastery blend, no AI calls),
│   │       │                                 LeaderboardService, AiUsageService
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
│   │   ├── ClassTest, ClassTestQuestion, ClassTestAttempt, ClassTestAnswer,
│   │   │   ClassTestEvent, ClassTestRecording,    ✅ timed quizzes + proctoring evidence
│   │   │   ClassTestSnapshot                       ✅ snapshot-evidence photo frames
│   │   ├── Document (source_type: library|note|material + LIBRARY_SCOPE global scope),
│   │   │   DocumentChunk, Embedding, DocumentApproval
│   │   ├── ChatSession, ChatMessage, MessageCitation, SavedAnswer
│   │   ├── Conversation (type: direct|group — 1:1 chats AND study rooms), Message
│   │   ├── PracticeQuiz, PracticeAttempt          ✅ student self-quizzing
│   │   ├── OfficeHourSlot                         ✅ single-capacity bookable slots
│   │   ├── Role, Permission, RoleUser, PermissionRole
│   │   ├── Notification, Note, Task, ActivityLog, Setting
│   │   ├── FlashcardDeck, Flashcard              ✅ spaced-repetition study decks
│   │   ├── SubmissionEmbedding, SubmissionSimilarity  ✅ similarity screening (chunk vectors + flagged pairs)
│   │   ├── PeerReview, CourseFeedback             ✅ anonymous peer review + per-section course feedback
│   │   ├── SectionWaitlist, QuestionBankItem      ✅ FIFO waitlists + per-course question bank
│   │   ├── Post, PostComment, PostReaction, PostReport  ✅ Community discussion feed
│   │   └── Concerns/BelongsToSection.php  ✅ shared trait (section-scoped models)
│   │
│   ├── Jobs/                             ✅ ProcessDocumentJob (library RAG ingest),
│   │                                        SyncNoteToRagJob + SyncCourseMaterialToRagJob (personal corpus),
│   │                                        ScreenSubmissionSimilarityJob (queued on every submit)
│   ├── Mail/                             ✅ WeeklyDigestMail, AssignmentDueMail (queued mailables)
│   ├── Policies/                         ✅ ChatSession, Course, Document, SavedAnswer
│   ├── Services/ActivityLogger.php       ✅ audit trail writer
│   ├── Providers/  Support/  Livewire/   ⚠️ Livewire dirs exist but are unused
│
├── bootstrap/app.php                     ✅ registers web.php + `role`/`permission` middleware aliases
│
├── config/
│   ├── ai.php                            ✅ provider/model/embedding config
│   ├── rag.php                           ✅ chunking/retrieval/citation config (+ submission_screening.flag_threshold)
│   ├── permissions.php                   ⚠️ legacy soft map (enum is source of truth)
│   ├── vector.php                        ⚠️ Pinecone/FAISS/Chroma config — unused (MySQL store)
│   └── … (standard Laravel configs)
│
├── database/
│   ├── migrations/                       ✅ ~61 migrations (RBAC, academic, RAG + personal corpus, terms/sections,
│   │                                        class tests + proctoring, conversations (direct+group), practice
│   │                                        quizzes, office hours, etc. — July-2026 wave adds tool_activity on
│   │                                        chat_messages, submission similarity tables, course_feedback,
│   │                                        peer_reviews, prerequisites + waitlists, question_bank_items)
│   ├── seeders/                          ✅ DatabaseSeeder → RBACSeeder, AcademicSeeder, KnowledgeBaseSeeder,
│   │                                        ClassTestAttemptSeeder, FlashcardSeeder, LeaderboardSeeder, DiscussionSeeder
│   └── factories/
│
├── resources/
│   ├── js/
│   │   ├── app.js                        ✅ Inertia + Vue 3 + Ziggy bootstrap; vue-toastification
│   │   ├── pages/                        ✅ Inertia pages (~81)
│   │   │   ├── Landing.vue, Dashboard.vue
│   │   │   ├── Auth/Login.vue
│   │   │   ├── Admin/                     18 pages (Users, Roles, Courses, Terms, Departments,
│   │   │   │                              Documents, Approvals, Analytics, AiUsage, Monitor, AISettings,
│   │   │   │                              Announcements, Exams, ExamSecurity, DiscussionReports, …)
│   │   │   ├── Faculty/                   15 pages (Dashboard, Courses, CourseDetail (+peer-review toggle),
│   │   │   │                              AIAssistant (streaming), Attendance, Analytics (+at-risk), Exams,
│   │   │   │                              Grading (+similarity badge, AI rubric draft), ClassTests/{…},
│   │   │   │                              QuestionBank, CourseFeedback, Students, Messages, OfficeHours,
│   │   │   │                              ArchivedChats)
│   │   │   ├── Student/                   29 pages (Dashboard, Chat (streaming + tool trail), Materials,
│   │   │   │                              Registration (+prereq badges/waitlist), Assignments (+peer review),
│   │   │   │                              Attendance, Transcript, Exams, Calendar (+.ics),
│   │   │   │                              ClassTests/{…}, Notes, Tasks, Roadmap, SavedAnswers, Documents,
│   │   │   │                              StudyPlanner, LearningAnalytics (+concept mastery map),
│   │   │   │                              Flashcards/{Index,Study}, Practice/{Index (+from-bank),Take},
│   │   │   │                              StudyRooms, OfficeHours, CourseFeedback, Leaderboard,
│   │   │   │                              Faculty, Messages, …)
│   │   │   ├── Community/Discussions.vue   ✅ course/section discussion feed
│   │   │   └── Notifications/Index.vue
│   │   ├── components/                    ✅ ui/ (Badge, Card, …), Chat/, Messenger (MessengerShell),
│   │   │                                     landing/, shared widgets, charts/StatChart.vue (Chart.js)
│   │   ├── Layouts/AppLayout.vue          ✅ authenticated shell + role-aware nav + ⌘K global search palette
│   │   ├── composables/                   ✅ usePermissions, useConfirm, useReveal, useTheme, useConversation
│   │   │                                     (1:1 + study rooms), useHeartbeat, exam-security composables
│   │   │                                     (useFaceLiveness, useExamSnapshots, usePhoneDetection,
│   │   │                                      useExamSounds, useExamRecorder — MediaPipe assets self-hosted
│   │   │                                      under public/vendor/mediapipe)
│   │   ├── lib/                           ✅ markdown.js, sse.js (fetch-based SSE parser for streaming chat)
│   │   └── tests/                         ✅ Vitest specs
│   ├── css/app.css                        ✅ Tailwind
│   └── views/
│       ├── app.blade.php                  ✅ single Inertia root view (@routes for Ziggy)
│       └── emails/                        ✅ layout, weekly-digest, assignment-due (queued mail views)
│
├── routes/
│   ├── web.php                            ✅ THE route file (public + student + faculty + admin)
│   ├── api.php                            ⬜ effectively empty
│   └── student.php / faculty.php / admin.php  ⚠️ unregistered dead stubs — editing them does nothing
│
├── storage/   public/build/
│
├── tests/{Feature,Unit}                  ✅ 45 feature suites — July-2026 wave adds ChatToolsTest,
│                                            SubmissionSimilarityTest, ConceptMasteryTest, EmailDigestTest,
│                                            AiGradeDraftTest, CourseFeedbackTest, PeerReviewTest,
│                                            PrerequisiteWaitlistTest, QuestionBankTest
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
