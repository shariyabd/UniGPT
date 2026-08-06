# Faculty LMS (SpaGreen) — Complete Feature List

**Faculty LMS** is a Laravel-based **Learning Management System (SaaS)** with an accompanying **Flutter mobile app**, an **AI Assistant**, and multi-organization support. This document merges the features from both CodeCanyon products — the **Faculty LMS SaaS with AI Assistant** and the **Faculty LMS Mobile App (Flutter)** — into one deduplicated list, grouped by the role that uses each feature, followed by shared platform capabilities.

Sources:
- <https://codecanyon.net/item/faculty-lms-learning-management-system-saas-with-ai-assistant/47989504>
- <https://codecanyon.net/item/faculty-lms-mobile-app-elearning-management-system-flutter-app/49267618>

---

## Administrator Features

The admin owns and configures the whole platform, its content, staff, and monetization.

### Admin Dashboard
Beautiful and powerful central admin panel to manage the entire platform.

### Advanced Home Page Builder
Build and arrange the storefront homepage, including top-courses and top-instructors sections and call-to-action blocks.

### Custom Static Pages
Create custom static pages (About, FAQ, etc.).

### Storefront Theme Customization
Unlimited theme color customization, admin panel color/font changes, two header versions, and branding alignment.

### Custom CSS / JS Injection
Inject custom CSS and JavaScript to modify platform appearance and behavior.

### Media Gallery & File Uploader
Media gallery management with a drag-and-drop file uploader.

### Product / Course Catalog Management
Manage courses as products with options, attributes, brands, tags, categories, and subcategories.

### Scheduled Special Pricing / Flash Sales
Schedule special product pricing and run flash sales.

### Digital / Downloadable Products
Sell digital/downloadable products alongside courses.

### Discount Code System
Create advanced discount codes for enrollment/purchases.

### Newsletter System
Newsletter subscription management and email newsletters.

### SEO & Rich Snippets
SEO optimization, rich snippets, and Google Schema readiness.

### Instant Search & Search Logs
Instant search suggestions plus search-log analysis for insight.

### Maintenance Mode
Put the platform into maintenance mode.

### Localization Management
Multiple locales/languages, multiple currencies, and multiple countries, states, and cities.

### Payment Gateway Configuration
Configure the supported gateways and offline payment approval workflow (see Platform Features for the gateway list).

### User Approval & Login History
Approve new user/registration accounts and review login history.

### Staff Roles & Permissions
Create distinct staff roles with role-based permissions and specific access levels; supports multiple departments.

### Organization Management (Multi-Tenant)
Manage separate organizations, each with its own students, instructors, custom pricing, dashboard, and staff.

### Support Ticket Administration
Manage and respond to course-based and admin support tickets.

### Noticeboard & Announcements
Publish notices and personalized announcements to users.

### Push / Promotional Notifications
Send automated notifications, customized promotional messages, and push notifications (OneSignal).

### Blog Management
Publish blog articles with HTML detail pages, homepage linking, and multi-language content.

### Analytics & Reporting
Sales analytics charts, enrollment history, real-time student progress, course completion tracking, and advanced system reporting.

### AI Writer Configuration
Enable the OpenAI-powered AI Writer service (requires a separate OpenAI fee) for AI content assistance.

### Storage Integration
Configure external storage — Amazon S3 / Wasabi (S3-compatible), plus YouTube and Vimeo for video hosting — to reduce hosting costs.

---

## Instructor Features

Instructors create and deliver courses and interact with their students.

### Instructor Dashboard
Dedicated instructor dashboard with a separate profile section.

### Course Creation & Editing
Create and edit courses organized into sections and chapters, with resources and attributes.

### Multiple Course Types
Author video courses, live classes/webinars, text-based courses (plugin), SCORM courses (plugin), and articles.

### Live Class Setup & Management
Schedule and manage live classes/webinars.

### Online Meeting Booking
Book one-on-one, in-person, or group meeting sessions.

### Assignment Management
Create assignments and track submissions.

### Quiz & Certificate Generation
Build quizzes and generate course certificates.

### Free Preview Lessons
Mark lessons as free video previews for prospective students.

### Instructor Analytics
Performance analytics and tracking for their courses.

### Student Communication
Communicate with students via in-app chat and the support ticket system.

---

## Student Features

Students discover, buy, and consume courses on web and mobile.

### Student Panel / Dashboard
User-friendly student panel with "My Courses" details and course access.

### Signup & Social Login
Easy signup/login with Google, Facebook, and SMS/text-message (Twilio) authentication.

### Course Discovery
Browse and select courses; choose your course and instructor; advanced search.

### Wishlist & Cart
Add courses to a wishlist/cart for later purchase.

### Multiple Content Types
Access video lessons, live classes, and written/text courses, with audio options for video.

### Downloadable Resources
Download course resources and materials.

### Free Preview Access
Watch free preview videos for lessons before buying.

### Course Progress Tracking
Track progress and course completion percentage.

### Live Class Attendance
Join live classes/Zoom meetings in-app, with live-class chat and screen sharing.

### Meeting Section
Join booked meetings (e.g., via Zoom) from the profile meeting section.

### Quizzes
Answer quizzes, review results, and see quiz scores/details in the profile.

### Assignment Submission
Submit assignments for instructor review.

### Certificates
Earn, preview, and download course certificates.

### Wallet
Wallet system to fund the account and recharge balance.

### Order & Payment History
Track order history, enrollment history, and download invoices.

### Support & Communication
Live chat with instructors, contact instructors for course help, request admin support, and open course-based support tickets.

### Noticeboard & Personal Notices
Receive personalized notices and announcements.

### Blog Access
Read the latest blog articles from the homepage.

### Profile & Preferences
Edit profile, select global language, and choose global currency.

---

## Shared / Platform-Wide Features

Capabilities spanning roles or belonging to the platform/infrastructure.

### Payment Gateways
Built-in support for **PayPal**, **Stripe**, **Razorpay**, **Bkash**, **SSLCommerz**, **Uddokta Pay**, **eSewa**, **Tap**, and **Paytm** (with sandbox mode), including SCA / 3D Secure. Multiple online options plus offline payment with admin approval and wallet recharge.

### Multi-Language & Localization
Translatable interface, multi-language content (including blog), multiple currencies, and RTL (right-to-left) support.

### AI Assistant / AI Writer
OpenAI-powered AI content generation to assist course/content creation (admin-enabled; separate OpenAI fee).

### Mobile App (Flutter)
Full-featured Flutter mobile app mirroring the web experience — signup/login, course enrollment, content playback, live classes, quizzes, certificates, chat, notices, blog, profile, and payments.

### Blog Platform
Blog publishing with HTML detail pages, homepage linking, and multi-language posts.

### Notifications & Engagement
Automated notifications, promotional messaging, email newsletters, and OneSignal push notifications.

### Support Ticket System
Course-based and admin support tickets connecting students, instructors, and admins.

### Responsive & Cross-Platform
High-quality responsive design, cross-browser compatibility (Firefox, Chrome, Edge), mobile-friendly UI, and shared-hosting support (no VPS required).

### External Storage & Video Hosting
Amazon S3 / Wasabi storage plus YouTube and Vimeo video hosting to reduce hosting costs.

### Marketing & Promotion
Marketing campaigns, content promotion tools, advanced discount codes, flash sales, and CTA blocks.
