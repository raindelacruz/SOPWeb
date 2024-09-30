<!DOCTYPE html>   
<html>
<?php require APPROOT . '/app/views/includes/header.php'; ?>
<body>
<h2>Activity Logs</h2>
<table>
	<form action="<?php echo URLROOT; ?>/activityLogs/search" method="post">
		<input type="text" name="keyword" placeholder="Search logs..." value="<?php echo $data['keyword'] ?? ''; ?>">
		<input type="submit" value="Search">
	</form>

    <thead>
        <tr>
            <th>User</th>
            <th>Action</th>
            <th>Description</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
	
		<?php if (isset($data['logs']) && is_array($data['logs'])): ?>
		<?php foreach ($data['logs'] as $log): ?>
			<tr>
                <td><?php echo $log->user_id; ?></td>
                <td><?php echo $log->action; ?></td>
                <td><?php echo $log->description; ?></td>
                <td><?php echo $log->created_at; ?></td>
            </tr>
		<?php endforeach; ?>
	<?php else: ?>
		<p>No activity logs available.</p>
	<?php endif; ?>
	
    </tbody>
</table>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>

</body>
</html>
