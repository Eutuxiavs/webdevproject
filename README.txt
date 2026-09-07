YONZON CLAIM — Full-Stack Setup
================================

FOLDER STRUCTURE (organized — drop straight into htdocs)
----------------------------------------------------
yonzon-app/
├── index.php              Public homepage (DB-backed marketplace)
├── register.php           Create account
├── login.php               Log in
├── logout.php              Log out
├── dashboard.php           Post-login hub — your registry as a photo inventory grid
├── browse.php               The shop: browse everyone's listings, filter by status/category
├── profile.php              Your profile — avatar, bio, business (all optional)
├── claim-add.php           Register a new item
├── claim-sell.php          List an owned item for sale (requires a real photo)
├── claim-status.php        POST-only: report lost, mark found, cancel listing, delete
├── start-conversation.php  Begins a chat about a listing (for-sale OR lost items)
├── messages.php             Full-page chat UI (conversation list + thread)
├── api-send-message.php    AJAX: send a chat message
├── api-fetch-messages.php  AJAX: poll for new chat messages
├── database.sql             Paste into phpMyAdmin to create everything
├── includes/
│   ├── config.php           DB connection + session setup
│   ├── functions.php        Shared helpers (auth, CSRF, uploads, formatting, brand_mark())
│   └── chat-widget.php      The floating chat bubble, included on every logged-in page
├── assets/
│   ├── css/style.css
│   └── js/main.js
└── uploads/                 Where item photos + profile pictures are stored

Every page file stays at the ROOT level (index.php, dashboard.php, etc.) —
only the shared backend code moved into includes/, and only CSS/JS moved
into assets/. That split matters: pages reference includes/ and assets/
using relative paths like "includes/config.php" and "assets/css/style.css",
so as long as the whole yonzon-app folder is copied in as one piece (not
just the .php files), nothing breaks. If you ever get a "failed to open
stream" error again, it means a subfolder (usually includes/) didn't get
copied along with the rest — check that it's actually there on disk.


SETUP (XAMPP)
----------------------------------------------------
1. Copy the whole "yonzon-app" folder into C:\xampp\htdocs\
2. Start Apache AND MySQL in the XAMPP control panel (both — MySQL is new).
3. Open http://localhost/phpmyadmin
4. Click the "SQL" tab, paste the entire contents of database.sql, and run it.
   This creates the "webdevproject" database and all four tables.
5. Visit http://localhost/yonzon-app/
6. Click "Register an item" → create an account → you're in.

config.php already assumes the default XAMPP MySQL login (user "root",
no password). If you set a MySQL password yourself, update DB_PASS in
includes/config.php to match.


WHAT'S NEW: BUY / SELL WITH OWNERSHIP TRANSFER
----------------------------------------------------
This adds a real transaction flow instead of just listing items:

1. A buyer clicks "Make Offer" on a for-sale item (browse.php) and
   proposes a price — offer-create.php.
2. The seller sees it under Offers > "Offers you've received" and can
   Accept or Decline. Accepting reserves the item (status becomes
   "reserved") and auto-declines every other pending offer on it.
3. Once accepted, BOTH sides see a "Confirm handover complete" button.
   Ownership does NOT transfer when the seller accepts, and does NOT
   transfer when only one side confirms — it only transfers once BOTH
   the buyer and seller have independently clicked confirm.
4. The moment both confirmations are in, offer-action.php runs the
   actual transfer: the item's user_id changes to the buyer, status
   goes back to "owned", and the price is cleared. The item now shows
   up in the BUYER's dashboard, not the seller's.
5. Either side can cancel an accepted-but-not-yet-completed deal,
   which puts the item back up for sale.

WHY THIS DESIGN: I can't build real payment escrow (that needs an
actual payment processor and real legal/financial infrastructure), so
this is the strongest safeguard achievable in-app: neither person can
unilaterally transfer ownership, fake a completed sale, or walk away
with something without the other side's explicit agreement. It won't
stop someone from lying about having sent payment through some outside
method — no software fix can — which is why offers.php also tells users
plainly to arrange payment through the chat first and meet safely for
in-person handoffs.

