# Field Application Management System (FMS)

A beginner-friendly PHP + MySQL (PDO) system for managing student field
attachment applications: registration → application → review →
accept/reject/return → placement → notification.

## PHASE 1 — Project setup (current state)

### What we are building and why

Before writing any feature, two things must exist:

1. **A folder structure** so every file has one clear responsibility (MVC style).
2. **A working database** — the original schema had errors that would have
   made the app impossible to build. See
   [`database/DATABASE_REVIEW.md`](database/DATABASE_REVIEW.md) for the full
   review (read this first — it is the most important document of Phase 1).

### Folder structure and what each folder is for

```
FMS/
├── config/        Configuration only. config.example.php is committed;
│                  your real config.php (with the DB password) is git-ignored.
├── controllers/   "Traffic police". Receive a request (e.g. "submit
│                  application"), call models to do the work, then pick a view.
│                  No SQL and no HTML lives here.
├── models/        Talk to the database. One class per main entity
│                  (User, Student, Application, ...). ALL SQL lives here,
│                  always through PDO prepared statements.
├── views/         HTML pages. They only display data given to them —
│                  they never run SQL themselves.
│   ├── auth/      login / register pages
│   ├── student/   student dashboard, profile, application forms
│   ├── admin/     admin dashboard, review pages
│   └── layouts/   header/navbar/sidebar/footer shared by every page,
│                  so the same HTML is never copy-pasted twice
├── public/        The ONLY folder the web server should expose.
│                  Keeping PHP source outside the web root means nobody can
│                  request models/User.php directly in a browser.
├── database/      schema.sql (corrected), seed.sql, DATABASE_REVIEW.md
├── uploads/       user-uploaded files (git-ignored, never web-executed)
└── assets/        css / js
```

Why this separation matters: when a form is submitted, data flows
**view → controller → model → MySQL → model → controller → view**. Each layer
only knows about its neighbour, so you can change the HTML without touching
SQL, and vice versa.

### Setting up the database

```bash
mysql -u root -p < database/schema.sql   # creates DB + 31 tables
mysql -u root -p < database/seed.sql     # roles, lookup values
```

Then create a dedicated MySQL user (never connect as root from PHP):

```sql
CREATE USER 'fms_user'@'localhost' IDENTIFIED BY 'choose_a_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON FMS.* TO 'fms_user'@'localhost';
```

Finally:

```bash
cp config/config.example.php config/config.php
# edit config.php with your DB user/password
```

### Verify Phase 1

- [ ] `schema.sql` runs with no errors
- [ ] `SHOW TABLES;` in the FMS database lists 31 tables
- [ ] `SELECT * FROM roles;` shows `student` and `admin`
- [ ] `config/config.php` exists locally and is NOT tracked by git (`git status`)

## PHASE 3 — Central PDO connection

One class, `config/Database.php`, owns the database connection. Every future
model calls `Database::getConnection()` and gets the same shared PDO object —
credentials live in exactly one git-ignored file (`config/config.php`) and
only one connection is opened per request. The class file itself explains
what PDO is, why prepared statements stop SQL injection, and why errors are
logged instead of shown to visitors.

Verify Phase 3:

```bash
cd FMS
php database/test_connection.php
# Expect: Connection OK, prepared statement OK, roles row count
```

## PHASE 4 — Authentication (with a slice of Phase 5)

**Schema fix found during this phase**: nothing linked `users` to `roles`, so
the app could not tell students from admins. Migration
`database/migrations/001_add_user_role.sql` adds `users.role_id`. Run it:

```bash
sudo mysql < database/migrations/001_add_user_role.sql
```

New files and their jobs:

- `config/session.php` — starts the session with a hardened cookie and
  defines the helpers used everywhere: `e()` (output escaping),
  `require_login()` / `require_role()` (authorization), CSRF token helpers,
  and flash messages. Read the comments — each helper explains the attack
  it prevents.
- `models/User.php` — all SQL for the users table: find, exists, create
  (with `password_hash()`), record last login.
- `controllers/AuthController.php` — register/login/logout logic:
  validation, `password_verify()`, one generic login error (so attackers
  can't probe which usernames exist), `session_regenerate_id()` against
  session fixation.
- `views/auth/login.php`, `views/auth/register.php` + shared
  `views/layouts/header.php`/`footer.php`.
- `public/index.php` — the FRONT CONTROLLER: the only browser-reachable PHP
  file; routes `?page=...` to controller methods.
- `database/create_admin.php` — CLI-only admin account creation
  (public registration always creates students).

Run the app locally:

```bash
cd FMS
php -S localhost:8000
# open http://localhost:8000/public/index.php
```

Create your admin account:

```bash
php database/create_admin.php admin admin@example.com StrongPass123
```

Verify Phase 4 (do each of these yourself):

- [ ] Register a student → redirected to login with a success message
- [ ] Wrong password → generic "Invalid username/email or password"
- [ ] Non-existent user → the SAME generic error
- [ ] Login → lands on the student dashboard
- [ ] Visit `?page=admin_dashboard` as a student → 403 Forbidden
- [ ] Visit a dashboard after logout → bounced to login
- [ ] `SELECT password_hash FROM users;` → bcrypt hashes, never plain text

## PHASE 6 — Student profile

New files:

- `models/Student.php` — SQL for the students table plus the dropdown
  lookups (institutions, nationalities, study levels). `saveProfile()`
  INSERTs on first save, UPDATEs afterwards; `user_id` always comes from
  the SESSION, never from the form (a student cannot edit someone else's
  profile by tampering with an ID).
- `controllers/StudentController.php` — dashboard + profile actions;
  validates every field server-side and re-checks the "registration number
  unique per institution" rule with a friendly message before the database
  would reject it.
- `views/student/dashboard.php` — shows profile summary, or a "complete
  your profile" prompt (applying is blocked until the profile exists).
- `views/student/profile.php` — the form; note the pre-fill pattern where
  just-typed values win over stored values after a validation error.

`database/seed.sql` gained sample institutions and nationalities so the
dropdowns are not empty (re-run it or insert your real ones).

Verify Phase 6:

- [ ] Login as a new student → dashboard says profile is incomplete
- [ ] Save with an empty name / no institution → validation errors, typed values kept
- [ ] Save a valid profile → redirected to dashboard showing your details
- [ ] Edit and re-save → values update
- [ ] Second student using the same registration number + institution → friendly error
- [ ] Admin visiting `?page=profile` → 403

## Next phases

Phases 7–16: applications,
admin review, placements, notifications, dashboards.
