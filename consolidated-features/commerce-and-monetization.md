# Commerce & Monetization

This catalog owns every feature that moves money: payment processing, pricing and purchase models, marketplace revenue sharing, discounts, cart/wallet, billing, invoices, transactions, and growth programs. These features come almost entirely from the two marketplace LMS products (Mentor LMS and SpaGreen Faculty LMS) and the wishlist (suggested-feature.md). The real "UniNexus" university app is **not** commerce-driven — it manages academics, RBAC, and AI copilot features rather than selling courses — so it contributes little or nothing here. Where another catalog owns the surface (instructor payout dashboard, admin approval screens, gateway config UIs, revenue analytics/reports, SaaS plan-limits and feature-flags), this file describes the underlying model and points there.

## Payment Gateways

Single consolidated list of every supported gateway across all sources. Multiple gateways can run simultaneously; the buyer picks a method at checkout. All processing goes through each gateway's official API — **raw card data is never stored**. Setup is admin-driven by entering API credentials (the config *screens* live in admin.md).

### Stripe
Card processing gateway. Sources: Mentor, SpaGreen, suggested-feature. Supports SCA / 3D-Secure.

### PayPal
Wallet and card gateway. Sources: Mentor, SpaGreen, suggested-feature.

### Paddle
Merchant-of-record checkout and billing gateway. Source: suggested-feature.

### Razorpay
India-focused gateway. Sources: Mentor, SpaGreen, suggested-feature. Supports SCA / 3D-Secure.

### SSLCommerz
Bangladesh payment aggregator. Sources: Mentor, SpaGreen, suggested-feature.

### Mollie
European multi-method gateway. Source: suggested-feature.

### Lemon Squeezy
Merchant-of-record gateway for digital products. Source: suggested-feature.

### Paystack
Africa-focused gateway. Source: Mentor.

### Bkash
Bangladesh mobile-wallet gateway. Source: SpaGreen.

### Uddokta Pay
Bangladesh aggregator gateway. Source: SpaGreen.

### eSewa
Nepal digital-wallet gateway. Source: SpaGreen.

### Tap
Middle-East gateway. Source: SpaGreen.

### Paytm
India wallet/gateway with sandbox mode for testing. Source: SpaGreen.

### Bank Transfer
Direct bank-transfer payment option. Source: suggested-feature.

### Offline / Manual Payments
Manual payment method with an admin approval workflow — the order stays pending until an administrator confirms receipt and approves it. Sources: Mentor, SpaGreen, suggested-feature.

### Gateway Platform Notes
Cross-cutting behaviors that apply to all gateways: run multiple gateways at once; SCA / 3D-Secure compliance where the gateway supports it; sandbox/test mode (notably Paytm); easy activation by pasting admin credentials — no code changes. Sources: Mentor, SpaGreen.

## Purchase & Pricing Models

How courses, exams, and digital goods are priced and sold. (The *content* being sold is described in the learning catalog; here we own the pricing/purchase mechanics.)

### One-Time Purchases
Buy a course, exam, or product outright for permanent access. Sources: Mentor, SpaGreen, suggested-feature.

### Subscription Plans
Recurring-billing plans granting ongoing access while active. Source: suggested-feature.

### Free Courses
Zero-price enrollment with no checkout. Source: suggested-feature.

### Trials
Time-limited or preview access before purchase. Source: suggested-feature. (Trial-management-as-tenancy is owned by the platform catalog; here it is trial pricing on a purchasable product.)

### Course Enrollment Pricing
Per-course price set by the instructor/admin; enrollment happens via the supported gateways or cart. Multi-tenant orgs can set their own custom pricing per organization. Sources: Mentor, SpaGreen.

### Digital / Downloadable Products
Sell downloadable digital goods alongside courses, priced and checked out through the same commerce engine. Source: SpaGreen.

### Standalone Exam Pricing
Exams sold as independent products (certification, practice, competitive) with their own price, separate from any course. Source: Mentor. (Exam content lives in the learning catalog.)

## Marketplace & Revenue Sharing

