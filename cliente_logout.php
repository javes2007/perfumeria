<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
unset($_SESSION["cliente_id"], $_SESSION["cliente_nombre"]);
session_regenerate_id(true);
header("Location: index.html"); exit;
