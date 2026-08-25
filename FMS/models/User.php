<?php
/**
 * User model — ALL database access for the `users` table lives here.
 * Controllers never write SQL; they call these methods instead.
 */
require_once __DIR__ . '/../config/Database.php';

class User
{
    /** Find one user by username OR email (used at login). */
    public static function findByUsernameOrEmail(string $identifier): ?array
    {
        $pdo = Database::getConnection();
        $statement = $pdo->prepare(
            'SELECT u.id, u.username, u.email, u.password_hash, u.status, r.name AS role
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.username = ? OR u.email = ?
             LIMIT 1'
        );
        $statement->execute([$identifier, $identifier]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    /** Does a username or email already exist? (used at registration) */
    public static function exists(string $username, string $email): bool
    {
        $pdo = Database::getConnection();
        $statement = $pdo->prepare('SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1');
        $statement->execute([$username, $email]);
        return $statement->fetch() !== false;
    }

    /**
     * Create a new user account and return its new id.
     *
     * The password arrives in PLAIN TEXT from the form (over HTTPS in
     * production) and is immediately hashed with password_hash().
     * password_hash() uses bcrypt: a one-way function with a built-in
     * random salt, so two users with the same password get different
     * hashes and the original password can never be recovered.
     */
    public static function create(string $username, string $email, string $password, string $roleName): int
    {
        $pdo = Database::getConnection();

        // Look up the role id from its name ('student' or 'admin').
        $roleStatement = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
        $roleStatement->execute([$roleName]);
        $role = $roleStatement->fetch();
        if ($role === false) {
            throw new RuntimeException("Role '$roleName' not found. Run database/seed.sql.");
        }

        $statement = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role['id'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    /** Record a successful login time. */
    public static function touchLastLogin(int $userId): void
    {
        $pdo = Database::getConnection();
        $statement = $pdo->prepare('UPDATE users SET last_login_at = NOW(), failed_login_attempts = 0 WHERE id = ?');
        $statement->execute([$userId]);
    }
}
