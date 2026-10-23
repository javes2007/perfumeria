<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/admin_config.php";
require_once __DIR__ . "/conexion.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $clave = isset($_POST["clave"]) ? (string)$_POST["clave"] : "";
    if (ADMIN_PASSWORD_HASH === "CONFIGURA_AQUI_EL_HASH" || ADMIN_PASSWORD_HASH === "") {
        $error = "Falta configurar la contraseña de administración en admin_config.php antes de iniciar sesión.";
    } elseif (password_verify($clave, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION["admin_autenticado"] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Contraseña incorrecta.";
    }
}
$titulo = "Acceso de administración";
require "header.php";
?>
<h1>Acceso de administración</h1>
<p>Esta zona está protegida. Solo el administrador debe ingresar.</p>
<?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
<form method="POST" action="login.php" class="formulario">
  <label>Contraseña de administración
    <input type="password" name="clave" autocomplete="current-password" required>
  </label>
  <button type="submit">Ingresar</button>
  <a href="index.html">Volver a la tienda</a>
</form>
<?php require "footer.php"; ?>
