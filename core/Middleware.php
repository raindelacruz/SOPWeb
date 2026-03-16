<?php
// SOPWEB/core/Middleware.php

class Middleware {
    private static function currentRole() {
        return $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
    }

    public static function checkAdmin() {
        $role = self::currentRole();
        if (!isset($_SESSION['user_id']) || ($role !== 'admin' && $role !== 'super_admin')) {
            header('Location: ' . URLROOT . '/users/login');
            exit;
        }
    }

    public static function checkSuperAdmin() {
        if (!isset($_SESSION['user_id']) || self::currentRole() !== 'super_admin') {
            header('Location: ' . URLROOT . '/users/login');
            exit;
        }
    }

    public static function checkUser() {
        if (!isset($_SESSION['user_id']) || self::currentRole() !== 'user') {
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
