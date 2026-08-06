# Communication, Community & Engagement

This catalog consolidates every communication, community, engagement, and support feature across the UniNexus academic copilot (Student / Faculty / Admin roles) and the two reference LMS products (Mentor, SpaGreen), plus the wishlist. It covers how people talk to each other (discussions, messaging, group rooms), how they meet (office hours, bookable meetings), how they give and receive feedback (anonymous course feedback, reviews & ratings), how they stay informed (notifications, presence, announcements, blog), how they stay motivated (gamification), and how they get help (support tickets, live chat, help center). Duplicates across sources are merged into single entries with per-role notes inline; AI-backed variants, the admin authoring/moderation surfaces, personal calendar/notes, certificates, global search, and email/SMTP config are owned by other files and appear here only as pointers.

## Discussions & Forums

### Course / Section Discussion Feed
A shared discussion feed where a course **Section acts as the group**, used by students and faculty together. Participants view posts, create posts and comments, run Q&A threads, like posts, and report inappropriate posts or comments for moderation. Access is relationship-based — limited to the sections a user is enrolled in or teaches. *Student:* post, comment, like, report. *Faculty:* everything students can do, plus (holding the moderation permission) **pin** important posts and moderate content within their own sections; instructors also receive new-post notifications so they can respond promptly. *Admin:* participates in any section (authorization-checked) with the same moderator capabilities.

### Content Moderation (pointer)
Campus-wide moderation of reported posts and comments — the review queue, resolve/dismiss, and remove-content actions — is the **admin Discussion Moderation Queue** (see admin.md). Reports raised in the feed above flow into it.

### AI Discussion Assistant (pointer)
AI-assisted discussion help (drafting/summarizing threads) is described in the **AI file**.

## Messaging

### Direct Messaging / Messenger
Relationship-based **1:1 direct chat**: a student can message only the faculty who teach their sections, and faculty can message only their own students (admins have access to the shared plumbing). Messages send instantly over real-time channels with a **polling fallback** when the realtime connection is unavailable, and the database is the source of truth. Includes a conversation overview, message history, and read receipts / unread tracking. *Student:* reached via a **My Faculty** directory of their instructors. *Faculty:* reached via a **My Students** directory of everyone across their sections. 1:1 threads are kept strictly separate from group study rooms.

## Groups & Study Rooms

### Study Rooms (section-scoped group chat)
Section-scoped **group study chats** where classmates in the same section collaborate. Students can browse rooms, create a room, join or leave, and view members; faculty participate where applicable. The chat runs on the shared messenger plumbing but group rooms never appear as 1:1 direct threads. Broader **Communities** and **Groups** concepts (from the wishlist) generalize this into interest- or cohort-based spaces beyond a single section.

## Office Hours & Meetings

### Office-Hour Slots (publish & manage)
Faculty publish **single-capacity bookable slots** with a start/end time, an optional location, and an optional note. From the same page faculty can **remove** slots and **cancel a booking** (which reopens the slot for others). Both booking and cancellation trigger notifications, and booked meetings flow into the calendar.

