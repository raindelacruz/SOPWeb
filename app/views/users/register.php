<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-xl-7 col-lg-8 col-md-10 col-12 mx-auto">
            <div class="card auth-card">
                <div class="card-body">
                    <?php flash('csrf_error'); ?>
                    <div class="eyebrow">New Account</div>
                    <h2 class="h3 font-weight-bold mb-2">Register for the Procedure and Document Management System</h2>
                    <p class="soft-muted mb-4">Create your account to browse procedures and request activation from the administrators.</p>

                    <form action="<?php echo URLROOT; ?>/users/register" method="post" id="registerForm">
                        <?php echo csrf_input(); ?>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">ID Number</label>
                                <input type="text" name="id_number" value="<?php echo htmlspecialchars($data['id_number']); ?>" placeholder="ID Number *" required class="form-control">
                                <span class="invalid-feedback"><?php echo $data['id_number_err']; ?></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Office</label>
                                <select name="office" required class="form-control">
                                    <option value="">Select Office</option>
                                    <?php foreach (($data['offices'] ?? []) as $value => $label): ?>
                                        <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($data['office'] == $value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="invalid-feedback d-block"><?php echo $data['office_err']; ?></span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold">First Name</label>
                                <input type="text" name="firstname" value="<?php echo htmlspecialchars($data['firstname']); ?>" placeholder="First Name *" required class="form-control">
                                <span class="invalid-feedback"><?php echo $data['firstname_err']; ?></span>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold">Last Name</label>
                                <input type="text" name="lastname" value="<?php echo htmlspecialchars($data['lastname']); ?>" placeholder="Last Name *" required class="form-control">
                                <span class="invalid-feedback"><?php echo $data['lastname_err']; ?></span>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold">Middle Name</label>
                                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($data['middle_name']); ?>" placeholder="Middle Name *" required class="form-control">
                                <span class="invalid-feedback"><?php echo $data['middle_name_err'] ?? ''; ?></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Email Address</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['email']); ?>" placeholder="Email Address *" required>
                            <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Password</label>
                                <input type="password" name="password" value="<?php echo htmlspecialchars($data['password']); ?>" placeholder="Password *" required class="form-control">
                                <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Confirm Password</label>
                                <input type="password" name="confirm_password" value="<?php echo htmlspecialchars($data['confirm_password']); ?>" placeholder="Confirm Password *" required class="form-control">
                                <span class="invalid-feedback"><?php echo $data['confirm_password_err']; ?></span>
                            </div>
                        </div>

                        <div class="form-group text-center mb-0">
                            <input type="submit" value="Register" class="btn btn-primary btn-lg btn-block">
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p>Already have an account? <a href="<?php echo URLROOT; ?>/users/login">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('registerForm').addEventListener('submit', function (e) {
        let pass = document.querySelector('[name="password"]').value;
        let confirmPass = document.querySelector('[name="confirm_password"]').value;
        if (pass !== confirmPass) {
            e.preventDefault();
            alert('Passwords do not match.');
        }
    });
</script>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
