<?php require APPROOT . '/app/views/includes/header.php'; ?>
<?php
    $encodePostId = function ($id) {
        return rtrim(strtr(base64_encode((string) $id), '+/', '-_'), '=');
    };
    $editLock = $data['edit_lock'] ?? ['is_locked' => false, 'status' => '', 'message' => ''];
    $pdmsContext = $data['pdmsContext'] ?? [];
    $pdmsSource = $pdmsContext['source'] ?? 'legacy';
    $procedureOverview = $pdmsContext['procedure_overview'] ?? null;
    $procedureHistory = $pdmsContext['history'] ?? [];
    $normalizedRelationships = $pdmsContext['relationships'] ?? [];
    $workflowActions = $pdmsContext['workflow_actions'] ?? [];
    $sectionHistory = $pdmsContext['section_history'] ?? [];
    $legacySnapshot = $pdmsContext['legacy_snapshot'] ?? null;
    $legacyRelationships = $pdmsContext['legacy_relationships'] ?? [];
    $legacyTimeline = $pdmsContext['legacy_timeline'] ?? [];
    $statusLabel = $procedureOverview->current_version_status ?? ($legacySnapshot->inferred_change_type ?? 'LEGACY');
    $isPdmsMapped = $pdmsSource === 'pdms' && !empty($procedureOverview);
    $isMappedCurrentVersion = $isPdmsMapped
        && !empty($procedureOverview->mapped_version_id)
        && !empty($procedureOverview->current_version_id)
        && (int) $procedureOverview->mapped_version_id === (int) $procedureOverview->current_version_id;
    $statusClass = 'secondary';

    if (in_array($statusLabel, ['EFFECTIVE', 'ACTIVE', 'NEW'], true)) {
        $statusClass = 'success';
    } elseif (in_array($statusLabel, ['REGISTERED', 'AMENDMENT'], true)) {
        $statusClass = 'primary';
    } elseif (in_array($statusLabel, ['SUPERSEDED', 'SUPERSEDING_PROCEDURE'], true)) {
        $statusClass = 'danger';
    }
?>

