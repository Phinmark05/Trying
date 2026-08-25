<?php
/**
 * Student model — all SQL for the `students` table plus the lookup
 * tables the profile form needs (institutions, nationalities, study levels).
 */
require_once __DIR__ . '/../config/Database.php';

class Student
{
    /** The logged-in user's student profile, or null if not yet completed. */
    public static function findByUserId(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $statement = $pdo->prepare(
            'SELECT s.*, i.name AS institution_name, n.name AS nationality_name, l.name AS study_level_name
             FROM students s
             JOIN institutions i ON i.id = s.institution_id
             LEFT JOIN nationalities n ON n.id = s.nationality_id
             LEFT JOIN study_levels l ON l.id = s.study_level_id
             WHERE s.user_id = ?'
        );
        $statement->execute([$userId]);
        $student = $statement->fetch();
        return $student === false ? null : $student;
    }

    /**
     * Create the profile on first save, update it afterwards.
     * user_id comes from the SESSION, never from the form — a student
     * must not be able to edit somebody else's profile by changing an ID.
     */
    public static function saveProfile(int $userId, array $data): void
    {
        $pdo = Database::getConnection();
        $existing = self::findByUserId($userId);

        if ($existing === null) {
            $statement = $pdo->prepare(
                'INSERT INTO students
                    (user_id, registration_no, institution_id, full_name, gender, nationality_id, dob, study_level_id, course_of_study)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $userId,
                $data['registration_no'],
                $data['institution_id'],
                $data['full_name'],
                $data['gender'],
                $data['nationality_id'],
                $data['dob'],
                $data['study_level_id'],
                $data['course_of_study'],
            ]);
        } else {
            $statement = $pdo->prepare(
                'UPDATE students SET
                    registration_no = ?, institution_id = ?, full_name = ?, gender = ?,
                    nationality_id = ?, dob = ?, study_level_id = ?, course_of_study = ?
                 WHERE user_id = ?'
            );
            $statement->execute([
                $data['registration_no'],
                $data['institution_id'],
                $data['full_name'],
                $data['gender'],
                $data['nationality_id'],
                $data['dob'],
                $data['study_level_id'],
                $data['course_of_study'],
                $userId,
            ]);
        }
    }

    /** Is a registration number already used at this institution by ANOTHER user? */
    public static function registrationTaken(string $registrationNo, int $institutionId, int $exceptUserId): bool
    {
        $pdo = Database::getConnection();
        $statement = $pdo->prepare(
            'SELECT 1 FROM students WHERE registration_no = ? AND institution_id = ? AND user_id <> ? LIMIT 1'
        );
        $statement->execute([$registrationNo, $institutionId, $exceptUserId]);
        return $statement->fetch() !== false;
    }

    // ---- Lookup lists for the profile form dropdowns -------------------

    public static function institutions(): array
    {
        return Database::getConnection()
            ->query('SELECT id, name FROM institutions WHERE is_active = TRUE ORDER BY name')
            ->fetchAll();
    }

    public static function nationalities(): array
    {
        return Database::getConnection()
            ->query('SELECT id, name FROM nationalities ORDER BY name')
            ->fetchAll();
    }

    public static function studyLevels(): array
    {
        return Database::getConnection()
            ->query('SELECT id, name FROM study_levels ORDER BY id')
            ->fetchAll();
    }
}
