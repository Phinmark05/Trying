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

## Next phases

Phase 4: authentication ·
Phase 5: roles/authorization · Phases 6–16: student profile, applications,
admin review, placements, notifications, dashboards.