<div class="container mt-5">
    <?php flash('csrf_error'); ?>
    <div class="page-hero">
        <span class="badge badge-light px-3 py-2 mb-3"><?php echo htmlspecialchars($data['post']->reference_number); ?></span>
        <span class="badge badge-<?php echo $statusClass; ?> px-3 py-2 mb-3 ml-2"><?php echo htmlspecialchars($statusLabel); ?></span>
        <h2><?php echo htmlspecialchars($data['post']->title); ?></h2>
        <p><?php echo htmlspecialchars($data['post']->description); ?></p>
    </div>

    <div class="card surface-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap mb-4">
                <a href="<?php echo URLROOT; ?>/posts" class="btn btn-outline-secondary mr-2 mb-2">Back to Posts</a>
                <?php if (!empty($data['post']->file)): ?>
                    <a href="<?php echo URLROOT; ?>../uploads/<?php echo rawurlencode($data['post']->file); ?>" target="_blank" class="btn btn-info mr-2 mb-2">Download SOP</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true)): ?>
                    <?php if ($isPdmsMapped): ?>
                        <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $procedureOverview->id; ?>" class="btn btn-outline-primary mr-2 mb-2">Open PDMS Procedure</a>
                        <?php if (!empty($procedureOverview->mapped_version_id)): ?>
                            <a href="<?php echo URLROOT; ?>/procedures/version/<?php echo (int) $procedureOverview->mapped_version_id; ?>" class="btn btn-outline-info mr-2 mb-2">Open PDMS Version</a>
                        <?php endif; ?>
                        <?php if ($isMappedCurrentVersion && !in_array(($procedureOverview->status ?? ''), ['SUPERSEDED', 'RESCINDED', 'ARCHIVED'], true)): ?>
                            <a href="<?php echo URLROOT; ?>/procedures/edit/<?php echo (int) $procedureOverview->id; ?>" class="btn btn-primary mb-2">Manage in PDMS</a>
                        <?php endif; ?>
                    <?php elseif (empty($editLock['is_locked'])): ?>
                        <a href="<?php echo URLROOT; ?>/procedures/create" class="btn btn-primary mr-2 mb-2">Create PDMS Procedure</a>
                        <a href="<?php echo URLROOT; ?>/posts/edit/<?php echo $data['post']->id; ?>?legacy_compatibility_intent=1" class="btn btn-outline-warning mr-2 mb-2">Legacy Edit (Compatibility Only)</a>
                    <?php else: ?>
                        <span class="btn btn-outline-secondary disabled mr-2 mb-2" aria-disabled="true">Edit Locked</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($editLock['is_locked'])): ?>
                <div class="alert alert-warning mb-4">
                    <?php echo htmlspecialchars($editLock['message']); ?>
                </div>
            <?php endif; ?>

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">Reference Number</span>
                    <span><?php echo htmlspecialchars($data['post']->reference_number); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Effectivity</span>
                    <span><?php echo htmlspecialchars($data['post']->date_of_effectivity); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Upload Date</span>
                    <span><?php echo htmlspecialchars($data['post']->upload_date); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($data['post']->file)): ?>
        <div class="embed-wrap mb-4">
            <div class="embed-responsive embed-responsive-16by9">
                <embed src="<?php echo URLROOT; ?>../uploads/<?php echo rawurlencode($data['post']->file); ?>" type="application/pdf" class="embed-responsive-item">
            </div>
        </div>
    <?php else: ?>
        <p class="alert alert-warning">No file uploaded.</p>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="list-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div>
                        <h4 class="h5 font-weight-bold mb-1">PDMS Readiness View</h4>
                        <p class="text-muted mb-0">
                            <?php if ($pdmsSource === 'pdms'): ?>
                                This SOP is already mapped into the new procedural document model and should now be managed from the PDMS procedure screens.
                            <?php else: ?>
                                This SOP is still being interpreted from legacy post data while PDMS migration is in progress. Use the PDMS procedures area for new revision registrations, and treat legacy edit as a temporary compatibility path.
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="badge badge-<?php echo $pdmsSource === 'pdms' ? 'success' : 'secondary'; ?> px-3 py-2 mt-2 mt-sm-0">
                        <?php echo $pdmsSource === 'pdms' ? 'PDMS mapped' : 'Legacy fallback'; ?>
                    </span>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <p class="mb-1"><strong>Procedure Code:</strong></p>
                        <p class="mb-0"><?php echo htmlspecialchars($procedureOverview->procedure_code ?? $legacySnapshot->procedure_code ?? $data['post']->reference_number); ?></p>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <p class="mb-1"><strong>Current Version:</strong></p>
                        <p class="mb-0"><?php echo htmlspecialchars($procedureOverview->current_version_number ?? 'Legacy record'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Controlling Status:</strong></p>
                        <p class="mb-0"><?php echo htmlspecialchars($procedureOverview->current_version_status ?? 'Unmapped legacy status'); ?></p>
                    </div>
                </div>

                <?php if (($procedureOverview->current_version_status ?? '') === 'REGISTERED'): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        The PDMS model already tracks this SOP, but the mapped procedure is still only registered and not yet marked effective.
                    </div>
                <?php endif; ?>

                <?php if ($pdmsSource !== 'pdms' && isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true) && empty($editLock['is_locked'])): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        This SOP has not been registered as a PDMS-managed procedure yet. Create or manage the current controlling procedure from the PDMS area first. Use legacy edit only when you need to preserve compatibility data before migration catches up.
                    </div>
                <?php endif; ?>

                <hr>

                <?php if ($pdmsSource === 'pdms'): ?>
                    <div class="row">
                        <div class="col-lg-3 mb-4 mb-lg-0">
                            <h5 class="h6 font-weight-bold">Version History</h5>
                            <?php if (!empty($procedureHistory)): ?>
                                <ul class="list-group list-group-flush timeline-list">
                                    <?php foreach ($procedureHistory as $historyItem): ?>
                                        <li class="list-group-item px-0">
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <strong>
                                                    <a href="<?php echo URLROOT; ?>/procedures/version/<?php echo (int) $historyItem->id; ?>">
                                                        <?php echo htmlspecialchars($historyItem->version_number); ?>
                                                    </a>
                                                </strong>
                                                <span class="text-muted"><?php echo htmlspecialchars($historyItem->effective_date ?: 'No effectivity date'); ?></span>
                                            </div>
                                            <div><?php echo htmlspecialchars($historyItem->title); ?></div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($historyItem->change_type); ?> | <?php echo htmlspecialchars($historyItem->status); ?>
                                            </small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No PDMS version history is available yet for this procedure.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-3 mb-4 mb-lg-0">
                            <h5 class="h6 font-weight-bold">Normalized Relationships</h5>
                            <?php if (!empty($normalizedRelationships)): ?>
                                <ul class="list-group list-group-flush timeline-list">
                                    <?php foreach ($normalizedRelationships as $relationship): ?>
                                        <li class="list-group-item px-0">
                                            <strong><?php echo htmlspecialchars($relationship->relationship_type); ?></strong>
                                            <div class="text-muted">
                                                <?php echo htmlspecialchars(($relationship->source_version_number ?? '-') . ' -> ' . ($relationship->target_version_number ?? '-')); ?>
                                            </div>
                                            <div>
                                                <?php echo htmlspecialchars(($relationship->source_title ?? 'Unknown source') . ' / ' . ($relationship->target_title ?? 'Unknown target')); ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">This mapped procedure does not have normalized relationship records yet.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-3 mb-4 mb-lg-0">
                            <h5 class="h6 font-weight-bold">Lifecycle Trail</h5>
                            <?php if (!empty($workflowActions)): ?>
                                <ul class="list-group list-group-flush timeline-list">
                                    <?php foreach ($workflowActions as $action): ?>
                                        <li class="list-group-item px-0">
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <strong><?php echo htmlspecialchars($action->lifecycle_action_type); ?></strong>
                                                <span class="text-muted"><?php echo htmlspecialchars($action->acted_at); ?></span>
                                            </div>
                                            <div>
                                                <?php echo htmlspecialchars(($action->from_status ?: 'None') . ' -> ' . ($action->to_status ?: 'None')); ?>
                                            </div>
                                            <?php if (!empty($action->remarks)): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($action->remarks); ?></small>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No PDMS lifecycle actions have been recorded for the current version yet.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-3">
                            <h5 class="h6 font-weight-bold">Section Lineage</h5>
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
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No structured section lineage has been recorded for this mapped procedure yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-lg-4 mb-4 mb-lg-0">
                            <h5 class="h6 font-weight-bold">Legacy-Inferred Change Type</h5>
                            <p class="mb-0"><?php echo htmlspecialchars($legacySnapshot->inferred_change_type ?? 'NEW'); ?></p>
                        </div>
                        <div class="col-lg-4 mb-4 mb-lg-0">
                            <h5 class="h6 font-weight-bold">Normalized Legacy Relationship View</h5>
                            <?php if (!empty($legacyRelationships)): ?>
                                <ul class="list-group list-group-flush timeline-list">
                                    <?php foreach ($legacyRelationships as $relationship): ?>
                                        <li class="list-group-item px-0">
                                            <strong><?php echo htmlspecialchars($relationship->relationship_type); ?></strong>
                                            <?php if ((int) $relationship->id !== (int) $data['post']->id): ?>
                                                <div>
                                                    <a href="<?php echo URLROOT; ?>/posts/show/<?php echo $encodePostId($relationship->id); ?>">
                                                        <?php echo htmlspecialchars($relationship->title); ?>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div><?php echo htmlspecialchars($relationship->title); ?></div>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($relationship->reference_number); ?> | <?php echo htmlspecialchars($relationship->date_of_effectivity); ?>
                                            </small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No legacy relationships were found for this SOP.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-4">
                            <h5 class="h6 font-weight-bold">Legacy Timeline</h5>
                            <?php if (!empty($legacyTimeline)): ?>
                                <ul class="list-group list-group-flush timeline-list">
                                    <?php foreach ($legacyTimeline as $timelineItem): ?>
                                        <li class="list-group-item px-0">
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <strong><?php echo htmlspecialchars($timelineItem->label); ?></strong>
                                                <span class="text-muted"><?php echo htmlspecialchars($timelineItem->date ?: 'No date'); ?></span>
                                            </div>
                                            <div><?php echo htmlspecialchars($timelineItem->title); ?></div>
                                            <?php if (!empty($timelineItem->note)): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($timelineItem->note); ?></small>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No legacy timeline entries are available for this SOP.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="list-card h-100">
                <h4 class="h5 font-weight-bold mb-2"><?php echo $isPdmsMapped ? 'Legacy Mirror Relationship Snapshot' : 'Legacy Relationship Snapshot'; ?></h4>
                <p class="text-muted small mb-3">
                    <?php if ($isPdmsMapped): ?>
                        These links come from the legacy mirror fields and remain available for compatibility tracing. Use the normalized PDMS relationships above as the primary lineage view.
                    <?php else: ?>
                        These links come from the legacy relationship fields while this SOP is still being interpreted outside the full PDMS model.
                    <?php endif; ?>
                </p>
                <?php if (!empty($data['amendedPost'])): ?>
                    <p class="mb-3"><strong>Amends:</strong> <a href="<?php echo URLROOT; ?>/posts/show/<?php echo $encodePostId($data['amendedPost']->id); ?>"><?php echo htmlspecialchars($data['amendedPost']->title); ?></a></p>
                <?php endif; ?>
                <?php if (!empty($data['supersededPost'])): ?>
                    <p class="mb-0"><strong>Supersedes:</strong> <a href="<?php echo URLROOT; ?>/posts/show/<?php echo $encodePostId($data['supersededPost']->id); ?>"><?php echo htmlspecialchars($data['supersededPost']->title); ?></a></p>
                <?php endif; ?>
                <?php if (empty($data['amendedPost']) && empty($data['supersededPost'])): ?>
                    <p class="text-muted mb-0">No legacy mirror amendment or supersession links are recorded for this SOP.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="list-card h-100">
                <h4 class="h5 font-weight-bold mb-2"><?php echo $isPdmsMapped ? 'Legacy Mirror Referencing Posts' : 'Legacy Referencing Posts'; ?></h4>
                <p class="text-muted small mb-3">
                    <?php if ($isPdmsMapped): ?>
                        These are legacy post records that still point to this SOP through compatibility fields. They can help with audits, but they do not replace the normalized PDMS history and relationship views above.
                    <?php else: ?>
                        These follow-on legacy posts still reference this SOP through amendment or supersession fields.
                    <?php endif; ?>
                </p>
                <?php if (!empty($data['amendingPosts'])): ?>
                    <p class="mb-2 font-weight-bold">Amending Legacy Posts</p>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($data['amendingPosts'] as $amendingPost): ?>
                            <li class="list-group-item px-0">
                                <a href="<?php echo URLROOT; ?>/posts/show/<?php echo $encodePostId($amendingPost->id); ?>"><?php echo htmlspecialchars($amendingPost->title); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($data['supersedingPosts'])): ?>
                    <p class="mb-2 font-weight-bold">Superseding Legacy Posts</p>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($data['supersedingPosts'] as $supersedingPost): ?>
                            <li class="list-group-item px-0">
                                <a href="<?php echo URLROOT; ?>/posts/show/<?php echo $encodePostId($supersedingPost->id); ?>"><?php echo htmlspecialchars($supersedingPost->title); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (empty($data['amendingPosts']) && empty($data['supersedingPosts'])): ?>
                    <p class="text-muted mb-0">No legacy posts currently reference this SOP through amendment or supersession fields.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
