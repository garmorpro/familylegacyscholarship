<?php
session_start();
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';
require_once '../path.php';

/**
 * Status counts + total
 */
try {
    $countsStmt = $pdo->query("
        SELECT application_status, COUNT(*) AS total
        FROM scholarship_applications
        WHERE archived_at IS NULL
        GROUP BY application_status
    ");

    $statusCounts = [
        'submitted' => 0,
        'reviewed'  => 0,
        'final_review'  => 0,
        'final_recipient' => 0
    ];

    while ($row = $countsStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($statusCounts[$row['application_status']])) {
            $statusCounts[$row['application_status']] = (int) $row['total'];
        }
    }

    // Total applications (all statuses)
    $totalApplications = array_sum($statusCounts);

} catch (Exception $e) {
    $statusCounts = [
        'submitted' => 0,
        'reviewed'  => 0,
        'final_review'  => 0,
        'final_recipient' => 0
    ];
    $totalApplications = 0;
}

/**
 * Fetch applications for table
 */
try {
    $applicationsStmt = $pdo->query("
        SELECT
            id,
            first_name,
            last_name,
            gpa,
            email,
            phone,
            intended_school,
            intended_major,
            application_status,
            submitted_at
        FROM scholarship_applications
        WHERE archived_at IS NULL
        ORDER BY
            CASE application_status
                WHEN 'final_recipient' THEN 1
                WHEN 'final_review' THEN 2
                WHEN 'reviewed' THEN 3
                ELSE 4
            END,
            submitted_at DESC
    ");


    $applications = $applicationsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $applications = [];
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css?v=11.1.0">
    <title>Application Portal - Morgan Legacy Scholarship</title>
    <style>
        /* Base modern action button */
.btn-action {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 14px;
    font-weight: 500;
    color: #111827;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
}

.btn-action:hover {
    background: #f9fafb;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
}

/* Soft danger button (not scary red) */
.btn-danger-soft {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-danger-soft:hover {
    background: #fecaca;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(153,27,27,0.15);
}

/* Dropdown polish */
.dropdown-menu {
    border-radius: 12px;
    padding: 6px;
}

.dropdown-item {
    border-radius: 8px;
    font-size: 14px;
}

.dropdown-item:hover {
    background: #f3f4f6;
}

/* Status filter tabs */
.status-tabs {
    display: flex;
    gap: 4px;
    background: #fff;
    border: 1px solid rgb(241,242,243);
    border-radius: 10px;
    padding: 4px;
    width: fit-content;
    overflow-x: auto;
    max-width: 100%;
}

.status-tab {
    padding: 8px 16px;
    border-radius: 7px;
    font-size: 13.5px;
    font-weight: 600;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    user-select: none;
}

.status-tab.active {
    background: rgb(7,5,55);
    color: #fff;
}

.status-tab .tab-count {
    font-size: 11px;
    background: #eee;
    color: #555;
    padding: 1px 7px;
    border-radius: 20px;
}

.status-tab.active .tab-count {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

/* Mini progress-dot indicator in the table */
.dot-stepper {
    display: flex;
    align-items: center;
    gap: 3px;
}

.dot-stepper .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #e2e2e8;
    flex-shrink: 0;
}

.dot-stepper .dot.done {
    background: #C5A059;
}

.dot-stepper .dot.current {
    background: rgb(7,5,55);
    width: 10px;
    height: 10px;
    box-shadow: 0 0 0 3px rgba(7,5,55,0.12);
}

.dot-stepper .line {
    width: 14px;
    height: 2px;
    background: #e2e2e8;
}

.dot-stepper .line.done {
    background: #C5A059;
}

.dot-stepper .stage-label {
    font-size: 12px;
    color: #495057;
    font-weight: 600;
    margin-left: 6px;
    white-space: nowrap;
}

/* Row-level context action buttons */
.row-action-btn {
    font-size: 12.5px;
    padding: 6px 14px;
    border-radius: 7px;
    font-weight: 600;
    border: none;
    white-space: nowrap;
}

.row-action-btn.review {
    background: rgb(233,236,255);
    color: rgb(7,5,55);
}

.row-action-btn.review:hover {
    background: rgb(220,224,255);
}

.row-action-btn.final {
    background: rgba(197,160,89,0.15);
    color: #8a6d2e;
}

.row-action-btn.final:hover {
    background: rgba(197,160,89,0.28);
}

.row-action-btn.recipient {
    background: rgb(7,5,55);
    color: #fff;
}

.row-action-btn.recipient:hover {
    background: rgb(20,16,80);
}

    </style>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">


  <!-- Text with padding preserved -->
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-4" style="padding: 15px 20px;">
    <!-- Left: Titles -->
    <div>
        <h3 class="mb-1" style="font-weight: 600; font-size: 1.5rem; color: #212529;">Application Portal</h3>
        <h5 class="mb-0" style="font-weight: 400; font-size: 1rem; color: #6c757d;">Review and manage scholarship applications</h5>
    </div>
</div>


<!-- STATUS ROW -->

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-3">

        <!-- Open Applications -->
        <div>
            <div class="d-flex align-items-center justify-content-between p-3 bg-white shadow-sm"
                 style="border-radius: 12px; border: 1px solid rgb(241,242,243);">

                <div class="d-flex align-items-center">
                    <div class="me-3 d-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; border-radius: 10px; background-color: rgba(13,110,253,0.1);">
                        <i class="bi bi-inbox-fill text-primary"></i>
                    </div>

                    <div>
                        <div class="fw-semibold">Open</div>
                        <div class="text-muted" style="font-size: 13px;">
                            Awaiting review
                        </div>
                    </div>
                </div>

                <div class="fs-4 fw-bold text-primary">
                    <?= $statusCounts['submitted'] ?>
                </div>
            </div>
        </div>

        <!-- Reviewed Applications -->
        <div>
            <div class="d-flex align-items-center justify-content-between p-3 bg-white shadow-sm"
                 style="border-radius: 12px; border: 1px solid rgb(241,242,243);">

                <div class="d-flex align-items-center">
                    <div class="me-3 d-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; border-radius: 10px; background-color: rgba(108,117,125,0.15);">
                        <i class="bi bi-eye-fill text-secondary"></i>
                    </div>

                    <div>
                        <div class="fw-semibold">Reviewed</div>
                        <div class="text-muted" style="font-size: 13px;">
                            Initial review
                        </div>
                    </div>
                </div>

                <div class="fs-4 fw-bold text-secondary">
                    <?= $statusCounts['reviewed'] ?>
                </div>
            </div>
        </div>

        <!-- Selected Applications -->
        <div>
            <div class="d-flex align-items-center justify-content-between p-3 bg-white shadow-sm"
                 style="border-radius: 12px; border: 1px solid rgb(241,242,243);">

                <div class="d-flex align-items-center">
                    <div class="me-3 d-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; border-radius: 10px; background-color: rgba(25,135,84,0.15);">
                        <i class="bi bi-check-circle-fill text-success"></i>
                    </div>

                    <div>
                        <div class="fw-semibold">Final Review</div>
                        <div class="text-muted" style="font-size: 13px;">
                            Further review
                        </div>
                    </div>
                </div>

                <div class="fs-4 fw-bold text-success">
                    <?= $statusCounts['final_review'] ?>
                </div>
            </div>
        </div>

        <!-- Total Applications -->
        <div>
        <div class="d-flex align-items-center justify-content-between p-3 bg-white shadow-sm"
             style="border-radius: 12px; border: 1px solid rgb(241,242,243);">

            <div class="d-flex align-items-center">
                <div class="me-3 d-flex align-items-center justify-content-center"
                     style="width: 44px; height: 44px; border-radius: 10px; background-color: rgba(255,159,67,0.15);">
                    <i class="bi bi-collection-fill" style="color: rgb(255,159,67);"></i>
                </div>

                <div>
                    <div class="fw-semibold">Total</div>
                    <div class="text-muted" style="font-size: 13px;">
                        All applications
                    </div>
                </div>
            </div>

            <div class="fs-4 fw-bold" style="color: rgb(255,159,67);">
                <?= $totalApplications ?>
            </div>
        </div>
    </div>

    </div>

    <!-- PIPELINE BAR -->
    <div class="p-3 bg-white shadow-sm mb-4" style="border-radius: 12px; border: 1px solid rgb(241,242,243);">
        <div class="d-flex justify-content-between align-items-baseline mb-2">
            <div class="fw-semibold" style="font-size: 14px;">Review Pipeline</div>
            <div class="text-muted" style="font-size: 12px;"><?= $totalApplications ?> total</div>
        </div>
        <?php if ($totalApplications > 0): ?>
        <div class="d-flex" style="height: 8px; border-radius: 999px; overflow: hidden; background: #f1f3f5;">
            <div style="width: <?= round($statusCounts['submitted'] / $totalApplications * 100, 2) ?>%; background: #0d6efd;"></div>
            <div style="width: <?= round($statusCounts['reviewed'] / $totalApplications * 100, 2) ?>%; background: #6c757d;"></div>
            <div style="width: <?= round($statusCounts['final_review'] / $totalApplications * 100, 2) ?>%; background: #198754;"></div>
        </div>
        <?php else: ?>
        <div class="text-muted" style="font-size: 13px;">No applications yet.</div>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-3 mt-2">
            <div style="font-size: 12px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#0d6efd;margin-right:5px;"></span>Submitted <strong><?= $statusCounts['submitted'] ?></strong></div>
            <div style="font-size: 12px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#6c757d;margin-right:5px;"></span>Reviewed <strong><?= $statusCounts['reviewed'] ?></strong></div>
            <div style="font-size: 12px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#198754;margin-right:5px;"></span>Final Review <strong><?= $statusCounts['final_review'] ?></strong></div>
        </div>
    </div>

<!-- END STATUS ROW -->


<!-- TABLE -->

<?php
// Name of the current final recipient (if any). Once one exists, the round
// is effectively decided and the "Archive Applications" action becomes
// available — this is also used in its confirmation message.
$finalRecipientName = null;
if ($statusCounts['final_recipient'] > 0) {
    $frStmt = $pdo->query("SELECT first_name, last_name FROM scholarship_applications WHERE application_status = 'final_recipient' AND archived_at IS NULL LIMIT 1");
    $frRow = $frStmt->fetch(PDO::FETCH_ASSOC);
    if ($frRow) {
        $finalRecipientName = $frRow['first_name'] . ' ' . $frRow['last_name'];
    }
}
?>

<!-- Status filter tabs -->
<div class="status-tabs mb-3" id="statusTabs">
    <div class="status-tab active" data-status="all">All <span class="tab-count"><?= $totalApplications ?></span></div>
    <div class="status-tab" data-status="submitted">Submitted <span class="tab-count"><?= $statusCounts['submitted'] ?></span></div>
    <div class="status-tab" data-status="reviewed">Reviewed <span class="tab-count"><?= $statusCounts['reviewed'] ?></span></div>
    <div class="status-tab" data-status="final_review">Final Review <span class="tab-count"><?= $statusCounts['final_review'] ?></span></div>
    <div class="status-tab" data-status="final_recipient">Recipient <span class="tab-count"><?= $statusCounts['final_recipient'] ?></span></div>
</div>

<!-- Bulk Actions Button -->
<div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">

    <!-- Left side: Search Bar + selection indicator -->
    <div class="d-flex align-items-center gap-2">
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Search applicants..." style="width: 260px; padding-top: 8px !important; padding-bottom: 8px !important; border-radius: 20px !important;">
        <span id="selectionIndicator" class="badge rounded-pill bg-primary-subtle text-primary d-none" style="font-size: 12px; font-weight: 600;"></span>
    </div>

    <!-- Right side: Buttons -->
    <div class="d-flex align-items-center gap-2">
        <!-- Archive Applications: appears once a final recipient has been chosen -->
        <?php if ($statusCounts['final_recipient'] > 0): ?>
            <button class="btn btn-action"
                    data-total="<?= (int)$totalApplications ?>"
                    data-recipient-name="<?= htmlspecialchars($finalRecipientName ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    onclick="performBulkAction('archive', this.dataset.total, this.dataset.recipientName || null)">
                <i class="bi bi-archive me-1"></i>
                Archive Applications
            </button>
        <?php endif; ?>

        <!-- Bulk Actions Dropdown -->
        <div class="dropdown">
            <button class="btn btn-action" type="button" id="bulkActionsBtn"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-lightning-fill me-1"></i>
                Bulk Actions
                <i class="bi bi-chevron-down ms-1 small"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2"
                       href="#" onclick="performBulkAction('delete'); return false;">
                        <i class="bi bi-trash text-danger"></i>
                        Delete
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2"
                       href="#" onclick="performBulkAction('mark_reviewed'); return false;">
                        <i class="bi bi-eye text-secondary"></i>
                        Mark Reviewed
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2"
                       href="#" onclick="performBulkAction('select'); return false;">
                        <i class="bi bi-check-circle text-success"></i>
                        Advance to Final Review
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>





    <div class="mt-4 bg-white shadow-sm"
     style="border-radius: 12px; border: 1px solid rgb(241,242,243); overflow: hidden;">

    <table class="table table-hover mb-0 align-middle" id="applicationsTable">
        <thead class="table-light">
            <tr>
                <th style="width: 40px;"></th>
                <th>Applicant</th>
                <th>Contact</th>
                <th>Intended School</th>
                <th>Submitted</th>
                <th>Progress</th>
                <th style="width: 190px;">Action</th>
            </tr>
        </thead>

        <tbody>
        <?php if (empty($applications)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    No applications found
                </td>
            </tr>
        <?php else: ?>
            <?php
            $stageOrder = ['submitted', 'reviewed', 'final_review', 'final_recipient'];
            $stageLabels = ['Submitted', 'Reviewed', 'Final Review', 'Recipient'];
            ?>
            <?php foreach ($applications as $app): ?>
                <?php $stageIdx = array_search($app['application_status'], $stageOrder, true); ?>
                <tr style="cursor: pointer;" data-status="<?= htmlspecialchars($app['application_status']) ?>"
                    onclick="window.location.href='application_view.php?id=<?= $app['id'] ?>'">

                    <!-- Checkbox -->
                    <td>
                        <input type="checkbox"
                               class="form-check-input app-checkbox"
                               data-id="<?= (int)$app['id'] ?>"
                               data-name="<?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>"
                               onclick="event.stopPropagation()">
                    </td>

                    <!-- Name + GPA -->
                    <td>
                        <div class="fw-semibold">
                            <?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>
                        </div>
                        <div class="text-muted" style="font-size: 13px;">
                            GPA: <?= htmlspecialchars($app['gpa']) ?>
                        </div>
                    </td>

                    <!-- Contact -->
                    <td>
                        <div><?= htmlspecialchars($app['email']) ?></div>
                        <div class="text-muted" style="font-size: 13px;">
                            <?= htmlspecialchars($app['phone']) ?>
                        </div>
                    </td>

                    <!-- Intended School -->
                    <td>
                        <div><?= htmlspecialchars($app['intended_school']) ?></div>
                        <div class="text-muted" style="font-size: 13px;">
                            <?= htmlspecialchars($app['intended_major']) ?>
                        </div>
                    </td>

                    <!-- Date Submitted -->
                    <td>
                        <?= date('M j, Y', strtotime($app['submitted_at'])) ?>
                    </td>

                    <!-- Progress -->
                    <td>
                        <div class="dot-stepper">
                            <?php foreach ($stageOrder as $i => $stage): ?>
                                <?php if ($i > 0): ?>
                                    <div class="line <?= $i <= $stageIdx ? 'done' : '' ?>"></div>
                                <?php endif; ?>
                                <div class="dot <?= $i < $stageIdx ? 'done' : ($i === $stageIdx ? 'current' : '') ?>"></div>
                            <?php endforeach; ?>
                            <span class="stage-label"><?= $stageIdx !== false ? $stageLabels[$stageIdx] : ucwords(str_replace('_', ' ', $app['application_status'])) ?></span>
                        </div>
                    </td>

                    <!-- Action -->
                    <td onclick="event.stopPropagation()">
                        <?php if ($app['application_status'] === 'submitted'): ?>
                            <form method="POST" action="mark_reviewed.php" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                <input type="hidden" name="return" value="index">
                                <button type="submit" class="row-action-btn review">Mark Reviewed</button>
                            </form>
                        <?php elseif ($app['application_status'] === 'reviewed'): ?>
                            <form method="POST" action="mark_final_review.php" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                <input type="hidden" name="return" value="index">
                                <button type="submit" class="row-action-btn final">Advance to Final Review</button>
                            </form>
                        <?php elseif ($app['application_status'] === 'final_review'): ?>
                            <?php if ($statusCounts['final_recipient'] > 0): ?>
                                <span class="text-muted" style="font-size: 12.5px;">Recipient already chosen</span>
                            <?php else: ?>
                                <a href="application_view.php?id=<?= $app['id'] ?>" class="row-action-btn recipient text-decoration-none d-inline-block">
                                    Designate Recipient
                                </a>
                            <?php endif; ?>
                        <?php elseif ($app['application_status'] === 'final_recipient'): ?>
                            <span class="text-success" style="font-size: 12.5px; font-weight: 600;">
                                <i class="bi bi-check-circle-fill me-1"></i>Selected
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>




<!-- END TABLE -->

    
  </div>
</div>


</div>
</main>

<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>





<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Combined status-tab + search filtering -- a row must match both to show.
let currentStatusTab = 'all';

function applyTableFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#applicationsTable tbody tr[data-status]');

    rows.forEach(row => {
        const matchesTab = currentStatusTab === 'all' || row.dataset.status === currentStatusTab;
        const matchesSearch = row.innerText.toLowerCase().includes(searchTerm);
        row.style.display = (matchesTab && matchesSearch) ? '' : 'none';
    });
}

document.getElementById('searchInput').addEventListener('keyup', applyTableFilters);

document.querySelectorAll('.status-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentStatusTab = this.dataset.status;
        applyTableFilters();
    });
});
</script>

