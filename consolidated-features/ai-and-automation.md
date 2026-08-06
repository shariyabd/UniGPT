# AI & Automation

This is the deduplicated catalog of every AI and automation capability across the products in this collection. AI spans all three UniNexus roles — **Student** (RAG chat, study tooling, OCR), **Faculty** (teaching assistant, generators, AI-assisted grading), and the **platform** itself — and is also represented by the two LMS products (Mentor LMS, Faculty LMS/SpaGreen) and the wishlist (suggested-feature). Every AI surface is **permission-gated** (UniNexus checks an AI-chat/AI-access permission per role) and, when a UniNexus deployment runs in **demo mode**, capped at a fixed number of AI requests across all AI features. Duplicate capabilities that appeared under multiple products or roles are merged here into single entries with product/role notes; the tool pages that *consume* these capabilities live in the student/faculty/admin files and are pointed to, not re-described.

---

## Conversational AI

### Student AI Chat (RAG Assistant)
UniNexus — Student. A full-screen, ChatGPT-style workspace where students ask academic questions and receive answers **grounded in cited sources** pulled from the university document library, the student's own notes, and their enrolled-section materials. Each answer carries an **Academic Sources** panel (document title, page, section, relevance tier, download link) and a confidence badge, so answers stay transparent and traceable. Responses render Markdown, code blocks and LaTeX math, and can be copied or saved to the student's Saved Answers collection.

### Faculty AI Teaching Assistant
UniNexus — Faculty. The faculty counterpart to student chat: a full ChatGPT-style workspace for teaching questions and drafting materials, running the same streaming and session-management plumbing. It is a persistent, session-based conversation surface gated by the AI-chat permission (and, in demo mode, the demo cap).

### Streaming Responses (SSE)
UniNexus — Student & Faculty. AI answers stream in **token-by-token** in real time over server-sent events, with a live typing indicator, so long answers feel responsive instead of appearing all at once. A non-streaming request path exists as a fallback and returns the same final answer.

### Agent vs. Answers-only Mode
UniNexus — Student. A segmented control above the composer switches the chat between two modes. In **Agent mode** the assistant can take real actions on the student's behalf (see Agentic Tools); the input takes a violet accent, the placeholder invites commands, and acted-on replies carry an **"⚡ Agent"** badge. In **Answers-only mode**, tools are never offered (enforced server-side) and the assistant only explains and answers. Welcome-card prompts, placeholder text and hints all adapt to the active mode.

### Agentic Tools (Function Calling)
UniNexus — Student. When Agent mode is on, the assistant performs real tasks through the app's own domain services, obeying the same permission and safety rules a manual action would: **listing upcoming deadlines and courses, listing/booking/cancelling office-hour slots, generating a practice quiz, generating a flashcard deck, and creating a task**. Each action shows as a status pill above the answer (running → success/warning) with a summary and, where relevant, a link to view the result (e.g. "View Quiz"). Failures become error results rather than exceptions.

### Response Modes
UniNexus — Student. Independent of Agent/Answers, students pick a response style: **Simple** (quick, concise), **Detailed** (in-depth with examples), **Exam Mode** (exam-focused key points), and **Assignment** (guides without doing the work for them). Welcome banners and starter suggestions adjust to the chosen style.

### Welcome-card Starter Prompts
UniNexus — Student. A fresh chat shows a personalised welcome card listing the student's enrolled courses plus four suggested starter prompts. Suggestions are course-aware (some auto-fill with the student's real course names), sampled/shuffled for variety, and swapped depending on whether Agent or Answers-only mode is active.

### Voice & Language Input
UniNexus — Student. The composer includes a voice-input button that captures speech via the browser and appends the transcript, plus a language selector so the student can converse in an enabled language (e.g. English or Bengali).

### Chat Session Management
UniNexus — Student & Faculty. Both the student chat and the faculty assistant organise conversations into manageable sessions: a searchable history sidebar (pinned sessions float to the top), inline **rename**, **pin/unpin**, **archive/unarchive**, and **delete**, plus a **New Chat** button for a clean session. Conversations can be **exported to a plain-text file**, and deep-links open a specific session and highlight a specific message. History access and deletion are governed by the relevant chat permissions.

