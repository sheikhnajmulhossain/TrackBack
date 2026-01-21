# TrackBack Copilot Instructions

## Project Overview
TrackBack is a **Lost & Found Platform** for university students. It's a PHP/MySQL backend with vanilla JavaScript frontend, using MVC architecture with role-based access control (Admin, Moderator, Student).

## Architecture

### MVC Structure
- **Model** (`Model/`): Database initialization, helper functions, and business logic
- **View** (`View/`): HTML templates and JavaScript for student/admin/moderator dashboards
- **Controller** (`Controller/`): API endpoints handling requests via action-based routing

### Key Files
- [Model/config.php](Model/config.php): Database credentials
- [Model/database.php](Model/database.php): Auto-creates database and tables on first connection
- [Model/functions.php](Model/functions.php): ~1100 lines of reusable utilities (CRUD, auth, validation)
- [Controller/auth_api.php](Controller/auth_api.php): Login, register, logout, email verification
- [Controller/post_api.php](Controller/post_api.php): Lost/found item CRUD and search
- [Controller/admin_api.php](Controller/admin_api.php): User management, moderation, stats
- [View/*.js](View/): Dashboard logic for each role

## Critical Patterns

### API Request Pattern
All API endpoints follow **action-based routing** in Controller files:
```php
$action = $_GET['action'] ?? $_POST['action'] ?? null;
switch ($action) {
    case 'action_name': actionHandler($conn); break;
    default: sendError('Invalid action', 400);
}
```
- Fetch from `../Controller/{api_name}.php?action={action}&other=params`
- Response format: `{success: bool, message: string, data: array}`

### Session & Authentication
- Sessions expire in 24 hours (86400s), HTTPOnly cookies, same-site
- `validateSession($requiredRole)` enforces auth on protected endpoints:
  - `validateSession('admin')`: Admin only
  - `validateSession(['moderator', 'admin'])`: Moderator or Admin
  - `validateSession()`: Any authenticated user
- Roles: `'admin'`, `'moderator'`, `'student'` (stored in `$_SESSION['user_type']`)

### Response Helpers
- `sendSuccess($data, $message)`: Returns `{success: true, message, data}` and exits
- `sendError($message, $code)`: Sets HTTP code, returns `{success: false, message}` and exits
- Both set `Content-Type: application/json` via `setCorsHeaders()`

### Data Validation
- `sanitizeInput($data)`: Strips tags, trims, and escapes via `mysqli::real_escape_string`
- **Mix of prepared & direct SQL**: Most code uses string concatenation; prepared statements appear in `updateUserProfile()`
- Always cast IDs to `(int)` before SQL: `$userId = (int)$userId`

### User Roles & Statuses
- **User Types**: admin, moderator, student
- **Account Status**: active, banned, pending (students need admin approval to activate)
- **Post Status**: pending (needs moderator approval), approved, banned, resolved
- Warnings table tracks user violations; audit_logs table tracks actions

### Image Upload
- `uploadImage($file, $folder)`: Saves to `View/img/{folder}` with uniqid + timestamp filename
- Allowed types: jpeg, png, gif, webp; max 5MB
- Returns relative path like `"img/uploads/uniqid_timestamp.jpg"`

### Frontend Patterns (JavaScript)
- `fetchAPI(endpoint, options)`: Wrapper around fetch using `../Controller/{endpoint}`, includes credentials
- Dashboards check session via `checkUserStatus()` before loading
- API calls use `?action=` query params
- Forms use FormData for file uploads
- UI updates via DOM manipulation (no framework)

## Common Tasks

### Adding a New API Endpoint
1. Create handler function in [Model/functions.php](Model/functions.php) (e.g., `function newFeatureHandler($conn)`)
2. Add case in appropriate controller switch statement (e.g., [Controller/post_api.php](Controller/post_api.php))
3. Add role check if needed: `validateSession('role_name')`
4. Return via `sendSuccess()` or `sendError()`

### Adding Database Fields
- Modify table creation SQL in [Model/database.php](Model/database.php) `CREATE TABLE` sections
- Only affects new installs; existing DBs require manual migration
- Update CRUD functions in [Model/functions.php](Model/functions.php) to include new fields

### Authentication Flow
1. User submits form → `auth_api.php?action=register/login` (POST)
2. `handleRegister()` / `handleLogin()` validates input and calls `createUser()` / `authenticateUser()`
3. Session variables set: `$_SESSION['user_id']`, `$_SESSION['user_type']`
4. Client receives redirect URL or success response
5. Protected endpoints check session via `validateSession()` at top of controller

### Moderator Workflow
- Admin assigns moderators via `appointModerator()` (sets user_type to 'moderator')
- [Controller/moderator_api.php](Controller/moderator_api.php) requires `validateSession(['moderator', 'admin'])`
- Moderators approve pending posts, flag suspicious content, issue warnings

## Database Schema Highlights
- **users**: student_id (unique), email (unique), password_hash (bcrypt), user_type, account_status
- **posts**: post_type (lost/found), post_status (pending/approved/banned/resolved), flagged, creator's user_id
- **messages**: sender_id, receiver_id, is_read
- **warnings**: user_id (tracks violations), removed_at (NULL if active)
- **audit_logs**: auto-created on first log action, tracks all admin/moderator actions

## Development Notes
- PHP error logging enabled (check browser console network tab for JSON responses)
- Direct SQL in most functions (injection risks mitigated by sanitization + type casting)
- No framework (no ORM, routing, validation middleware) — manual CORS headers, session management
- Images stored in `View/img/` subdirectories; clients fetch from relative paths
- Dashboard JS files (~1300 lines) handle UI state management with global `postsData`, `messagesData` arrays

## When Modifying
- Always escape user input via `sanitizeInput()` or prepared statements
- Cast numeric IDs to `(int)` before SQL queries
- Check role requirements using `validateSession()` before DB queries
- Return JSON responses via `sendSuccess()` or `sendError()` (never echo plain HTML in APIs)
- Update [Model/functions.php](Model/functions.php) for shared logic; API-specific logic stays in controllers
