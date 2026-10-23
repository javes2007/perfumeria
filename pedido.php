<?php
require_once __DIR__ . "/cuenta_helpers.php";
require_once __DIR__ . "/conexion.php";
if (empty($_SESSION["cliente_id"])) { $_SESSION["continuar_compra"] = true; header("Location: cliente_login.php"); exit; }
$conexion = conectar();
$conexion->query("CREATE TABLE IF NOT EXISTS pedido_entregas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 pedido_id INT NOT NULL UNIQUE,
 nombre_recibe VARCHAR(120) NOT NULL,
 telefono VARCHAR(30) NOT NULL,
 direccion VARCHAR(255) NOT NULL,
 ciudad VARCHAR(100) NOT NULL,
 indicaciones VARCHAR(500) NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_entrega_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
if (empty($_SESSION["carrito"]) || !is_array($_SESSION["carrito"])) { header("Location: carrito.php"); exit; }
$error = ""; $items = []; $total = 0;
$ids = array_filter(array_map("intval", array_keys($_SESSION["carrito"])), function($id){ return $id > 0; });
if (!$ids) { header("Location: carrito.php"); exit; }
$res = $conexion->query("SELECT id,nombre,precio,stock FROM perfumes WHERE id IN (" . implode(",", $ids) . ")");
while ($row = $res->fetch_assoc()) {
    $cant = (int)($_SESSION["carrito"][$row["id"]] ?? 0);
    if ($cant < 1 || $cant > (int)$row["stock"]) { $error = "Hay un producto sin stock suficiente. Actualiza tu carrito."; break; }
    $row["cantidad"] = $cant; $row["subtotal"] = $cant * (float)$row["precio"]; $total += $row["subtotal"]; $items[] = $row;
}
$nombre_recibe = trim((string)($_POST["nombre_recibe"] ?? $_SESSION["cliente_nombre"] ?? ""));
$telefono = trim((string)($_POST["telefono"] ?? ""));
$direccion = trim((string)($_POST["direccion"] ?? ""));
$ciudad = trim((string)($_POST["ciudad"] ?? "Montería, Córdoba"));
$indicaciones = trim((string)($_POST["indicaciones"] ?? ""));
if ($_SERVER["REQUEST_METHOD"] === "POST" && $error === "" && count($items) === count($ids)) {
    if (!cuenta_validar_csrf()) {
        $error = "La sesión del formulario venció. Recarga la página e inténtalo otra vez.";
    } elseif ($nombre_recibe === "" || $telefono === "" || $direccion === "" || $ciudad === "") {
        $error = "Completa el nombre de quien recibe, teléfono, dirección y ciudad.";
    } elseif (strlen($nombre_recibe) > 120 || strlen($telefono) > 30 || strlen($direccion) > 255 || strlen($ciudad) > 100 || strlen($indicaciones) > 500) {
        $error = "Uno de los datos de entrega supera la longitud permitida.";
    } else {
        $clienteId = (int)$_SESSION["cliente_id"];
        $conexion->begin_transaction();
        try {
            $stmt = $conexion->prepare("INSERT INTO pedidos (cliente_id,total,metodo_pago,estado) VALUES (?,?,'contraentrega','Pedido recibido - pendiente de confirmación')");
            $stmt->bind_param("id", $clienteId, $total);
            if (!$stmt->execute()) { throw new Exception("No se pudo crear el pedido."); }
            $pedidoId = (int)$stmt->insert_id; $stmt->close();
            $detalle = $conexion->prepare("INSERT INTO pedido_detalles (pedido_id,perfume_id,nombre_producto,precio,cantidad,subtotal) VALUES (?,?,?,?,?,?)");
            $stockStmt = $conexion->prepare("UPDATE perfumes SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($items as $it) {
                $pid=(int)$it["id"]; $cant=(int)$it["cantidad"]; $nombre=$it["nombre"]; $precio=(float)$it["precio"]; $subtotal=(float)$it["subtotal"];
                $detalle->bind_param("iisdid", $pedidoId, $pid, $nombre, $precio, $cant, $subtotal);
                if (!$detalle->execute()) { throw new Exception("No se pudo guardar un producto del pedido."); }
                $stockStmt->bind_param("iii", $cant, $pid, $cant);
                if (!$stockStmt->execute() || $stockStmt->affected_rows !== 1) { throw new Exception("Stock insuficiente."); }
            }
            $detalle->close(); $stockStmt->close();
            $entrega = $conexion->prepare("INSERT INTO pedido_entregas (pedido_id,nombre_recibe,telefono,direccion,ciudad,indicaciones) VALUES (?,?,?,?,?,?)");
            $entrega->bind_param("isssss", $pedidoId, $nombre_recibe, $telefono, $direccion, $ciudad, $indicaciones);
            if (!$entrega->execute()) { throw new Exception("No se pudieron guardar los datos de entrega."); }
            $entrega->close();
            $conexion->commit();
            unset($_SESSION["carrito"], $_SESSION["continuar_compra"]);
            header("Location: pedido_confirmado.php?id=" . $pedidoId); exit;
        } catch (Throwable $e) {
            $conexion->rollback();
            $error = "No se pudo registrar el pedido. Revisa el stock e inténtalo otra vez.";
            error_log("Aroma Luxe pedido: " . $e->getMessage());
        }
    }
}
$titulo = "Finalizar compra";
require __DIR__ . "/header.php";
?>
<section class="formulario-cuenta checkout-cuenta">
<h1>Finalizar compra</h1>
<p>Completa la información de entrega. Por ahora puedes confirmar el pedido con pago contra entrega; pagarás cuando recibas tus perfumes.</p>
<?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
<?php if ($items): ?>
<div class="resumen-pedido"><h2>Resumen del pedido</h2>
<ul><?php foreach ($items as $it): ?><li><?php echo e($it["nombre"]); ?> × <?php echo (int)$it["cantidad"]; ?> — $<?php echo number_format($it["subtotal"],0,",","."); ?></li><?php endforeach; ?></ul>
<p class="carrito-total">Total: $<?php echo number_format($total,0,",","."); ?></p></div>
<form method="post" class="formulario">
<input type="hidden" name="csrf_token" value="<?php echo e(cuenta_csrf_token()); ?>">
<label>Nombre de quien recibe<input name="nombre_recibe" required maxlength="120" autocomplete="name" value="<?php echo e($nombre_recibe); ?>"></label>
<label>Teléfono de contacto<input type="tel" name="telefono" required maxlength="30" autocomplete="tel" value="<?php echo e($telefono); ?>"></label>
<label>Dirección completa de entrega<input name="direccion" required maxlength="255" autocomplete="street-address" placeholder="Barrio, calle, número, casa o apartamento" value="<?php echo e($direccion); ?>"></label>
<label>Ciudad y departamento<input name="ciudad" required maxlength="100" value="<?php echo e($ciudad); ?>"></label>
<label>Indicaciones para entregar (opcional)<textarea name="indicaciones" maxlength="500" rows="3" placeholder="Punto de referencia, horario, etc."><?php echo e($indicaciones); ?></textarea></label>
<div class="metodo-pago-elegido"><strong>Forma de pago:</strong> Contra entrega — pagas al recibir el pedido.</div>
<button type="submit">Confirmar compra · $<?php echo number_format($total,0,",","."); ?></button>
</form>
<p><small>El pedido quedará registrado como pendiente de confirmación. El pago en línea con Wompi se habilitará al configurar y validar las credenciales de comercio.</small></p>
<?php endif; ?>
</section>
<?php require __DIR__ . "/footer.php"; $conexion->close(); ?>