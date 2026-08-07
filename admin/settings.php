<?php
require_once '../app/functions.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

require_once '../path.php';
// Ensure PDO exists
if (!isset($pdo)) {
    die("PDO connection not initialized!");
}

// Fetch all settings from DB
try {
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];

    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $committeeMembersStmt = $pdo->query("SELECT id, name, email FROM committee_members ORDER BY name");
    $committeeMembers = $committeeMembersStmt->fetchAll(PDO::FETCH_ASSOC);

    $adminUsersStmt = $pdo->query("
        SELECT id, name, email, is_active, failed_login_attempts, unlock_token
        FROM admin_users ORDER BY name, email
    ");
    $adminUsers = $adminUsersStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $settings = [];
    $committeeMembers = [];
    $adminUsers = [];
}

// Derives a human status from is_active / failed_login_attempts / whether
// an unlock-or-invite token is still pending, since all three real-world
// states (freshly invited, locked out from failed logins, and manually
// disabled) share the same underlying is_active=false column.
function adminUserStatus(array $admin): array {
    if ($admin['is_active']) {
        return ['label' => 'Active', 'class' => 'yes'];
    }
    if (!empty($admin['unlock_token']) && (int) $admin['failed_login_attempts'] === 0) {
        return ['label' => 'Pending setup', 'class' => 'scheduled'];
    }
    if ((int) $admin['failed_login_attempts'] >= 3) {
        return ['label' => 'Locked', 'class' => 'no'];
    }
    return ['label' => 'Disabled', 'class' => 'no'];
}

// Helper function to safely get a setting
function getSetting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? htmlspecialchars($settings[$key], ENT_QUOTES, 'UTF-8') : $default;
}

// Matches the fallback used on application-form.php, so the field here
// shows the real current prompt even before it's ever been explicitly saved.
const DEFAULT_ESSAY_PROMPT = 'In 500–750 words, please tell us about yourself, your goals, and what makes you a strong candidate for this scholarship.';

// Matches the fallback used everywhere the limit is enforced, so this field
// shows the real effective limit even before it's ever been explicitly saved.
const DEFAULT_FINAL_REVIEW_LIMIT = 10;

// Same open/not_open/closed/unset logic the homepage uses, so this banner
// actually reflects the Timeline dates instead of always claiming
// applications are open.
$applicationOpenDate = getSetting('application_open');
$applicationCloseDate = getSetting('application_closed');
$today = date('Y-m-d');

if (empty($applicationOpenDate) || empty($applicationCloseDate)) {
    $cycleState = 'unset';
} elseif ($today < $applicationOpenDate) {
    $cycleState = 'not_open';
} elseif ($today > $applicationCloseDate) {
    $cycleState = 'closed';
} else {
    $cycleState = 'open';
}

