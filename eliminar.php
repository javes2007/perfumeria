<?php
require_once __DIR__ . "/admin_auth.php";
requiere_admin();
$titulo = "Eliminar perfume";
require_once "conexion.php";

$conexion = conectar();
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;


$stmt = $conexion->prepare("SELECT nombre, marca, imagen FROM perfumes WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fila) {
    $stmt = $conexion->prepare("DELETE FROM perfumes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    borrar_imagen($fila['imagen']);
    $conexion->close();
    header("Location: admin.php?msg=" . urlencode("Perfume eliminado"));
    exit;
}
$conexion->close();

require "header.php";
?>

<?php if (!$fila): ?>
  <p class="error">El perfume no existe.</p>
  <p><a href="admin.php">Volver</a></p>
<?php else: ?>
  <h1>Eliminar perfume</h1>
  <div class="vista-previa"><?php echo imagen_perfume($fila['imagen'], $fila['nombre']); ?></div>
  <p>¿Seguro que quieres eliminar <strong><?php echo e($fila['nombre']); ?></strong> (<?php echo e($fila['marca']); ?>)? Esta acción no se puede deshacer.</p>
  <form method="POST" action="eliminar.php" class="fila-botones">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <button type="submit" class="peligro">Sí, eliminar</button>
    <a href="admin.php">Cancelar</a>
  </form>
<?php endif; ?>

<?php require "footer.php"; ?>
