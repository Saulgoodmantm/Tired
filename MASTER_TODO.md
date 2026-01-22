# 📋 TIREDOFDOINTM - MASTER TODO CHECKLIST
## Last Updated: 2026-01-22

---

## 🔴 PHASE 1: VPS & INFRASTRUCTURE (CRITICAL)
- [ ] Fix VPS nginx/PHP configuration
- [ ] Run database migrations successfully
- [ ] Verify site loads at http://164.92.99.233
- [ ] Configure SSL with certbot
- [ ] Set up GitHub webhook for auto-deploy
- [ ] Configure .env with all credentials

## 🟠 PHASE 2: AUTHENTICATION SYSTEM
- [ ] Gate system ("67" password)
- [ ] Email OTP login (passwordless)
- [ ] Google OAuth login
- [ ] Session management with cookies
- [ ] Device fingerprinting
- [ ] Admin auto-seeding from ADMIN_EMAILS
- [ ] Account recovery flow
- [ ] Remember me functionality

## 🟡 PHASE 3: USER & ROLE SYSTEM
- [ ] Role hierarchy (Admin/Manager/Staff/Model/Client/Registered/Guest)
- [ ] Permission matrix enforcement
- [ ] Role-based route protection
- [ ] User profile management
- [ ] Settings page (dark/light mode toggle)

## 🟢 PHASE 4: HOMEPAGE & UI
- [ ] Dark mode default with light mode toggle
- [ ] Brand banner with Instagram embed/pfp
- [ ] GTA V-style hero slideshow (Ken Burns effect)
  - [ ] Images slide in quickly, stop, zoom slowly
  - [ ] Random pan speed per image
  - [ ] Smooth crossfade transitions
  - [ ] Never static - always moving
- [ ] Buttons ABOVE slideshow: [View Schedule] [View Portfolio] [Book Now]
- [ ] Testimonials carousel
- [ ] All animations at 60fps
- [ ] Neon purple accents with glow effects
- [ ] Responsive for all screen sizes

## 🔵 PHASE 5: NAVIGATION & MENU
- [ ] Menu button (top right) with hamburger → X animation
- [ ] Full-screen diagonal sweep overlay
- [ ] Gallery submenu (Personal/Product/Group/Event/Misc)
- [ ] Socials submenu (Instagram x2, TikTok) - open in new tab
- [ ] Profile avatar visible when logged in
- [ ] Dashboard link (admin/staff only)
- [ ] Sign out button when logged in
- [ ] Staggered animation on menu items

## 🟣 PHASE 6: GALLERY SYSTEM
- [ ] Three gallery types: Public / Unlisted (UUID link) / Private
- [ ] Categories: Personal, Product, Group, Event, Misc
- [ ] Masonry layout with lightbox (fullscreen)
- [ ] Cover image selection
- [ ] Search by name, date, category
- [ ] Drag-and-drop reordering (admin)
- [ ] Pin images to homepage showcase
- [ ] Progressive blur-up image loading
- [ ] Client galleries with unique share links
- [ ] Password protection option
- [ ] View tracking (who accessed, when)
- [ ] Download options (ZIP for desktop, Camera Roll for iOS)

## 🟤 PHASE 7: IMAGE PIPELINE & R2 STORAGE
- [ ] Upload to Cloudflare R2 (not local)
- [ ] RAW file storage (encrypted, admin only)
- [ ] Generate derivatives: full, web (1920px), mobile (1080px), thumb (400px)
- [ ] Watermark system (toggleable per image/gallery)
- [ ] EXIF metadata extraction
- [ ] Separate /raw/ and /edited/ folders in R2
- [ ] Batch upload with progress indicators
- [ ] Image tools: crop, rotate, filters

## ⚫ PHASE 8: BOOKING SYSTEM
- [ ] Public calendar shows ONLY available dates
- [ ] Date → Time → Shoot Type → Questions → Add-ons → Contract → Payment → Confirmation
- [ ] Pre-booking questions (8 required):
  1. Full name
  2. Email
  3. Duration
  4. Shoot type
  5. Date
  6. Moodboard/idea
  7. Location preference
  8. Studio help needed
  9. Model hiring needed