<script>
// Keep a live "N selected" indicator so selection state is visible before
// the Bulk Actions menu is even opened.
function updateSelectionIndicator() {
    const count = document.querySelectorAll('.app-checkbox:checked').length;
    const indicator = document.getElementById('selectionIndicator');
    if (count > 0) {
        indicator.textContent = count + ' selected';
        indicator.classList.remove('d-none');
    } else {
        indicator.classList.add('d-none');
    }
}
document.getElementById('applicationsTable').addEventListener('change', function(e) {
    if (e.target.classList.contains('app-checkbox')) {
        updateSelectionIndicator();
    }
});
</script>

<script>
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function performBulkAction(action, total, recipientName) {
    // Collect selected applications
    const selectedCheckboxes = Array.from(document.querySelectorAll('.app-checkbox:checked'));
    const selectedIds = selectedCheckboxes.map(cb => cb.dataset.id);
    const selectedNames = selectedCheckboxes.map(cb => cb.dataset.name);

    // Only require selection for actions other than archive (which applies to everything)
    if (action !== 'archive' && selectedIds.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No applications selected',
            text: 'Please select at least one application to continue.'
        });
        return;
    }

    // Build HTML list of names using Bootstrap list group
    const nameList = selectedNames.map(name => `<li class="list-group-item p-2">${escapeHtml(name)}</li>`).join('');

    // Build title/message based on action
    let title, htmlMessage;
    if (action === 'delete') {
        title = 'Delete Applications';
        htmlMessage = `
            <p>Are you sure you want to delete the following applications?</p>
            <ul class="list-group" style="
                max-height: 200px;
                overflow-y: auto;
                margin-top: 10px;
                margin-bottom: 15px;
            ">
                ${nameList}
            </ul>
            <p style="color: red; font-weight: bold; font-size: 16px;">
                This action <u>cannot</u> be undone.
            </p>
        `;
    } else if (action === 'mark_reviewed') {
        title = 'Mark Applications Reviewed';
        htmlMessage = `
            <p>Mark the following applications as reviewed?</p>
            <ul class="list-group" style="
                max-height: 200px;
                overflow-y: auto;
                margin-top: 10px;
                margin-bottom: 15px;
            ">
                ${nameList}
            </ul>
            <p class="text-muted" style="font-size: 13px;">
                Only applications currently marked "Submitted" will actually advance.
            </p>
        `;
    } else if (action === 'select') {
        title = 'Advance Applicants to Final Review';
        htmlMessage = `
            <p>Are you sure you want to advance the following applications to final review?</p>
            <ul class="list-group" style="
                max-height: 200px;
                overflow-y: auto;
                margin-top: 10px;
                margin-bottom: 15px;
            ">
                ${nameList}
            </ul>
            <p class="text-muted" style="font-size: 13px;">
                Only applications currently marked "Reviewed" will actually advance — anything still "Submitted" will be skipped.
            </p>
            <p style="color: orange; font-weight: bold; font-size: 16px;">
                This action is permanent.
            </p>
        `;
    } else if (action === 'archive') {
        title = 'Archive Applications';
        const recipientNote = recipientName
            ? `<p><strong>${escapeHtml(recipientName)}</strong> is this round's final recipient and already appears on your Recipients page.</p>`
            : '';
        htmlMessage = `
            <p>This will archive all <strong>${escapeHtml(String(total))}</strong> application(s) from this cycle. Nothing is deleted — they'll just move off the active dashboard and into Archives, kept on file for your records.</p>
            ${recipientNote}
            <p class="text-muted" style="font-size: 13px;">
                You'll get a clean dashboard for next cycle's applications.
            </p>
        `;
    }

    // Show SweetAlert2 confirmation
    Swal.fire({
        title: title,
        html: htmlMessage,
        icon: action === 'archive' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel',
        focusConfirm: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            fetch('/app/bulk_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action, ids: selectedIds, csrf_token: csrfToken})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        html: data.message
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: data.message
                    });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while performing bulk action.'
                });
            });
        }
    });
}

</script>



</body>
</html>