### Book / Cancel Office Hours (student)
Students browse the open slots published by their faculty and **book** a slot or **cancel** their booking. Booking is **relationship-gated** (only faculty who teach the student's sections) and uses an **atomic claim** so two students can't grab the same single-capacity slot — the loser gets a clear conflict message. Booked meetings appear automatically on the student's calendar and notify both parties.

### Online Meeting Booking
Broader meeting-booking capability (from the reference LMS products): instructors offer **one-on-one, in-person, or group** meeting sessions, and students join booked meetings (e.g., via Zoom) from a profile meeting section. This generalizes the single-capacity office-hour model to multiple meeting formats.

## Course Feedback

### Anonymous Course Feedback
A mid-semester feedback loop run per section. *Faculty:* **open or close** the feedback window for each section they teach — opening notifies the whole roster. *Student:* while the window is open, submit **one anonymous** response (a 1–5 rating plus an optional comment) and revise it until the window closes. Results are **withheld below a minimum response count**; once reached, faculty see the average, the rating distribution, and the comments — shuffled and stripped of timestamps and identifiers. Student identity is never exposed to faculty.

### AI Feedback Theme Summary (pointer)
The one-click AI summary that groups qualitative feedback comments into themes lives in the **AI file**.

## Notifications & Presence

### In-App Notification Center
An in-app notification center available to every role, covering deadlines, grades, office-hour bookings/cancellations, waitlist promotions, discussion activity, feedback-window openings, announcements, and more. Each notification carries a type-specific icon, title/message, and relative time; unread items are emphasized and the header shows an unread count. Users can mark one notification read, mark all read, delete a notification, and click a notification to open its **deep link** (which also marks it read). The list is paginated and **polls in the background** for new items.

### Notification Channels
Beyond in-app, notifications can be delivered over multiple channels (from the reference products and wishlist): **Email**, **Push** (OneSignal), **SMS**, and **Webhooks**, alongside automated and promotional messaging. Personal notices and a **Noticeboard** surface targeted/personalized announcements to users.

### Announcements (recipient view)
Broadcast announcements arrive as in-app notifications delivered to a chosen audience (Everyone / Students / Faculty / Admins), with a recipient count. From the recipient's side this is simply a notification with the announcement title and message; edits to a sent announcement update every recipient's copy. The admin **authoring** surface (compose, audience selection, edit-sent) lives in admin.md.

### Activity Feed
A chronological feed of recent platform/community activity, surfacing what has happened across a user's spaces.

### Presence / Heartbeat
Every authenticated page quietly sends a presence **heartbeat**, powering online / last-seen and "Active Now" indicators across the app (e.g., in the messenger and directories) with no user action required.

## Reviews & Ratings

### Course Reviews & Ratings
Students leave **star ratings and written feedback** on completed courses. *Student:* write and submit a review. *Admin:* moderate reviews (review, edit, or remove) to maintain community standards. Aggregated ratings surface **Top Courses** and **Top Instructors** on storefront/home pages. (Reviews used as a graded assessment artifact are owned by the learning file.)

## Gamification & Engagement

### Leaderboard
An **opt-in**, gamified **XP ranking** letting students see how they rank by **department, semester, or section**. Participation is off by default; a student opts in and can set a **display alias** so their real name isn't shown, and opting out removes them from the board. **XP is computed from study activity at read time.**

### XP, Levels & Rewards
A points economy of **XP Points**, **Levels**, and **Rewards** that accumulate from learning activity and gate progression or perks.

### Badges & Achievements
**Badges** and **Achievements** awarded for milestones and accomplishments, displayed on a learner's profile.

### Missions & Challenges
Structured **Missions** and **Challenges** — goal-oriented activities that reward completion and drive engagement.

### Learning Streaks & Daily Goals
**Learning Streaks** track consecutive active days, and **Daily Goals** set a recurring target to keep learners returning. (The student dashboard surfaces the current study streak.)

## Blog

### Blog Publishing & Reading
A built-in **blog** for content marketing and community engagement: publish articles with **HTML detail pages**, link posts on the homepage, and support **multi-language** blog content. *Student / visitor:* read the latest blog articles from the homepage and open full detail pages.

## Support & Help

### Support Ticket System
A **support ticket** system connecting students, instructors, and admins, supporting both **course-based** tickets (raised against a specific course) and **admin** tickets (raised to platform staff). *Student:* open tickets for course help or request admin support. *Instructor:* respond to their course tickets. *Admin:* manage and respond to all tickets.

### Live Chat
**Live chat** for direct, real-time support — students can chat with instructors for course help and reach support staff.

### Help Center & Knowledge Base
A self-service **Help Center** and **Knowledge Base** of articles and guides so users can resolve common questions without opening a ticket.

### Feedback Portal & Feature Requests
A **Feedback Portal** where users share product feedback and submit **Feature Requests**, giving the platform a structured channel for improvement ideas.

### Contact Instructor / Admin Support
Direct contact paths letting students reach out to an instructor or to admin support for help with a course or the platform.
