<?php require APPROOT . '/app/views/includes/header.php'; ?>
<?php $procedure = $data['procedure']; ?>

<div class="container mt-4">
    <div class="page-hero">
        <span class="badge badge-light px-3 py-2 mb-3"><?php echo htmlspecialchars($procedure->procedure_code); ?></span>
        <span class="badge badge-danger px-3 py-2 mb-3 ml-2">Rescission</span>
        <h2>Rescind Procedure</h2>
        <p>
            This will mark the current controlling version as rescinded and move the entire procedure into a terminal historical state.
        </p>
    </div>

    <div class="card surface-card">
        <div class="card-body">
            <div class="mb-4">
                <p class="mb-1"><strong>Procedure:</strong> <?php echo htmlspecialchars($procedure->title); ?></p>
                <p class="mb-1"><strong>Current Version:</strong> <?php echo htmlspecialchars($procedure->current_version_number ?: 'Unknown'); ?></p>
                <p class="mb-0"><strong>Current Status:</strong> <?php echo htmlspecialchars($procedure->current_version_status ?: 'Unknown'); ?></p>
            </div>

            <?php if (!empty($data['pdms_err'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($data['pdms_err']); ?></div>
            <?php endif; ?>

            <div class="alert alert-warning">
                Rescission is intended for procedures that are being formally withdrawn. After rescission, the procedure becomes historical and cannot be edited or receive further revision registrations.
            </div>

            <form action="<?php echo URLROOT; ?>/procedures/rescind/<?php echo (int) $procedure->id; ?>" method="post">
                <?php echo csrf_input(); ?>
                <div class="form-group">
                    <label for="remarks">Rescission Reason / Note</label>
                    <textarea
                        name="remarks"
                        id="remarks"
                        rows="4"
                        class="form-control <?php echo !empty($data['remarks_err']) ? 'is-invalid' : ''; ?>"
                    ><?php echo htmlspecialchars($data['remarks']); ?></textarea>
                    <span class="invalid-feedback"><?php echo htmlspecialchars($data['remarks_err']); ?></span>
                </div>

                <div class="d-flex flex-wrap">
                    <a href="<?php echo URLROOT; ?>/procedures/show/<?php echo (int) $procedure->id; ?>" class="btn btn-outline-secondary mr-2 mb-2">Cancel</a>
                    <button type="submit" class="btn btn-danger mb-2">Confirm Rescission</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
