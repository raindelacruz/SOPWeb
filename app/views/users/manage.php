<!DOCTYPE html>   
<html>
<?php require APPROOT . '/app/views/includes/header.php'; ?>
<body>
<h2>User Management</h2>
<div class="container">
    <form action="<?php echo URLROOT; ?>/users/search" method="post">
		<input type="text" name="keyword" placeholder="Search users..." value="<?php echo $data['keyword'] ?? ''; ?>">
		<input type="submit" value="Search">
	</form>
    <table class="table">
        <thead>
            <tr>
                <th>ID Number</th>
				<th>Name</th>
				<th>Office</th>
                <th>Email</th>
                <th>Status</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($data['users'] as $user): ?>
                <tr>
					<td><?php echo $user->id_number; ?></td>
                    <td><?php echo $user->lastname . ', ' . $user->firstname . ' ' . substr($user->middle_name, 0, 1) . '.';?></td>
					<td><?php echo $user->office; ?></td>
                    <td><?php echo $user->email; ?></td>
                    <td><?php echo $user->status; ?></td>
                    <td><?php echo $user->role; ?></td>
                    <td>
                        <?php if ($user->status == 'active'): ?>
                            <a href="<?php echo URLROOT; ?>/users/deactivate/<?php echo $user->id; ?>" class="btn btn-warning">Deactivate</a>
                        <?php else: ?>
                            <a href="<?php echo URLROOT; ?>/users/activate/<?php echo $user->id; ?>" class="btn btn-success">Activate</a>
                        <?php endif; ?>
                        
                        <?php if ($user->role == 'admin'): ?>
                            <a href="<?php echo URLROOT; ?>/users/changeRole/<?php echo $user->id; ?>/user" class="btn btn-secondary">Set as User</a>
                        <?php else: ?>
                            <a href="<?php echo URLROOT; ?>/users/changeRole/<?php echo $user->id; ?>/admin" class="btn btn-primary">Set as Admin</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>

</body>
</html>
