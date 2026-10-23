<?php
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuenta_helpers.php";
$secrets_file = __DIR__ . "/cuenta_secrets.php";
if (is_file($secrets_file)) { require_once $secrets_file; }
$conexion = conectar();
cuenta_tabla_recuperacion($conexion);
$error = "";
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!cuenta_validar_csrf()) {
        $error = "La sesión del formulario venció. Recarga la página e inténtalo otra vez.";
    } else {
        $correo = strtolower(trim($_POST["correo"] ?? ""));
        if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conexion->prepare("SELECT id, nombre FROM clientes WHERE correo = ? LIMIT 1");
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $cliente = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($cliente) {
                $token = bin2hex(random_bytes(32));
                $token_hash = hash("sha256", $token);
                $vence = date("Y-m-d H:i:s", time() + 3600);
                $id = (int)$cliente["id"];
                $stmt = $conexion->prepare("UPDATE recuperacion_claves SET usado = 1 WHERE cliente_id = ? AND usado = 0");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conexion->prepare("INSERT INTO recuperacion_claves (cliente_id, token_hash, vence_en) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $id, $token_hash, $vence);
                $guardado = $stmt->execute();
                $stmt->close();

                if ($guardado) {
                    $url = "https://aromaluxe.freehosting.dev/restablecer_clave.php?token=" . urlencode($token);
                    $desde = (defined("AROMA_MAIL_FROM") && AROMA_MAIL_FROM !== "") ? AROMA_MAIL_FROM : (getenv("AROMA_MAIL_FROM") ?: "no-reply@aromaluxe.freehosting.dev");
                    $asunto = "Recupera tu contraseña de Aroma Luxe";
                    $cuerpo = "Hola " . $cliente["nombre"] . ",\n\n";
                    $cuerpo .= "Recibimos una solicitud para cambiar la contraseña de tu cuenta Aroma Luxe.\n";
                    $cuerpo .= "Abre este enlace dentro de la próxima hora:\n" . $url . "\n\n";
                    $cuerpo .= "Si no solicitaste el cambio, ignora este mensaje.\n\nAroma Luxe";
                    $cabeceras = "From: Aroma Luxe <" . $desde . ">\r\n";
                    $cabeceras .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    $enviado = @mail($correo, "=?UTF-8?B?" . base64_encode($asunto) . "?=", $cuerpo, $cabeceras);
                    if (!$enviado) {
                        error_log("Aroma Luxe: PHP mail() no pudo enviar un correo de recuperación.");
                    }
                }
            }
            // Mensaje genérico: no revela si el correo está registrado.
            $mensaje = "Si el correo pertenece a una cuenta y el servidor puede enviar mensajes, recibirás un enlace para cambiar la contraseña.";
        } else {
            $mensaje = "Si el correo pertenece a una cuenta y el servidor puede enviar mensajes, recibirás un enlace para cambiar la contraseña.";
        }
    }
}
$titulo = "Recuperar contraseña";
require __DIR__ . "/header.php";
?>
<section class="formulario-cuenta">
  <h1>Recuperar contraseña</h1>
  <p>Escribe el correo con el que creaste tu cuenta. El enlace de recuperación vence en una hora.</p>
  <?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
  <?php if ($mensaje !== ""): ?><p class="mensaje-cuenta"><?php echo e($mensaje); ?></p><?php endif; ?>
  <form method="post" class="formulario">
    <input type="hidden" name="csrf_token" value="<?php echo e(cuenta_csrf_token()); ?>">
    <label>Correo electrónico<input type="email" name="correo" required autocomplete="email" maxlength="190"></label>
    <button type="submit">Enviar enlace de recuperación</button>
  </form>
  <p><a href="cliente_login.php">Volver al inicio de sesión</a></p>
</section>
<?php require __DIR__ . "/footer.php"; $conexion->close(); ?>
