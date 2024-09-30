<!DOCTYPE html>   
<html>
<?php require APPROOT . '/app/views/includes/header.php'; ?>
<body>


<h2>Posts</h2>

<form action="<?php echo URLROOT; ?>/posts/search" method="post">
    <div class="form-group">
        <label for="search">Search by Title or Description:</label>
        <input type="text" name="search" class="form-control" placeholder="Enter title or description">
		<input type="submit" class="btn btn-primary" value="Search">
    </div>
    
</form>

<?php if (!empty($data['posts'])): ?>
    <table>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Date of Effectivity</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($data['posts'] as $post): ?>
            <tr>
                <td><?php echo $post->title; ?></td>
                <td><?php echo $post->description; ?></td>
                <td><?php echo $post->date_of_effectivity; ?></td>
                <td>
					<a href="<?php echo URLROOT; ?>/posts/show/<?php echo $post->id; ?>">Show</a>
					
					<?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') : ?>
						<a href="<?php echo URLROOT; ?>/posts/edit/<?php echo $post->id; ?>">Edit</a>
						
							<!--  Logic for discussion -->	
							<!--
							<?php if(!$this->postModel->isPostReferenced($post->id)) : ?>
								<a href="<?php echo URLROOT; ?>/posts/edit/<?php echo $post->id; ?>">Edit</a>
								<form action="<?php echo URLROOT; ?>/posts/delete/<?php echo $post->id; ?>" method="POST" style="display:inline;">
									<input type="submit" value="Delete">
								</form
							<?php endif; ?>
							-->
							
					<?php endif; ?>
				</td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No posts found.</p>
<?php endif; ?>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>

</body>
</html>
