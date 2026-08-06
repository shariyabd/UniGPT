# Platform, Infrastructure & Integrations

This catalog consolidates the foundational, cross-cutting capabilities that every role stands on — the multi-tenant core, the ways users sign in and are authorized, the tools for branding and building a site, where files and video live, how the platform stays secure, accessible, and mobile-ready, the third-party services it connects to, its developer surface, its SaaS-operation controls, marketing reach, enterprise structure, global productivity primitives, maintenance, and forward-looking roadmap items. It draws from three products — the live UniNexus academic copilot and two commercial LMS platforms (Mentor LMS, Faculty/SpaGreen LMS) — plus a forward-looking wishlist. Features owned by other catalogs are referenced with a pointer rather than re-described: admin configuration *screens* live in the Admin catalog; payments and billing money-flow in Commerce; reports and dashboards in Analytics; AI/RAG in the AI catalog; discussions, messaging, and the notification center in Communication; and the pedagogical content/assessment engine in Learning.

---

## Core Platform & Multi-Tenancy

### Multi-Tenant Architecture
A single installation hosts multiple isolated organizations, each with its own students, instructors, staff, custom pricing, and dashboard. Tenants share the codebase while keeping data and configuration separate.

### White Label
The platform can be rebranded so no vendor identity is visible, letting operators present it entirely as their own product.

### Custom Domains
Each tenant or deployment can be served from its own domain name rather than a shared subdomain.

### Organization Management
Administrators create and manage separate organizations as distinct tenants, each carrying its own users, catalog, pricing, staff, and branding. Supports multiple departments within an organization.

### Workspace Management
Logical workspaces partition users and content within an organization, giving teams or cohorts their own scoped area of the platform.

### Multi-Language & Localization
The full interface is translatable, with static text across every dashboard (admin, faculty/instructor, student) editable into any language. Supports multi-language content including blog posts, plus multiple locales, countries, states, and cities.

### RTL Support
Right-to-left language rendering is supported so the interface reads correctly for languages such as Arabic and Hebrew.

### Multi-Currency
Multiple currencies are supported platform-wide; users can select a global currency preference. (Money movement itself belongs to the Commerce catalog.)

### Time Zone Support
Dates, deadlines, and schedules respect configurable time zones so users in different regions see correct local times.

### Self-Hosted Deployment
A fully self-hosted Laravel application with a modern SPA frontend delivered through Inertia.js — UniNexus on Vue 3, the commercial LMS products on React. Runs on ordinary shared hosting (no VPS required in the LMS products).

### Web Installer, One-Click Updates & Backups
A web-based installer sets the platform up without command-line work; the application can be updated in one click (with a manual option), and backups can be created and managed from the admin side. System maintenance includes reboot/maintain routines.

---

## Authentication & Access

This section owns the authentication *mechanisms and capabilities*. The admin User Management screen and the Role–Permission matrix editor UI live in the Admin catalog.

### Email Login
Standard credential-based sign-in with email and password.

### Social Login (Google / Facebook)
One-tap sign-in via Google OAuth and Facebook, reducing signup friction. Google authentication is configured with OAuth credentials.

### SSO (Single Sign-On)
Enterprise single sign-on lets users authenticate once against a central identity provider and access the platform without a separate login.

### Two-Factor Authentication
An optional second verification factor at login hardens accounts against credential theft.

### Magic Link Login
Passwordless sign-in via a one-time link emailed to the user.

### SMS / OTP Login (Twilio)
Phone-based signup and login using a text-message one-time code delivered through Twilio.

### User Invitations
Administrators invite users directly, provisioning accounts by email rather than waiting for self-registration.

### User Approval on Registration
New self-registered accounts can require admin approval before activation; a login-history review accompanies the approval workflow.

### Role & Permission Model (RBAC)
A role-based access model where users hold roles and roles hold permissions (many-to-many both ways, with pivot-level expiry support). Distinct staff roles carry specific access levels. This section owns the RBAC *concept*; the visual matrix editor that grants/revokes cells is in the Admin catalog.

### Session Management
Active sessions are tracked and can be managed, so users (or admins) can review and terminate logins.

### Device Management
Sign-ins are associated with devices, letting users see and control which devices have access.

### Password Policies
Configurable password strength, rotation, and complexity rules enforce credential hygiene.

### Guest & Inactive-User Handling
Guests are routed to login for protected areas; deactivated or inactive users are logged out and prevented from signing back in until reactivated.

---

## Branding & Site Building

This section owns branding and site-building *capabilities*. The admin configuration *actions* that flip these settings are pointers to the Admin catalog.

### Theme Builder
A theming system lets operators reshape the platform's look — layouts, headers, and visual treatment — without code.

### Branding Management
Site name, logo, colors, fonts, and global identity settings are configurable, keeping the platform on-brand without editing code.

### Color System
A customizable color system drives both the public storefront theme and the admin panel, with unlimited theme-color customization.

