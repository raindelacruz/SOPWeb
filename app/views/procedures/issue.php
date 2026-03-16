<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container mt-4">
    <?php flash('csrf_error'); ?>

    <div class="page-hero">
        <span class="badge badge-light px-3 py-2 mb-3"><?php echo htmlspecialchars($data['procedure']->procedure_code ?? ''); ?></span>
        <h2>Register Revision</h2>
        <p>Register an already-approved external revision, reference, or superseding procedure for this SOP.</p>
    </div>

    <div class="card surface-card">
        <div class="card-body">
            <?php if (!empty($data['pdms_err'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($data['pdms_err']); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo URLROOT; ?>/procedures/issue/<?php echo (int) ($data['procedure']->id ?? 0); ?>" method="post" data-pdms-authoring-form data-authoring-rules="<?php echo htmlspecialchars(json_encode(PdmsAuthoringOptions::pdmsAuthoringUiRules()), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo csrf_input(); ?>

                <div class="form-group">
                    <label class="font-weight-bold" for="title">Version Title</label>
                    <input type="text" name="title" class="form-control <?php echo (!empty($data['title_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['title']); ?>">
                    <span class="invalid-feedback"><?php echo $data['title_err']; ?></span>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold" for="description">Version Description</label>
                    <textarea name="description" class="form-control <?php echo (!empty($data['description_err'])) ? 'is-invalid' : ''; ?>" rows="4"><?php echo htmlspecialchars($data['description']); ?></textarea>
                    <span class="invalid-feedback"><?php echo $data['description_err']; ?></span>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold" for="responsibility_center">Responsibility Center</label>
                        <select name="responsibility_center" class="form-control">
                            <option value="">Select responsibility center</option>
                            <?php foreach (($data['responsibility_center_options'] ?? []) as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo (($data['responsibility_center'] ?? '') === $option) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="border rounded p-3 mb-4">
                    <h5 class="font-weight-bold mb-3">Revision Details</h5>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold" for="document_number">Document Number</label>
                            <input type="text" name="document_number" class="form-control <?php echo (!empty($data['document_number_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['document_number']); ?>">
                            <span class="invalid-feedback"><?php echo $data['document_number_err']; ?></span>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold" for="change_type">Change Type</label>
                            <select name="change_type" class="form-control <?php echo (!empty($data['change_type_err'])) ? 'is-invalid' : ''; ?>">
                                <?php foreach (($data['options']['change_types'] ?? []) as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($data['change_type'] === $option) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['change_type_err']; ?></span>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold" for="status">Registry State</label>
                            <select name="status" class="form-control <?php echo (!empty($data['status_err'])) ? 'is-invalid' : ''; ?>">
                                <?php foreach (($data['options']['workflow_statuses'] ?? []) as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($data['status'] === $option) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['status_err']; ?></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" for="effective_date">Effectivity Date</label>
                            <input type="date" name="effective_date" class="form-control <?php echo (!empty($data['effective_date_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['effective_date']); ?>">
                            <span class="invalid-feedback"><?php echo $data['effective_date_err']; ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" for="file">PDF File Path</label>
                            <div class="input-group pdf-picker-field" data-pdf-picker data-picker-url="<?php echo URLROOT; ?>/procedures/pdfCatalog">
                                <input type="text" name="file" class="form-control <?php echo (!empty($data['file_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['file']); ?>" placeholder="Choose a PDF file" readonly data-pdf-path-input>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" data-pdf-picker-open>Locate File</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Choose from the configured server PDF folders.</small>
                            <span class="invalid-feedback d-block"><?php echo $data['file_err']; ?></span>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold" for="summary_of_change">Summary of Change</label>
                        <textarea name="summary_of_change" class="form-control" rows="3"><?php echo htmlspecialchars($data['summary_of_change']); ?></textarea>
                    </div>
                </div>

                <div class="border rounded p-3">
                    <h5 class="font-weight-bold mb-3">Relationship Mapping</h5>
                    <div class="alert alert-light border small" data-pdms-authoring-helper>
                        Optional relationship mode: choose a target version only when this registry record should link to another current procedure. Amendments require affected sections.
                    </div>
                    <p class="text-muted small mb-3">Amendments and revisions normally point to this procedure's current controlling version. Superseding registrations should point to the other active current procedure being replaced.</p>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" for="target_version_id">Target Version</label>
                            <select name="target_version_id" class="form-control <?php echo (!empty($data['target_version_id_err'])) ? 'is-invalid' : ''; ?>">
                                <option value="">Auto / none</option>
                                <?php foreach (($data['options']['targets'] ?? []) as $target): ?>
                                    <?php if (!empty($target->current_version_id)): ?>
                                        <option value="<?php echo (int) $target->current_version_id; ?>" <?php echo ((string) $data['target_version_id'] === (string) $target->current_version_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($target->procedure_code . ' - ' . $target->title . ' (' . ($target->current_version_number ?: 'No version') . ')'); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['target_version_id_err']; ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" for="relationship_type">Relationship Type</label>
                            <select name="relationship_type" class="form-control <?php echo (!empty($data['relationship_type_err'])) ? 'is-invalid' : ''; ?>">
                                <option value="">Auto-map from change type</option>
                                <?php foreach (($data['options']['relationship_types'] ?? []) as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($data['relationship_type'] === $option) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['relationship_type_err']; ?></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" for="affected_sections">Affected Sections</label>
                            <input type="text" name="affected_sections" class="form-control <?php echo (!empty($data['affected_sections_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['affected_sections']); ?>" placeholder="Example: Sections 2.1, 4.3">
                            <span class="invalid-feedback"><?php echo $data['affected_sections_err']; ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" for="relationship_remarks">Relationship Remarks</label>
                            <input type="text" name="relationship_remarks" class="form-control" value="<?php echo htmlspecialchars($data['relationship_remarks']); ?>" placeholder="Optional context for this revision registration">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) ($data['procedure']->id ?? 0); ?>" class="btn btn-outline-secondary">Cancel</a>
                    <input type="submit" class="btn btn-primary px-4" value="Register Revision">
                </div>
            </form>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
