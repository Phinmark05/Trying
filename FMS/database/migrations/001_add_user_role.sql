-- MIGRATION 001 — link users to roles.
--
-- PROBLEM FOUND DURING PHASE 4:
-- The schema has a `roles` table, but nothing connects a user to a role.
-- `users` has no role_id column and there is no user_roles table, so the
-- application cannot answer "is this user a student or an admin?".
--
-- FIX: one role per user via users.role_id (simplest design that satisfies
-- the current scope; a many-to-many user_roles table can replace it later
-- if a user ever needs several roles).
--
-- Run with:  sudo mysql < FMS/database/migrations/001_add_user_role.sql
USE FMS;

ALTER TABLE users
    ADD COLUMN role_id INT UNSIGNED NULL AFTER password_hash,
    ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT;
