<?php
require_once __DIR__ . "/cuenta_helpers.php";
require_once __DIR__ . "/conexion.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: catalogo.php"); exit; }
$id = (int)($_POST["id"] ?? 0);
$cantidad = (int)($_POST["cantidad"] ?? 1);
$accion = (string)($_POST["accion"] ?? "carrito");
if ($id < 1 || $cantidad < 1) { header("Location: catalogo.php"); exit; }
$conexion = conectar();
$stmt = $conexion->prepare("SELECT stock FROM perfumes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$perfume = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conexion->close();
if (!$perfume || (int)$perfume["stock"] < 1) { header("Location: catalogo.php?msg=agotado"); exit; }
if (!isset($_SESSION["carrito"]) || !is_array($_SESSION["carrito"])) { $_SESSION["carrito"] = []; }
$actual = isset($_SESSION["carrito"][$id]) ? (int)$_SESSION["carrito"][$id] : 0;
$_SESSION["carrito"][$id] = min($actual + $cantidad, (int)$perfume["stock"]);
if ($accion === "comprar") {
    if (empty($_SESSION["cliente_id"])) {
        $_SESSION["continuar_compra"] = true;
        header("Location: cliente_login.php?continuar=compra");
        exit;
    }
    header("Location: pedido.php");
    exit;
}
header("Location: carrito.php?msg=agregado");
exit;
?>