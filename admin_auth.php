<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/conexion.php";

function requiere_admin() {
    if (empty($_SESSION["admin_autenticado"])) {
        header("Location: login.php");
        exit;
    }
}