<?php
// SOPWEB/core/Middleware.php

class Middleware {
    public static function checkAdmin() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . URLROOT . '/users/login');
            exit;
        }
    }

    public static function checkUser() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
            header('Location: ' . URLROOT . '/users/login');
            exit;
        }
    }

    public static function checkLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . URLROOT . '/users/login');
            exit;
        }
    }
}



?>