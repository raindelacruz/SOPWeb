<?php require APPROOT . '/app/views/includes/header.php'; ?>
<?php
    $overview = $data['overview'];
    $history = $data['history'] ?? [];
    $latestVersion = $data['latest_version'] ?? null;
    $relationships = $data['relationships'] ?? [];
    $workflowActions = $data['workflow_actions'] ?? [];
    $sectionHistory = $data['section_history'] ?? [];
    $nextWorkflowStatus = $data['next_workflow_status'] ?? null;
    $showWorkflowLane = !empty($data['show_workflow_lane']);
    $isHistoricalProcedure = in_array(($overview->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true);
    $isHistoricalAnchorVersion = !empty($overview->historical_anchor_version);
    $isAdmin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true);
    $status = $overview->current_version_status ?? 'UNMAPPED';
    $statusClass = 'secondary';

    if ($status === 'EFFECTIVE') {
        $statusClass = 'success';
    } elseif ($status === 'REGISTERED') {
        $statusClass = 'primary';
    } elseif ($status === 'SUPERSEDED') {
        $statusClass = 'danger';
    }

    $formatLifecycleAction = function ($actionType) {
        $actionType = strtoupper((string) $actionType);
        $labels = [
            'PDMS_REGISTER_PROCEDURE' => 'Procedure Registered',
            'PDMS_REGISTER_REVISION' => 'Revision Registered',
            'PDMS_REGISTERED_REPLACEMENT' => 'Previous Version Replaced',
            'PDMS_REGISTERED_SUPERSESSION' => 'Procedure Superseded',
            'PDMS_RESCIND' => 'Procedure Rescinded',
            'PDMS_MARK_EFFECTIVE' => 'Marked Effective',
            'PDMS_ARCHIVE_VERSION' => 'Version Archived'
        ];

        if (isset($labels[$actionType])) {
            return $labels[$actionType];
        }

        return ucwords(strtolower(str_replace('_', ' ', $actionType)));
    };
?>