### Drag-and-Drop Page Builder
A visual, no-code builder assembles public-facing pages from draggable sections, with instant reordering and design customization (colors, fonts, images, headlines, copy).

### Prebuilt Homepage Templates
A library of ready-made homepage templates that can be selected and switched with a single click, including top-courses, top-instructors, and call-to-action sections.

### Unlimited Custom Pages
Create any number of static pages (About, FAQ, Terms, Privacy, and more) with full content control.

### Navigation Integration
Custom pages and sections plug into the site navigation menus automatically.

### Custom CSS / JS Injection
Operators can inject custom CSS and JavaScript to fine-tune appearance and behavior beyond the built-in options.

### Media Gallery
A central media gallery organizes uploaded images and files for reuse across pages and content.

### Drag-Drop File Uploader
A drag-and-drop uploader handles file intake for the media gallery and content areas.

### Email & Notification Templates
Reusable templates for transactional emails and notifications keep messaging consistent and brand-aligned. (The notification *center* itself is in the Communication catalog.)

### Two Header Versions
Two selectable storefront header layouts let operators pick the navigation presentation that fits their brand.

### Rich Snippets / Google Schema
Structured-data markup (rich snippets, Google Schema) is emitted so pages surface richer results in search engines.

### Mobile-Responsive Templates
All builder templates render responsively, adapting automatically to phones and tablets.

---

## Storage

### Pluggable Storage Backends
Files can be stored locally or on external object storage — AWS S3, Wasabi (S3-compatible), and Cloudflare R2 — configurable per deployment to control hosting cost and scale.

### Video Hosting (YouTube & Vimeo)
Course and lesson video can be hosted on YouTube or Vimeo rather than the origin server, offloading bandwidth-heavy playback.

### Secure File Links
File access is served through secure links so materials and downloads aren't exposed via guessable public URLs.

---

## Security & Compliance

### Data Encryption
Sensitive data (such as API keys and SMTP passwords) is stored encrypted and never exposed back to the browser in plaintext.

### GDPR Compliance
The platform supports GDPR obligations around personal-data handling and user rights.

### SOC 2 Readiness
Controls and practices are aligned toward SOC 2 readiness for enterprise trust.

### IP Restrictions
Access can be restricted by IP address to limit where the platform (or admin areas) can be reached from.

### Security Logs
Security-relevant events and access are logged for audit and incident review.

### reCAPTCHA
reCAPTCHA protection guards registration and other public forms against bots and abuse.

### Secure Payment API Handling
Payments are processed through each gateway's official API and raw card data is never stored, keeping the platform out of scope for direct card handling. (Gateway configuration and money-flow live in Commerce.)

---

## Accessibility

### WCAG Compliance
The interface targets WCAG accessibility guidelines so users with disabilities can operate it.

### Keyboard Navigation
Core flows, including the global command palette, are fully keyboard-navigable without a mouse.

### Screen Reader Support
Semantic markup and labeling make content usable with assistive screen readers.

### Captions & Transcripts
Video content supports captions and text transcripts for accessibility and searchability.

### Dark Mode
A dark color theme reduces eye strain and is user-selectable.

---

## Mobile

### Responsive Design
A high-quality responsive layout adapts across desktop, tablet, and mobile screens with cross-browser compatibility (Chrome, Firefox, Edge).

### Progressive Web App
The web app can be installed and used like a native app, with app-like behavior in the browser.

### Native Apps (Android & iOS)
Dedicated Android and iOS applications extend the platform to native mobile.

### Flutter Mobile App
A full-featured Flutter app mirrors the web experience — signup/login, enrollment, content playback, live classes, quizzes, certificates, chat, notices, blog, profile, and payments.

### Offline Learning & Downloads
Content can be downloaded for offline access so learning continues without connectivity.

### Cross-Browser Compatibility
Verified to work across major browsers, ensuring consistent behavior regardless of the user's browser choice.

---

## Integrations

### Video Conferencing (Zoom, Google Meet, Microsoft Teams)
Live sessions integrate with Zoom (native, with in-app join, live-class chat, and screen sharing), Google Meet, and Microsoft Teams, letting instructors schedule and run classes without leaving the platform.

### Cloud Storage (Google Drive, Dropbox, OneDrive)
Integrations with Google Drive, Dropbox, and OneDrive let content and files sync with external cloud storage.

### Team Messaging (Slack, Discord)
Slack and Discord integrations pipe platform events and community activity into external team-chat channels.

### Automation (Zapier, Make)
Zapier and Make integrations connect the platform to thousands of external apps through no-code automation workflows.

### Push Notifications (OneSignal)
OneSignal powers automated and promotional push notifications to web and mobile.

### SMS (Twilio)
Twilio delivers SMS for OTP login and text-message notifications.

### Webhooks
Outbound webhooks fire on platform events so external systems can react in real time.

---

## Developer & API

