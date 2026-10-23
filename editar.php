<?php
require_once __DIR__ . "/admin_auth.php";
requiere_admin();
$titulo = "Editar perfume";
require "header.php";

$conexion = conectar();
$error = "";
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;


$stmt = $conexion->prepare("SELECT nombre, marca, genero, precio, stock, descripcion, imagen FROM perfumes WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$actual) {
    echo '<p class="error">El perfume no existe.</p><p><a href="admin.php">Volver</a></p>';
    $conexion->close();
    require "footer.php";
    exit;
}
$v = $actual;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['nombre']      = trim($_POST['nombre'] ?? '');
    $v['marca']       = trim($_POST['marca'] ?? '');
    $v['genero']      = $_POST['genero'] ?? '';
    $v['precio']      = trim($_POST['precio'] ?? '');
    $v['stock']       = trim($_POST['stock'] ?? '');
    $v['descripcion'] = trim($_POST['descripcion'] ?? '');

    if ($v['nombre'] === '' || $v['marca'] === '' || $v['precio'] === '' || $v['stock'] === '') {
        $error = "Completa nombre, marca, precio y stock.";
    } elseif (!is_numeric($v['precio']) || $v['precio'] < 0 || !ctype_digit($v['stock'])) {
        $error = "Precio y stock deben ser números válidos.";
    } elseif (!in_array($v['genero'], ['Hombre','Mujer','Unisex'])) {
        $error = "Género no válido.";
    } else {
        $nueva = subir_imagen('imagen', $error);
        if ($error === "") {
            $imagen_final = $nueva !== null ? $nueva : $actual['imagen'];
            $stmt = $conexion->prepare(
                "UPDATE perfumes SET nombre=?, marca=?, genero=?, precio=?, stock=?, descripcion=?, imagen=? WHERE id=?"
            );
            $stmt->bind_param("sssdissi", $v['nombre'], $v['marca'], $v['genero'], $v['precio'], $v['stock'], $v['descripcion'], $imagen_final, $id);
            if ($stmt->execute()) {
                $stmt->close();
                if ($nueva !== null) { borrar_imagen($actual['imagen']); }  // se reemplazó la foto
                $conexion->close();
                header("Location: admin.php?msg=" . urlencode("Perfume actualizado con éxito"));
                exit;
            }
            $error = "No se pudo actualizar: " . $conexion->error;
            if ($nueva !== null) { borrar_imagen($nueva); }
        }
    }
}
?>

<h1>Editar perfume #<?php echo $id; ?></h1>
<?php if ($error): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>

<form method="POST" action="editar.php" enctype="multipart/form-data" class="formulario">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
  <label>Nombre
    <input type="text" name="nombre" maxlength="100" required value="<?php echo e($v['nombre']); ?>">
  </label>
  <label>Marca
    <input type="text" name="marca" maxlength="80" required value="<?php echo e($v['marca']); ?>">
  </label>
  <label>Género
    <select name="genero">
      <?php foreach (['Hombre','Mujer','Unisex'] as $g): ?>
        <option value="<?php echo $g; ?>" <?php echo $v['genero'] === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Precio
    <input type="number" name="precio" min="0" step="0.01" required value="<?php echo e($v['precio']); ?>">
  </label>
  <label>Stock
    <input type="number" name="stock" min="0" step="1" required value="<?php echo e($v['stock']); ?>">
  </label>
  <label>Descripción
    <textarea name="descripcion" rows="4"><?php echo e($v['descripcion']); ?></textarea>
  </label>
  <div>
    <p class="etiqueta-campo">Foto actual</p>
    <div class="vista-previa"><?php echo imagen_perfume($actual['imagen'], $actual['nombre']); ?></div>
  </div>
  <label>Cambiar foto (opcional)
    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
  </label>
  <div class="fila-botones">
    <button type="submit">Actualizar</button>
    <a href="admin.php">Cancelar</a>
  </div>
</form>

<?php
$conexion->close();
require "footer.php";
?>
