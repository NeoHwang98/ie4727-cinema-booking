# NECT Cinema Booking Web App

NECT is a cinema ticket booking web application built with a traditional web stack. It supports movie discovery, schedules, seat selection, cart and checkout, booking history, rewards/coupons, and admin management for movies and sales.

## Tech Stack
- PHP 8 (server-side rendering, business logic)
- MySQL / MariaDB (relational data storage)
- HTML5 + CSS3 (custom UI)
- Vanilla JavaScript (client-side validation and interaction)
- XAMPP (Apache, MySQL, Mercury local mail)

## Main Features
- User authentication: signup, login, logout, account view
- Movies module: now showing and coming soon listings
- Schedule module: date and time filtering with session cards
- Seat selection: availability handling and per-session purchase limits
- Cart and checkout: selectable items, pricing summary, coupon support
- Booking confirmation: acknowledgement page and email notification flow
- Rewards page: coupon display and redemption rules
- Admin panel:
  - Add and edit movies
  - Manage showtimes
  - Manage coupons
  - Sales report with revenue summary and top movie by revenue

## Project Structure
- `index.php` - home page
- `inc/` - shared bootstrap, DB connection, helpers, header/footer, mailer
- `pages/` - application pages (movies, schedule, details, cart, checkout, admin)
- `assets/` - CSS, JavaScript, images, posters, highlights
- `database/` - schema and seed SQL scripts

## Setup (Local)
1. Place project in your web root (example: `htdocs/cinema-test`).
2. Start Apache and MySQL in XAMPP.
3. Create database and import:
   - `database/schema.sql`
   - seed files required for your test run (movies, coupons, showtimes)
4. Configure DB credentials in `inc/db.php`.
5. Open `http://localhost/cinema-test`.

## Local Email (Mercury)
- Mail sending is configured for local Mercury testing.
- Ensure Mercury is running in XAMPP and local test mailboxes exist.
- Configure Thunderbird (or equivalent) to read the local mailbox used in your test flow.

## Notes
- Single-location cinema model (no branch/location selection in user flow).
- Validation is implemented on both client side (JavaScript) and server side (PHP).
- Admin actions are role-protected.

## Author
F32-DG04 Team
