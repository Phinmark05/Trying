CREATE DATABASE IF NOT EXISTS FMS CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE FMS;

-- 1. ROLES & PERMISSIONS
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- 2. USERS
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    email_verified_at DATETIME NULL,
    status ENUM('active', 'suspended', 'deleted') NOT NULL DEFAULT 'active',
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_status (status),
    INDEX idx_users_email_verified (email_verified_at)
);

-- 3. STAFF
CREATE TABLE staff (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL UNIQUE,
    staff_number VARCHAR(50) NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone_number VARCHAR(30) NULL,
    organization_id INT UNSIGNED NULL,
    department_id INT UNSIGNED NULL,
    job_title VARCHAR(100) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 4. LOOKUP / REFERENCE TABLES
CREATE TABLE nationalities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(10) NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE training_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE study_levels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE document_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    max_size_bytes INT UNSIGNED NOT NULL DEFAULT 2097152,
    allowed_mime_types JSON NULL,
    is_required BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE duration_units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE workflow_action_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    requires_comment BOOLEAN DEFAULT FALSE
);

-- 5. INSTITUTIONS & PROGRAMMES
CREATE TABLE institutions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(30) NULL UNIQUE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE programmes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(30) NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE institution_programmes (
    institution_id INT UNSIGNED NOT NULL,
    programme_id INT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (institution_id, programme_id),
    CONSTRAINT fk_institution_programmes_institution FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    CONSTRAINT fk_institution_programmes_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE RESTRICT
);

-- 6. STUDENTS & ACCOUNTS
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_no VARCHAR(50) NOT NULL,
    institution_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    gender ENUM('Male','Female','Other') NULL,
    nationality_id INT UNSIGNED NULL,
    dob DATE NULL,
    study_level_id INT UNSIGNED NULL,
    course_of_study VARCHAR(150) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_students_regno_institution (registration_no, institution_id),
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_students_institution FOREIGN KEY (institution_id) REFERENCES institutions(id),
    CONSTRAINT fk_students_nationality FOREIGN KEY (nationality_id) REFERENCES nationalities(id),
    CONSTRAINT fk_students_education_level FOREIGN KEY (study_level_id) REFERENCES study_levels(id),
    INDEX idx_students_search (full_name, course_of_study)
) ENGINE=InnoDB;

-- 7. ORGANIZATIONS & DEPARTMENTS
CREATE TABLE organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NULL UNIQUE,
    type ENUM('company','government','ngo','university','research_institution','other') NOT NULL DEFAULT 'company',
    email VARCHAR(150) NULL,
    phone_number VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_organization_name_type (name, type)
);

CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    max_capacity SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_departments_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_department_organization_name (organization_id, name),
    UNIQUE KEY uq_department_organization_id (organization_id, id)
);



-- 8. SUPERVISORS & SPECIALIZATIONS
CREATE TABLE supervisors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id INT UNSIGNED NULL UNIQUE,
    organization_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone_number VARCHAR(30) NULL,
    designation VARCHAR(100) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_supervisors_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    CONSTRAINT fk_supervisors_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT,
    CONSTRAINT fk_supervisors_department FOREIGN KEY (organization_id, department_id) REFERENCES departments(organization_id, id) ON DELETE RESTRICT,
    UNIQUE KEY uq_supervisor_organization_id (organization_id, id)
);

CREATE TABLE specializations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 9. APPLICATION WINDOWS
CREATE TABLE application_windows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    open_date DATETIME NOT NULL,
    close_date DATETIME NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_application_windows_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_application_window_dates CHECK (close_date > open_date),
    UNIQUE KEY uq_application_window_name (name)
);

CREATE TABLE application_window_training_types (
    application_window_id INT UNSIGNED NOT NULL,
    training_type_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (application_window_id, training_type_id),
    CONSTRAINT fk_window_training_type_window FOREIGN KEY (application_window_id) REFERENCES application_windows(id) ON DELETE CASCADE,
    CONSTRAINT fk_window_training_type_type FOREIGN KEY (training_type_id) REFERENCES training_types(id) ON DELETE RESTRICT
);

