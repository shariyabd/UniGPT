# UniNexus — Feature Index

> Feature names only, grouped by role. For full descriptions and working criteria, see [features.md](features.md).
> Source of truth = code (verified 2026-07-17). "Shared" = features available to more than one role; "Engine / System" = platform-wide infrastructure.

---

## Student

1. Student Dashboard
2. AI Tutor Chat (RAG, streaming, cited)
3. Chat — Agent Mode (8 in-chat tools)
4. Chat — Answers-Only Mode
5. Chat Session Management (pin / rename / archive / delete)
6. Saved Answers
7. Course Registration (confirm / drop assigned sections)
8. Prerequisites Enforcement
9. Section Waitlists (queue position)
10. Course Materials
11. Document Library
12. My Documents (submissions to approval queue)
13. Assignments & Submission
14. Anonymous Peer Review
15. Class Tests (take, proctored, auto-graded)
16. Practice Quizzes (AI-generated)
17. Practice Quizzes from Question Bank
18. Flashcards (SM-2 spaced repetition)
19. AI Flashcard Generation
20. Notes
21. OCR Handwritten Notes
22. Tasks
23. AI Study Planner
24. Transcript (GPA / CGPA)
25. Roadmap (degree progress)
26. Attendance View
27. Exams View
28. Calendar
29. Calendar .ics Export & Subscribe Feed
30. Learning Analytics ("My Progress")
31. Concept Mastery Map & Adaptive Review
32. Leaderboard (opt-in XP)
33. Discussion Feed
34. Study Rooms (group chat)
35. Office Hours Booking
36. Anonymous Course Feedback
37. Messaging with Faculty
38. My Faculty Directory
39. Profile & Settings

## Faculty

1. Faculty Dashboard
2. My Students Directory
3. Messaging with Students
4. My Courses & Course Detail
5. Course Materials Management
6. My Documents (submissions to approval queue)
7. AI Teaching Assistant (chat + streaming)
8. AI Quiz Generation
9. AI Assignment Generation
10. Publish AI Draft as Assignment
11. Publish AI Draft as Class Test
12. Attendance Management
13. Exams Timetable View
14. Class Tests Authoring
15. Class Test Results & Attempt Review Dossier
16. Risk Scoring
17. Exam Security / Proctoring Layer Selection
18. Grading
19. AI Suggested Feedback
20. AI-Assisted Rubric Grading ("Draft grade with AI")
21. Submission Similarity Screening
22. Peer-Review Averages in Grading
23. Question Bank
24. Draft Class Test from Question Bank
25. Office Hours Publishing & Management
26. Anonymous Course Feedback Windows
27. AI Course-Feedback Summary
28. Faculty Analytics
29. At-Risk Early Warning
30. Assignment Management (edit / status / delete)
31. Peer Review Toggle
32. Discussion Feed Participation & Moderation

## Admin

1. Admin Dashboard
2. User Management
3. Role & Permission Matrix
4. Course Catalog Management
5. Course Prerequisites
6. Sections Management & Student Assignment
7. Terms & End-of-Term Rollover
8. Registration Window Control
9. Departments Management
10. Document Library (all statuses)
11. Document Approval Workflow
12. Exams & Timetable Management
13. Exam Security Global Gate
14. AI Settings
15. AI Provider Connection Test
16. Email (SMTP) Settings
17. Platform Analytics
18. AI Usage Monitor & Access Control (block / unblock)
19. System Monitor
20. Announcements / Broadcast Notifications
21. Discussion Moderation Queue
22. User Activity Tracking

## Shared (Cross-Role)

1. Authentication (login with role selection)
2. Self-Service Signup
3. Demo Login
4. Password Reset
5. RBAC Enforcement (role + permission + temporal grants)
6. In-App Notifications
7. Email Digests & Deadline Nudges
8. Real-Time Messaging & Presence
9. Discussion Feed (shared student + faculty)
10. Global Search (⌘K)
11. Public Document Library (`/docs`)
12. Landing Page
13. Product Presentation
14. Legal Pages (Terms / Privacy)

## Engine / System

1. RAG Pipeline (chunk → embed → retrieve → cite)
2. Personal-Corpus RAG ("chat with my materials")
3. Multi-Provider AI with Fallback Chain (OpenAI / OpenRouter / Mock)
4. Multi-Backend Embeddings with Fallback (OpenAI / Jina / Mock)
5. Confidence Scoring & Citations
6. Streaming Responses (SSE)
7. Model-Tagged Vectors & Corpus Re-embedding
8. Demo-Mode AI Usage Cap
9. Hidden Maintenance Switch
10. Activity Logging / Audit Trail
