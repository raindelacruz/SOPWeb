<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container mt-4">
    <?php flash('csrf_error'); ?>
    <?php flash('profile_message'); ?>

    <div class="page-hero">
        <h2>Manage Profile</h2>
        <p>Review your account details, update your office assignment, and change your password from one secure panel.</p>
    </div>

    <div class="card surface-card">
        <div class="card-body">
            <form action="<?php echo URLROOT; ?>/users/profile" method="POST">
                <?php echo csrf_input(); ?>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="id_number">ID Number</label>
                        <input type="text" name="id_number" class="form-control readonly-field" value="<?php echo htmlspecialchars($data['id_number']); ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input type="email" name="email" class="form-control readonly-field" value="<?php echo htmlspecialchars($data['email']); ?>" readonly>
                        <span class="invalid-feedback d-block"><?php echo $data['email_err'] ?? ''; ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="firstname">First Name</label>
                        <input type="text" name="firstname" class="form-control readonly-field" value="<?php echo htmlspecialchars($data['firstname']); ?>" readonly>
                        <span class="invalid-feedback d-block"><?php echo $data['firstname_err'] ?? ''; ?></span>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="lastname">Last Name</label>
                        <input type="text" name="lastname" class="form-control readonly-field" value="<?php echo htmlspecialchars($data['lastname']); ?>" readonly>
                        <span class="invalid-feedback d-block"><?php echo $data['lastname_err'] ?? ''; ?></span>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control readonly-field" value="<?php echo htmlspecialchars($data['middle_name']); ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="office">Office</label>
                        <select name="office" required class="form-control">
                            <option value="">Select Office</option>
                            <?php foreach ($data['offices'] as $key => $officeName): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($data['office'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($officeName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback d-block"><?php echo $data['office_err'] ?? ''; ?></span>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="role">Role</label>
                        <select name="role" class="form-control readonly-field" disabled>
                            <option value="user" <?php echo $data['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $data['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="super_admin" <?php echo $data['role'] == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="status">Status</label>
                        <select name="status" class="form-control readonly-field" disabled>
                            <option value="active" <?php echo $data['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $data['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="password">New Password</label>
                        <input type="password" name="password" class="form-control">
                        <small class="text-muted">Leave blank if you don't want to change the password.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
