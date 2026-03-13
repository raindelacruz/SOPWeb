<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container mt-4">
    <?php flash('csrf_error'); ?>

    <div class="page-hero">
        <span class="badge badge-light px-3 py-2 mb-3"><?php echo htmlspecialchars($data['procedure']->procedure_code ?? ''); ?></span>
        <h2>Edit Procedure</h2>
        <p>Update the procedure master metadata and the currently controlling version details without falling back to the legacy SOP form.</p>
    </div>

    <div class="card surface-card">
        <div class="card-body">
            <?php if (!empty($data['pdms_err'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($data['pdms_err']); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo URLROOT; ?>/procedures/edit/<?php echo (int) ($data['procedure']->id ?? 0); ?>" method="post" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Procedure Code</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['procedure']->procedure_code ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Current Version</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['procedure']->current_version_number ?? ''); ?>" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold" for="title">Title</label>
                    <input type="text" name="title" class="form-control <?php echo (!empty($data['title_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['title']); ?>">
                    <span class="invalid-feedback"><?php echo $data['title_err']; ?></span>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold" for="description">Description</label>
                    <textarea name="description" class="form-control <?php echo (!empty($data['description_err'])) ? 'is-invalid' : ''; ?>" rows="4"><?php echo htmlspecialchars($data['description']); ?></textarea>
                    <span class="invalid-feedback"><?php echo $data['description_err']; ?></span>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold" for="category">Category</label>
                        <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($data['category']); ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold" for="owner_office">Owner Office</label>
                        <input type="text" name="owner_office" class="form-control" value="<?php echo htmlspecialchars($data['owner_office']); ?>">
                    </div>
                </div>

                <div class="border rounded p-3">
                    <h5 class="font-weight-bold mb-3">Current Version Details</h5>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" for="document_number">Document Number</label>
                            <input type="text" name="document_number" class="form-control <?php echo (!empty($data['document_number_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['document_number']); ?>">
                            <span class="invalid-feedback"><?php echo $data['document_number_err']; ?></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" for="effective_date">Effectivity Date</label>
                            <input type="date" name="effective_date" class="form-control <?php echo (!empty($data['effective_date_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['effective_date']); ?>">
                            <span class="invalid-feedback"><?php echo $data['effective_date_err']; ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold" for="summary_of_change">Summary of Change</label>
                        <textarea name="summary_of_change" class="form-control" rows="3"><?php echo htmlspecialchars($data['summary_of_change']); ?></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold" for="file">Replace Current PDF</label>
                        <input type="file" name="file" class="form-control-file <?php echo (!empty($data['file_err'])) ? 'is-invalid' : ''; ?>" accept=".pdf">
                        <input type="hidden" name="existing_file" value="<?php echo htmlspecialchars($data['file']); ?>">
                        <span class="invalid-feedback d-block"><?php echo $data['file_err']; ?></span>

                        <?php if (!empty($data['file'])): ?>
                            <p class="mt-2 mb-0">
                                Current File:
                                <a href="<?php echo URLROOT; ?>../uploads/<?php echo rawurlencode($data['file']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($data['file']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) ($data['procedure']->id ?? 0); ?>" class="btn btn-outline-secondary">Cancel</a>
                    <input type="submit" class="btn btn-primary px-4" value="Save Procedure">
                </div>
            </form>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