New files: offer-create.php, offers.php, offer-action.php
New DB table: offers (see database.sql)
New item status: "reserved" (shown as "Offer Pending")



The database is named "webdevproject" (not "yonzon_claim"). If you'd
already imported the old schema under a different name, drop it first —
database.sql has the exact DROP DATABASE commands at the top as a comment.


WHAT'S NEW IN THIS PASS
----------------------------------------------------
- Dashboard ("Your registry") is now a photo-forward inventory grid —
  each item shows its real uploaded photo, category, claim ID, status,
  and price, laid out like a proper catalog instead of plain text rows.

- browse.php is the actual shop: every listed item across every user,
  with filter pills (All / For Sale / Lost & Found) and a category
  dropdown. This is separate from the homepage's static "Live examples"
  section — browse.php is the real, filterable, logged-in experience.

- Messaging now has two forms: the original full-page messages.php, and
  a floating circular chat bubble (bottom-right corner) that's on every
  logged-in page — dashboard, browse, profile, add/sell forms. Click it
  to see your conversations and chat inline without leaving the page.
  Both use the same api-send-message.php / api-fetch-messages.php
  endpoints under the hood, so messages sent from one show up in both.

- profile.php: profile picture, bio, and an optional "business / related
  to" field, shown at the top of the profile page along with a quick
  item-count summary. All three fields are optional — name is the only
  required one, since it's already set at registration.

- "I Found This" — browse.php now also lets you start a conversation on
  someone else's LOST item, not just items for sale, since that's the
  actual point of the lost & found feature (a finder reaching the owner).


HOW TO SWAP THE LOGO
----------------------------------------------------
The diamond monogram is defined in exactly ONE place: the brand_mark()
function near the top of includes/functions.php. Every page (homepage,
dashboard, browse, profile, auth pages, footer) calls that one function
instead of repeating the logo markup, so changing it there changes it
everywhere at once.

To use your own logo image:

1. Create the folder assets/img/ and put your logo file in it, e.g.
   assets/img/logo.png (png, jpg, or svg all work).

2. Open includes/functions.php, find the brand_mark() function, and
   replace the <svg>...</svg> block inside it with:

       echo '<img src="' . asset_path('img/logo.png') . '" alt="YONZON"
             style="width:' . $size . 'px; height:' . $size . 'px; object-fit:contain;">';

3. Save. That's it — every instance of the logo across the whole site
   updates immediately, and each one stays a FIXED size (34px in nav
   bars, 26px in the footer, 24px on login/register) no matter what
   pixel dimensions your actual image file is, because object-fit:contain
   scales it to fit that box without stretching or distorting it.

If your logo image isn't square, that's fine — object-fit:contain keeps
its proportions and just centers it inside the fixed-size box instead of
cropping or squishing it.


UPLOADS FOLDER SECURITY NOTE
----------------------------------------------------
The uploads/.htaccess lockdown (blocking PHP execution and folder
browsing) was removed earlier at your request since this is a school
project. Uploaded files are still validated by real file content (not
just the extension) and given random filenames, but the extra Apache-
level lockdown is gone. If this ever became a real public site, that
.htaccess should go back in.



- Real accounts: passwords hashed with password_hash(), never stored plain.
- Add / edit / delete claims, all scoped to the logged-in user.
- "List for sale" requires an actual uploaded photo (JPG/PNG/WEBP, max 5MB) —
  no more icon placeholders for real listings. The homepage marketplace and
  "Live examples" grid now pull real rows from the database.
- A working buyer↔seller chat: click "Message Seller" on a listing, it opens
  (or resumes) a conversation, and messages.php polls for new messages every
  3 seconds without a page reload.
- Dashboard is action-only — no marketing copy, just your item count, quick
  actions, your claims list with inline status changes, and a message preview.


