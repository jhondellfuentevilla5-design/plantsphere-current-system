<?php
$pageTitle = 'Manage Users';
include __DIR__ . '/../partials/layout_head.php';

$userModel = new User($conn);
$secLog    = new SecurityLog($conn);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = intval($_POST['user_id'] ?? 0);
    $act      = $_POST['act'] ?? '';

    if ($targetId && $targetId !== $_SESSION['user']['id']) {
        switch ($act) {
            case 'activate':
                $userModel->setActive($targetId, true);
                $secLog->logActivity($_SESSION['user']['id'], 'activate_user', 'admin', "Activated user ID $targetId");
                $success = 'User activated.';
                break;
            case 'deactivate':
                $userModel->setActive($targetId, false);
                $secLog->logActivity($_SESSION['user']['id'], 'deactivate_user', 'admin', "Deactivated user ID $targetId");
                $success = 'User deactivated.';
                break;
            case 'unlock':
                $userModel->unlockAccount($targetId);
                $secLog->logActivity($_SESSION['user']['id'], 'unlock_user', 'admin', "Unlocked account for user ID $targetId");
                $success = 'Account unlocked.';
                break;
            case 'change_role':
                $newRole = $_POST['new_role'] ?? '';
                if ($userModel->updateRole($targetId, $newRole)) {
                    $secLog->logActivity($_SESSION['user']['id'], 'change_role', 'admin', "Changed role of user ID $targetId to $newRole");
                    $success = 'Role updated.';
                } else {
                    $error = 'Invalid role.';
                }
                break;
            case 'change_classification':
                $cls = $_POST['classification'] ?? '';
                if ($userModel->updateClassification($targetId, $cls)) {
                    $secLog->logActivity($_SESSION['user']['id'], 'change_classification', 'admin', "Set data classification of user ID $targetId to $cls");
                    $success = 'Data classification updated.';
                }
                break;
            case 'delete':
                $userModel->delete($targetId);
                $secLog->logActivity($_SESSION['user']['id'], 'delete_user', 'admin', "Deleted user ID $targetId");
                $success = 'User deleted.';
                break;
            case 'reset_password':
                $newPw = $_POST['new_password'] ?? '';
                if ($userModel->changePassword($targetId, $newPw)) {
                    $secLog->logActivity($_SESSION['user']['id'], 'reset_password', 'admin', "Reset password for user ID $targetId");
                    $success = 'Password reset successfully.';
                } else {
                    $error = 'Password does not meet policy. ' . User::passwordPolicyHint();
                }
                break;
        }
    }
}

$users = $userModel->getAll();
$roles = ['admin','community_organizer','community_affairs_worker','agricultural_technologist','mao'];
$classifications = ['public','internal','confidential'];
?>

<div class="ps-page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>User Management</h2>
        <p>Manage accounts, roles, data classification, and account status.</p>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger py-2 small mb-3"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="ps-card">
    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Classification</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="small text-muted"><?= $u['id'] ?></td>
                    <td class="fw-semibold small">
                        <?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?>
                        <?php if ($u['is_locked']): ?>
                            <span class="ps-badge ps-badge-rejected ms-1">Locked</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <!-- Role change inline -->
                        <form method="POST" class="d-flex gap-1 align-items-center">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="act" value="change_role">
                            <select name="new_role" class="form-select form-select-sm" style="min-width:140px;"
                                <?= $u['id'] === $_SESSION['user']['id'] ? 'disabled' : '' ?>>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$r)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Save role">
                                <i class="bi bi-check2"></i>
                            </button>
                            <?php endif; ?>
                        </form>
                    </td>
                    <td>
                        <!-- Classification change inline -->
                        <form method="POST" class="d-flex gap-1 align-items-center">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="act" value="change_classification">
                            <select name="classification" class="form-select form-select-sm" style="min-width:120px;">
                                <?php foreach ($classifications as $c): ?>
                                    <option value="<?= $c ?>" <?= ($u['data_classification'] ?? 'internal') === $c ? 'selected' : '' ?>>
                                        <?= ucfirst($c) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Save">
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                    </td>
                    <td>
                        <span class="ps-badge <?= $u['is_active'] ? 'ps-badge-approved' : 'ps-badge-rejected' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="small text-muted">
                        <?= $u['last_login'] ? date('M d, Y g:i A', strtotime($u['last_login'])) : 'Never' ?>
                    </td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                        <div class="d-flex gap-1 flex-wrap">
                            <!-- Activate / Deactivate -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="act" value="<?= $u['is_active'] ? 'deactivate' : 'activate' ?>">
                                <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                    title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="bi <?= $u['is_active'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                </button>
                            </form>
                            <!-- Unlock -->
                            <?php if ($u['is_locked']): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="act" value="unlock">
                                <button type="submit" class="btn btn-sm btn-outline-info" title="Unlock">
                                    <i class="bi bi-unlock"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <!-- Reset Password -->
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="openResetPw(<?= $u['id'] ?>, '<?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?>')"
                                title="Reset Password">
                                <i class="bi bi-key"></i>
                            </button>
                            <!-- Delete -->
                            <?php if ($u['role'] !== 'admin'): ?>
                            <form method="POST" class="d-inline"
                                onsubmit="return confirm('Delete <?= htmlspecialchars($u['firstname']) ?>? This cannot be undone.')">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="act" value="delete">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                            <span class="small text-muted">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPwModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Reset Password</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="resetUserId">
                    <input type="hidden" name="act" value="reset_password">
                    <p class="small text-muted mb-3">Setting new password for: <strong id="resetUserName"></strong></p>
                    <label class="form-label small fw-semibold">New Password</label>
                    <input type="password" name="new_password" class="form-control form-control-sm"
                           placeholder="Min 8 chars, upper, lower, number, special" required>
                    <div class="form-text"><?= User::passwordPolicyHint() ?></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-ps-primary">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openResetPw(id, name) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUserName').textContent = name;
    new bootstrap.Modal(document.getElementById('resetPwModal')).show();
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
