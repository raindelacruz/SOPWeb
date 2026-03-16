<?php require APPROOT . '/app/views/includes/header.php'; ?>
<?php $procedure = $data['procedure']; ?>

<div class="container mt-4">
    <?php flash('csrf_error'); ?>

    <div class="page-hero">
        <span class="badge badge-light px-3 py-2 mb-3"><?php echo htmlspecialchars($procedure->procedure_code); ?></span>
        <span class="badge badge-danger px-3 py-2 mb-3 ml-2">Superseding Procedure</span>
        <h2>Register Superseding Procedure</h2>
        <p>
            Register a brand-new PDMS procedure that replaces the current controlling version of
            <?php echo htmlspecialchars($procedure->procedure_code); ?>.
        </p>
    </div>

    <div class="card surface-card">
        <div class="card-body">
            <?php if (!empty($data['pdms_err'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($data['pdms_err']); ?></div>
            <?php endif; ?>

            <div class="alert alert-warning">
                Completing this flow creates a new procedure and marks the current procedure as historical through a normalized <strong>SUPERSEDES</strong> relationship.
            </div>

            <form action="<?php echo URLROOT; ?>/procedures/supersede/<?php echo (int) $procedure->id; ?>" method="post">
                <?php echo csrf_input(); ?>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold" for="procedure_code">New Procedure Code</label>
                        <input type="text" name="procedure_code" class="form-control <?php echo !empty($data['procedure_code_err']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['procedure_code']); ?>">
                        <span class="invalid-feedback"><?php echo htmlspecialchars($data['procedure_code_err']); ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold" for="document_number">New Document Number</label>
                        <input type="text" name="document_number" class="form-control <?php echo !empty($data['document_number_err']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['document_number']); ?>">
                        <span class="invalid-feedback"><?php echo htmlspecialchars($data['document_number_err']); ?></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold" for="title">New Procedure Title</label>
                    <input type="text" name="title" class="form-control <?php echo !empty($data['title_err']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['title']); ?>">
                    <span class="invalid-feedback"><?php echo htmlspecialchars($data['title_err']); ?></span>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold" for="description">Description</label>
                    <textarea name="description" rows="4" class="form-control <?php echo !empty($data['description_err']) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($data['description']); ?></textarea>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($data['description_err']); ?></span>
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

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold" for="status">Registry State</label>
                        <select name="status" class="form-control <?php echo !empty($data['status_err']) ? 'is-invalid' : ''; ?>">
                            <?php foreach (['REGISTERED', 'EFFECTIVE'] as $statusOption): ?>
                                <option value="<?php echo htmlspecialchars($statusOption); ?>" <?php echo $data['status'] === $statusOption ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($statusOption); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback"><?php echo htmlspecialchars($data['status_err']); ?></span>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold" for="effective_date">Effectivity Date</label>
                        <input type="date" name="effective_date" class="form-control <?php echo !empty($data['effective_date_err']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['effective_date']); ?>">
                        <span class="invalid-feedback"><?php echo htmlspecialchars($data['effective_date_err']); ?></span>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold" for="file">PDF File Path</label>
                        <div class="input-group pdf-picker-field" data-pdf-picker data-picker-url="<?php echo URLROOT; ?>/procedures/pdfCatalog">
                            <input type="text" name="file" class="form-control <?php echo !empty($data['file_err']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['file']); ?>" placeholder="Choose a PDF file" readonly data-pdf-path-input>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" data-pdf-picker-open>Locate File</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Choose from the configured server PDF folders.</small>
                        <span class="invalid-feedback d-block"><?php echo htmlspecialchars($data['file_err']); ?></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold" for="summary_of_change">Summary of Change</label>
                    <textarea name="summary_of_change" rows="3" class="form-control"><?php echo htmlspecialchars($data['summary_of_change']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold" for="relationship_remarks">Supersession Note</label>
                    <textarea name="relationship_remarks" rows="3" class="form-control <?php echo !empty($data['relationship_remarks_err']) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($data['relationship_remarks']); ?></textarea>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($data['relationship_remarks_err']); ?></span>
                </div>

                <div class="border rounded p-3 mb-4">
                    <h5 class="font-weight-bold mb-3">Superseded Procedure</h5>
                    <p class="mb-1"><strong>Procedure Code:</strong> <?php echo htmlspecialchars($procedure->procedure_code); ?></p>
                    <p class="mb-1"><strong>Title:</strong> <?php echo htmlspecialchars($procedure->title); ?></p>
                    <p class="mb-0"><strong>Current Version:</strong> <?php echo htmlspecialchars($procedure->current_version_number ?: 'Unknown'); ?></p>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $procedure->id; ?>" class="btn btn-outline-secondary">Cancel</a>
                    <input type="submit" class="btn btn-danger px-4" value="Register Superseding Procedure">
                </div>
            </form>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
