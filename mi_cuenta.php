<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION["cliente_id"])) { header("Location: cliente_login.php"); exit; }
require_once __DIR__ . "/conexion.php"; $conexion = conectar();
$id = (int)$_SESSION["cliente_id"];
$stmt = $conexion->prepare("SELECT nombre, correo, telefono, creado_en FROM clientes WHERE id = ?"); $stmt->bind_param("i", $id); $stmt->execute(); $cliente = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$cliente) { unset($_SESSION["cliente_id"], $_SESSION["cliente_nombre"]); header("Location: cliente_login.php"); exit; }
$stmt = $conexion->prepare("SELECT id, total, metodo_pago, estado, creado_en FROM pedidos WHERE cliente_id = ? ORDER BY id DESC"); $stmt->bind_param("i", $id); $stmt->execute(); $pedidos = $stmt->get_result();
$titulo = "Mi cuenta"; require __DIR__ . "/header.php";
?>
<section class="cuenta-cliente"><h1>Hola, <?php echo e($cliente["nombre"]); ?> 👋</h1><p>Desde aquí puedes consultar tus datos y pedidos.</p>
<div class="datos-cliente"><h2>Mis datos</h2><p><strong>Correo:</strong> <?php echo e($cliente["correo"]); ?></p><p><strong>Teléfono:</strong> <?php echo e($cliente["telefono"] ?: "No registrado"); ?></p><a class="btn-borde" href="cliente_logout.php">Cerrar sesión</a></div>
<h2>Mis pedidos</h2>
<?php if ($pedidos->num_rows === 0): ?><p>Aún no tienes pedidos. <a href="catalogo.php">Explora el catálogo</a>.</p><?php else: ?>
<div class="tabla-scroll"><table><thead><tr><th>Pedido</th><th>Fecha</th><th>Total</th><th>Pago</th><th>Estado</th></tr></thead><tbody>
<?php while ($p = $pedidos->fetch_assoc()): ?><tr><td>#<?php echo (int)$p["id"]; ?></td><td><?php echo e($p["creado_en"]); ?></td><td>$<?php echo number_format((float)$p["total"],0,",","."); ?></td><td><?php echo e($p["metodo_pago"] === "contraentrega" ? "Contra entrega" : $p["metodo_pago"]); ?></td><td><?php echo e($p["estado"]); ?></td></tr><?php endwhile; ?>
</tbody></table></div><?php endif; ?></section>
<?php $stmt->close(); require __DIR__ . "/footer.php"; $conexion->close(); ?>
