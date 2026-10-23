<?php
require_once __DIR__ . "/admin_auth.php";
requiere_admin();
$titulo = "Administrar";
require "header.php";

$conexion = conectar();
$resultado = $conexion->query("SELECT * FROM perfumes ORDER BY id DESC");
$pedidosPendientes = 0;
$consultaPendientes = $conexion->query("SELECT COUNT(*) AS total FROM pedidos WHERE LOWER(estado) LIKE '%pendiente%' OR LOWER(estado) LIKE '%recibido%'");
if ($consultaPendientes) {
    $filaPendientes = $consultaPendientes->fetch_assoc();
    $pedidosPendientes = (int)($filaPendientes["total"] ?? 0);
}
?>

<?php if (isset($_GET['msg'])): ?>
  <p class="aviso"><?php echo e($_GET['msg']); ?></p>
<?php endif; ?>

<?php if ($pedidosPendientes > 0): ?>
  <section class="aviso-pedidos-admin" role="status">
    <h2>🔔 ¡Tienes <?php echo $pedidosPendientes; ?> pedido(s) por revisar!</h2>
    <p>Un cliente hizo una compra en Aroma Luxe. Entra a revisar los productos y los datos de entrega.</p>
    <a class="btn" href="admin_pedidos.php">Ver pedidos nuevos →</a>
  </section>
<?php endif; ?>

<div class="titulo-fila">
  <h1>Administrar perfumes</h1>
  <div class="fila-botones">
    <a class="btn" href="admin_pedidos.php">📦 Ver pedidos<?php if ($pedidosPendientes > 0): ?> (<?php echo $pedidosPendientes; ?>)<?php endif; ?></a>
    <a class="btn" href="crear.php">+ Nuevo perfume</a>
  </div>
</div>

<?php if ($resultado->num_rows === 0): ?>
  <p>Aún no hay perfumes. ¡Agrega el primero!</p>
<?php else: ?>
<div class="tabla-scroll">
<table>
  <thead>
    <tr><th>Foto</th><th>ID</th><th>Nombre</th><th>Marca</th><th>Género</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr>
  </thead>
  <tbody>
  <?php while ($fila = $resultado->fetch_assoc()): ?>
    <tr>
      <td class="miniatura"><?php echo imagen_perfume($fila['imagen'], $fila['nombre']); ?></td>
      <td><?php echo (int)$fila['id']; ?></td>
      <td><strong><?php echo e($fila['nombre']); ?></strong></td>
      <td><?php echo e($fila['marca']); ?></td>
      <td><?php echo e($fila['genero']); ?></td>
      <td>$<?php echo number_format($fila['precio'], 0, ',', '.'); ?></td>
      <td><?php echo (int)$fila['stock']; ?></td>
      <td class="acciones">
        <a class="btn-chico" href="editar.php?id=<?php echo (int)$fila['id']; ?>">Editar</a>
        <a class="btn-chico peligro" href="eliminar.php?id=<?php echo (int)$fila['id']; ?>">Eliminar</a>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<?php
$conexion->close();
require "footer.php";
?>
