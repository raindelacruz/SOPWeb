<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container mt-4">
    <?php flash('procedures_backfill'); ?>
    <?php flash('procedures_cleanup'); ?>
    <?php
        $currentCount = 0;
        $registeredCount = 0;

        foreach (($data['procedures'] ?? []) as $procedureSummary) {
            if (($procedureSummary->current_version_status ?? '') === 'EFFECTIVE') {
                $currentCount++;
            }

            if (($procedureSummary->current_version_status ?? '') === 'REGISTERED') {
                $registeredCount++;
            }
        }
    ?>

    <div class="page-hero">
        <h2>Current Procedures Dashboard</h2>
        <p>Browse current controlling procedures, review their active versions, and use the PDMS-first registry tools as the primary path for registering new SOP revisions.</p>
    </div>

    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true)): ?>
        <div class="card surface-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div class="mb-3 mb-md-0">
                        <h5 class="font-weight-bold mb-1">PDMS Primary Path</h5>
                        <p class="text-muted mb-0">Start new procedures, revisions, supersessions, and rescissions from the PDMS area first. Use legacy SOP screens mainly for compatibility and historical access.</p>
                    </div>
                    <div class="d-flex flex-wrap action-strip">
                        <a href="<?php echo URLROOT; ?>/procedures/create" class="btn btn-primary mr-2 mb-2">Create Procedure</a>
                        <a href="<?php echo URLROOT; ?>/posts" class="btn btn-outline-secondary mb-2">Open Legacy SOPs</a>
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
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="dashboard-stat h-100">
                    <div class="label">Current Procedures</div>
                    <div class="h3 font-weight-bold mb-0"><?php echo (int) $currentCount; ?></div>
                    <small class="text-muted">Procedures currently treated as controlling in the registry</small>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="dashboard-stat h-100">
                    <div class="label">Registered Procedures</div>
                    <div class="h3 font-weight-bold mb-0"><?php echo (int) $registeredCount; ?></div>
                    <small class="text-muted">Registry records captured but not yet marked effective</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-stat h-100">
                    <div class="label">Unmapped Legacy Posts</div>
                    <div class="h3 font-weight-bold mb-0"><?php echo (int) ($data['backfill_status']['unmapped_posts'] ?? 0); ?></div>
                    <small class="text-muted">Legacy items still outside the PDMS model</small>
                </div>
            </div>
        </div>

        <div class="lifecycle-callout mb-4">
            <strong>Recommended admin flow:</strong> create a PDMS procedure, register revisions from the procedure page, preserve lineage and section changes there, and reserve legacy SOP editing for compatibility-only corrections.
        </div>

        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true)): ?>
            <div class="card surface-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div class="mb-3 mb-md-0">
                            <h5 class="font-weight-bold mb-1">Legacy Backfill Utility</h5>
                            <p class="text-muted mb-0">Synchronize older `posts` records into the PDMS model without reopening each SOP manually.</p>
                        </div>
                        <form action="<?php echo URLROOT; ?>/procedures/backfill" method="post" class="form-inline">
                            <?php echo csrf_input(); ?>
                            <label class="mr-2 font-weight-bold" for="limit">Batch size</label>
                            <input type="number" min="1" max="250" value="25" name="limit" id="limit" class="form-control mr-2 mb-2 mb-sm-0">
                            <button type="submit" class="btn btn-primary">Run Backfill</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card surface-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div class="mb-3 mb-md-0">
                            <h5 class="font-weight-bold mb-1">Relationship Cleanup Utility</h5>
                            <p class="text-muted mb-0">Mark older synchronized relationship rows so future re-syncs preserve curated PDMS links instead of treating everything as replaceable.</p>
                        </div>
                        <form action="<?php echo URLROOT; ?>/procedures/cleanup" method="post" class="form-inline">
                            <?php echo csrf_input(); ?>
                            <label class="mr-2 font-weight-bold" for="cleanup_limit">Batch size</label>
                            <input type="number" min="1" max="500" value="100" name="limit" id="cleanup_limit" class="form-control mr-2 mb-2 mb-sm-0">
                            <button type="submit" class="btn btn-outline-primary">Run Cleanup</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?php echo URLROOT; ?>/procedures" method="get" class="search-panel">
            <div class="form-row align-items-end">
                <div class="form-group col-lg-10 mb-lg-0">
                    <label class="font-weight-bold">Search procedures</label>
                    <input type="text" name="search" class="form-control form-control-lg" placeholder="Procedure code, title, description, or document number" value="<?php echo htmlspecialchars($data['search']); ?>">
                </div>
                <div class="form-group col-lg-2 mb-0">
                    <button type="submit" class="btn btn-primary btn-lg btn-block">Search</button>
                </div>
            </div>
        </form>

        <?php if (!empty($data['procedures'])): ?>
            <div class="row">
                <?php foreach ($data['procedures'] as $procedure): ?>
                    <?php
                        $badgeClass = 'secondary';
                        if (($procedure->current_version_status ?? '') === 'EFFECTIVE') {
                            $badgeClass = 'success';
                        } elseif (($procedure->current_version_status ?? '') === 'REGISTERED') {
                            $badgeClass = 'primary';
                        } elseif (($procedure->current_version_status ?? '') === 'SUPERSEDED') {
                            $badgeClass = 'danger';
                        }
                    ?>
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card sop-card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge-soft"><?php echo htmlspecialchars($procedure->procedure_code); ?></span>
                                    <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($procedure->current_version_status ?? 'UNMAPPED'); ?></span>
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
                                    <span class="label">Authoring Path</span>
                                    <span>PDMS-first</span>
                                </div>
                                <?php if (($procedure->current_version_status ?? '') === 'REGISTERED'): ?>
                                    <p class="alert alert-info small mb-3">
                                        This procedure is registered in the SOP registry but is not yet marked as the effective controlling version.
                                    </p>
                                <?php endif; ?>
                                <div class="mt-auto d-flex flex-wrap">
                                    <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $procedure->id; ?>" class="btn btn-primary btn-sm mr-2 mb-2">Open Procedure</a>
                                    <?php if (!empty($procedure->current_file_path)): ?>
                                        <a href="<?php echo URLROOT; ?>../uploads/<?php echo rawurlencode($procedure->current_file_path); ?>" target="_blank" class="btn btn-outline-info btn-sm mb-2">Open Current PDF</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="surface-card p-4 text-center">
                <p class="mb-0 text-muted">No PDMS procedures matched your search.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