-- 10. APPLICATIONS & SPECIALIZATIONS
CREATE TABLE applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    application_window_id INT UNSIGNED NOT NULL,
    training_type_id INT UNSIGNED NOT NULL,
    study_level_id INT UNSIGNED NOT NULL,
    institution_id INT UNSIGNED NOT NULL,
    programme_id INT UNSIGNED NOT NULL,
    registration_number_snapshot VARCHAR(100) NULL,
    reference_number VARCHAR(40) NOT NULL UNIQUE,
    application_type ENUM('initial', 'reapplication') NOT NULL DEFAULT 'initial',
    previous_application_id INT UNSIGNED NULL,
    skill_level ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
    interest_statement TEXT NOT NULL,
    reason_for_application TEXT NOT NULL,
    expected_learning_objectives TEXT NOT NULL,
    requested_duration_value DECIMAL(8,2) NULL,
    requested_duration_unit_id INT UNSIGNED NULL,
    requested_start_date DATE NULL,
    requested_end_date DATE NULL,
    declaration_accepted BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('draft', 'submitted', 'under_review', 'returned_for_correction', 'accepted', 'rejected', 'cancelled', 'placement_assigned', 'in_training', 'completed') NOT NULL DEFAULT 'draft',
    creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submission_date DATETIME NULL,
    decision_date DATETIME NULL,
    accepted_at DATETIME NULL,
    rejected_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active_student_id INT UNSIGNED GENERATED ALWAYS AS ( 
    CASE WHEN status IN ('draft', 'submitted', 'under_review', 'returned_for_correction', 'accepted', 'placement_assigned', 'in_training')
        THEN student_id ELSE NULL END
    ) STORED,
    CONSTRAINT fk_applications_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_window FOREIGN KEY (application_window_id) REFERENCES application_windows(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_training_type FOREIGN KEY (training_type_id) REFERENCES training_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_study_level FOREIGN KEY (study_level_id) REFERENCES study_levels(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_institution FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_programme_offering FOREIGN KEY (institution_id, programme_id) REFERENCES institution_programmes(institution_id, programme_id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_previous FOREIGN KEY (previous_application_id) REFERENCES applications(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_duration_unit FOREIGN KEY (requested_duration_unit_id) REFERENCES duration_units(id) ON DELETE RESTRICT,
    CONSTRAINT chk_requested_duration CHECK (requested_duration_value IS NULL OR requested_duration_value > 0),
    CONSTRAINT chk_requested_dates CHECK (requested_end_date IS NULL OR requested_start_date IS NULL OR requested_end_date >= requested_start_date),
    CONSTRAINT chk_reapplication_reference CHECK ((application_type = 'initial' AND previous_application_id IS NULL) OR (application_type = 'reapplication' AND previous_application_id IS NOT NULL)),
    UNIQUE KEY uq_one_active_application (active_student_id),
    INDEX idx_applications_student (student_id),
    INDEX idx_applications_status (status),
    INDEX idx_applications_window (application_window_id),
    INDEX idx_applications_training_type (training_type_id),
    INDEX idx_applications_previous (previous_application_id),
    INDEX idx_applications_submission_date (submission_date)
);

CREATE TABLE application_specializations (
    application_id INT UNSIGNED NOT NULL,
    specialization_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (application_id, specialization_id),
    CONSTRAINT fk_app_spec_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_app_spec_specialization FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON DELETE RESTRICT
);

-- 11. PLACEMENTS & LOGBOOKS
CREATE TABLE placements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL UNIQUE,
    organization_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    academic_supervisor_id INT UNSIGNED NULL,
    industrial_supervisor_id INT UNSIGNED NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'active', 'completed', 'terminated') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_placements_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE RESTRICT,
    CONSTRAINT fk_placements_org_dept FOREIGN KEY (organization_id, department_id) REFERENCES departments(organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_placements_academic_sup FOREIGN KEY (academic_supervisor_id) REFERENCES supervisors(id) ON DELETE SET NULL,
    CONSTRAINT fk_placements_industrial_sup FOREIGN KEY (organization_id, industrial_supervisor_id) REFERENCES supervisors(organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT chk_placement_dates CHECK (end_date >= start_date)
);

CREATE TABLE logbook_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    entry_date DATE NOT NULL,
    tasks_performed TEXT NOT NULL,
    skills_acquired TEXT NULL,
    challenges_faced TEXT NULL,
    academic_supervisor_comment TEXT NULL,
    industrial_supervisor_comment TEXT NULL,
    is_verified_by_industrial BOOLEAN NOT NULL DEFAULT FALSE,
    verified_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_logbook_placement FOREIGN KEY (placement_id) REFERENCES placements(id) ON DELETE CASCADE,
    UNIQUE KEY uq_placement_entry_date (placement_id, entry_date)
);

-- 12. COMMENTS
CREATE TABLE comment_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    code VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comment_type_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    commentable_type ENUM('application', 'placement', 'logbook') NOT NULL,
    commentable_id INT UNSIGNED NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_type FOREIGN KEY (comment_type_id) REFERENCES comment_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_comments_entity (commentable_type, commentable_id),
    INDEX idx_comments_user (user_id)
) ENGINE=InnoDB;

-- 13. AUDIT LOGS
CREATE TABLE application_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_audit_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_app_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE placement_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_placement_audit_placement FOREIGN KEY (placement_id) REFERENCES placements(id) ON DELETE CASCADE,
    CONSTRAINT fk_placement_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE logbook_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    logbook_entry_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logbook_audit_entry FOREIGN KEY (logbook_entry_id) REFERENCES logbook_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_logbook_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 14. NOTIFICATIONS
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
