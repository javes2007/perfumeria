<?php
require_once __DIR__ . "/admin_auth.php";
requiere_admin();
$titulo = "Nuevo perfume";
require "header.php";

$error = "";
$v = ['nombre'=>'', 'marca'=>'', 'genero'=>'Unisex', 'precio'=>'', 'stock'=>'', 'descripcion'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $campo => $_) {
        $v[$campo] = isset($_POST[$campo]) ? trim($_POST[$campo]) : '';
    }

    if ($v['nombre'] === '' || $v['marca'] === '' || $v['precio'] === '' || $v['stock'] === '') {
        $error = "Completa nombre, marca, precio y stock.";
    } elseif (!is_numeric($v['precio']) || $v['precio'] < 0 || !ctype_digit($v['stock'])) {
        $error = "Precio y stock deben ser números válidos.";
    } elseif (!in_array($v['genero'], ['Hombre','Mujer','Unisex'])) {
        $error = "Género no válido.";
    } else {
        $imagen = subir_imagen('imagen', $error);   // null si no subió foto
        if ($error === "") {
            $conexion = conectar();
            $stmt = $conexion->prepare(
                "INSERT INTO perfumes (nombre, marca, genero, precio, stock, descripcion, imagen) VALUES (?,?,?,?,?,?,?)"
            );
            $stmt->bind_param("sssdiss", $v['nombre'], $v['marca'], $v['genero'], $v['precio'], $v['stock'], $v['descripcion'], $imagen);
            if ($stmt->execute()) {
                $stmt->close();
                $conexion->close();
                header("Location: admin.php?msg=" . urlencode("Perfume guardado con éxito"));
                exit;
            }
            $error = "No se pudo guardar: " . $conexion->error;
            borrar_imagen($imagen);   // si falló la base de datos, no dejamos la foto huérfana
        }
    }
}
?>

<h1>Nuevo perfume</h1>
<?php if ($error): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>

<form method="POST" action="crear.php" enctype="multipart/form-data" class="formulario">
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
  <label>Foto del perfume (JPG, PNG o WEBP, máx. 2 MB)
    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
  </label>
  <div class="fila-botones">
    <button type="submit">Guardar</button>
    <a href="admin.php">Cancelar</a>
  </div>
</form>

<?php require "footer.php"; ?>
