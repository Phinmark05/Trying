<?php
/**
 * Admin Organizations Management
 *
 * Lists all organizations and provides forms to add, edit, and
 * activate/deactivate organizations. Respects the existing enum type
 * field: company, government, ngo, university, research_institution, other.
 */
require_once __DIR__ . '/../includes/admin_check.php';

// Only admins can manage organizations
if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can manage organizations.');
    redirect('/FMS/staff/dashboard.php');
}

$pageTitle = 'Organizations';

$orgs = get_all_organizations($pdo);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Organizations</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Add/Edit form -->
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Add Organization</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/save_organization.php" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Type</label>
                                    <select name="type" class="form-control" required>
                                        <option value="company">Company</option>
                                        <option value="government">Government</option>
                                        <option value="ngo">NGO</option>
                                        <option value="university">University</option>
                                        <option value="research_institution">Research Institution</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone_number" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Organizations list -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">All Organizations</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr><th>Name</th><th>Type</th><th>Email</th><th>Phone</th><th>Active</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orgs as $o): ?>
                                    <tr>
                                        <td><?= e($o['name']) ?></td>
                                        <td><?= e(ucwords(str_replace('_', ' ', $o['type']))) ?></td>
                                        <td><?= e($o['email'] ?? '—') ?></td>
                                        <td><?= e($o['phone_number'] ?? '—') ?></td>
                                        <td>
                                            <?php if ($o['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Toggle active/inactive -->
                                            <form action="/FMS/actions/toggle_organization.php" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="organization_id" value="<?= (int) $o['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-<?= $o['is_active'] ? 'secondary' : 'success' ?>">
                                                    <?= $o['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
