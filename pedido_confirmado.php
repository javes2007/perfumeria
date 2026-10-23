<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION["cliente_id"])) { header("Location: cliente_login.php"); exit; }
require_once __DIR__ . "/conexion.php";
$conexion = conectar();
$id = (int)($_GET["id"] ?? 0);
$clienteId = (int)$_SESSION["cliente_id"];
$stmt = $conexion->prepare("SELECT id,total,estado,creado_en FROM pedidos WHERE id = ? AND cliente_id = ? LIMIT 1");
$stmt->bind_param("ii", $id, $clienteId); $stmt->execute(); $pedido = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$pedido) { header("Location: mi_cuenta.php"); exit; }
$stmt = $conexion->prepare("SELECT nombre_recibe,telefono,direccion,ciudad,indicaciones FROM pedido_entregas WHERE pedido_id = ? LIMIT 1");
$stmt->bind_param("i", $id); $stmt->execute(); $entrega = $stmt->get_result()->fetch_assoc(); $stmt->close();
$stmt = $conexion->prepare("SELECT nombre_producto,cantidad,subtotal FROM pedido_detalles WHERE pedido_id = ?"); $stmt->bind_param("i", $id); $stmt->execute(); $detalles = $stmt->get_result();
$titulo = "Pedido recibido"; require __DIR__ . "/header.php";
?>
<section class="formulario-cuenta">
<h1>¡Gracias por tu compra! ✨</h1>
<p>Tu pedido de Aroma Luxe quedó registrado correctamente.</p>
<p><strong>Número de pedido:</strong> #<?php echo (int)$pedido["id"]; ?></p>
<p><strong>Estado:</strong> <?php echo e($pedido["estado"]); ?></p>
<h2>Resumen</h2><ul><?php while ($d = $detalles->fetch_assoc()): ?><li><?php echo e($d["nombre_producto"]); ?> × <?php echo (int)$d["cantidad"]; ?> — $<?php echo number_format((float)$d["subtotal"],0,",","."); ?></li><?php endwhile; ?></ul>
<p class="carrito-total">Total: $<?php echo number_format((float)$pedido["total"],0,",","."); ?></p>
<h2>Entrega</h2>
<?php if ($entrega): ?><p><strong>Recibe:</strong> <?php echo e($entrega["nombre_recibe"]); ?></p><p><strong>Teléfono:</strong> <?php echo e($entrega["telefono"]); ?></p><p><strong>Dirección:</strong> <?php echo e($entrega["direccion"]); ?>, <?php echo e($entrega["ciudad"]); ?></p><?php if ($entrega["indicaciones"]): ?><p><strong>Indicaciones:</strong> <?php echo e($entrega["indicaciones"]); ?></p><?php endif; ?><?php endif; ?>
<p><strong>Pago:</strong> contra entrega. Pagarás al recibir tu pedido.</p>
<div class="fila-botones"><a class="btn" href="mi_cuenta.php">Ver mis pedidos</a><a class="btn-borde" href="catalogo.php">Seguir comprando</a></div>
</section>
<?php require __DIR__ . "/footer.php"; $conexion->close(); ?>