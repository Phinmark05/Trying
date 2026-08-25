-- Minimal reference data needed before the application can run.
-- Run AFTER schema.sql:  mysql < FMS/database/seed.sql
USE FMS;

INSERT INTO roles (name, description) VALUES
    ('student', 'Student applying for field attachment'),
    ('admin', 'Administrator / Field Coordinator');

INSERT INTO duration_units (code, name) VALUES
    ('weeks', 'Weeks'),
    ('months', 'Months');

INSERT INTO training_types (code, name, description) VALUES
    ('field_attachment', 'Field Attachment', 'Standard field attachment programme');

INSERT INTO study_levels (name, description) VALUES
    ('Certificate', NULL),
    ('Diploma', NULL),
    ('Bachelors Degree', NULL),
    ('Masters Degree', NULL);

INSERT INTO nationalities (name, code) VALUES
    ('Kenyan', 'KE'),
    ('Ugandan', 'UG'),
    ('Tanzanian', 'TZ'),
    ('Other', NULL);

INSERT INTO institutions (name, code) VALUES
    ('Example University', 'EX-U'),
    ('Example Technical Institute', 'EX-TI');

INSERT INTO comment_types (name, code, description) VALUES
    ('Rejection Reason', 'rejection_reason', 'Reason given when an application is rejected'),
    ('Correction Request', 'correction_request', 'What the student must fix before resubmitting'),
    ('General Comment', 'general', 'Any other comment');

INSERT INTO workflow_action_types (code, name, requires_comment) VALUES
    ('submit', 'Submit Application', FALSE),
    ('review', 'Start Review', FALSE),
    ('return_for_correction', 'Return for Correction', TRUE),
    ('accept', 'Accept Application', FALSE),
    ('reject', 'Reject Application', TRUE),
    ('assign_placement', 'Assign Placement', FALSE);
