<?php
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/cuenta_helpers.php";
$secrets_file = __DIR__ . "/cuenta_secrets.php";
if (is_file($secrets_file)) { require_once $secrets_file; }

function aroma_credencial($constante, $variable) {
    if (defined($constante) && constant($constante) !== "") { return (string)constant($constante); }
    return (string)(getenv($variable) ?: "");
}
$proveedor = strtolower(trim((string)($_GET["provider"] ?? "")));
if (!in_array($proveedor, ["google", "facebook"], true)) {
    header("Location: cliente_login.php?oauth=proveedor");
    exit;
}
$credenciales = [
    "google" => [
        "id" => aroma_credencial("AROMA_GOOGLE_CLIENT_ID", "GOOGLE_CLIENT_ID"),
        "secret" => aroma_credencial("AROMA_GOOGLE_CLIENT_SECRET", "GOOGLE_CLIENT_SECRET"),
        "authorize" => "https://accounts.google.com/o/oauth2/v2/auth",
        "token" => "https://oauth2.googleapis.com/token",
        "userinfo" => "https://openidconnect.googleapis.com/v1/userinfo",
        "redirect" => "https://aromaluxe.freehosting.dev/oauth.php?provider=google&callback=1"
    ],
    "facebook" => [
        "id" => aroma_credencial("AROMA_FACEBOOK_APP_ID", "FACEBOOK_APP_ID"),
        "secret" => aroma_credencial("AROMA_FACEBOOK_APP_SECRET", "FACEBOOK_APP_SECRET"),
        "authorize" => "https://www.facebook.com/v21.0/dialog/oauth",
        "token" => "https://graph.facebook.com/v21.0/oauth/access_token",
        "userinfo" => "https://graph.facebook.com/me?fields=id,name,email",
        "redirect" => "https://aromaluxe.freehosting.dev/oauth.php?provider=facebook&callback=1"
    ]
];
$c = $credenciales[$proveedor];
function oauth_fallo($mensaje) {
    $_SESSION["oauth_error"] = $mensaje;
    header("Location: cliente_login.php");
    exit;
}
if (empty($c["id"]) || empty($c["secret"])) {
    oauth_fallo("El acceso con " . ucfirst($proveedor) . " todavía necesita sus credenciales oficiales en el hosting.");
}
if (empty($_GET["callback"])) {
    $estado = bin2hex(random_bytes(32));
    $_SESSION["oauth_state"] = $estado;
    $_SESSION["oauth_provider"] = $proveedor;
    $params = [
        "client_id" => $c["id"],
        "redirect_uri" => $c["redirect"],
        "response_type" => "code",
        "scope" => $proveedor === "google" ? "openid email profile" : "email,public_profile",
        "state" => $estado
    ];
    if ($proveedor === "google") { $params["prompt"] = "select_account"; }
    header("Location: " . $c["authorize"] . "?" . http_build_query($params));
    exit;
}
$estado_recibido = (string)($_GET["state"] ?? "");
$estado_guardado = (string)($_SESSION["oauth_state"] ?? "");
$proveedor_guardado = (string)($_SESSION["oauth_provider"] ?? "");
unset($_SESSION["oauth_state"], $_SESSION["oauth_provider"]);
if ($estado_recibido === "" || $estado_guardado === "" || !hash_equals($estado_guardado, $estado_recibido) || $proveedor_guardado !== $proveedor) {
    oauth_fallo("No se pudo validar el acceso social. Inténtalo de nuevo.");
}
if (!empty($_GET["error"]) || empty($_GET["code"])) {
    oauth_fallo("Cancelaste o no se pudo completar el acceso con " . ucfirst($proveedor) . ".");
}
if (!function_exists("curl_init")) {
    oauth_fallo("El hosting necesita habilitar cURL para conectar con " . ucfirst($proveedor) . ".");
}
$datos_token = [
    "client_id" => $c["id"],
    "client_secret" => $c["secret"],
    "redirect_uri" => $c["redirect"],
    "code" => (string)$_GET["code"],
    "grant_type" => "authorization_code"
];
$ch = curl_init($c["token"]);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($datos_token),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ["Accept: application/json"]
]);
$respuesta_token = curl_exec($ch);
$codigo_http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$token_json = json_decode((string)$respuesta_token, true);
$access_token = (string)($token_json["access_token"] ?? "");
if ($codigo_http < 200 || $codigo_http >= 300 || $access_token === "") {
    error_log("Aroma Luxe OAuth " . $proveedor . ": no se pudo obtener token.");
    oauth_fallo("El proveedor no pudo validar el acceso. Inténtalo otra vez.");
}
$url_usuario = $c["userinfo"];
if ($proveedor === "facebook") { $url_usuario .= "&access_token=" . urlencode($access_token); }
$ch = curl_init($url_usuario);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token, "Accept: application/json"]
]);
$respuesta_usuario = curl_exec($ch);
$codigo_http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$usuario = json_decode((string)$respuesta_usuario, true);
$proveedor_id = (string)($usuario["sub"] ?? $usuario["id"] ?? "");
$correo = strtolower(trim((string)($usuario["email"] ?? "")));
$nombre = trim((string)($usuario["name"] ?? ""));
if ($codigo_http < 200 || $codigo_http >= 300 || $proveedor_id === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL) || $nombre === "") {
    oauth_fallo("El proveedor no entregó un correo válido. Comprueba los permisos de email de la aplicación.");
}
if ($proveedor === "google" && empty($usuario["email_verified"])) {
    oauth_fallo("Google no confirmó que el correo esté verificado.");
}
$conexion = conectar();
if (!cuenta_tabla_social($conexion)) {
    error_log("Aroma Luxe: no se pudo preparar la tabla de cuentas sociales.");
    oauth_fallo("No se pudo preparar el acceso social en la base de datos.");
}
$stmt = $conexion->prepare("SELECT c.id, c.nombre FROM cliente_social s JOIN clientes c ON c.id = s.cliente_id WHERE s.proveedor = ? AND s.proveedor_usuario_id = ? LIMIT 1");
$stmt->bind_param("ss", $proveedor, $proveedor_id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cliente) {
    $stmt = $conexion->prepare("SELECT id FROM clientes WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($existente) {
        oauth_fallo("Ya existe una cuenta con ese correo. Inicia sesión con correo y contraseña primero; por seguridad, no vinculamos cuentas automáticamente.");
    }
    $clave_temporal = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $stmt = $conexion->prepare("INSERT INTO clientes (nombre, correo, telefono, clave_hash) VALUES (?, ?, '', ?)");
    $stmt->bind_param("sss", $nombre, $correo, $clave_temporal);
    if (!$stmt->execute()) {
        $stmt->close();
        oauth_fallo("No se pudo crear tu cuenta social. Inténtalo otra vez.");
    }
    $cliente_id = (int)$stmt->insert_id;
    $stmt->close();
    $stmt = $conexion->prepare("INSERT INTO cliente_social (cliente_id, proveedor, proveedor_usuario_id, correo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $cliente_id, $proveedor, $proveedor_id, $correo);
    if (!$stmt->execute()) {
        $stmt->close();
        oauth_fallo("No se pudo asociar el acceso social a tu cuenta. Inténtalo de nuevo.");
    }
    $stmt->close();
    $cliente = ["id" => $cliente_id, "nombre" => $nombre];
}
cuenta_iniciar_sesion_cliente($cliente["id"], $cliente["nombre"]);
if (!empty($_SESSION["continuar_compra"])) { unset($_SESSION["continuar_compra"]); header("Location: pedido.php"); } else { header("Location: mi_cuenta.php"); }
exit;
