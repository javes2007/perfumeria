<?php
require_once __DIR__ . "/admin_auth.php";
requiere_admin();
require_once __DIR__ . "/conexion.php";
$conexion = conectar();
$titulo = "Pedidos de clientes";

$sql = "SELECT p.id, p.total, p.metodo_pago, p.estado, p.creado_en,
               c.nombre AS cliente_nombre, c.correo AS cliente_correo, c.telefono AS cliente_telefono,
               e.nombre_recibe, e.telefono AS entrega_telefono, e.direccion, e.ciudad, e.indicaciones
        FROM pedidos p
        LEFT JOIN clientes c ON c.id = p.cliente_id
        LEFT JOIN pedido_entregas e ON e.pedido_id = p.id
        ORDER BY p.creado_en DESC, p.id DESC";
$pedidos = $conexion->query($sql);
$pendientes = 0;
if ($pedidos) {
    while ($p = $pedidos->fetch_assoc()) {
        if (stripos((string)$p["estado"], "pendiente") !== false || stripos((string)$p["estado"], "recibido") !== false) $pendientes++;
    }
    $pedidos->data_seek(0);
}
require __DIR__ . "/header.php";
?>
<section class="admin-pedidos">
  <div class="titulo-fila">
    <h1>📦 Pedidos de clientes</h1>
    <a class="btn-borde" href="admin.php">Volver a administrar</a>
  </div>
  <p>Consulta aquí las compras registradas, los datos de entrega y los productos que pidió cada cliente.</p>
  <?php if ($pendientes > 0): ?><p class="aviso" role="status">🔔 Tienes <?php echo (int)$pendientes; ?> pedido(s) pendiente(s) de revisar.</p><?php endif; ?>
  <?php if (!$pedidos || $pedidos->num_rows === 0): ?>
    <p>Aún no hay pedidos registrados.</p>
  <?php else: ?>
    <?php while ($p = $pedidos->fetch_assoc()): ?>
      <article class="pedido-admin">
        <div class="titulo-fila">
          <h2>Pedido #<?php echo (int)$p["id"]; ?></h2>
          <strong>$<?php echo number_format((float)$p["total"], 0, ",", "."); ?></strong>
        </div>
        <p><strong>Fecha:</strong> <?php echo e($p["creado_en"]); ?></p>
        <p><strong>Estado:</strong> <?php echo e($p["estado"]); ?></p>
        <p><strong>Pago:</strong> <?php echo e($p["metodo_pago"] === "contraentrega" ? "Contra entrega" : $p["metodo_pago"]); ?></p>
        <h3>Cliente</h3>
        <p><strong>Nombre:</strong> <?php echo e($p["cliente_nombre"] ?? "No disponible"); ?></p>
        <p><strong>Correo:</strong> <?php echo e($p["cliente_correo"] ?? "No disponible"); ?></p>
        <p><strong>Teléfono de cuenta:</strong> <?php echo e($p["cliente_telefono"] ?? "No registrado"); ?></p>
        <h3>Entrega</h3>
        <p><strong>Recibe:</strong> <?php echo e($p["nombre_recibe"] ?? "No registrado"); ?></p>
        <p><strong>Teléfono:</strong> <?php echo e($p["entrega_telefono"] ?? "No registrado"); ?></p>
        <p><strong>Dirección:</strong> <?php echo e(trim(($p["direccion"] ?? "") . " " . ($p["ciudad"] ?? "")) ?: "No registrada"); ?></p>
        <?php if (!empty($p["indicaciones"])): ?><p><strong>Indicaciones:</strong> <?php echo e($p["indicaciones"]); ?></p><?php endif; ?>
        <h3>Productos</h3>
        <ul>
        <?php
          $det = $conexion->prepare("SELECT nombre_producto, cantidad, subtotal FROM pedido_detalles WHERE pedido_id = ? ORDER BY id ASC");
          $pid = (int)$p["id"];
          $det->bind_param("i", $pid);
          $det->execute();
          $lista = $det->get_result();
          while ($d = $lista->fetch_assoc()):
        ?>
          <li><?php echo e($d["nombre_producto"]); ?> × <?php echo (int)$d["cantidad"]; ?> — $<?php echo number_format((float)$d["subtotal"], 0, ",", "."); ?></li>
        <?php endwhile; $det->close(); ?>
        </ul>
      </article>
    <?php endwhile; ?>
  <?php endif; ?>
</section>
<?php require __DIR__ . "/footer.php"; $conexion->close(); ?>