### Archived Chats
UniNexus — Student & Faculty. A dedicated view (for both the student chat and the faculty assistant) listing conversations the user has archived, keeping the main history clean while preserving the ability to unarchive and reopen them.

---

## AI Content & Assessment Generation

### AI Quiz Generator
UniNexus — Faculty (generate a quiz from a prompt/parameters, drafted for review, not saved until published); Mentor LMS & suggested-feature (AI Section Quizzes / AI Quiz Generator). Merged: an AI action that drafts quiz questions for an instructor to review, edit or discard before persisting.

### AI Assignment Generator
UniNexus — Faculty (generate an assignment with AI, drafted for review and persisted only on publish); suggested-feature (AI Assignment Generator). Merged into one entry.

### AI Class-Test Question Generation (in-form)
UniNexus — Faculty. Inside the class-test authoring form, an AI action drafts test questions from the instructor's inputs and populates the form in place; nothing is persisted until the test is saved, so suggestions can be freely edited or discarded.

### AI Flashcard / Deck Generator
UniNexus — Student (generate a study deck by topic, card count, difficulty and optional course; also invocable via the chat agent and the Teaching-Assistant service); suggested-feature (AI Flashcard Generator). The generated deck feeds the student Flashcards tool (SM-2 review lives in student.md).

### AI Practice-Quiz Generation
UniNexus — Student. AI generation of a self-paced practice quiz (topic, optional course, question count, difficulty), restricted to MCQ/true-false, with correct answers kept server-side until grading. Reuses the AI-chat access gate. (The Practice-Quiz *page* is in student.md; a separate, AI-free "from Question Bank" path also exists there.)

### AI Study Planner Generation Engine
UniNexus — Student. The generation capability that turns the student's upcoming deadlines into a proposed study schedule of sessions (which the student can save as Tasks). Runs behind the AI-access gate so it respects admin AI restrictions. (The Study Planner *page* is in student.md.)

### AI Course Generator
Mentor LMS & suggested-feature (and Faculty LMS/SpaGreen AI Writer, at content level). Generate a full draft course from a single prompt — structure, descriptions, sections, FAQs, learning outcomes, requirements, optional lessons, and a thumbnail. Includes **AI Course Updates** (revise title, short and full descriptions via instruction) and **AI Section Management** (AI-rename existing sections; creation stays manual). Instructor-facing in the LMS products; gated by the AI Assistant plugin's token quota.

### AI Lesson Generator
Mentor LMS (AI Text Lessons — generate new lessons with HTML body content or revise existing lesson titles/bodies) & suggested-feature (AI Lesson Generator). Merged.

### AI Course-Info Content Generation
Mentor LMS. Generate or edit FAQs, learning outcomes and requirements individually or in bulk during course setup.

### "Write with AI" Rich-Text Editing / AI Writer
Mentor LMS ("Write with AI" toolbar button that generates or refines HTML content inside any editor) & Faculty LMS/SpaGreen (OpenAI-powered **AI Writer** for course/content creation; admin-enabled, separate OpenAI fee). Merged into one rich-text AI authoring capability.

### AI Thumbnail / Image Generation
Mentor LMS (AI course-thumbnail generation during setup or later via the Media tab) & suggested-feature (AI Image Generator). Merged.

---

## AI Grading & Feedback

### AI Feedback Suggestion
UniNexus — Faculty. A one-click button that drafts written feedback for a submission from the assignment title, current grade, total points, an excerpt of the student's work, and the rubric criteria. The suggestion returns to the grading panel for review and edit — never saved automatically.

### AI Draft Rubric Grade
UniNexus — Faculty. A deeper assist that reads the actual submission text (typed content plus extracted file text) and grades it against the assignment rubric, returning **per-criterion scores clamped to each criterion's maximum**, a suggested overall grade, and feedback with strengths and improvements. It prefills the rubric inputs (with italic per-criterion justifications) for the faculty to review, adjust and save; when no AI provider is configured it falls back to a clearly-labelled **heuristic**. Human-in-the-loop by design — nothing auto-releases to the student.

