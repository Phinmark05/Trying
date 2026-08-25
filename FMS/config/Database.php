<?php
/**
 * Central database connection for the whole application.
 *
 * WHAT IS PDO?
 * PDO (PHP Data Objects) is PHP's built-in library for talking to databases.
 * We use it (instead of the old mysql_* functions) because:
 *   1. It supports PREPARED STATEMENTS — the query and the data travel to
 *      MySQL separately, so user input can never be executed as SQL.
 *      This is the #1 defence against SQL injection.
 *   2. It throws exceptions on errors, so mistakes are loud, not silent.
 *   3. The same code style works with other databases later.
 *
 * WHY ONE CENTRAL CLASS?
 * Every model needs a connection. If each file created its own, we would
 * duplicate credentials everywhere and open many connections per request.
 * Instead, every model calls Database::getConnection() and receives the
 * SAME single PDO object (this reuse pattern is called a "singleton").
 */
class Database
{
    /** Holds the one shared PDO object after the first call. */
    private static ?PDO $connection = null;

    /**
     * Returns the shared PDO connection, creating it on the first call.
     */
    public static function getConnection(): PDO
    {
        // Already connected? Reuse it — do not open a second connection.
        if (self::$connection !== null) {
            return self::$connection;
        }

        // Load credentials from config.php (which is git-ignored, so the
        // real password never ends up in the repository).
        $configFile = __DIR__ . '/config.php';
        if (!file_exists($configFile)) {
            throw new RuntimeException(
                'Missing config/config.php. Copy config/config.example.php to config/config.php and fill in your database details.'
            );
        }
        $config = require $configFile;
        $db = $config['db'];

        // The DSN (Data Source Name) tells PDO where the database lives.
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        try {
            self::$connection = new PDO($dsn, $db['user'], $db['password'], [
                // Throw an exception whenever a query fails (never fail silently).
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Fetch rows as associative arrays: $row['email'] not $row[3].
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Use REAL prepared statements (MySQL does the work, not PHP).
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Never echo $e->getMessage() to visitors — it can leak the
            // hostname/username. Log it, show a generic message instead.
            error_log('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Check config/config.php and that MySQL is running.');
        }

        return self::$connection;
    }
}
