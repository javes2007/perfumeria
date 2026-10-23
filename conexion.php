<?php
// Conexión de la perfumería (Aroma Luxe) + funciones de ayuda.
// - En InfinityFree el despliegue crea config-hosting.php con los datos del hosting.
// - En local (XAMPP / Termux) no existe ese archivo y se usan los valores por defecto.

function conectar() {
    $archivo = __DIR__ . '/config-hosting.php';
    $esHosting = is_file($archivo);

    if ($esHosting) {
        $c = require $archivo;
        $host    = $c['host'] ?? '';
        $usuario = $c['user'] ?? '';
        $clave   = $c['password'] ?? '';
        $bd      = $c['name'] ?? '';
        ini_set('display_errors', '0');
    } else {
        $host    = "127.0.0.1";
        $usuario = "root";
        $clave   = "";
        $bd      = "perfumeria";
        ini_set('display_errors', '1');
    }
    error_reporting(E_ALL);
    mysqli_report(MYSQLI_REPORT_OFF);

    $conexion = new mysqli($host, $usuario, $clave, $bd);
    if ($conexion->connect_errno) {
        if ($esHosting) {
            error_log("Error de conexión MySQL: " . $conexion->connect_error);
            http_response_code(500);
            die("No se pudo conectar a la base de datos. Revisa la configuración del sitio.");
        }
        die("No se pudo conectar a la base de datos: " . $conexion->connect_error . " -- ¿Importaste database.sql?");
    }
    $conexion->set_charset("utf8mb4");
    return $conexion;
}

// Escapa texto para mostrarlo en HTML de forma segura
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// Guarda la imagen subida en /uploads. Devuelve el nombre del archivo,
// null si no se subió nada, y llena $error si algo salió mal.
function subir_imagen($campo, &$error) {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$campo];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = "No se pudo subir la imagen (código " . $f['error'] . ").";
        return null;
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        $error = "La imagen es muy pesada (máximo 2 MB).";
        return null;
    }
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = new finfo(FILEINFO_MIME_TYPE);
    $mime = $info->file($f['tmp_name']);
    if (!isset($permitidos[$mime])) {
        $error = "Formato no permitido. Usa JPG, PNG o WEBP.";
        return null;
    }
    $nombre = bin2hex(random_bytes(8)) . "." . $permitidos[$mime];
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . "/uploads/" . $nombre)) {
        $error = "No se pudo guardar la imagen en la carpeta uploads.";
        return null;
    }
    return $nombre;
}

// Borra un archivo de /uploads (si existe)
function borrar_imagen($nombre) {
    if ($nombre && $nombre === basename($nombre)) {
        $ruta = __DIR__ . "/uploads/" . $nombre;
        if (is_file($ruta)) { unlink($ruta); }
    }
}

// Imagen de un perfume: la subida o un frasco dibujado si no tiene foto
function imagen_perfume($imagen, $alt = "") {
    if ($imagen) {
        return '<img src="uploads/' . e($imagen) . '" alt="' . e($alt) . '" loading="lazy">';
    }
    return '<div class="sin-foto" aria-label="Sin foto">' . frasco_svg() . '</div>';
}

function frasco_svg() {
    return '<svg viewBox="0 0 120 160" xmlns="http://www.w3.org/2000/svg" role="img">
      <rect x="46" y="8" width="28" height="22" rx="4" fill="#2b2326"/>
      <rect x="52" y="28" width="16" height="12" fill="#c9a96e"/>
      <rect x="22" y="40" width="76" height="106" rx="18" fill="#f3d9e2" stroke="#8c3b5a" stroke-width="3"/>
      <rect x="34" y="62" width="52" height="50" rx="8" fill="#fff" opacity=".75"/>
      <text x="60" y="92" text-anchor="middle" font-family="Georgia,serif" font-size="11" fill="#6d2d46">AROMA</text>
      <text x="60" y="104" text-anchor="middle" font-family="Georgia,serif" font-size="9" fill="#6d2d46">LUXE</text>
    </svg>';
}
