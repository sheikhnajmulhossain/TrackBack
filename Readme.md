# TrackBack: Lost & Found Platform for University Students

## Overview
TrackBack is a web-based Lost & Found platform designed for university environments. It enables students to report lost or found items, facilitates communication between users, and provides robust moderation and administration tools. The project is built with a PHP/MySQL backend and a vanilla JavaScript frontend, following the MVC (Model-View-Controller) architecture.

---

## Features
- **User Roles:**
  - **Student:** Report lost/found items, message other users, manage own posts.
  - **Moderator:** Approve/ban posts, flag suspicious content, issue warnings.
  - **Admin:** Manage users, assign moderators, view statistics, audit logs.
- **Authentication:**
  - Registration, login, logout, email verification
  - Session-based authentication with 24-hour expiry
- **Lost & Found Posts:**
  - Create, edit, search, and manage lost/found item posts
  - Image uploads (jpeg, png, gif, webp; max 5MB)
  - Post statuses: pending, approved, banned, resolved
- **Moderation:**
  - Approve or ban posts, issue warnings, audit logs for actions
- **Messaging:**
  - Direct messaging between users regarding posts
- **Security:**
  - Input sanitization, role-based access control, session validation

---

## Architecture
- **Backend:** PHP (no framework), MySQL
- **Frontend:** HTML, CSS, vanilla JavaScript (no framework)
- **MVC Structure:**
  - **Model/**: Database logic, helper functions, business logic
  - **View/**: HTML templates, CSS, JS for dashboards and UI
  - **Controller/**: API endpoints, action-based routing

---

## Key Files & Folders
- **Model/**
  - `config.example.php` and `mail_config.example.php`: Templates for local configuration
  - `database.php`: Database connection used by the application
  - `schema.sql`: Table definitions for a fresh database
  - `functions.php`: Core business logic, CRUD, auth, validation
  - `PHPMailer/`: Email sending library
- **Controller/**
  - `auth_api.php`: Auth endpoints (login, register, verify)
  - `post_api.php`: Lost/found post management
  - `admin_api.php`: Admin tools, user management
  - `moderator_api.php`: Moderator tools
- **View/**
  - `*-dashboard.html/.js/.css`: Dashboards for each role
  - `img/`: Uploaded images (organized by type)
- **Root**
  - `index.html`: Homepage. The login and registration pages are under `View/`.

---

## Database Schema Highlights
- **users**: student_id, email, password_hash, user_type, account_status
- **posts**: post_type, post_status, flagged, creator's user_id
- **messages**: sender_id, receiver_id, is_read
- **warnings**: user_id, removed_at
- **audit_logs**: Tracks admin/moderator actions

---

## API Usage Pattern
- All API endpoints use `Controller/*.php?action={action}`
- Responses are JSON: `{success: bool, message: string, data: array}`
- Use `fetchAPI()` JS wrapper for frontend requests

---

## Security & Best Practices
- All inputs sanitized via `sanitizeInput()`
- Numeric IDs cast to `(int)` before SQL
- Role checks via `validateSession($role)`
- API always returns JSON (never HTML)
- Image uploads validated for type/size

---

## Getting Started
1. Clone the repository and serve its root with PHP and the MySQLi extension.
2. On a fresh installation, create an empty MySQL or MariaDB database and import `Model/schema.sql`. Existing deployments should keep their existing database.
3. Copy `Model/config.example.php` to `Model/config.php` and set the `TRACKBACK_DB_*` values for your server. Copy `Model/mail_config.example.php` to `Model/mail_config.php` and configure the `TRACKBACK_SMTP_*` and `TRACKBACK_MAIL_*` settings. Keep both live config files on the server.
4. Open `index.html`. Registration and password recovery send email over SMTP. New student accounts require admin approval.

---

## Development Notes
- No external PHP framework or ORM
- Manual CORS and session management
- Error logging enabled by default
- For new DB fields, update `Model/database.php` and `Model/functions.php`

---

## License
This project is for educational purposes. See LICENSE file if present.
