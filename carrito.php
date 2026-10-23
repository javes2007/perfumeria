<?php
$titulo = "Carrito de compras";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "conexion.php";
$conexion = conectar();
$mensaje = "";
if (isset($_GET["msg"]) && $_GET["msg"] === "agregado") { $mensaje = "Producto agregado al carrito."; }
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["eliminar"])) {
        $idEliminar = (int)$_POST["eliminar"];
        unset($_SESSION["carrito"][$idEliminar]);
        header("Location: carrito.php");
        exit;
    }
    if (isset($_POST["actualizar"]) && isset($_POST["cantidades"]) && is_array($_POST["cantidades"])) {
        foreach ($_POST["cantidades"] as $id => $cantidad) {
            $id = (int)$id;
            $cantidad = (int)$cantidad;
            if ($cantidad < 1) { unset($_SESSION["carrito"][$id]); continue; }
            $stmtStock = $conexion->prepare("SELECT stock FROM perfumes WHERE id = ?");
            $stmtStock->bind_param("i", $id);
            $stmtStock->execute();
            $datoStock = $stmtStock->get_result()->fetch_assoc();
            $stmtStock->close();
            if (!$datoStock || (int)$datoStock["stock"] < 1) { unset($_SESSION["carrito"][$id]); }
            else { $_SESSION["carrito"][$id] = min($cantidad, (int)$datoStock["stock"]); }
        }
        header("Location: carrito.php?msg=actualizado");
        exit;
    }
}
require "header.php";
$items = [];
$total = 0;
if (isset($_SESSION["carrito"]) && is_array($_SESSION["carrito"]) && count($_SESSION["carrito"]) > 0) {
    $ids = array_filter(array_map("intval", array_keys($_SESSION["carrito"])), function($id) { return $id > 0; });
    if (count($ids) > 0) {
        $consulta = $conexion->query("SELECT id, nombre, marca, precio, stock FROM perfumes WHERE id IN (" . implode(",", $ids) . ")");
        while ($fila = $consulta->fetch_assoc()) {
            $cantidad = min((int)$_SESSION["carrito"][$fila["id"]], (int)$fila["stock"]);
            if ($cantidad < 1) { unset($_SESSION["carrito"][$fila["id"]]); continue; }
            $_SESSION["carrito"][$fila["id"]] = $cantidad;
            $fila["cantidad"] = $cantidad;
            $fila["subtotal"] = (float)$fila["precio"] * $cantidad;
            $total += $fila["subtotal"];
            $items[] = $fila;
        }
    }
}
?>
<div class="titulo-fila">
  <h1>🛒 Tu carrito de compras</h1>
  <a class="btn-borde" href="catalogo.php">Seguir comprando</a>
</div>
<?php if ($mensaje !== ""): ?><p class="aviso"><?php echo e($mensaje); ?></p><?php endif; ?>
<?php if (isset($_GET["msg"]) && $_GET["msg"] === "actualizado"): ?><p class="aviso">Carrito actualizado.</p><?php endif; ?>
<?php if (count($items) === 0): ?>
  <p>Tu carrito está vacío. Explora el catálogo y agrega tus perfumes favoritos.</p>
  <a class="btn" href="catalogo.php">Ver catálogo</a>
<?php else: ?>
  <form method="POST" action="carrito.php">
    <div class="tabla-scroll carrito-tabla">
      <table>
        <thead><tr><th>Perfume</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th>Acción</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><strong><?php echo e($item["nombre"]); ?></strong><br><small><?php echo e($item["marca"]); ?></small></td>
            <td>$<?php echo number_format($item["precio"], 0, ",", "."); ?></td>
            <td><input type="number" name="cantidades[<?php echo (int)$item["id"]; ?>]" min="0" max="<?php echo (int)$item["stock"]; ?>" value="<?php echo (int)$item["cantidad"]; ?>" style="max-width:90px"></td>
            <td>$<?php echo number_format($item["subtotal"], 0, ",", "."); ?></td>
            <td><button type="submit" name="eliminar" value="<?php echo (int)$item["id"]; ?>" class="peligro">Quitar</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="carrito-acciones"><button type="submit" name="actualizar" value="1">Actualizar cantidades</button></div>
  </form>
  <p class="carrito-total">Total: $<?php echo number_format($total, 0, ",", "."); ?></p>
  <p>Para finalizar, inicia sesión o crea una cuenta. Por ahora está disponible el pago contra entrega.</p><?php if (!empty($_SESSION["cliente_id"])): ?><a class="btn" href="pedido.php">Finalizar compra</a><?php else: ?><a class="btn" href="cliente_login.php">Iniciar sesión para comprar</a><a class="btn-borde" href="registro.php">Crear cuenta</a><?php endif; ?>
<?php endif; ?>
<?php
$conexion->close();
require "footer.php";
?>