- [ ] Add-ons with pricing (model hiring $25-150, extra time, rush editing)
- [ ] 4-hour buffer before/after bookings
- [ ] Unique booking link per booking (UUID)
- [ ] Auto-cancel abandoned bookings after 30 min

## ⚪ PHASE 9: CALENDAR & GOOGLE INTEGRATION
- [ ] Google Calendar 2-way sync
- [ ] Push bookings to Google Calendar
- [ ] Pull availability from Google Calendar
- [ ] Event details: client, location, weather, time
- [ ] Reminders: 1 week, 24 hours, 2 hours before
- [ ] Admin calendar with year view
- [ ] Color coding: Green (available), Red (booked), Grey (unavailable)

## 🔶 PHASE 10: PAYMENT SYSTEM (STRIPE)
- [ ] Stripe integration (LIVE keys configured)
- [ ] 50% deposit required to book
- [ ] Device-aware payment order (Apple Pay first on iOS, etc.)
- [ ] Fee surcharge display per method
- [ ] Invoice generation (PDF)
- [ ] Webhook for payment status
- [ ] Refund processing (deposit non-refundable)
- [ ] "Manage Prices" dashboard tab

## 🔷 PHASE 11: CONTRACT & DOCUMENT SYSTEM
- [ ] NO DocuSign - custom built
- [ ] Contract templates with placeholders
- [ ] Auto-fill from booking data
- [ ] E-signature (draw or type)
- [ ] Timestamp + IP + document hash
- [ ] Contract MUST be signed BEFORE payment
- [ ] PDF generation of signed contracts
- [ ] Model release template
- [ ] Copyright license template
- [ ] Client agreement template
- [ ] Legally binding format (E-Sign Act compliant)

## 🔸 PHASE 12: MESSAGING SYSTEM
- [ ] Floating message icon (bottom right)
- [ ] Only visible when logged in
- [ ] Threaded conversations
- [ ] Reply via website OR email (synced)
- [ ] Gmail API integration
- [ ] Unread message indicator
- [ ] File attachments (images only)
- [ ] All messages saved to database

## 🔹 PHASE 13: LOCATION & MAPS SYSTEM
- [ ] Save photoshoot locations to database
- [ ] Dashboard map with all saved locations
- [ ] Google Maps + Street View integration
- [ ] Distance calculation from base (auto travel fee)
- [ ] Locations >1.5 hours require manual approval
- [ ] Weather forecast for shoot date/time
- [ ] Rain alerts (1 week, 1 day before)
- [ ] Traffic data collection
- [ ] Click location → open in Google Maps/Earth

## 🟫 PHASE 14: MODEL MANAGEMENT
- [ ] Model profiles (name, portfolio, stats, rates)
- [ ] Commission rate per model
- [ ] Job assignment system
- [ ] Model can accept/decline jobs
- [ ] Payout tracking
- [ ] Model dashboard (view jobs, earnings, availability)
- [ ] Tags: Fashion, Lifestyle, Fitness, etc.

## ⬛ PHASE 15: ADMIN DASHBOARD
- [ ] Hover-expand left sidebar
- [ ] Overview: stats, charts, recent bookings
- [ ] Calendar management
- [ ] Bookings list with filters
- [ ] Clients management (search, history, revenue)
- [ ] Messages inbox
- [ ] Contracts (templates, signed, pending)
- [ ] Gallery manager
- [ ] Billing (invoices, refunds, payment toggles)
- [ ] Locations map
- [ ] Models management
- [ ] Pricing ("Manage Prices" tab)
- [ ] Analytics (traffic, conversions, revenue)
- [ ] Settings (email templates, API keys, backups)
- [ ] Server management panel (deploy, domains, SSL, logs)

## 🟪 PHASE 16: ANALYTICS & TRACKING
- [ ] Track: page_view, gallery_view, booking_start, payment_success, etc.
- [ ] Store: user_id, IP, device, referrer, location
- [ ] Dashboard graphs (line charts, funnels)
- [ ] Revenue reports
- [ ] Export to CSV

## 🟧 PHASE 17: EMAIL INTEGRATION
- [ ] Gmail SMTP configured
- [ ] OTP verification emails
- [ ] Booking confirmation
- [ ] Payment receipts
- [ ] Contract sent/signed
- [ ] Gallery delivered
- [ ] Reminder emails (1 week, 24h, 2h before)
- [ ] Admin notifications (new booking, payment, message)
- [ ] Dark-themed email templates

