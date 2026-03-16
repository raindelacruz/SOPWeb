<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDMS</title>
    <?php
        $styleVersion = @filemtime(APPROOT . '/public/css/style.css') ?: time();
        $navbarVersion = @filemtime(APPROOT . '/public/css/navbar.css') ?: time();
    ?>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css?v=<?php echo (int) $styleVersion; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/navbar.css?v=<?php echo (int) $navbarVersion; ?>">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php require APPROOT . '/app/views/inc/navbar.php'; ?>
<main class="app-shell">
