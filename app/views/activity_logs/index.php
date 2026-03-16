<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container mt-4">
    <?php flash('csrf_error'); ?>

    <div class="page-hero">
        <h2>Activity Logs</h2>
        <p>Review recent system actions, filter by keyword, and monitor administrative changes across the platform.</p>
    </div>

    <div class="card surface-card mb-4">
        <div class="card-body">
            <form action="<?php echo URLROOT; ?>/activityLogs/search" method="post" class="form-row align-items-end">
                <?php echo csrf_input(); ?>
                <div class="form-group col-lg-10 mb-lg-0">
                    <label class="font-weight-bold">Search logs</label>
                    <input type="text" class="form-control" name="keyword" placeholder="Search action, user, description, or date" value="<?php echo htmlspecialchars($data['keyword'] ?? ''); ?>">
                </div>
                <div class="form-group col-lg-2 mb-0">
                    <button type="submit" class="btn btn-primary btn-block">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card surface-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($data['logs']) && is_array($data['logs'])): ?>
                            <?php foreach ($data['logs'] as $log) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log->firstname . ' ' . $log->lastname); ?></td>
                                    <td><?php echo htmlspecialchars($log->action); ?></td>
                                    <td><?php echo htmlspecialchars($log->description); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No activity logs available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