The monetization engine for the multi-instructor marketplace: how a sale is split, tracked, and paid out. The admin approval *actions/screens* (approve instructor, approve payout) live in admin.md and the instructor payout *dashboard view* lives in instructor.md — this section owns the model behind them.

### Multi-Instructor Marketplace Monetization
Run the platform as a solo site or a multi-instructor marketplace, switchable without reinstalling. Supports unlimited instructors, each selling their own courses under the platform's commerce and payout rules. Source: Mentor (with marketplace listed as an emerging feature in suggested-feature).

### Platform Commission Configuration
Admin sets the platform's commission rate (the cut the platform keeps on each sale). Applied globally or per the marketplace's rules. Source: Mentor.

### Per-Sale Revenue Calculation & Tracking
On every sale the system computes the split between platform commission and instructor earnings, and records it against the course and instructor for later payout and reporting. Source: Mentor. (Revenue analytics/reports are owned by the analytics catalog.)

### Instructor Earnings & Account Balance
Running balance of an instructor's net earnings after commission, accumulating until withdrawn. Source: Mentor.

### Payout / Withdrawal Requests
Instructors request a disbursement once earnings meet the minimum threshold. The request enters a queue for admin review; on approval, funds transfer through the configured payment method. This file owns the withdrawal model and eligibility rules; the request *dashboard* is in instructor.md and the approval *screen/action* is in admin.md. Source: Mentor.

### Revenue Split Model
The end-to-end apply → sell → calculate split → accrue earnings → request → approve → transfer workflow that governs marketplace cash flow. Source: Mentor.

## Discounts & Promotions

### Coupons
Redeemable coupon codes that reduce checkout price. Source: suggested-feature.

### Discount Codes (Advanced)
Advanced, rule-based discount codes for enrollments and purchases (usage limits, targeting, etc.). Sources: SpaGreen, suggested-feature.

### Promotions
General promotional pricing campaigns on courses/products. Source: suggested-feature.

### Flash Sales
Time-boxed sale events with steep temporary discounts. Source: SpaGreen (listed there under marketing/promotion).

### Scheduled Special Pricing
Set special product pricing that automatically activates and deactivates on a schedule. Source: SpaGreen.

## Cart & Wallet

### Shopping Cart
Add one or more courses/products to a cart and check out together via a supported gateway. Sources: Mentor, SpaGreen.

### Wishlist / Save for Later
Save courses to a wishlist for future purchase; move to cart when ready. Sources: Mentor, SpaGreen, suggested-feature.

### Wallet — Account Funding & Recharge
A per-user wallet that can be topped up (recharged) with real money and then spent on courses/products at checkout; offline recharge is admin-approved. Sources: SpaGreen, suggested-feature.

## Billing, Invoices & Transactions

### Invoice Management / Generation & Download
Generate an invoice for each order and let the buyer download it (e.g., from order history). Sources: SpaGreen, suggested-feature.

### Tax Management
Configure and apply taxes to orders. Source: suggested-feature.

### Refund Management
Process refunds against prior purchases. Source: suggested-feature.

### Order / Purchase & Payment History
Buyer-facing record of every purchase and payment made, including enrollment history. Sources: Mentor, SpaGreen.

### Transaction Management
Platform-wide payment log with filtering by gateway or date and export for accounting. Source: Mentor. (Revenue analytics dashboards are owned by the analytics catalog.)

## Growth Programs

### Affiliate Program
Reward affiliates with commission for driving sales through tracked links. Source: suggested-feature.

### Referral Program
Reward existing users for referring new buyers. Source: suggested-feature.

## SaaS Billing (Multi-Tenant Selling)

For multi-tenant deployments, the billing and payment side of running the platform as a SaaS. Note the overlap with the platform SaaS-management catalog: **plan-limits, feature-flags, and trial-management-as-tenancy live in the platform file** — only the billing/payment mechanics are owned here.

### Tenant Billing
Charge each tenant organization for its subscription to the platform, on the plan it has selected. Source: suggested-feature.

### Billing Portal
Self-service portal where a tenant views charges, manages its payment method, and handles its subscription billing. Source: suggested-feature.