ABOUT THE WATCH PHOTO YOU SENT
----------------------------------------------------
I didn't embed that image into the project — it's real Rolex product
photography, which is copyrighted by Rolex/the photographer, not something
I can ship as a site asset. What I built instead is the actual feature:
sellers upload their OWN photo of their OWN item through claim-sell.php.
That's what a real marketplace needs anyway — a stock photo of someone
else's watch wouldn't prove anything about the listing.


FLAWS I FOUND AND FIXED
----------------------------------------------------
1. SQL injection — every query now uses PDO prepared statements with bound
   parameters, never string-concatenated SQL.

2. Passwords — hashed with password_hash()/PASSWORD_DEFAULT and checked
   with password_verify(). Nothing is ever stored or compared in plain text.

3. Session fixation — session_regenerate_id(true) is called immediately
   after both login and registration.

4. Stored XSS — the old static mockup embedded item names directly into
   HTML (e.g. `<?= $item['name'] ?>`), which was safe only because the data
   was hardcoded. Now that item names come from real user input, EVERY
   user-supplied field is passed through e() (htmlspecialchars) on output.

5. CSRF — every form that changes data (register, login, add/sell/delete
   claim, send message) includes a per-session token that's verified
   server-side before anything happens.

6. GET requests changing data — the original mockup used <a href> links
   with query strings for "report lost" / "delete", which is bad practice
   (bookmarkable, cacheable, shows up in browser history and server logs,
   and is vulnerable to CSRF via a simple image tag). These are now POST-only
   forms in claim-status.php, which also rejects any non-POST request.

7. File upload attacks — uploaded files are validated by real file content
   (finfo / MIME sniffing, not just the filename extension), capped at 5MB,
   and renamed to a random hex string on save (no path traversal, no
   overwriting other users' files).
   NOTE: the /uploads/.htaccess that blocked PHP execution and folder
   browsing in that directory was removed on request, since it was
   causing a 403 Forbidden page and this is a school project, not a
   public deployment. If this ever goes on a real server, put a simple
   .htaccess back in /uploads/ with:
       <FilesMatch "\.(php|phtml)$">
           Require all denied
       </FilesMatch>
   so an uploaded file can never accidentally be executed as PHP.

8. Authorization vs. authentication — require_login() only checks that
   SOMEONE is logged in. Every claim/status/message action additionally
   filters by `WHERE user_id = ?` (or checks conversation membership) so a
   logged-in user can't edit or delete another user's items just by
   guessing an ID in the URL.

9. PHP 8-only syntax — the earlier mockup used `match()`, which needs
   PHP 8.0+. All application logic now uses plain switch/if statements so
   it also runs on PHP 7.4, which some XAMPP installs still ship.

10. Generic errors — login shows the same "Incorrect email or password"
    message whether the email doesn't exist or the password is wrong, so
    you can't enumerate valid accounts by testing emails.

11. Empty states — new install / empty database no longer breaks the page;
    "Live examples" and "Marketplace" show a clean empty state instead of
    a blank grid or a PHP warning.


KNOWN LIMITATIONS (things I'd flag before this went to real users)
----------------------------------------------------
- No email verification or password reset flow yet.
- No rate limiting on login — someone could brute-force a password with
  enough attempts. Worth adding after a few failed tries (lockout or delay).
- Chat uses polling (a fetch every 3 seconds), not WebSockets — fine for a
  small app, but it won't scale to many simultaneous conversations. A real
  production build would use something like Pusher, Ratchet, or Socket.IO.
- No pagination on the dashboard claims list — fine for a handful of items,
  will need it once someone has 50+.
- display_errors is whatever your php.ini has it set to. On a real server,
  make sure display_errors is OFF and log_errors is ON, so PHP warnings
  (like the one in your screenshot) never appear to real visitors.


LAYOUT WIDTH
----------------------------------------------------
The public homepage content area is 1240px wide, the dashboard/app area is
1360px wide, both centered with 40px side padding — so the whole app sits
comfortably inside a 1440px laptop viewport with margin to spare, no
horizontal scroll.