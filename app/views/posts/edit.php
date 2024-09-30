<!DOCTYPE html>   
<html>
<?php require APPROOT . '/app/views/includes/header.php'; ?>
<body>

<h2>Edit Post</h2>
	
	<form action="<?php echo URLROOT; ?>/posts/edit/<?php echo $data['id']; ?>" method="post" enctype="multipart/form-data">
		<div class="form-group">
			<label for="title">Title: <sup>*</sup></label>
			<input type="text" name="title" class="form-control form-control-lg" value="<?php echo $data['title']; ?>">
		</div>
		<div class="form-group">
			<label for="description">Description: <sup>*</sup></label>
			<textarea name="description" class="form-control form-control-lg"><?php echo $data['description']; ?></textarea>
		</div>
		<div class="form-group">
			<label for="reference_number">Reference Number: <sup>*</sup></label>
			<input type="text" name="reference_number" class="form-control form-control-lg" value="<?php echo $data['reference_number']; ?>">
		</div>
		<div class="form-group">
			<label for="date_of_effectivity">Date of Effectivity: <sup>*</sup></label>
			<input type="date" name="date_of_effectivity" class="form-control form-control-lg" value="<?php echo $data['date_of_effectivity']; ?>">
		</div>
		<div class="form-group">
			<label for="file">Upload File: </label>
			<input type="file" name="file" class="form-control form-control-lg">
			<input type="hidden" name="existing_file" value="<?php echo $data['file']; ?>">
		</div>
		<div class="form-group">
			<label for="amended_post_id">Amended Post:</label>
			<select name="amended_post_id" class="form-control form-control-lg">
				<option value="">None</option>
				<?php foreach ($data['posts'] as $post): ?>
					<option value="<?php echo $post->id; ?>" <?php echo ($data['amended_post_id'] == $post->id) ? 'selected' : ''; ?>>
						<?php echo $post->title; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-group">
			<label for="superseded_post_id">Superseded Post:</label>
			<select name="superseded_post_id" class="form-control form-control-lg">
				<option value="">None</option>
				<?php foreach ($data['posts'] as $post): ?>
					<option value="<?php echo $post->id; ?>" <?php echo ($data['superseded_post_id'] == $post->id) ? 'selected' : ''; ?>>
						<?php echo $post->title; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-group">
			<a href="<?php echo URLROOT; ?>/posts" class="btn btn-light">Back</a>
			<input type="submit" class="btn btn-success" value="Submit">
		</div>
	</form>
	<?php require APPROOT . '/app/views/includes/footer.php'; ?>

</body>
</html>