### Public REST API
A public REST API exposes platform data and actions for external integration and automation.

### GraphQL API
A GraphQL endpoint offers flexible, client-shaped queries against platform data.

### API Keys
API keys authenticate programmatic access and can be issued and revoked.

### SDKs
Software development kits ease building against the platform's API from common languages.

### Plugin System
A plugin architecture lets third parties extend functionality without forking the core (used for optional capabilities such as the AI Assistant and content-type plugins).

### Theme System
A theme system allows custom front-end themes to be built and applied independently of core code.

### Custom Fields
Custom fields extend built-in entities with operator-defined data attributes.

---

## SaaS Management

Tenant billing money-flow lives in Commerce; this section owns the operational SaaS controls.

### Plan Limits
Per-plan quotas cap usage (seats, storage, AI requests, and similar) so tenants stay within their subscription tier.

### Feature Flags
Feature flags gate which capabilities each tenant or plan can access, enabling staged rollout and tier differentiation.

### Trial Management
Free trials are provisioned, tracked, and expired automatically, converting or downgrading tenants at trial end.

### Upgrade / Downgrade
Tenants can move between plans, with entitlements adjusting to the new tier.

### License Management
Licenses are issued and validated to authorize each deployment or tenant.

### Custom Branding Per-Tenant
Each tenant carries its own branding, so the same installation presents differently per organization.

### Tenant Isolation
Tenant data and configuration are kept strictly separated, preventing cross-tenant leakage.

### Tenant Billing (pointer)
Subscription pricing, invoices, and payment collection are documented in the Commerce catalog.

---

## Marketing

### Landing Page Builder
Dedicated landing pages are built visually to drive campaigns and conversions.

### SEO Management
Per-page SEO metadata (title, description, keywords) plus overall SEO optimization improve organic discoverability.

### Email Marketing
Targeted email campaigns can be composed and sent to segments of students or instructors.

### Newsletter Capability
Newsletter subscription management and email newsletters keep an audience engaged over time.

### Popup Builder
On-site popups capture attention for promotions, signups, or announcements.

### Lead Capture
Lead-capture forms collect prospect information for follow-up.

### CRM Integration
Captured leads and contacts can sync to external CRM systems.

### Social Sharing
Built-in social sharing lets users promote courses and content across their networks, with CTA blocks reinforcing conversion.

---

## Enterprise

### Department Management
Academic and organizational departments are managed as first-class structure, grouping users and courses.

### Team Management
Teams organize employees or learners into managed groups within an organization.

### Employee Training & Compliance Training
The platform supports internal employee-training and compliance-training use cases alongside public course selling.

### Skill Matrix & Competency Tracking
A skill matrix and competency tracking map who has which skills and at what level across the organization.

### Organization Structure
Multi-level organizational structure models the real reporting and departmental hierarchy.

### Manager Oversight
Managers oversee their teams' progress and training. (The *reports* managers read are in the Analytics catalog.)

---

## Global Search & Productivity

### Global Command Palette (⌘K)
An all-role ⌘K command palette, available on every page, blends on-page matches with grouped remote results after a short debounce. Results include a semantic **Knowledge** group (AI hits across the user's accessible RAG corpus) and lexical groups — courses, assignments, discussions, chat history (deep-linked to the exact message), and users (admin only) — with unified keyboard navigation and deep links straight to each item.

### Saved Filters
Frequently used filter combinations can be saved and reapplied, speeding up repeated searches across list views.

### General Search
Instant search suggestions surface results as the user types across the platform's content.

---

## Maintenance

### Maintenance Mode
Operators can take the platform offline for maintenance, showing a maintenance page to visitors while work proceeds. (UniNexus's hidden URL-driven `?live=` switch that toggles this is configured in the Admin catalog; here we own the general capability.)

### Backups & Updates
Backups can be created and managed, and the application updated in one click or manually, keeping the deployment current and recoverable. (See also Web Installer under Core Platform.)

---

## Emerging & Roadmap

Pedagogical emerging items — microlearning, adaptive learning, cohort management, mentorship, virtual classroom, podcast lessons, interactive whiteboard, digital credentials/open badges, and skills assessment — are owned and described in the Learning catalog. The infrastructure-and-reach roadmap items below live here.

### Career Paths
Guided, multi-course career tracks that sequence learning toward a target role or profession.

### Job Board Integration
Integration with job boards connects completed learning and credentials to real employment opportunities.

### Resume Builder
A tool that turns a learner's achievements, courses, and credentials into a formatted resume.

### Portfolio Builder
A showcase builder lets learners assemble projects and work products into a shareable portfolio.

### Voice-Based Learning
Voice-driven interfaces that let learners consume and interact with content hands-free through speech.

### AR/VR Learning
Immersive augmented- and virtual-reality experiences for spatial, hands-on learning scenarios.

### Learning Marketplace
An open marketplace connecting creators, courses, and learners at platform scale beyond a single organization's catalog.
