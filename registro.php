<?php
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuenta_helpers.php";
$conexion = conectar();
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = strtolower(trim($_POST["correo"] ?? ""));
    $telefono = trim($_POST["telefono"] ?? "");
    $clave = (string)($_POST["clave"] ?? "");
    if (!cuenta_validar_csrf()) {
        $error = "La sesión del formulario venció. Recarga la página e inténtalo otra vez.";
    } elseif ($nombre === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($clave) < 8) {
        $error = "Completa los datos y usa una contraseña de al menos 8 caracteres.";
    } else {
        $stmt = $conexion->prepare("SELECT id FROM clientes WHERE correo = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) { $error = "Ya existe una cuenta con ese correo. Inicia sesión o recupera tu contraseña."; }
        $stmt->close();
        if ($error === "") {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("INSERT INTO clientes (nombre, correo, telefono, clave_hash) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $correo, $telefono, $hash);
            if ($stmt->execute()) {
                $id_cliente = (int)$stmt->insert_id;
                $stmt->close();
                cuenta_iniciar_sesion_cliente($id_cliente, $nombre);
                if (!empty($_SESSION["continuar_compra"])) { unset($_SESSION["continuar_compra"]); header("Location: pedido.php"); } else { header("Location: mi_cuenta.php"); }
                exit;
            }
            $stmt->close();
            $error = "No se pudo crear la cuenta. Inténtalo de nuevo.";
        }
    }
}
$titulo = "Crear cuenta";
require __DIR__ . "/header.php";
?>
<section class="formulario-cuenta">
  <h1>Crear tu cuenta</h1>
  <p>Guarda tus datos y consulta tus pedidos de Aroma Luxe.</p>
  <?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
  <form method="post" class="formulario" autocomplete="on">
    <input type="hidden" name="csrf_token" value="<?php echo e(cuenta_csrf_token()); ?>">
    <label>Nombre completo<input name="nombre" required maxlength="120" autocomplete="name" value="<?php echo e($_POST["nombre"] ?? ""); ?>"></label>
    <label>Correo electrónico<input type="email" name="correo" required maxlength="190" autocomplete="email" value="<?php echo e($_POST["correo"] ?? ""); ?>"></label>
    <label>Teléfono<input type="tel" name="telefono" maxlength="30" autocomplete="tel" value="<?php echo e($_POST["telefono"] ?? ""); ?>"></label>
    <label>Contraseña (mínimo 8 caracteres)<input type="password" name="clave" required minlength="8" autocomplete="new-password"></label>
    <button type="submit">Crear cuenta</button>
  </form>
  <p>¿Ya tienes cuenta? <a href="cliente_login.php">Inicia sesión</a></p>
</section>
<?php require __DIR__ . "/footer.php"; $conexion->close(); ?>
