<!DOCTYPE html>   
<html>
<?php require APPROOT . '/app/views/includes/header.php'; ?>
<body>
	<h2Posts</h2>
    <h2><?php echo htmlspecialchars($data['post']->title); ?></h2>
    <p>Description: <?php echo htmlspecialchars($data['post']->description); ?></p>
    <p>Reference Number: <?php echo htmlspecialchars($data['post']->reference_number); ?></p>
    <p>Date of Effectivity: <?php echo htmlspecialchars($data['post']->date_of_effectivity); ?></p>
    <p>Upload Date: <?php echo htmlspecialchars($data['post']->upload_date); ?></p>

    <?php if (!empty($data['post']->file)): ?>
        <p>Uploaded File: <a href="<?php echo URLROOT; ?>../uploads/<?php echo htmlspecialchars($data['post']->file); ?>" target="_blank">Download</a></p>
        <embed src="<?php echo URLROOT; ?>../uploads/<?php echo htmlspecialchars($data['post']->file); ?>" type="application/pdf" width="600" height="400">
    <?php else: ?>
        <p>No file uploaded.</p>
    <?php endif; ?>

    <?php if (!empty($data['amendedPost'])): ?>
        <h3>Amended Post</h3>
        <p>Title: <a href="<?php echo URLROOT; ?>/posts/show/<?php echo htmlspecialchars($data['amendedPost']->id); ?>"><?php echo htmlspecialchars($data['amendedPost']->title); ?></a></p>
        <?php if (!empty($data['amendedPost']->file)): ?>
            <embed src="<?php echo URLROOT; ?>../uploads/<?php echo htmlspecialchars($data['amendedPost']->file); ?>" type="application/pdf" width="600" height="400">
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($data['supersededPost'])): ?>
        <h3>Superseded Post</h3>
        <p>Title: <a href="<?php echo URLROOT; ?>/posts/show/<?php echo htmlspecialchars($data['supersededPost']->id); ?>"><?php echo htmlspecialchars($data['supersededPost']->title); ?></a></p>
        <?php if (!empty($data['supersededPost']->file)): ?>
            <embed src="<?php echo URLROOT; ?>../uploads/<?php echo htmlspecialchars($data['supersededPost']->file); ?>" type="application/pdf" width="600" height="400">
        <?php endif; ?>
    <?php endif; ?>

    <h3>Amending Posts</h3>
    <?php if (!empty($data['amendingPosts'])): ?>
        <ul>
        <?php foreach ($data['amendingPosts'] as $amendingPost): ?>
            <li>
                <a href="<?php echo URLROOT; ?>/posts/show/<?php echo htmlspecialchars($amendingPost->id); ?>"><?php echo htmlspecialchars($amendingPost->title); ?></a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No posts amending this post.</p>
    <?php endif; ?>

    <h3>Superseding Posts</h3>
    <?php if (!empty($data['supersedingPosts'])): ?>
        <ul>
        <?php foreach ($data['supersedingPosts'] as $supersedingPost): ?>
            <li>
                <a href="<?php echo URLROOT; ?>/posts/show/<?php echo htmlspecialchars($supersedingPost->id); ?>"><?php echo htmlspecialchars($supersedingPost->title); ?></a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No posts superseding this post.</p>
    <?php endif; ?>

    <!-- Access control: Show edit and delete options only if the user is an admin -->
    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') : ?>
		<a href="<?php echo URLROOT; ?>/posts/edit/<?php echo $data['post']->id; ?>" class="btn btn-primary">Edit</a>
			
			
			<!-- logic for discussion
			
				<?php if(!$data['isReferenced']) : ?>
					<a href="<?php echo URLROOT; ?>/posts/edit/<?php echo $data['post']->id; ?>" class="btn btn-primary">Edit</a>
					<!--<form action="<?php echo URLROOT; ?>/posts/delete/<?php echo $data['post']->id; ?>" method="POST" style="display:inline;">
						<input type="submit" value="Delete" class="btn btn-danger">
					</form>
				<?php endif; ?>
			-->
			
	<?php endif; ?>

    <a href="<?php echo URLROOT; ?>/posts">Back to Posts</a>
    <br>
    <?php if ($_SESSION['user_role'] == 'admin'): ?>
        <a href="<?php echo URLROOT; ?>/posts/create?amend=<?php echo htmlspecialchars($data['post']->id); ?>">Amend this Post</a>
		<a href="<?php echo URLROOT; ?>/posts/create?supersede=<?php echo htmlspecialchars($data['post']->id); ?>">Supersede this Post</a>
	<?php endif; ?>

    <?php require APPROOT . '/app/views/includes/footer.php'; ?>
</body>
</html>
