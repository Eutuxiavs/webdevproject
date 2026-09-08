YONZON CLAIM (GI AI KO NI SIR ROADMAP BREAKDOWN)

webdevproject/
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
