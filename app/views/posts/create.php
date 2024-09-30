<!DOCTYPE html>   
<html>
<?php require APPROOT . '/app/views/includes/header.php'; ?>
<body>

<h2>Create Post</h2>

<div class="card card-body bg-light mt-5">
    <form action="<?php echo URLROOT; ?>/posts/create" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Title: <sup>*</sup></label>
            <input type="text" name="title" class="form-control form-control-lg" value="<?php echo $data['title']; ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description: <sup>*</sup></label>
            <textarea name="description" class="form-control form-control-lg" required><?php echo $data['description']; ?></textarea>
        </div>
        <div class="form-group">
            <label for="reference_number">Reference Number: <sup>*</sup></label>
            <input type="text" name="reference_number" class="form-control form-control-lg" value="<?php echo $data['reference_number']; ?>" required>
        </div>
        <div class="form-group">
            <label for="date_of_effectivity">Date of Effectivity: <sup>*</sup></label>
            <input type="date" name="date_of_effectivity" class="form-control form-control-lg" value="<?php echo $data['date_of_effectivity']; ?>" required>
        </div>
        <div class="form-group">
            <label for="file">Upload File (PDF only):</label>
            <input type="file" name="file" class="form-control form-control-lg" required>
        </div>
        <div class="form-group">
            <label for="amended_post_id">Amended Post:</label>
            <select name="amended_post_id" class="form-control form-control-lg">
                <option value="">Select Post</option>
                <?php foreach ($data['posts'] as $post): ?>
                    <option value="<?php echo $post->id; ?>" <?php echo isset($_GET['amend']) && $_GET['amend'] == $post->id ? 'selected' : ''; ?>><?php echo $post->title; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="superseded_post_id">Superseded Post:</label>
            <select name="superseded_post_id" class="form-control form-control-lg">
                <option value="">Select Post</option>
                <?php foreach ($data['posts'] as $post): ?>
                    <option value="<?php echo $post->id; ?>" <?php echo isset($_GET['supersede']) && $_GET['supersede'] == $post->id ? 'selected' : ''; ?>><?php echo $post->title; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
		
        <div class="form-group">
			<a href="<?php echo URLROOT; ?>/posts" class="btn btn-light">Cancel</a>
            <input type="submit" class="btn btn-success btn-block" value="Submit">
        </div>
    </form>
</div>

<?php require APPROOT . '/app/views/includes/footer.php'; ?>

</body>
</html>
