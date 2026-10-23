<?php
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuenta_helpers.php";
$conexion = conectar();
cuenta_tabla_recuperacion($conexion);
$token = trim((string)($_GET["token"] ?? $_POST["token"] ?? ""));
$token_valido = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$error = "";
$exito = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!cuenta_validar_csrf()) {
        $error = "La sesión del formulario venció. Recarga el enlace e inténtalo otra vez.";
    } elseif (!$token_valido) {
        $error = "El enlace no es válido. Solicita uno nuevo.";
    } else {
        $hash_token = hash("sha256", $token);
        $stmt = $conexion->prepare("SELECT id, cliente_id FROM recuperacion_claves WHERE token_hash = ? AND usado = 0 AND vence_en > NOW() LIMIT 1");
        $stmt->bind_param("s", $hash_token);
        $stmt->execute();
        $registro = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $clave = (string)($_POST["clave"] ?? "");
        $confirmar = (string)($_POST["confirmar_clave"] ?? "");
        if (!$registro) {
            $error = "El enlace venció o ya fue utilizado. Solicita uno nuevo.";
        } elseif (strlen($clave) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres.";
        } elseif ($clave !== $confirmar) {
            $error = "Las contraseñas no coinciden.";
        } else {
            $id_cliente = (int)$registro["cliente_id"];
            $hash_clave = password_hash($clave, PASSWORD_DEFAULT);
            $conexion->begin_transaction();
            $stmt = $conexion->prepare("UPDATE clientes SET clave_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hash_clave, $id_cliente);
            $ok_clave = $stmt->execute() && $stmt->affected_rows >= 0;
            $stmt->close();
            $id_token = (int)$registro["id"];
            $stmt = $conexion->prepare("UPDATE recuperacion_claves SET usado = 1 WHERE id = ? AND usado = 0");
            $stmt->bind_param("i", $id_token);
            $ok_token = $stmt->execute() && $stmt->affected_rows === 1;
            $stmt->close();

            if ($ok_clave && $ok_token) {
                $conexion->commit();
                $exito = true;
            } else {
                $conexion->rollback();
                $error = "No se pudo actualizar la contraseña. Solicita un enlace nuevo.";
            }
        }
    }
}
$titulo = "Crear contraseña nueva";
require __DIR__ . "/header.php";
?>
<section class="formulario-cuenta">
  <h1>Crear contraseña nueva</h1>
  <?php if ($exito): ?>
    <p class="mensaje-cuenta">Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.</p>
    <p><a class="btn-borde" href="cliente_login.php">Iniciar sesión</a></p>
  <?php else: ?>
    <?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
    <?php if (!$token_valido && $error === ""): ?><p class="error">El enlace no es válido. Solicita uno nuevo.</p><?php endif; ?>
    <?php
      $mostrar_formulario = false;
      if ($token_valido && $_SERVER["REQUEST_METHOD"] !== "POST") {
          $h = hash("sha256", $token);
          $stmt = $conexion->prepare("SELECT id FROM recuperacion_claves WHERE token_hash = ? AND usado = 0 AND vence_en > NOW() LIMIT 1");
          $stmt->bind_param("s", $h);
          $stmt->execute();
          $mostrar_formulario = (bool)$stmt->get_result()->fetch_assoc();
          $stmt->close();
          if (!$mostrar_formulario) { echo '<p class="error">El enlace venció o ya fue utilizado. Solicita uno nuevo.</p>'; }
      } elseif ($token_valido && $_SERVER["REQUEST_METHOD"] === "POST" && strpos($error, "contraseña") !== false) {
          $mostrar_formulario = true;
      }
    ?>
    <?php if ($mostrar_formulario): ?>
      <form method="post" class="formulario">
        <input type="hidden" name="csrf_token" value="<?php echo e(cuenta_csrf_token()); ?>">
        <input type="hidden" name="token" value="<?php echo e($token); ?>">
        <label>Contraseña nueva (mínimo 8 caracteres)<input type="password" name="clave" required minlength="8" autocomplete="new-password"></label>
        <label>Repite la contraseña<input type="password" name="confirmar_clave" required minlength="8" autocomplete="new-password"></label>
        <button type="submit">Guardar contraseña nueva</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
  <p><a href="recuperar_clave.php">Solicitar otro enlace</a></p>
</section>
<?php require __DIR__ . "/footer.php"; $conexion->close(); ?>
