<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container mt-4">
    <?php flash('csrf_error'); ?>

    <div class="page-hero">
        <h2>User Management</h2>
        <p>Filter accounts by office, status, or role, then activate users and manage access from a single admin dashboard.</p>
    </div>

    <div class="card surface-card mb-4">
        <div class="card-body">
            <form action="<?php echo URLROOT; ?>/users/manage" method="get" class="form-row align-items-end">
                <div class="form-group col-lg-4">
                    <label class="font-weight-bold" for="office">Office</label>
                    <select name="office" id="office" class="form-control">
                        <option value="">All Offices</option>
                        <?php foreach (($data['offices'] ?? []) as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo (isset($data['office']) && $data['office'] == $value) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3 col-lg-2">
                    <label class="font-weight-bold" for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo (isset($data['status']) && $data['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (isset($data['status']) && $data['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group col-md-3 col-lg-2">
                    <label class="font-weight-bold" for="role">Role</label>
                    <select name="role" id="role" class="form-control">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo (isset($data['role']) && $data['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo (isset($data['role']) && $data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="super_admin" <?php echo (isset($data['role']) && $data['role'] == 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>
                <div class="form-group col-md-6 col-lg-2">
                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                </div>
            </form>

            <form action="<?php echo URLROOT; ?>/users/search" method="post" class="form-row align-items-end mt-2">
                <?php echo csrf_input(); ?>
                <div class="form-group col-lg-10 mb-lg-0">
                    <label class="font-weight-bold">Search users</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Search by name, email, or ID number" value="<?php echo htmlspecialchars($data['keyword'] ?? ''); ?>">
                </div>
                <div class="form-group col-lg-2 mb-0">
                    <button type="submit" class="btn btn-outline-primary btn-block">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card surface-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th class="d-none d-sm-table-cell">Office</th>
                            <th class="d-none d-md-table-cell">Email</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="d-none d-md-table-cell">Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['users'] as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user->id_number); ?></td>
                                <td><?php echo htmlspecialchars($user->firstname . ' ' . $user->lastname); ?></td>
                                <td class="d-none d-sm-table-cell"><?php echo htmlspecialchars($user->office); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($user->email); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars(ucfirst($user->status)); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars(ucfirst($user->role)); ?></td>
                                <td>
                                    <?php if ($user->status == 'active'): ?>
                                        <form action="<?php echo URLROOT; ?>/users/deactivate/<?php echo $user->id; ?>" method="post" class="d-inline">
                                            <?php echo csrf_input(); ?>
                                            <button type="submit" class="btn btn-warning btn-sm mb-1">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?php echo URLROOT; ?>/users/activate/<?php echo $user->id; ?>" method="post" class="d-inline">
                                            <?php echo csrf_input(); ?>
                                            <button type="submit" class="btn btn-success btn-sm mb-1">Activate</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($user->role == 'admin'): ?>
                                        <form action="<?php echo URLROOT; ?>/users/changeRole/<?php echo $user->id; ?>/user" method="post" class="d-inline">
                                            <?php echo csrf_input(); ?>
                                            <button type="submit" class="btn btn-secondary btn-sm mb-1">Set as User</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?php echo URLROOT; ?>/users/changeRole/<?php echo $user->id; ?>/admin" method="post" class="d-inline">
                                            <?php echo csrf_input(); ?>
                                            <button type="submit" class="btn btn-primary btn-sm mb-1">Set as Admin</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($data['current_page'] <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $data['current_page'] - 1; ?>&office=<?php echo urlencode($data['office'] ?? ''); ?>&status=<?php echo urlencode($data['status'] ?? ''); ?>&role=<?php echo urlencode($data['role'] ?? ''); ?>">Previous</a>
            </li>

            <?php for ($i = 1; $i <= $data['total_pages']; $i++): ?>
                <li class="page-item <?php echo ($i == $data['current_page']) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&office=<?php echo urlencode($data['office'] ?? ''); ?>&status=<?php echo urlencode($data['status'] ?? ''); ?>&role=<?php echo urlencode($data['role'] ?? ''); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?php echo ($data['current_page'] >= $data['total_pages']) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $data['current_page'] + 1; ?>&office=<?php echo urlencode($data['office'] ?? ''); ?>&status=<?php echo urlencode($data['status'] ?? ''); ?>&role=<?php echo urlencode($data['role'] ?? ''); ?>">Next</a>
            </li>
        </ul>
    </nav>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
