<?php
$titulo = "Catálogo";
require "header.php";

$conexion = conectar();
$buscar = isset($_GET['q']) ? trim($_GET['q']) : "";
$genero = isset($_GET['genero']) ? $_GET['genero'] : "";
if (!in_array($genero, ['Hombre', 'Mujer', 'Unisex'])) { $genero = ""; }

$sql = "SELECT * FROM perfumes WHERE 1=1";
$tipos = "";
$params = [];
if ($buscar !== "") {
    $sql .= " AND (nombre LIKE ? OR marca LIKE ?)";
    $like = "%" . $buscar . "%";
    $tipos .= "ss";
    $params[] = $like;
    $params[] = $like;
}
if ($genero !== "") {
    $sql .= " AND genero = ?";
    $tipos .= "s";
    $params[] = $genero;
}
$sql .= " ORDER BY id DESC";

$stmt = $conexion->prepare($sql);
if ($tipos !== "") { $stmt->bind_param($tipos, ...$params); }
$stmt->execute();
$resultado = $stmt->get_result();
?>

<h1>Nuestro catálogo</h1>

<form method="GET" action="catalogo.php" class="buscador">
  <input type="text" name="q" placeholder="Buscar por nombre o marca..." value="<?php echo e($buscar); ?>">
  <select name="genero" class="select-chico">
    <option value="">Todos</option>
    <?php foreach (['Hombre', 'Mujer', 'Unisex'] as $g): ?>
      <option value="<?php echo $g; ?>" <?php echo $genero === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Filtrar</button>
  <?php if ($buscar !== "" || $genero !== ""): ?><a href="catalogo.php">Limpiar</a><?php endif; ?>
</form>

<?php if ($resultado->num_rows === 0): ?>
  <p>No encontramos perfumes con ese filtro.</p>
<?php else: ?>
<div class="tarjetas">
  <?php while ($p = $resultado->fetch_assoc()): ?>
    <article class="tarjeta">
      <div class="tarjeta-foto">
        <?php echo imagen_perfume($p['imagen'], $p['nombre']); ?>
        <span class="insignia"><?php echo e($p['genero']); ?></span>
      </div>
      <div class="tarjeta-cuerpo">
        <h3><?php echo e($p['nombre']); ?></h3>
        <p class="marca"><?php echo e($p['marca']); ?></p>
        <p class="desc"><?php echo e($p['descripcion']); ?></p>
        <div class="tarjeta-pie">
          <span class="precio">$<?php echo number_format($p['precio'], 0, ',', '.'); ?></span>
          <?php if ((int)$p['stock'] > 0): ?>
            <span class="stock">Disponibles: <?php echo (int)$p['stock']; ?></span>
          <?php else: ?>
            <span class="stock agotado">Agotado</span>
          <?php endif; ?>
        </div>
        <?php if ((int)$p['stock'] > 0): ?>
          <form method="POST" action="agregar_carrito.php" class="form-carrito">
            <label for="cantidad-<?php echo (int)$p['id']; ?>">Cantidad</label>
            <input id="cantidad-<?php echo (int)$p['id']; ?>" type="number" name="cantidad" min="1" max="<?php echo (int)$p['stock']; ?>" value="1" required>
            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
            <button type="submit" name="accion" value="carrito" class="btn-comprar">🛒 Agregar al carrito</button>
            <button type="submit" name="accion" value="comprar" class="btn-comprar btn-comprar-ahora">Comprar ahora ↗</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endwhile; ?>
</div>
<?php endif; ?>

<?php
$stmt->close();
$conexion->close();
require "footer.php";
?>
