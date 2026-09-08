<?php
/**
 * New Application Page
 *
 * Shows a form for the student to create a new application.
 * The form includes:
 *   - Application window (dropdown of active windows)
 *   - Training type (dropdown)
 *   - Study level (dropdown)
 *   - Application type (initial / reapplication)
 *   - Skill level (beginner / intermediate / advanced)
 *   - Interest statement, reason, learning objectives (text areas)
 *   - Requested start/end dates
 *   - Specializations (multi-select checkboxes)
 *
 * If the student already has a non-draft application, they are informed
 * that creating a new one may not be allowed depending on the window rules.
 */
require_once __DIR__ . '/../includes/student_check.php';

$pageTitle = 'New Application';

// Load data for dropdowns
$windows         = get_active_application_windows($pdo);
$trainingTypes   = get_training_types($pdo);
$studyLevels     = get_study_levels($pdo);
$specializations = get_specializations($pdo);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">New Application</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-10">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Application Form</h3></div>
                        <div class="card-body">
                            <?php if (empty($windows)): ?>
                                <div class="alert alert-warning">
                                    There are no active application windows at this time. Please check back later.
                                </div>
                            <?php else: ?>
                            <form action="/FMS/actions/save_application.php" method="post">
                                <?= csrf_field() ?>

                                <!-- Application window -->
                                <div class="form-group">
                                    <label>Application Window</label>
                                    <select name="application_window_id" class="form-control" required>
                                        <option value="">— Select —</option>
                                        <?php foreach ($windows as $w): ?>
                                            <option value="<?= (int) $w['id'] ?>">
                                                <?= e($w['name']) ?> (<?= format_date($w['open_date']) ?> — <?= format_date($w['close_date']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Training Type</label>
                                            <select name="training_type_id" class="form-control" required>
                                                <option value="">— Select —</option>
                                                <?php foreach ($trainingTypes as $t): ?>
                                                    <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Study Level</label>
                                            <select name="study_level_id" class="form-control" required>
                                                <option value="">— Select —</option>
                                                <?php foreach ($studyLevels as $sl): ?>
                                                    <option value="<?= (int) $sl['id'] ?>"><?= e($sl['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Application Type</label>
                                            <select name="application_type" class="form-control" required>
                                                <option value="initial">Initial</option>
                                                <option value="reapplication">Reapplication</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Skill Level</label>
                                            <select name="skill_level" class="form-control" required>
                                                <option value="beginner">Beginner</option>
                                                <option value="intermediate">Intermediate</option>
                                                <option value="advanced">Advanced</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Interest Statement</label>
                                    <textarea name="interest_statement" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Reason for Application</label>
                                    <textarea name="reason_for_application" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Expected Learning Objectives</label>
                                    <textarea name="expected_learning_objectives" class="form-control" rows="3" required></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Requested Start Date</label>
                                            <input type="date" name="requested_start_date" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Requested End Date</label>
                                            <input type="date" name="requested_end_date" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <!-- Specializations (multi-select via checkboxes) -->
                                <div class="form-group">
                                    <label>Specializations (select all that apply)</label>
                                    <div class="row">
                                        <?php foreach ($specializations as $sp): ?>
                                            <div class="col-md-4 mb-1">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="specializations[]" value="<?= (int) $sp['id'] ?>" id="sp_<?= (int) $sp['id'] ?>">
                                                    <label class="form-check-label" for="sp_<?= (int) $sp['id'] ?>"><?= e($sp['name']) ?></label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Submit button: save as draft (default) -->
                                <button type="submit" name="action" value="draft" class="btn btn-secondary">Save as Draft</button>
                                <!-- Submit button: submit immediately -->
                                <button type="submit" name="action" value="submit" class="btn btn-primary"
                                        onclick="return confirm('Are you sure you want to submit this application? You will not be able to edit it after submission unless it is returned for correction.')">
                                    Submit Application
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
