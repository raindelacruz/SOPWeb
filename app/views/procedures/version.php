<?php require APPROOT . '/app/views/includes/header.php'; ?>
<?php
    $version = $data['version'];
    $relationships = $data['relationships'] ?? [];
    $workflowActions = $data['workflow_actions'] ?? [];
    $history = $data['history'] ?? [];
    $sectionHistory = $data['section_history'] ?? [];
    $canArchive = $data['can_archive'] ?? false;
    $isHistoricalProcedure = in_array(($version->procedure_status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true);
    $status = $version->status ?? 'UNKNOWN';
    $statusClass = 'secondary';

    if ($status === 'EFFECTIVE') {
        $statusClass = 'success';
    } elseif ($status === 'REGISTERED') {
        $statusClass = 'primary';
    } elseif (in_array($status, ['SUPERSEDED', 'RESCINDED'], true)) {
        $statusClass = 'danger';
    }
?>

<div class="container mt-4">
    <?php flash('procedures_backfill'); ?>
    <div class="page-hero">
        <span class="badge badge-light px-3 py-2 mb-3"><?php echo htmlspecialchars($version->procedure_code); ?></span>
        <span class="badge badge-<?php echo $statusClass; ?> px-3 py-2 mb-3 ml-2"><?php echo htmlspecialchars($status); ?></span>
        <h2><?php echo htmlspecialchars($version->title); ?></h2>
        <p>
            Version <?php echo htmlspecialchars($version->version_number ?: 'Unknown'); ?>
            <?php if (!empty($version->document_number)): ?>
                | Document No. <?php echo htmlspecialchars($version->document_number); ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="card surface-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap mb-4">
                <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $version->procedure_id; ?>" class="btn btn-outline-secondary mr-2 mb-2">Back to Procedure</a>
                <?php if (!empty($version->file_path)): ?>
                    <a href="<?php echo URLROOT; ?>../uploads/<?php echo rawurlencode($version->file_path); ?>" target="_blank" class="btn btn-info mr-2 mb-2">Open Version PDF</a>
                <?php endif; ?>
                <?php if ((int) ($version->current_version_id ?? 0) === (int) $version->id): ?>
                    <span class="btn btn-outline-success disabled mb-2" aria-disabled="true">Current Controlling Version</span>
                <?php endif; ?>
                <?php if ($canArchive): ?>
                    <form action="<?php echo URLROOT; ?>/procedures/archiveVersion/<?php echo (int) $version->id; ?>" method="post" class="mb-2">
                        <?php echo csrf_input(); ?>
                        <button type="submit" class="btn btn-outline-danger">Archive Historical Version</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">Procedure Status</span>
                    <span><?php echo htmlspecialchars($version->procedure_status ?: 'Unknown'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Change Type</span>
                    <span><?php echo htmlspecialchars($version->change_type ?: 'Not set'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Effectivity</span>
                    <span><?php echo htmlspecialchars($version->effective_date ?: 'No effectivity date'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><?php echo $isHistoricalProcedure ? 'Current Controlling Version' : 'Current Controlling Version'; ?></span>
                    <span><?php echo htmlspecialchars($version->current_version_number ?: 'None'); ?></span>
                </div>
            </div>

            <?php if ($isHistoricalProcedure && empty($version->current_version_id)): ?>
                <div class="alert alert-secondary mt-4 mb-0">
                    This procedure is historical and no longer has a controlling-version pointer. Review this version as part of the historical record rather than as an active controlling document.
                </div>
            <?php endif; ?>

            <?php if (!empty($version->summary_of_change)): ?>
                <hr>
                <h4 class="h6 font-weight-bold">Summary of Change</h4>
                <p class="mb-0"><?php echo htmlspecialchars($version->summary_of_change); ?></p>
            <?php endif; ?>

            <?php if ($canArchive): ?>
                <div class="alert alert-warning mt-4 mb-0">
                    This version is no longer controlling. Archiving is only available for historical versions already marked superseded or rescinded.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="list-card h-100">
                <h4 class="h5 font-weight-bold mb-3">Version Relationships</h4>
                <?php if (!empty($relationships)): ?>
                    <ul class="list-group list-group-flush timeline-list">
                        <?php foreach ($relationships as $relationship): ?>
                            <li class="list-group-item px-0">
                                <strong><?php echo htmlspecialchars($relationship->relationship_type); ?></strong>
                                <div><?php echo htmlspecialchars(($relationship->source_title ?? 'Unknown source') . ' -> ' . ($relationship->target_title ?? 'Unknown target')); ?></div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(($relationship->source_version_number ?? '-') . ' -> ' . ($relationship->target_version_number ?? '-')); ?>
                                </small>
                                <?php if (!empty($relationship->affected_sections)): ?>
                                    <div><small class="text-muted">Affected sections: <?php echo htmlspecialchars($relationship->affected_sections); ?></small></div>
                                <?php endif; ?>
                                <?php if (!empty($relationship->remarks)): ?>
                                    <div><small class="text-muted"><?php echo htmlspecialchars($relationship->remarks); ?></small></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No normalized relationships are recorded for this version.</p>
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
                                    <strong><?php echo htmlspecialchars($action->lifecycle_action_type); ?></strong>
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
                    <p class="text-muted mb-0">No lifecycle actions are recorded for this version.</p>
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
                                <div><?php echo htmlspecialchars($entry->entry_kind ?: 'AFFECTED_SECTION'); ?> | <?php echo htmlspecialchars($entry->change_type ?: 'Unknown'); ?></div>
                                <?php if (!empty($entry->change_summary)): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($entry->change_summary); ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No structured section lineage is recorded for this version.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-3 mb-4">
            <div class="list-card h-100">
                <h4 class="h5 font-weight-bold mb-3">Procedure History</h4>
                <?php if (!empty($history)): ?>
                    <ul class="list-group list-group-flush timeline-list">
                        <?php foreach ($history as $historyItem): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between flex-wrap">
                                    <strong>
                                        <a href="<?php echo URLROOT; ?>/procedures/version/<?php echo (int) $historyItem->id; ?>">
                                            <?php echo htmlspecialchars($historyItem->version_number); ?>
                                        </a>
                                    </strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($historyItem->effective_date ?: 'No date'); ?></span>
                                </div>
                                <div><?php echo htmlspecialchars($historyItem->title); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($historyItem->change_type); ?> | <?php echo htmlspecialchars($historyItem->status); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No version history is available for this procedure.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
