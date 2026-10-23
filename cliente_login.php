<?php
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuenta_helpers.php";
$conexion = conectar();
$error = "";
$mensaje_social = trim((string)($_SESSION["oauth_error"] ?? ""));
unset($_SESSION["oauth_error"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!cuenta_validar_csrf()) {
        $error = "La sesión del formulario venció. Recarga la página e inténtalo otra vez.";
    } else {
        $correo = strtolower(trim($_POST["correo"] ?? ""));
        $clave = (string)($_POST["clave"] ?? "");
        $stmt = $conexion->prepare("SELECT id, nombre, clave_hash FROM clientes WHERE correo = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($fila && password_verify($clave, $fila["clave_hash"])) {
            cuenta_iniciar_sesion_cliente($fila["id"], $fila["nombre"]);
            if (!empty($_SESSION["continuar_compra"])) { unset($_SESSION["continuar_compra"]); header("Location: pedido.php"); } else { header("Location: mi_cuenta.php"); }
            exit;
        } else {
            $error = "Correo o contraseña incorrectos.";
        }
    }
}
$titulo = "Iniciar sesión";
require __DIR__ . "/header.php";
?>
<section class="formulario-cuenta">
  <h1>Iniciar sesión</h1>
  <p>Entra a tu cuenta para consultar tus datos y pedidos de Aroma Luxe.</p>
  <?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
  <?php if ($mensaje_social !== ""): ?><p class="error"><?php echo e($mensaje_social); ?></p><?php endif; ?>
  <form method="post" class="formulario">
    <input type="hidden" name="csrf_token" value="<?php echo e(cuenta_csrf_token()); ?>">
    <label>Correo electrónico<input type="email" name="correo" required autocomplete="email" maxlength="190" value="<?php echo e($_POST["correo"] ?? ""); ?>"></label>
    <label>Contraseña<input type="password" name="clave" required autocomplete="current-password"></label>
    <button type="submit">Entrar a mi cuenta</button>
  </form>
  <p class="enlace-recuperacion"><a href="recuperar_clave.php">¿Olvidaste tu contraseña?</a></p>
  <div class="separador-social"><span>o continúa con</span></div>
  <div class="botones-sociales">
    <a class="btn-social btn-google" href="oauth.php?provider=google"><span aria-hidden="true">G</span> Continuar con Google</a>
    <a class="btn-social btn-facebook" href="oauth.php?provider=facebook"><span aria-hidden="true">f</span> Continuar con Facebook</a>
  </div>
  <p class="crear-cuenta-enlace">¿Nuevo en Aroma Luxe? <a href="registro.php">Crear cuenta</a></p>
</section>
<?php require __DIR__ . "/footer.php"; $conexion->close(); ?>