// Which tab to land on -- lets other pages/links deep-link straight to
// Committee, for example, instead of always landing on General.
$allowedTabs = ['general', 'timeline', 'review', 'committee', 'admins'];
$activeTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? $_GET['tab'] : 'general';
if (!empty($_GET['member_error']) || !empty($_GET['member_success'])) {
    $activeTab = 'committee';
}
if (!empty($_GET['admin_error']) || !empty($_GET['admin_success'])) {
    $activeTab = 'admins';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css?v=11.2.0">
    <title>Settings - Morgan Legacy Scholarship</title>
    <style>
        .settings-body { display: flex; align-items: flex-start; }
        .settings-nav { width: 220px; flex-shrink: 0; padding: 4px 16px 24px; border-right: 1px solid #f3f3f6; }
        .settings-nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; font-size: 14px; font-weight: 600; color: #6c757d; cursor: pointer; margin-bottom: 2px; text-decoration: none; }
        .settings-nav-item:hover { background: #f8f8fa; color: #495057; }
        .settings-nav-item i { font-size: 15px; color: #9a9aa5; width: 18px; text-align: center; }
        .settings-nav-item.active { background: rgb(7,5,55); color: #fff; }
        .settings-nav-item.active i { color: #C5A059; }
        .settings-nav-item .nav-count { margin-left: auto; font-size: 11px; background: #eee; color: #555; padding: 1px 7px; border-radius: 20px; }
        .settings-nav-item.active .nav-count { background: rgba(255,255,255,0.2); color: #fff; }
        .settings-panel { flex: 1; min-width: 0; padding: 4px 28px 28px; }
        .settings-panel-content { display: none; }
        .settings-panel-content.active { display: block; }
        .panel-title { font-size: 16px; font-weight: 700; color: #212529; margin-bottom: 4px; }
        .panel-desc { font-size: 13px; color: #9a9aa5; margin-bottom: 20px; }
        .settings-save-btn { padding: 10px 24px; background: rgb(7,5,55); color: #fff; font-weight: 600; border: none; border-radius: 6px; }
        .settings-save-btn:hover { background: rgb(20,16,80); color: #fff; }

        .roster-list { border: 1px solid rgb(241,242,243); border-radius: 12px; overflow: hidden; margin-bottom: 22px; }
        .roster-item { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-bottom: 1px solid #f6f6f8; background: #fff; }
        .roster-item:last-child { border-bottom: none; }
        .roster-item:hover { background: #fafbff; }
        .roster-avatar { width: 38px; height: 38px; border-radius: 50%; background: rgb(7,5,55); color: #C5A059; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
        .roster-info { flex: 1; min-width: 0; }
        .roster-name { font-weight: 600; font-size: 14.5px; color: #212529; }
        .roster-email { font-size: 12.5px; color: #8a8a94; }
        .roster-delete { background: none; border: none; color: #c9c9d1; padding: 6px 8px; border-radius: 6px; flex-shrink: 0; }
        .roster-delete:hover { color: #dc3545; background: rgba(220,53,69,0.08); }

        .roster-empty { text-align: center; padding: 32px 20px; border: 1px dashed #e2e2e8; border-radius: 12px; color: #9a9aa5; font-size: 13.5px; margin-bottom: 22px; }
        .roster-empty i { font-size: 22px; display: block; margin-bottom: 8px; color: #d5d5db; }

        .add-member-card { background: rgb(249,250,251); border: 1px solid #f3f3f6; border-radius: 12px; padding: 18px 20px; }
        .add-member-title { font-size: 13.5px; font-weight: 700; color: #212529; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
        .add-member-title i { color: #C5A059; }

        .status-badge { display: inline-block; white-space: nowrap; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; margin-right: 10px; }
        .status-badge.yes { background: rgba(25,135,84,0.12); color: #198754; }
        .status-badge.no { background: rgba(220,53,69,0.1); color: #dc3545; }
        .status-badge.scheduled { background: rgba(197,160,89,0.16); color: #8a6d2e; }
        .roster-you { font-size: 11.5px; color: #9a9aa5; font-weight: 600; margin-right: 4px; }
        .roster-edit { background: none; border: none; color: #c9c9d1; padding: 6px 8px; border-radius: 6px; margin-right: 4px; flex-shrink: 0; }
        .roster-edit:hover { color: rgb(7,5,55); background: #f1f1f4; }
        .roster-action-btn { border: 1px solid #e2e2e8; background: #fff; color: #495057; border-radius: 6px; padding: 5px 12px; font-size: 12.5px; font-weight: 600; white-space: nowrap; }
        .roster-action-btn:hover { background: #f8f8fa; }
        .roster-action-btn.danger:hover { color: #dc3545; border-color: rgba(220,53,69,0.4); background: rgba(220,53,69,0.06); }
        @media (max-width: 767px) {
            .settings-body { flex-direction: column; }
            .settings-nav { width: 100%; border-right: none; border-bottom: 1px solid #f3f3f6; padding: 4px 4px 16px; display: flex; overflow-x: auto; gap: 4px; }
            .settings-nav-item { white-space: nowrap; margin-bottom: 0; }
            .settings-panel { padding: 20px 4px 4px; }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 16px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
  <div class="case-accent"></div>

  <div style="padding: 28px 32px 20px;">
    <a href="<?= BASE_URL ?>/admin/" class="text-decoration-none" style="font-size: 13.5px; color: #9a9aa5; font-weight: 600;">
        <i class="bi bi-arrow-left me-1"></i> Back to application portal
    </a>

    <?php
        // Colors/copy per state, matching the same open/not_open/closed/unset
        // states the homepage computes, so this tells the truth about the
        // Timeline dates instead of always saying "open."
        $cycleBannerConfig = [
            'open' => [
                'border' => 'rgb(62,163,45)', 'bg' => 'rgb(242,253,244)', 'icon_color' => 'rgb(62,163,75)',
                'title_color' => 'rgb(38,82,47)', 'text_color' => 'rgb(51,128,63)', 'icon' => 'bi-check-circle',
                'title' => 'Applications are currently open',
                'text' => 'Through ' . date('M j, Y', strtotime($applicationCloseDate ?: 'now')) . '.',
            ],
            'not_open' => [
                'border' => '#C5A059', 'bg' => '#FBF7EE', 'icon_color' => '#8a6d2e',
                'title_color' => '#6b5321', 'text_color' => '#8a6d2e', 'icon' => 'bi-hourglass-split',
                'title' => 'Applications haven\'t opened yet',
                'text' => 'Opens ' . date('M j, Y', strtotime($applicationOpenDate ?: 'now')) . '.',
            ],
            'closed' => [
                'border' => '#dc3545', 'bg' => 'rgba(220,53,69,0.06)', 'icon_color' => '#dc3545',
                'title_color' => '#a3202c', 'text_color' => '#b8394a', 'icon' => 'bi-x-circle',
                'title' => 'Applications are closed',
                'text' => 'Closed ' . date('M j, Y', strtotime($applicationCloseDate ?: 'now')) . '.',
            ],
            'unset' => [
                'border' => '#9a9aa5', 'bg' => '#f6f6f8', 'icon_color' => '#6c757d',
                'title_color' => '#495057', 'text_color' => '#6c757d', 'icon' => 'bi-calendar2',
                'title' => 'Application dates aren\'t set yet',
                'text' => 'Set them in Timeline.',
            ],
        ][$cycleState];
    ?>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mt-3">
        <div>
            <h3 class="mb-1" style="font-weight: 700; font-size: 1.5rem; color: #212529;">Admin Settings</h3>
            <h5 class="mb-0" style="font-weight: 400; font-size: 1rem; color: #6c757d;">Manage application periods, award amounts, and timeline dates</h5>
        </div>

        <div style="
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid <?= $cycleBannerConfig['border'] ?>;
            background-color: <?= $cycleBannerConfig['bg'] ?>;
            padding: 10px 16px;
            border-radius: 12px;
            white-space: nowrap;
            flex-shrink: 0;
        ">
            <div style="flex-shrink: 0; font-size: 18px; color: <?= $cycleBannerConfig['icon_color'] ?>; line-height: 1;">
                <i class="bi <?= $cycleBannerConfig['icon'] ?>"></i>
            </div>
            <div>
                <div style="font-weight: 600; font-size: 13.5px; color: <?= $cycleBannerConfig['title_color'] ?> !important;"><?= $cycleBannerConfig['title'] ?></div>
                <div style="font-weight: 400; font-size: 12px; color: <?= $cycleBannerConfig['text_color'] ?> !important;"><?= $cycleBannerConfig['text'] ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 mt-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mt-3" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div>Settings saved.</div>
        </div>
    <?php endif; ?>
  </div>

  <div class="settings-body" style="border-top: 1px solid #f3f3f6;">

    <!-- Sidebar nav -->
    <div class="settings-nav">
        <div class="settings-nav-item<?= $activeTab === 'general' ? ' active' : '' ?>" data-tab="general"><i class="bi bi-sliders"></i>General</div>
        <div class="settings-nav-item<?= $activeTab === 'timeline' ? ' active' : '' ?>" data-tab="timeline"><i class="bi bi-calendar3"></i>Timeline</div>
        <div class="settings-nav-item<?= $activeTab === 'review' ? ' active' : '' ?>" data-tab="review"><i class="bi bi-bar-chart"></i>Review Limits</div>
        <div class="settings-nav-item<?= $activeTab === 'committee' ? ' active' : '' ?>" data-tab="committee">
            <i class="bi bi-people"></i>Committee
            <?php if (!empty($committeeMembers)): ?><span class="nav-count"><?= count($committeeMembers) ?></span><?php endif; ?>
        </div>
        <div class="settings-nav-item<?= $activeTab === 'admins' ? ' active' : '' ?>" data-tab="admins">
            <i class="bi bi-shield-lock"></i>Admin Users
            <?php if (!empty($adminUsers)): ?><span class="nav-count"><?= count($adminUsers) ?></span><?php endif; ?>
        </div>
    </div>

    <!-- Panels -->
    <div class="settings-panel">

        <form method="POST" action="save_settings.php">
            <?= csrf_field() ?>
            <input type="hidden" name="active_tab" id="activeTabField" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">

            <!-- General -->
            <div class="settings-panel-content<?= $activeTab === 'general' ? ' active' : '' ?>" data-panel="general">
                <div class="panel-title">General</div>
                <div class="panel-desc">Award amount, notifications, and the essay prompt applicants see</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="award_amount" style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 14px;">Award Amount ($)</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-weight: 600; color: #495057;">$</span>
                            <input type="text" id="award_amount" name="award_amount"
                                   value="<?= getSetting('award_amount') ?>"
                                   style="width: 100%; padding: 8px 20px 8px 24px; border-radius: 6px; border: 1px solid #ced4da;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="notification_email" style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 14px;">Notification Email</label>
                        <input type="email" id="notification_email" name="notification_email"
                               value="<?= getSetting('notification_email') ?>"
                               placeholder="admin@domain.com"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
                        <div class="text-muted mt-2" style="font-size: 13px;">
                            Where application and recommendation notifications are sent.
                        </div>
                    </div>
                </div>

                <label for="essay_prompt" style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 14px; margin-top: 20px;">Essay Prompt</label>
                <textarea id="essay_prompt" name="essay_prompt" rows="3"
                          style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;"
                ><?= getSetting('essay_prompt', DEFAULT_ESSAY_PROMPT) ?></textarea>
                <div class="text-muted mt-2" style="font-size: 13px;">
                    Shown to applicants above the essay field on the application form. Word count guidance can be included right in the prompt text.
                </div>

                <div class="d-flex justify-content-end mt-4 pt-3" style="border-top: 1px solid #f3f3f6;">
                    <button type="submit" class="settings-save-btn">Save Settings</button>
                </div>
            </div>

            <!-- Timeline -->
            <div class="settings-panel-content<?= $activeTab === 'timeline' ? ' active' : '' ?>" data-panel="timeline">
                <div class="panel-title">Timeline</div>
                <div class="panel-desc">Key dates shown to applicants</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="application_open" style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 14px;">Application Opens</label>
                        <input type="date" id="application_open" name="application_open"
                               value="<?= getSetting('application_open') ?>"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
                    </div>
                    <div class="col-md-6">
                        <label for="application_closed" style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 14px;">Application Closes</label>
                        <input type="date" id="application_closed" name="application_closed"
                               value="<?= getSetting('application_closed') ?>"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
                    </div>
                </div>
                <div class="text-muted mt-2 mb-3" style="font-size: 13px;">
                    Set the dates when students can submit applications.
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="review_start" style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 14px;">Review Begins</label>
                        <input type="date" id="review_start" name="review_start"
                               value="<?= getSetting('review_start') ?>"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
                    </div>
                    <div class="col-md-6">
                        <label for="review_end" style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 14px;">Review Ends</label>
                        <input type="date" id="review_end" name="review_end"
                               value="<?= getSetting('review_end') ?>"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
                    </div>
                </div>
                <div class="text-muted mt-2 mb-3" style="font-size: 13px;">
                    Estimated period for reviewing and evaluating applications.
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="announcement_date" style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 14px;">Recipient Announced</label>
                        <input type="date" id="announcement_date" name="announcement_date"
                               value="<?= getSetting('announcement_date') ?>"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
                    </div>
                </div>
                <div class="text-muted mt-2" style="font-size: 13px;">
                    Date when the scholarship recipient will be announced.
                </div>

                <div class="d-flex justify-content-end mt-4 pt-3" style="border-top: 1px solid #f3f3f6;">
                    <button type="submit" class="settings-save-btn">Save Settings</button>
                </div>
            </div>

            <!-- Review Limits -->
            <div class="settings-panel-content<?= $activeTab === 'review' ? ' active' : '' ?>" data-panel="review">
                <div class="panel-title">Review Limits</div>
                <div class="panel-desc">Caps used while reviewing applications</div>

                <label for="final_review_limit" style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 14px;">Final Review Limit</label>
                <input type="number" id="final_review_limit" name="final_review_limit" min="1" step="1"
                       value="<?= getSetting('final_review_limit', DEFAULT_FINAL_REVIEW_LIMIT) ?>"
                       style="width: 100%; max-width: 160px; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
                <div class="text-muted mt-2" style="font-size: 13px;">
                    Maximum number of applications that can be advanced to Final Review each cycle. Once this many are in Final Review, advancing more is blocked until the cycle is archived.
                </div>

                <div class="d-flex justify-content-end mt-4 pt-3" style="border-top: 1px solid #f3f3f6;">
                    <button type="submit" class="settings-save-btn">Save Settings</button>
                </div>
            </div>
        </form>

        <!-- Committee -->
        <div class="settings-panel-content<?= $activeTab === 'committee' ? ' active' : '' ?>" data-panel="committee">
            <div class="panel-title">Committee Members</div>
            <div class="panel-desc">The roster you can choose from when sending the Final Review link out for outside review</div>

            <?php if (!empty($_GET['member_error'])): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                    <div><?= htmlspecialchars($_GET['member_error'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php elseif (!empty($_GET['member_success'])): ?>
                <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><?= htmlspecialchars($_GET['member_success'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?php if (empty($committeeMembers)): ?>
                <div class="roster-empty">
                    <i class="bi bi-people"></i>
                    No committee members added yet.
                </div>
            <?php else: ?>
                <div class="roster-list">
                    <?php foreach ($committeeMembers as $member): ?>
                        <div class="roster-item">
                            <div class="roster-avatar"><?= htmlspecialchars(strtoupper(substr($member['name'], 0, 1))) ?></div>
                            <div class="roster-info">
                                <div class="roster-name"><?= htmlspecialchars($member['name']) ?></div>
                                <div class="roster-email"><?= htmlspecialchars($member['email']) ?></div>
                            </div>
                            <form method="POST" action="delete_committee_member.php" class="d-inline"
                                  onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($member['name'])) ?> from the committee roster?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                                <button type="submit" class="roster-delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="add-member-card">
                <div class="add-member-title"><i class="bi bi-person-plus-fill"></i>Add a Committee Member</div>
                <form method="POST" action="save_committee_member.php" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="col-md-5">
                        <label for="member_name" style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px; color: #495057;">Name</label>
                        <input type="text" id="member_name" name="member_name" required placeholder="Full name"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da; background: #fff;">
                    </div>
                    <div class="col-md-5">
                        <label for="member_email" style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px; color: #495057;">Email</label>
                        <input type="email" id="member_email" name="member_email" required placeholder="name@example.com"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da; background: #fff;">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="settings-save-btn" style="width: 100%;">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Admin Users -->
        <div class="settings-panel-content<?= $activeTab === 'admins' ? ' active' : '' ?>" data-panel="admins">
            <div class="panel-title">Admin Users</div>
            <div class="panel-desc">Full access to the admin portal. Separate from Committee Members, who only get a review link and never sign in.</div>

            <?php if (!empty($_GET['admin_error'])): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                    <div><?= htmlspecialchars($_GET['admin_error'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php elseif (!empty($_GET['admin_success'])): ?>
                <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><?= htmlspecialchars($_GET['admin_success'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?php if (empty($adminUsers)): ?>
                <div class="roster-empty">
                    <i class="bi bi-shield-lock"></i>
                    No admin users found.
                </div>
            <?php else: ?>
                <div class="roster-list">
                    <?php foreach ($adminUsers as $admin): ?>
                        <?php
                            $status = adminUserStatus($admin);
                            $isSelf = (int) $admin['id'] === (int) ($_SESSION['admin_id'] ?? 0);
                            $displayName = $admin['name'] !== '' && $admin['name'] !== null ? $admin['name'] : $admin['email'];
                        ?>
                        <div class="roster-item">
                            <div class="roster-avatar"><?= htmlspecialchars(strtoupper(substr($displayName, 0, 1))) ?></div>
                            <div class="roster-info">
                                <div class="roster-name"><?= htmlspecialchars($displayName) ?></div>
                                <div class="roster-email"><?= htmlspecialchars($admin['email']) ?></div>
                            </div>
                            <span class="status-badge <?= $status['class'] ?>"><?= $status['label'] ?></span>

                            <button type="button" class="roster-edit" title="Edit"
                                    data-admin-id="<?= (int) $admin['id'] ?>"
                                    data-admin-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                    data-admin-email="<?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-admin-pending="<?= $status['label'] === 'Pending setup' ? '1' : '0' ?>"
                                    onclick="openEditAdminModal(this)">
                                <i class="bi bi-pencil"></i>
                            </button>

                            <?php if ($isSelf): ?>
                                <span class="roster-you">You</span>
                            <?php elseif ($status['label'] === 'Pending setup'): ?>
                                <form method="POST" action="toggle_admin_user.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                                    <input type="hidden" name="action" value="resend">
                                    <button type="submit" class="roster-action-btn">Resend Invite</button>
                                </form>
                            <?php elseif ($admin['is_active']): ?>
                                <form method="POST" action="toggle_admin_user.php" class="d-inline"
                                      onsubmit="return confirm('Disable admin access for <?= htmlspecialchars(addslashes($displayName)) ?>?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                                    <input type="hidden" name="action" value="disable">
                                    <button type="submit" class="roster-action-btn danger">Disable</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="toggle_admin_user.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                                    <input type="hidden" name="action" value="enable">
                                    <button type="submit" class="roster-action-btn">Enable</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="add-member-card">
                <div class="add-member-title"><i class="bi bi-person-plus-fill"></i>Invite a New Admin</div>
                <form method="POST" action="save_admin_user.php" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="col-md-5">
                        <label for="admin_name" style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px; color: #495057;">Name</label>
                        <input type="text" id="admin_name" name="admin_name" required placeholder="Full name"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da; background: #fff;">
                    </div>
                    <div class="col-md-5">
                        <label for="admin_email" style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 13px; color: #495057;">Email</label>
                        <input type="email" id="admin_email" name="admin_email" required placeholder="name@example.com"
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da; background: #fff;">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="settings-save-btn" style="width: 100%;">Invite</button>
                    </div>
                </form>
                <div class="text-muted mt-2" style="font-size: 13px;">
                    They'll get an email with a link to set their own password -- you never choose or see it.
                </div>
            </div>
        </div>

        <!-- Edit Admin modal (single instance, reused for whichever row triggered it) -->
        <div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="editAdminForm" method="POST" action="update_admin_user.php">
                    <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" id="editAdminId">
                        <div class="modal-header" style="background: rgb(7,5,55); border: none; padding: 20px 24px;">
                            <h5 class="modal-title text-white mb-0" id="editAdminLabel" style="font-weight: 600;">Edit Admin</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="padding: 28px 30px; background: #fbfbfc;">
                            <label for="editAdminName" class="form-label" style="font-weight: 600; font-size: 14px;">Name</label>
                            <input type="text" class="form-control mb-3" id="editAdminName" name="admin_name" required
                                   style="border-radius: 6px; border: 1px solid #ced4da;">

                            <label for="editAdminEmail" class="form-label" style="font-weight: 600; font-size: 14px;">Email</label>
                            <input type="email" class="form-control" id="editAdminEmail" name="admin_email" required
                                   style="border-radius: 6px; border: 1px solid #ced4da;">
                            <div class="text-muted mt-2" style="font-size: 12.5px;" id="editAdminEmailHint"></div>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #ececf1; padding: 16px 24px;">
                            <button type="button" class="btn" style="background: #f1f1f4; color: #495057;" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn" style="background: rgb(7,5,55); color: #fff;">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
  </div>
</div>

</div>
</main>

<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.querySelectorAll('.settings-nav-item').forEach(function(item) {
    item.addEventListener('click', function() {
        const tab = this.dataset.tab;

        document.querySelectorAll('.settings-nav-item').forEach(function(el) { el.classList.remove('active'); });
        this.classList.add('active');

        document.querySelectorAll('.settings-panel-content').forEach(function(panel) {
            panel.classList.toggle('active', panel.dataset.panel === tab);
        });

        const activeTabField = document.getElementById('activeTabField');
        if (activeTabField) activeTabField.value = tab;

        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        url.searchParams.delete('member_error');
        url.searchParams.delete('member_success');
        url.searchParams.delete('admin_error');
        url.searchParams.delete('admin_success');
        url.searchParams.delete('error');
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url);
    });
});
</script>

<script>
var editAdminModal = new bootstrap.Modal(document.getElementById('editAdminModal'));

function openEditAdminModal(btn) {
    document.getElementById('editAdminId').value = btn.dataset.adminId;
    document.getElementById('editAdminName').value = btn.dataset.adminName;
    document.getElementById('editAdminEmail').value = btn.dataset.adminEmail;

    var hint = document.getElementById('editAdminEmailHint');
    hint.textContent = btn.dataset.adminPending === '1'
        ? 'Their invite hasn\'t been accepted yet -- changing the email sends a fresh invite there instead.'
        : '';

    editAdminModal.show();
}
</script>

</body>
</html>