<div class="container mt-4">
    <?php flash('procedures_backfill'); ?>
    <div class="page-hero">
        <span class="badge badge-light px-3 py-2 mb-3"><?php echo htmlspecialchars($overview->procedure_code); ?></span>
        <span class="badge badge-<?php echo $statusClass; ?> px-3 py-2 mb-3 ml-2"><?php echo htmlspecialchars($status); ?></span>
        <h2><?php echo htmlspecialchars($overview->title); ?></h2>
        <p><?php echo htmlspecialchars($overview->description ?: 'No procedure description available.'); ?></p>
    </div>

    <div class="card surface-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap mb-4">
                <a href="<?php echo URLROOT; ?>/procedures" class="btn btn-outline-secondary mr-2 mb-2">Back to Dashboard</a>
                <?php if (!empty($overview->current_file_path) && !empty($overview->current_version_id)): ?>
                    <a href="<?php echo URLROOT; ?>/procedures/file/<?php echo (int) $overview->current_version_id; ?>" target="_blank" class="btn btn-info mr-2 mb-2"><?php echo $isHistoricalProcedure ? 'Open Historical Anchor PDF' : 'Open Current PDF'; ?></a>
                <?php endif; ?>
                <?php if (!empty($overview->current_version_id)): ?>
                    <a href="<?php echo URLROOT; ?>/procedures/version/<?php echo (int) $overview->current_version_id; ?>" class="btn btn-outline-info mr-2 mb-2"><?php echo $isHistoricalProcedure ? 'Open Historical Anchor Version' : 'Open Current Version Detail'; ?></a>
                <?php endif; ?>
                <?php if ($isAdmin && !in_array(($overview->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)): ?>
                    <a href="<?php echo URLROOT; ?>/procedures/edit/<?php echo (int) $overview->id; ?>" class="btn btn-outline-primary mr-2 mb-2">Edit Procedure</a>
                    <a href="<?php echo URLROOT; ?>/procedures/issue/<?php echo (int) $overview->id; ?>" class="btn btn-primary mr-2 mb-2">Register Revision</a>
                    <?php if (!empty($data['can_supersede'])): ?>
                        <a href="<?php echo URLROOT; ?>/procedures/supersede/<?php echo (int) $overview->id; ?>" class="btn btn-outline-danger mr-2 mb-2">Create Superseding Procedure</a>
                    <?php endif; ?>
                    <?php if (!empty($data['can_rescind'])): ?>
                        <a href="<?php echo URLROOT; ?>/procedures/rescind/<?php echo (int) $overview->id; ?>" class="btn btn-outline-danger mb-2">Rescind Procedure</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="lifecycle-callout">
                <?php if ($isAdmin): ?>
                    <strong>Admin tools:</strong> use this page to register revisions, rescind the procedure, or create a superseding procedure. This record is managed directly in PDMS.
                <?php else: ?>
                    <strong>Record status:</strong> this procedure is maintained in PDMS. You can review the current or historical record here.
                <?php endif; ?>
            </div>

            <div class="clarity-band">
                <div class="clarity-card <?php echo $isHistoricalProcedure ? 'is-historical' : (($overview->current_version_status ?? '') === 'REGISTERED' ? 'is-registered' : 'is-current'); ?>">
                    <span class="eyebrow-label">What This Record Means</span>
                    <span class="value">
                        <?php
                            if ($isHistoricalProcedure) {
                                echo 'Historical Procedure';
                            } elseif (($overview->current_version_status ?? '') === 'REGISTERED') {
                                echo 'Registered, Not Yet Controlling';
                            } else {
                                echo 'Active Controlling Procedure';
                            }
                        ?>
                    </span>
                    <p>
                        <?php
                            if ($isHistoricalProcedure) {
                                echo 'This record stays visible for audit history. It is no longer the procedure to use for active operations.';
                            } elseif (($overview->current_version_status ?? '') === 'REGISTERED') {
                                echo 'The procedure has been recorded in the registry, but another effective record is still the operative controlling procedure.';
                            } else {
                                echo 'This procedure currently represents the operative PDMS record for day-to-day use.';
                            }
                        ?>
                    </p>
                </div>
                <div class="clarity-card <?php echo $isHistoricalAnchorVersion ? 'is-historical' : 'is-legacy'; ?>">
                    <span class="eyebrow-label"><?php echo $isHistoricalAnchorVersion ? 'How To Read This Page' : 'Source Of Record'; ?></span>
                    <span class="value"><?php echo $isHistoricalAnchorVersion ? 'Showing Latest Historical Version' : 'Live PDMS Record'; ?></span>
                    <p>
                        <?php
                            if ($isHistoricalAnchorVersion) {
                                echo 'The links on this page point to the latest historical version so you can review the retired record clearly.';
                            } else {
                                echo 'This page is driven directly from PDMS, which is the source of truth for lifecycle, lineage, and history.';
                            }
                        ?>
                    </p>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label"><?php echo $isHistoricalProcedure ? 'Latest Historical Version' : 'Current Controlling Version'; ?></span>
                    <span><?php echo htmlspecialchars($overview->current_version_number ?: 'No mapped version'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><?php echo $isHistoricalProcedure ? 'Historical Change Type' : 'Current Change Type'; ?></span>
                    <span><?php echo htmlspecialchars($overview->current_change_type ?: 'Not set'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Effectivity</span>
                    <span><?php echo htmlspecialchars($overview->current_effective_date ?: 'No effectivity date'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Procedure Status</span>
                    <span><?php echo htmlspecialchars($overview->status ?: 'Unknown'); ?></span>
                </div>
            </div>

            <?php if (($overview->current_version_status ?? '') === 'REGISTERED'): ?>
                <div class="alert alert-info mt-4 mb-0">
                    This procedure has been recorded in the registry, but it is not yet the active version for day-to-day use.
                </div>
            <?php endif; ?>

            <?php if (in_array(($overview->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)): ?>
                <div class="alert alert-secondary mt-4 mb-0">
                    This procedure is now historical. Review it for audit or reference purposes, and use the replacement or active procedure for current work.
                    <?php if ($isHistoricalAnchorVersion): ?>
                        The buttons and fields above now use the latest historical version for reference instead of treating any version as the active controlling record.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="list-card h-100">
                <h4 class="h5 font-weight-bold mb-3">Version History</h4>
                <?php if (!empty($history)): ?>
                    <ul class="list-group list-group-flush timeline-list">
                        <?php foreach ($history as $item): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between flex-wrap">
                                    <strong>
                                        <a href="<?php echo URLROOT; ?>/procedures/version/<?php echo (int) $item->id; ?>">
                                            <?php echo htmlspecialchars($item->version_number); ?>
                                        </a>
                                    </strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($item->effective_date ?: 'No date'); ?></span>
                                </div>
                                <div><?php echo htmlspecialchars($item->title); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($item->change_type); ?> | <?php echo htmlspecialchars($item->status); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No version history is available for this procedure.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-3 mb-4">
            <div class="list-card h-100">
                <h4 class="h5 font-weight-bold mb-3">Relationship Map</h4>
                <?php if (!empty($relationships)): ?>
                    <ul class="list-group list-group-flush timeline-list">
                        <?php foreach ($relationships as $relationship): ?>
                            <li class="list-group-item px-0">
                                <strong><?php echo htmlspecialchars($relationship->relationship_type); ?></strong>
                                <div><?php echo htmlspecialchars(($relationship->source_title ?? 'Unknown source') . ' -> ' . ($relationship->target_title ?? 'Unknown target')); ?></div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(($relationship->source_version_number ?? '-') . ' -> ' . ($relationship->target_version_number ?? '-')); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0"><?php echo $isHistoricalProcedure ? 'No normalized relationships are recorded for the latest historical anchor version.' : 'No normalized relationships are recorded for the current controlling version.'; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-3 mb-4">
            <div class="list-card h-100">
                <h4 class="h5 font-weight-bold mb-3">Lifecycle Trail</h4>
                <?php if (!empty($workflowActions)): ?>
                    <ul class="list-group list-group-flush timeline-list">
                        <?php foreach ($workflowActions as $action): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between flex-wrap">
                                    <strong><?php echo htmlspecialchars($formatLifecycleAction($action->lifecycle_action_type ?? '')); ?></strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($action->acted_at); ?></span>
                                </div>
                                <div><?php echo htmlspecialchars(($action->from_status ?: 'None') . ' -> ' . ($action->to_status ?: 'None')); ?></div>
                                <?php if (!empty($action->remarks)): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($action->remarks); ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0"><?php echo $isHistoricalProcedure ? 'No lifecycle actions have been recorded for the latest historical anchor version.' : 'No lifecycle actions have been recorded for the current controlling version.'; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-3 mb-4">
            <div class="list-card h-100">
                <h4 class="h5 font-weight-bold mb-3">Section Lineage</h4>
                <?php if (!empty($sectionHistory)): ?>
                    <ul class="list-group list-group-flush timeline-list">
                        <?php foreach ($sectionHistory as $entry): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between flex-wrap">
                                    <strong><?php echo htmlspecialchars($entry->section_title ?: $entry->section_label ?: $entry->section_key); ?></strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($entry->created_at); ?></span>
                                </div>
                                <div>
                                    <a href="<?php echo URLROOT; ?>/procedures/version/<?php echo (int) $entry->procedure_version_id; ?>">
                                        <?php echo htmlspecialchars($entry->version_number ?: 'Unknown version'); ?>
                                    </a>
                                    <span class="text-muted">| <?php echo htmlspecialchars($entry->change_type ?: 'Unknown'); ?></span>
                                </div>
                                <?php if (!empty($entry->change_summary)): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($entry->change_summary); ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No structured section lineage has been recorded for this procedure yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
