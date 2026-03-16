<?php require APPROOT . '/app/views/includes/header.php'; ?>

<div class="container auth-shell d-flex align-items-center">
    <div class="row justify-content-center w-100">
        <div class="col-xl-5 col-lg-6 col-md-8">
            <div class="card auth-card">
                <div class="card-body">
                    <?php flash('register_success'); ?>
                    <?php flash('csrf_error'); ?>
                    <div class="eyebrow">Secure Access</div>
                    <h2 class="h3 font-weight-bold mb-2">Sign in to the Procedure and Document Management System</h2>
                    <p class="soft-muted mb-4">Access procedure records, profile tools, and administrative actions from one place.</p>

                    <form action="<?php echo URLROOT; ?>/users/login" method="post">
                        <?php echo csrf_input(); ?>
                        <div class="form-group">
                            <label class="font-weight-bold">Email address</label>
                            <input type="email" name="email" class="form-control form-control-lg <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['email']); ?>" placeholder="Email Address *">
                            <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($data['password']); ?>" placeholder="Password *">
                            <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                        </div>
                        <div class="form-group mb-3">
                            <input type="submit" value="Login" class="btn btn-primary btn-lg btn-block">
                        </div>
                        <div class="form-group mb-0">
                            <a href="<?php echo URLROOT; ?>/users/register" class="btn btn-outline-secondary btn-lg btn-block">Create an account</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>