## 🟥 PHASE 18: AUTOMATION & BACKGROUND JOBS
- [ ] Image processing queue
- [ ] Email sending queue
- [ ] Google Calendar sync (every 5 min)
- [ ] Analytics aggregation (hourly)
- [ ] Database backup (daily)
- [ ] Temp file cleanup (daily)
- [ ] Booking reminders
- [ ] Weather alerts

## 🟩 PHASE 19: SECURITY
- [ ] CSRF protection on all forms
- [ ] Rate limiting
- [ ] Cookie security (HttpOnly, Secure, SameSite)
- [ ] Session fingerprinting
- [ ] IP tracking
- [ ] Malicious cookie detection
- [ ] Encryption for sensitive data (AES-256-GCM)
- [ ] Constant-time comparisons
- [ ] Audit logging

## 🟦 PHASE 20: DATABASE SCHEMA
- [ ] roles table
- [ ] users table (with google_id, stripe_customer_id, etc.)
- [ ] sessions table
- [ ] otp_codes table
- [ ] galleries table
- [ ] gallery_links table
- [ ] images table
- [ ] image_versions table
- [ ] locations table
- [ ] bookings table
- [ ] payments table
- [ ] contract_templates table
- [ ] contracts table
- [ ] message_threads table
- [ ] messages table
- [ ] models table
- [ ] model_jobs table
- [ ] testimonials table
- [ ] analytics_events table
- [ ] notifications table
- [ ] settings table

---

## 📝 ADDITIONAL REQUESTS FROM CHAT

### UI/UX
- [ ] Dark mode ON by default, toggleable with flashing light icon
- [ ] All corners rounded (no sharp edges)
- [ ] All buttons have neon glow on hover
- [ ] All cards lift on hover
- [ ] Subtle black text-shadow for contrast
- [ ] Loading skeleton shimmer
- [ ] NO static UI - everything has motion

### Slideshow (GTA V Style)
- [ ] Images slide in QUICKLY (200-300ms)
- [ ] Stop centered, slightly zoomed (1.05-1.1x)
- [ ] SLOWLY pan across frame (5-7 seconds)
- [ ] SLOWLY zoom (1.15-1.2x)
- [ ] Speed RANDOMIZED per image
- [ ] Speed RAMPS UP on exit
- [ ] Motion NEVER stops
- [ ] NO simple fade-only transitions

### Gallery Features
- [ ] Categories pop out with animations
- [ ] Show name, caption, date, location on hover
- [ ] Location opens Google Maps/Earth
- [ ] Save locations for future shoots

### Account Features
- [ ] Create account for someone using their email
- [ ] Send account creation email
- [ ] Username format: tiredofdointm0001-100000
- [ ] Allow view-only access without account
- [ ] Extend/resend invite links (7 day expiry)

### Contact Page
- [ ] No redirect - inline form
- [ ] Instagram links (@tiredofdointm, @tiredflics)
- [ ] TikTok link
- [ ] Add/remove contact methods in dashboard

### Cookies
- [ ] Store logged-in user
- [ ] Random identifier cookies
- [ ] Detect malicious cookies
- [ ] Save IP and device fingerprint
- [ ] Check on every visit

---

## ✅ COMPLETED
- [x] R2Service created
- [x] EmailService created
- [x] StripeService created
- [x] ServerService created
- [x] VPS setup script
- [x] Deploy script with branch selection
- [x] GitHub webhook endpoint
- [x] Stripe webhook endpoint
- [x] Server management panel view
- [x] Deploy user created on VPS
- [x] Deploy key added to GitHub
- [x] Repo cloned to VPS
- [x] Nginx configured
- [x] .env created on VPS

---

## 🚧 CURRENT BLOCKERS
1. migrate.php has duplicate function error - NEEDS FIX
2. Site not loading at http://164.92.99.233 - NEEDS DEBUG
3. Database tables not created yet

---

## 📅 PRIORITY ORDER
1. Fix VPS errors and get site loading
2. Run database migrations
3. Implement Gate system
4. Implement Authentication (OTP + Google)
5. Homepage with slideshow
6. Gallery system
7. Booking system
8. Payment integration
9. Contract system
10. Everything else
