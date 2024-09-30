<?php

// SOPWEB/app/helpers/url_helper.php

function redirect($url) {
    header('Location: ' . URLROOT . '/' . $url);
    exit();
}


?>