### AI Feedback Theme Summary
UniNexus — Faculty. Once a section's anonymous course-feedback has enough responses, a one-click AI action summarises the qualitative comments into **themes**, drawing on the comments and rating distribution. Gated behind the AI-chat access controls (and demo limits), with a heuristic fallback, and only runnable after results are revealed.

### AI Grading & AI Feedback (generic)
suggested-feature. Generic automatic grading and AI-generated feedback capabilities — the product-agnostic superset that the UniNexus faculty grading assists above are concrete implementations of.

---

## AI for Learners

### OCR Handwriting Transcription
UniNexus — Student. On the Notes page, a student uploads a photo of handwritten notes and the AI **transcribes** it to text via vision OCR, turning handwritten study material into searchable, editable notes. Runs behind the AI-access gate, with a mock/offline fallback. (The Notes page itself is in student.md.)

### AI Tutor
suggested-feature. A conversational tutoring assistant that guides a learner through material — the wishlist generalisation of the UniNexus Student AI Chat.

### AI Study Assistant
suggested-feature. A study-companion assistant for learners (summarising, explaining, quizzing), overlapping the UniNexus RAG chat and study tooling.

### AI Content Summarizer
suggested-feature. Condenses lesson/course/document content into summaries for faster review.

### AI Translation
suggested-feature. Machine translation of learning content between languages (complements UniNexus multi-language chat input).

### AI Voice Narration
suggested-feature. Text-to-speech narration that reads lesson/course content aloud for accessibility and audio learning.

### AI Video Subtitle Generator
suggested-feature. Automatic caption/subtitle generation for video lessons.

### AI Course Recommendations
suggested-feature. Personalised course suggestions driven by a learner's history and goals.

### AI Learning Path Generator
suggested-feature. Generates a sequenced, personalised learning path toward a goal.

### AI Discussion Assistant
suggested-feature. An assistant that helps draft, answer, or moderate discussion/forum contributions.

### AI Question Answering
suggested-feature. General question-answering over content — the wishlist umbrella that the UniNexus grounded RAG chat delivers concretely with citations.

### AI Learning Insights
suggested-feature. AI-surfaced insights about a learner's progress, strengths and gaps (the wishlist counterpart to UniNexus concept-mastery/early-warning signals).

---

## Generic / Platform AI Capabilities

### AI Assistant Plugin (role-gated, quota-based)
Mentor LMS & Faculty LMS/SpaGreen. An optional, admin-enabled AI plugin providing course/lesson/quiz/content/thumbnail generation and "Write with AI"/AI-Writer editing. Access is **role-gated with a token quota** that resets **daily/weekly/monthly**: **admins** configure the plugin and hold an **unlimited** quota, **instructors** use all AI features **under their quota**, and **students** have **no access**. (SpaGreen's AI Writer requires a separate OpenAI fee.)

---

## AI Access & Governance

### Provider & Fallback Model (capability level)
UniNexus. AI capabilities resolve through a multi-provider layer — chat via a fallback chain (**OpenAI** or **OpenRouter** as primary, the other as backup, `openrouter/auto` as last resort, and a **Mock** provider as the never-fail terminal link), and embeddings resolved separately (OpenAI or **Jina**, since OpenRouter has no embeddings endpoint) with optional dual-embedding. Vectors are **model-tagged** so mixed-provider vectors never cross-compare, and switching the embedding provider triggers a corpus re-embed. This is described here only at the capability level — the **admin configuration screen** (provider/model selection, embeddings, keys, test-connection) lives in **admin.md**.

### Permission Gating
UniNexus — all roles. Every AI surface is gated by an AI-chat/AI-access permission held by the user's role; without it, the AI features are hidden or blocked. Admins can also block an individual user from all AI features (the block/unblock control is in **admin.md**).

### Demo-Mode AI Request Cap
UniNexus — all roles. When a deployment runs in demo mode, every account is capped at a fixed number of AI requests across **all** AI surfaces (student chat/agent, faculty assistant, all AI generators, grade/feedback drafts, feedback summariser, planner generation, OCR). The cap is charged up-front per request-consuming AI action (never on page loads or history reads); once exhausted, further AI actions return a "demo limit reached" response. The governance policy and its enforcement detail live in **admin.md**.
