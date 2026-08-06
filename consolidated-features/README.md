# Consolidated Feature Catalog

A single, **deduplicated** feature set consolidated from six source documents. Every feature from every source is preserved and appears **exactly once**, filed into the one file that best matches its **role** or **scope**.

> The original source files (`admin.md`, `faculty.md`, `student.md`, `mentor.md`, `spagreen.md`, `suggested-feature.md`) are left **untouched**. This folder is the consolidated view.

## Sources merged

| Source | What it is |
|---|---|
| `student.md`, `faculty.md`, `admin.md` | The live **UniNexus** app — an AI-powered university academic copilot (richest, most authoritative detail). |
| `mentor.md` | **Mentor LMS** — a self-hosted course-selling LMS. |
| `spagreen.md` | **Faculty LMS (SpaGreen)** — an LMS SaaS + Flutter app with AI Assistant. |
| `suggested-feature.md` | A broad wishlist / superset of LMS capabilities across many categories. |

## File structure

**Role files** — features a single role performs in its portal:
- [`student.md`](student.md) — learner workflows and personal study tools.
- [`instructor.md`](instructor.md) — teaching, authoring, and grading workflows (UniNexus "faculty" + other products' "instructor").
- [`admin.md`](admin.md) — platform administration, academic structure, moderation, and configuration.

**Scope files** — cross-cutting systems and capabilities shared across roles/products (each entry notes role-specific behavior where relevant):
- [`ai-and-automation.md`](ai-and-automation.md) — all AI capabilities (chat, generators, RAG, agentic tools, OCR, AI grading, and generic AI features).
- [`learning-content-and-assessment.md`](learning-content-and-assessment.md) — course/content engine, content types, live classes, the assessment engine, proctoring, certificates.
- [`communication-community-and-engagement.md`](communication-community-and-engagement.md) — discussions, messaging, meetings/office hours, notifications, reviews, gamification, feedback, blog, support.
- [`commerce-and-monetization.md`](commerce-and-monetization.md) — payments, marketplace, subscriptions, coupons, wallet, invoicing, billing.
- [`analytics-and-reporting.md`](analytics-and-reporting.md) — analytics, reporting, and system/usage monitoring across all roles.
- [`platform-infrastructure-and-integrations.md`](platform-infrastructure-and-integrations.md) — core/multi-tenancy, auth, branding, storage, security, accessibility, mobile, integrations, developer/API, SaaS management, marketing, enterprise, and emerging/roadmap ideas.

## Deduplication method

- A feature described by several sources becomes **one** consolidated entry, using the richest description and noting differences by role or product.
- Cross-role systems (e.g. Discussions, AI Chat, Payments) are documented **once** in their scope file with per-role notes; role files link to them rather than repeating them.
- Where a source only lists a feature name, it is kept with a short description so nothing is lost.
