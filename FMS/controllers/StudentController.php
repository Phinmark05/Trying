<?php
/**
 * StudentController — student dashboard and profile pages.
 * Every method starts with require_role('student'): authorization first.
 */
require_once __DIR__ . '/../models/Student.php';

class StudentController
{
    /** Dashboard: name, registration number, profile-completeness prompt. */
    public function dashboard(): void
    {
        require_role('student');
        $student = Student::findByUserId((int) $_SESSION['user_id']);
        require __DIR__ . '/../views/student/dashboard.php';
    }

    /** Show (GET) or save (POST) the profile form. */
    public function profile(): void
    {
        require_role('student');
        $userId  = (int) $_SESSION['user_id'];
        $student = Student::findByUserId($userId);

        // Dropdown data for the form.
        $institutions  = Student::institutions();
        $nationalities = Student::nationalities();
        $studyLevels   = Student::studyLevels();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/student/profile.php';
            return;
        }

        verify_csrf();

        // Collect + validate. (int) casts turn malicious strings into 0,
        // which then fails the "> 0" checks below.
        $data = [
            'full_name'       => trim($_POST['full_name'] ?? ''),
            'registration_no' => trim($_POST['registration_no'] ?? ''),
            'institution_id'  => (int) ($_POST['institution_id'] ?? 0),
            'gender'          => $_POST['gender'] ?? null,
            'nationality_id'  => (int) ($_POST['nationality_id'] ?? 0) ?: null,
            'dob'             => $_POST['dob'] ?: null,
            'study_level_id'  => (int) ($_POST['study_level_id'] ?? 0) ?: null,
            'course_of_study' => trim($_POST['course_of_study'] ?? '') ?: null,
        ];

        $errors = [];
        if ($data['full_name'] === '') {
            $errors[] = 'Full name is required.';
        }
        if ($data['registration_no'] === '') {
            $errors[] = 'Registration number is required.';
        }
        if ($data['institution_id'] <= 0) {
            $errors[] = 'Select your institution.';
        }
        if (!in_array($data['gender'], ['Male', 'Female', 'Other', null, ''], true)) {
            $errors[] = 'Invalid gender value.';
        }
        $data['gender'] = $data['gender'] ?: null;
        if ($data['dob'] !== null && DateTime::createFromFormat('Y-m-d', $data['dob']) === false) {
            $errors[] = 'Date of birth must be a valid date.';
        }
        if (empty($errors)
            && Student::registrationTaken($data['registration_no'], $data['institution_id'], $userId)) {
            $errors[] = 'That registration number is already used at this institution.';
        }

        if (!empty($errors)) {
            require __DIR__ . '/../views/student/profile.php';
            return;
        }

        Student::saveProfile($userId, $data);
        flash_set('success', 'Profile saved.');
        header('Location: index.php?page=student_dashboard');
        exit;
    }
}
