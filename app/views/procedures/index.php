<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container mt-3 procedures-dashboard">
    <?php flash('procedures_backfill'); ?>
    <?php
        $currentCount = 0;
        $registeredCount = 0;
        $isAdmin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true);
        $activeTotalCount = (int) (($data['dashboard_counts']['active_total'] ?? 0));
        $historicalTotalCount = (int) (($data['dashboard_counts']['historical_total'] ?? 0));
        $historicalProcedures = $data['historical_procedures'] ?? [];
        $filters = $data['filters'] ?? [];
        $selectedResponsibilityCenter = $filters['responsibility_center'] ?? '';
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo = $filters['date_to'] ?? '';
        $viewMode = $data['view_mode'] ?? 'card';
        $hasActiveFilters = ($data['search'] ?? '') !== ''
            || $selectedResponsibilityCenter !== ''
            || $dateFrom !== ''
            || $dateTo !== '';
        $queryBase = [
            'search' => $data['search'] ?? '',
            'responsibility_center' => $selectedResponsibilityCenter,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
        $cardViewUrl = URLROOT . '/procedures?' . http_build_query(array_merge($queryBase, ['view' => 'card']));
        $listViewUrl = URLROOT . '/procedures?' . http_build_query(array_merge($queryBase, ['view' => 'list']));

        $statusBadgeClass = function ($status) {
            if ($status === 'EFFECTIVE') {
                return 'success';
            }

            if ($status === 'REGISTERED') {
                return 'primary';
            }

            if ($status === 'SUPERSEDED') {
                return 'danger';
            }

            return 'secondary';
        };

        foreach (($data['procedures'] ?? []) as $procedureSummary) {
            if (($procedureSummary->current_version_status ?? '') === 'EFFECTIVE') {
                $currentCount++;
            }

            if (($procedureSummary->current_version_status ?? '') === 'REGISTERED') {
                $registeredCount++;
            }
        }
    ?>

    <div class="page-hero compact-hero">
        <h2>Current Procedures Dashboard</h2>
        <p><?php echo $isAdmin
            ? 'Browse current procedures, open the latest approved record, and review historical records when needed.'
            : 'Browse current procedures and open the latest approved record for day-to-day use.'; ?></p>
    </div>

    <?php if ($isAdmin): ?>
        <div class="card surface-card compact-admin-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="compact-admin-copy mr-3">
                        <h5 class="font-weight-bold mb-0">PDMS Primary Path</h5>
                        <p class="text-muted mb-0">Create procedures and register revisions from the PDMS area.</p>
                    </div>
                    <div class="d-flex flex-wrap action-strip">
                        <a href="<?php echo URLROOT; ?>/procedures/create" class="btn btn-primary btn-sm compact-admin-button">Create Procedure</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$data['has_pdms']): ?>
        <div class="alert alert-warning">
            The PDMS tables are not available yet. Run the PDMS migration first to unlock the procedures dashboard.
        </div>
    <?php else: ?>
        <div class="dashboard-control-frame mb-3">
            <div class="dashboard-control-section dashboard-summary-section">
                <div class="dashboard-stat-grid">
                    <div class="dashboard-stat compact-stat">
                        <div class="label">Current Procedures</div>
                        <div class="h3 font-weight-bold mb-0"><?php echo (int) $currentCount; ?></div>
                        <small class="text-muted">Controlling in registry</small>
                    </div>
                    <div class="dashboard-stat compact-stat">
                        <div class="label">Registered Procedures</div>
                        <div class="h3 font-weight-bold mb-0"><?php echo (int) $registeredCount; ?></div>
                        <small class="text-muted">Registered, not yet effective</small>
                    </div>
                    <div class="dashboard-stat compact-stat">
                        <div class="label">Visible Active PDMS Procedures</div>
                        <div class="h3 font-weight-bold mb-0"><?php echo $activeTotalCount; ?></div>
                        <small class="text-muted">Shown in active dashboard</small>
                    </div>
                    <?php if ($isAdmin): ?>
                        <div class="dashboard-stat compact-stat">
                            <div class="label">Historical Procedures</div>
                            <div class="h3 font-weight-bold mb-0"><?php echo $historicalTotalCount; ?></div>
                            <small class="text-muted">Audit and traceability records</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-control-divider"></div>

            <div class="dashboard-control-section dashboard-filter-section">
                <div class="dashboard-filter-intro">
                    <?php if ($isAdmin): ?>
                        <strong>Recommended admin flow:</strong> create a procedure, register revisions from the procedure page, and keep lineage and section changes in PDMS.
                    <?php else: ?>
                        <strong>How to use this page:</strong> open the current procedure for day-to-day work and use search or filters to find the active record you need.
                    <?php endif; ?>
                </div>
                <div class="dashboard-guidance sr-only">
                    <?php if ($isAdmin): ?>
                        Recommended admin flow: create a procedure, register revisions from the procedure page, and keep lineage and section changes in PDMS.
                    <?php else: ?>
                        How to use this page: open the current procedure for day-to-day work and use search or filters to find the active record you need.
                    <?php endif; ?>
                </div>
                <form action="<?php echo URLROOT; ?>/procedures" method="get" class="dashboard-filter-form dense-filter-form">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($viewMode); ?>">
                    <div class="dashboard-filter-grid">
                        <div class="form-group dashboard-filter-field dashboard-filter-search">
                            <label class="font-weight-bold" for="search">Search procedures</label>
                            <input type="text" id="search" name="search" class="form-control form-control-lg" placeholder="Procedure code, title, description, or document number" value="<?php echo htmlspecialchars($data['search']); ?>">
                        </div>
                        <div class="form-group dashboard-filter-field dashboard-filter-center">
                            <label class="font-weight-bold" for="responsibility_center">Responsibility center</label>
                            <select id="responsibility_center" name="responsibility_center" class="form-control form-control-lg">
                                <option value="">All responsibility centers</option>
                                <?php foreach (($data['responsibility_center_options'] ?? []) as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $selectedResponsibilityCenter === $option ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group dashboard-filter-field dashboard-filter-date-from">
                            <label class="font-weight-bold" for="date_from">Effective from</label>
                            <input type="date" id="date_from" name="date_from" class="form-control form-control-lg" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="form-group dashboard-filter-field dashboard-filter-date-to">
                            <label class="font-weight-bold" for="date_to">Effective to</label>
                            <input type="date" id="date_to" name="date_to" class="form-control form-control-lg" value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        <div class="form-group dashboard-filter-field dashboard-filter-actions-field">
                            <label class="font-weight-bold dashboard-filter-actions-label" aria-hidden="true">Actions</label>
                            <div class="dashboard-filter-actions">
                                <?php if ($hasActiveFilters): ?>
                                    <div class="dashboard-filter-status small text-muted">Showing filtered dashboard results.</div>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary dashboard-filter-button">Search</button>
                                <a href="<?php echo URLROOT; ?>/procedures" class="btn btn-outline-secondary dashboard-filter-button">Clear</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="surface-card procedures-view-toolbar mb-3">
            <div class="procedures-view-toolbar-inner">
                <div class="procedures-view-copy">
                    <h5 class="font-weight-bold mb-1">Browse Procedures</h5>
                    <p class="text-muted mb-0">Switch between a visual card layout and a denser list layout without losing your current filters.</p>
                </div>
                <div class="btn-group procedures-view-toggle" role="group" aria-label="Procedure layout options">
                    <a href="<?php echo htmlspecialchars($cardViewUrl); ?>" class="btn <?php echo $viewMode === 'card' ? 'btn-primary' : 'btn-outline-primary'; ?>" aria-pressed="<?php echo $viewMode === 'card' ? 'true' : 'false'; ?>">Card View</a>
                    <a href="<?php echo htmlspecialchars($listViewUrl); ?>" class="btn <?php echo $viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary'; ?>" aria-pressed="<?php echo $viewMode === 'list' ? 'true' : 'false'; ?>">List View</a>
                </div>
            </div>
        </div>

        <?php if (!empty($data['procedures'])): ?>
            <?php if ($viewMode === 'list'): ?>
                <div class="surface-card p-0 overflow-hidden">
                    <div class="table-responsive procedures-list-table">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Procedure</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Current Version</th>
                                    <th scope="col">Effectivity</th>
                                    <th scope="col">Responsibility Center</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['procedures'] as $procedure): ?>
                                    <tr>
                                        <td>
                                            <div class="procedure-list-title"><?php echo htmlspecialchars($procedure->title); ?></div>
                                            <div class="procedure-list-meta">
                                                <span class="badge-soft mr-2"><?php echo htmlspecialchars($procedure->procedure_code); ?></span>
                                                <span><?php echo htmlspecialchars($procedure->description ?: 'No description available.'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $statusBadgeClass($procedure->current_version_status ?? ''); ?>">
                                                <?php echo htmlspecialchars($procedure->current_version_status ?? 'UNMAPPED'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($procedure->current_version_number ?: 'No mapped version'); ?></td>
                                        <td><?php echo htmlspecialchars($procedure->current_effective_date ?: 'No effectivity date'); ?></td>
                                        <td><?php echo htmlspecialchars($procedure->owner_office ?: 'Not assigned'); ?></td>
                                        <td>
                                            <div class="procedure-list-actions">
                                                <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $procedure->id; ?>" class="btn btn-primary btn-sm">Open</a>
                                                <?php if (!empty($procedure->current_file_path) && !empty($procedure->current_version_id)): ?>
                                                    <a href="<?php echo URLROOT; ?>/procedures/file/<?php echo (int) $procedure->current_version_id; ?>" target="_blank" class="btn btn-outline-info btn-sm">PDF</a>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (($procedure->current_version_status ?? '') === 'REGISTERED'): ?>
                                                <div class="small text-info mt-2">Registered in the registry and pending effectivity.</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($data['procedures'] as $procedure): ?>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card sop-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="badge-soft"><?php echo htmlspecialchars($procedure->procedure_code); ?></span>
                                        <span class="badge badge-<?php echo $statusBadgeClass($procedure->current_version_status ?? ''); ?>"><?php echo htmlspecialchars($procedure->current_version_status ?? 'UNMAPPED'); ?></span>
                                    </div>
                                    <h5 class="font-weight-bold mb-2"><?php echo htmlspecialchars($procedure->title); ?></h5>
                                    <p class="soft-muted mb-3"><?php echo htmlspecialchars($procedure->description ?: 'No description available.'); ?></p>
                                    <div class="detail-item mb-3">
                                        <span class="label">Current Controlling Version</span>
                                        <span><?php echo htmlspecialchars($procedure->current_version_number ?: 'No mapped version'); ?></span>
                                    </div>
                                    <div class="detail-item mb-3">
                                        <span class="label">Effectivity</span>
                                        <span><?php echo htmlspecialchars($procedure->current_effective_date ?: 'No effectivity date'); ?></span>
                                    </div>
                                    <div class="detail-item mb-3">
                                        <span class="label">Responsibility Center</span>
                                        <span><?php echo htmlspecialchars($procedure->owner_office ?: 'Not assigned'); ?></span>
                                    </div>
                                    <div class="detail-item mb-3">
                                        <span class="label">Authoring Path</span>
                                        <span>PDMS-first</span>
                                    </div>
                                    <?php if (($procedure->current_version_status ?? '') === 'REGISTERED'): ?>
                                        <p class="alert alert-info small mb-3">
                                            This procedure is registered in the SOP registry but is not yet the effective controlling version.
                                        </p>
                                    <?php endif; ?>
                                    <div class="mt-auto d-flex flex-wrap">
                                        <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $procedure->id; ?>" class="btn btn-primary btn-sm mr-2 mb-2">Open Procedure</a>
                                        <?php if (!empty($procedure->current_file_path) && !empty($procedure->current_version_id)): ?>
                                            <a href="<?php echo URLROOT; ?>/procedures/file/<?php echo (int) $procedure->current_version_id; ?>" target="_blank" class="btn btn-outline-info btn-sm mb-2">Open Current PDF</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="surface-card p-4 text-center">
                <p class="mb-0 text-muted">No PDMS procedures matched your search.</p>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <div class="surface-card p-4 mt-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div class="mb-2 mb-md-0">
                        <h5 class="font-weight-bold mb-1">Historical Procedures</h5>
                        <p class="text-muted mb-0">Retained registry records stay available here for audit, traceability, and historical review.</p>
                    </div>
                </div>

                <?php if (!empty($historicalProcedures)): ?>
                    <?php if ($viewMode === 'list'): ?>
                        <div class="table-responsive procedures-list-table historical-list-table">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Procedure</th>
                                        <th scope="col">Master Status</th>
                                        <th scope="col">Latest Historical Version</th>
                                        <th scope="col">Last Effectivity</th>
                                        <th scope="col">Responsibility Center</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historicalProcedures as $procedure): ?>
                                        <tr>
                                            <td>
                                                <div class="procedure-list-title"><?php echo htmlspecialchars($procedure->title); ?></div>
                                                <div class="procedure-list-meta">
                                                    <span class="badge-soft mr-2"><?php echo htmlspecialchars($procedure->procedure_code); ?></span>
                                                    <span><?php echo htmlspecialchars($procedure->description ?: 'No description available.'); ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-secondary"><?php echo htmlspecialchars($procedure->status ?: 'HISTORICAL'); ?></span></td>
                                            <td><?php echo htmlspecialchars($procedure->current_version_number ?: 'Resolved on detail page'); ?></td>
                                            <td><?php echo htmlspecialchars($procedure->current_effective_date ?: 'No effectivity date'); ?></td>
                                            <td><?php echo htmlspecialchars($procedure->owner_office ?: 'Not assigned'); ?></td>
                                            <td>
                                                <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $procedure->id; ?>" class="btn btn-outline-secondary btn-sm">Open Historical Procedure</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($historicalProcedures as $procedure): ?>
                                <div class="col-xl-4 col-md-6 mb-4">
                                    <div class="card sop-card">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <span class="badge-soft"><?php echo htmlspecialchars($procedure->procedure_code); ?></span>
                                                <span class="badge badge-secondary"><?php echo htmlspecialchars($procedure->status ?? 'HISTORICAL'); ?></span>
                                            </div>
                                            <h5 class="font-weight-bold mb-2"><?php echo htmlspecialchars($procedure->title); ?></h5>
                                            <p class="soft-muted mb-3"><?php echo htmlspecialchars($procedure->description ?: 'No description available.'); ?></p>
                                            <div class="detail-item mb-3">
                                                <span class="label">Procedure Master Status</span>
                                                <span><?php echo htmlspecialchars($procedure->status ?: 'Unknown'); ?></span>
                                            </div>
                                            <div class="detail-item mb-3">
                                                <span class="label">Latest Historical Version</span>
                                                <span><?php echo htmlspecialchars($procedure->current_version_number ?: 'Resolved on detail page'); ?></span>
                                            </div>
                                            <div class="detail-item mb-3">
                                                <span class="label">Last Recorded Effectivity</span>
                                                <span><?php echo htmlspecialchars($procedure->current_effective_date ?: 'No effectivity date'); ?></span>
                                            </div>
                                            <div class="detail-item mb-3">
                                                <span class="label">Responsibility Center</span>
                                                <span><?php echo htmlspecialchars($procedure->owner_office ?: 'Not assigned'); ?></span>
                                            </div>
                                            <div class="mt-auto d-flex flex-wrap">
                                                <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $procedure->id; ?>" class="btn btn-outline-secondary btn-sm mr-2 mb-2">Open Historical Procedure</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="mb-0 text-muted">No historical procedures matched your search.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
