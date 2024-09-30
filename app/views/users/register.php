<!DOCTYPE html>
<html lang="en">
<head>
    <?php require APPROOT . '/app/views/includes/header.php'; ?>
<body>
    <h2>Registration</h2>

	<form action="<?php echo URLROOT; ?>/users/register" method="post">
		<div>
			<label for="id_number">NFA ID Number: <sup>*</sup></label>
			<input type="text" name="id_number" value="<?php echo $data['id_number']; ?>" placeholder="ID Number" required>
			<span><?php echo $data['id_number_err']; ?></span>
		</div>
				<div>
			<label for="firstname">First Name: <sup>*</sup></label>
			<input type="text" name="firstname" value="<?php echo $data['firstname']; ?>" placeholder="First Name" required>
			<span><?php echo $data['firstname_err']; ?></span>
		</div>
		<div>
			<label for="lastname">Last Name: <sup>*</sup></label>
			<input type="text" name="lastname" value="<?php echo $data['lastname']; ?>" placeholder="Last Name" required>
			<span><?php echo $data['lastname_err']; ?></span>
		</div>
		<div>
			<label for="middle_name">Middle Name: <sup>*</sup></label>
			<input type="text" name="middle_name" value="<?php echo $data['middle_name']; ?>" placeholder="Middle Name" required>
			<span><?php echo $data['middle_name']; ?></span>
		</div>		
		<div>
			<label for="office">Office: <sup>*</sup></label>
			<input type="text" name="office" value="<?php echo $data['office']; ?>" placeholder="Office" required>
			<span><?php echo $data['office_err']; ?></span>
		</div>	
		<div>
			<label for="email">Email: <sup>*</sup></label>
			<input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['email']; ?>" placeholder="Email" required>
			<span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
		</div>		
		<div>
			<label for="password">Password: <sup>*</sup></label>
			<input type="password" name="password" value="<?php echo $data['password']; ?>" placeholder="Password" required>
			<span><?php echo $data['password_err']; ?></span>
		</div>
		<div>
			<label for="confirm_password">Confirm Password: <sup>*</sup></label>
			<input type="password" name="confirm_password" value="<?php echo $data['confirm_password']; ?>" placeholder="Confirm Password" required>
			<span><?php echo $data['confirm_password_err']; ?></span>
		</div>
		<div>
			<input type="submit" value="Register">
		</div>
	</form>

	
</body>
	<?php require APPROOT . '/app/views/includes/footer.php'; ?>
</html>