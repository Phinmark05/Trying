# PHASE 2 PREVIEW — DATABASE REVIEW (done first, as required)

Before any PHP was written, the original `final1.sql` was loaded into a real
MySQL 8 server to see if it actually runs. It does **not** — it fails on the
`students` table. Below is every problem found, why it matters, and what was
changed in the corrected `schema.sql`.

---

## Problem 1 — Invalid column type syntax in `students` (script-breaking)

**What was wrong**

```sql
institution_id UNSIGNED INT NOT NULL,
nationality_id UNSIGNED INT NULL,
study_level_id UNSIGNED INT NULL,
```

**Why it is a problem**

MySQL requires the type name first, then the modifier: `INT UNSIGNED`.
`UNSIGNED INT` is a syntax error, so the whole script stops at line 149 and
no tables after `students` are ever created. This was confirmed by running
the file: `ERROR 1064 (42000) at line 149`.

**The fix**

```sql
institution_id INT UNSIGNED NOT NULL,
nationality_id INT UNSIGNED NULL,
study_level_id INT UNSIGNED NULL,
```

---

## Problem 2 — `students` has no `id`, but other tables reference `students(id)` (relationship-breaking)

**What was wrong**

The original `students` table used a composite primary key:

```sql
PRIMARY KEY (registration_no, institution_id)
```

…but later, `applications` declares:

```sql
CONSTRAINT fk_applications_student FOREIGN KEY (student_id) REFERENCES students(id)
```

There is no `students.id` column, so this foreign key can never be created.
The core relationship of the whole system (student → application) would fail.

**Why it is a problem**

A foreign key must point at a column (or column set) that actually exists and
is indexed as a key on the parent table. Referencing a non-existent column is
an immediate error.

**The fix**

Give `students` a normal auto-increment surrogate key, and keep the business
rule "one registration number per institution" as a UNIQUE constraint instead:

```sql
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
...
UNIQUE KEY uq_students_regno_institution (registration_no, institution_id)
```

**What changed conceptually**: the *identity* of a row (its `id`) is now
separate from its *business uniqueness* (registration number + institution).
This is the standard pattern: surrogate primary key + unique business key.
Nothing about the real-world rule was lost — a duplicate registration number
at the same institution is still rejected.

---

## Problem 3 — `ON DELETE SET NULL` on a composite FK containing a NOT NULL column (script-breaking)

**What was wrong** (in `placements`)

```sql
CONSTRAINT fk_placements_industrial_sup
    FOREIGN KEY (organization_id, industrial_supervisor_id)
    REFERENCES supervisors(organization_id, id) ON DELETE SET NULL
```

MySQL rejects this with:
`ERROR 1830: Column 'organization_id' cannot be NOT NULL: needed in a foreign key constraint ... SET NULL`

**Why it is a problem**

`ON DELETE SET NULL` means "if the parent row is deleted, set ALL the FK
columns to NULL". But `placements.organization_id` is NOT NULL (a placement
must always belong to an organization), so MySQL refuses to create the
constraint.

**The fix**

```sql
... REFERENCES supervisors(organization_id, id) ON DELETE RESTRICT
```

`RESTRICT` means: you cannot delete a supervisor while a placement still
points at them. That is the safer behaviour anyway — an admin should reassign
the placement first. (Deactivating a supervisor via `is_active = FALSE` is the
normal day-to-day operation, not deleting the row.)

**Good news**: this composite FK design is actually clever and was kept.
Because the FK is `(organization_id, industrial_supervisor_id)` referencing
`supervisors(organization_id, id)`, the database itself guarantees the
industrial supervisor belongs to the same organization as the placement —
exactly the rule required in the project brief. The same trick is used for
`departments` via `fk_placements_org_dept`.

---

## Problem 4 — No `notifications` table (missing required feature)

The project brief requires notifications (submitted / returned / accepted /
rejected / placement assigned), but the schema had no table for them. Added:

```sql
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_read (user_id, is_read)
);
```

Notes: it points at `users` (not `students`) so admins can receive
notifications too later; `ON DELETE CASCADE` means deleting a user removes
their notifications (they are worthless without the owner); the composite
index makes "unread notifications for this user" fast — the exact query the
dashboard will run constantly.

---

## Minor additions (not errors)

* `students.updated_at` was added, matching every other table, so we can see
  when a profile was last edited.
* `CREATE DATABASE` became `CREATE DATABASE IF NOT EXISTS` so re-running the
  script during development doesn't error out.

## Things reviewed and found CORRECT (left untouched)

* `users` ↔ `students` one-to-one via `students.user_id ... UNIQUE`.
* `departments` belong to `organizations`; the extra
  `UNIQUE KEY uq_department_organization_id (organization_id, id)` exists
  specifically so composite FKs (see Problem 3) can enforce org/department
  consistency.
* `applications.active_student_id` generated column + unique key is a neat
  trick to enforce "one active application per student" at database level.
* `placements.application_id` is UNIQUE — one placement per application.
* Status ENUM on `applications` matches the required workflow.
* Future tables (`logbook_entries`, audit logs, etc.) are created but their
  functionality is intentionally NOT built in this version.

---

## How the corrected schema was verified

```bash
mysql < FMS/database/schema.sql   # runs cleanly, creates 31 tables
mysql FMS -e "SHOW TABLES;"
```
