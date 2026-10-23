<?php
require_once __DIR__ . "/cuenta_helpers.php";
$secrets_file = __DIR__ . "/cuenta_secrets.php";
if (is_file($secrets_file)) { require_once $secrets_file; }
function aroma_config_value($constante, $variable) {
    if (defined($constante) && constant($constante) !== "") { return (string)constant($constante); }
    return (string)(getenv($variable) ?: "");
}
$google_ok = aroma_config_value("AROMA_GOOGLE_CLIENT_ID", "GOOGLE_CLIENT_ID") !== "" && aroma_config_value("AROMA_GOOGLE_CLIENT_SECRET", "GOOGLE_CLIENT_SECRET") !== "";
$facebook_ok = aroma_config_value("AROMA_FACEBOOK_APP_ID", "FACEBOOK_APP_ID") !== "" && aroma_config_value("AROMA_FACEBOOK_APP_SECRET", "FACEBOOK_APP_SECRET") !== "";
$titulo = "Configuración de acceso social";
require __DIR__ . "/header.php";
?>
<section class="formulario-cuenta">
  <h1>Acceso con Google y Facebook</h1>
  <p>El flujo OAuth ya está integrado. Para activarlo, crea las aplicaciones oficiales y coloca las claves en un archivo privado del hosting.</p>
  <h2>Estado</h2>
  <p>Google: <strong><?php echo $google_ok ? "credenciales detectadas" : "falta configurar las credenciales"; ?></strong></p>
  <p>Facebook: <strong><?php echo $facebook_ok ? "credenciales detectadas" : "falta configurar las credenciales"; ?></strong></p>
  <h2>URL de retorno que debes registrar exactamente</h2>
  <p><strong>Google</strong></p>
  <code class="url-oauth">https://aromaluxe.freehosting.dev/oauth.php?provider=google&amp;callback=1</code>
  <p><strong>Facebook</strong></p>
  <code class="url-oauth">https://aromaluxe.freehosting.dev/oauth.php?provider=facebook&amp;callback=1</code>
  <h2>Archivo privado de configuración</h2>
  <p>En la carpeta principal del sitio (junto a conexion.php), crea <code>cuenta_secrets.php</code> con este contenido y reemplaza los textos de ejemplo por tus credenciales reales. Este archivo no se debe subir a GitHub ni compartir.</p>
  <pre class="bloque-config"><code>&lt;?php
define('AROMA_GOOGLE_CLIENT_ID', 'TU_CLIENT_ID_DE_GOOGLE');
define('AROMA_GOOGLE_CLIENT_SECRET', 'TU_CLIENT_SECRET_DE_GOOGLE');
define('AROMA_FACEBOOK_APP_ID', 'TU_APP_ID_DE_FACEBOOK');
define('AROMA_FACEBOOK_APP_SECRET', 'TU_APP_SECRET_DE_FACEBOOK');
define('AROMA_MAIL_FROM', 'correo-verificado@tudominio.com');</code></pre>
  <p>En Google Cloud configura el dominio autorizado <code>aromaluxe.freehosting.dev</code> y la URL de retorno indicada. En Meta for Developers activa Facebook Login, solicita el permiso <code>email</code> y registra la URL de retorno. Las aplicaciones deben estar disponibles para las cuentas que probarán el acceso.</p>
  <p>El hosting debe tener PHP cURL habilitado. Para recuperación de contraseña, el servidor debe permitir mail() y el remitente AROMA_MAIL_FROM debe ser una dirección válida que el hosting permita usar. Si el hosting no envía correo con mail(), habrá que configurar SMTP. No compartas las claves secretas.</p>
</section>
<?php require __DIR__ . "/footer.php"; ?>
