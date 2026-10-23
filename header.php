<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "conexion.php";
$carrito_cantidad = 0;
if (isset($_SESSION["carrito"]) && is_array($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $cantidad) { $carrito_cantidad += (int)$cantidad; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($titulo) ? e($titulo) . " | " : ""; ?>Aroma Luxe</title>
  <meta name="theme-color" content="#211a1c">
  <link rel="icon" type="image/svg+xml" href="favicon.svg?v=20261020-1">
  <link rel="stylesheet" href="estilos.css?v=20261021-8">
</head>
<body>
<header class="barra">
  <a class="logo" href="index.html">✦ Aroma Luxe</a>
  <nav>
    <a href="index.html">Inicio</a>
    <a href="catalogo.php">Catálogo</a>
    <a href="carrito.php">🛒 Carrito (<?php echo $carrito_cantidad; ?>)</a>
    <?php if (!empty($_SESSION["cliente_id"])): ?><a href="mi_cuenta.php">Mi cuenta</a><a href="cliente_logout.php">Salir</a><?php else: ?><a href="cliente_login.php">Mi cuenta</a><?php endif; ?>
    <?php if (!empty($_SESSION["admin_autenticado"])): ?><a href="admin.php">Administrar</a><a href="logout.php">Salir</a><?php else: ?><a href="login.php">Administración</a><?php endif; ?>
  </nav>
</header>
<main class="contenedor">
