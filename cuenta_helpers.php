<?php
// Utilidades compartidas para el área de clientes.
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function cuenta_csrf_token() {
    if (empty($_SESSION['cuenta_csrf'])) {
        $_SESSION['cuenta_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['cuenta_csrf'];
}

function cuenta_validar_csrf() {
    $enviado = (string)($_POST['csrf_token'] ?? '');
    return $enviado !== '' && !empty($_SESSION['cuenta_csrf']) && hash_equals($_SESSION['cuenta_csrf'], $enviado);
}

function cuenta_tabla_recuperacion(mysqli $conexion) {
    $sql = "CREATE TABLE IF NOT EXISTS recuperacion_claves (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        vence_en DATETIME NOT NULL,
        usado TINYINT(1) NOT NULL DEFAULT 0,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_recuperacion_cliente (cliente_id),
        INDEX idx_recuperacion_vence (vence_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    return $conexion->query($sql);
}

function cuenta_tabla_social(mysqli $conexion) {
    $sql = "CREATE TABLE IF NOT EXISTS cliente_social (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        proveedor VARCHAR(20) NOT NULL,
        proveedor_usuario_id VARCHAR(190) NOT NULL,
        correo VARCHAR(190) NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_proveedor_usuario (proveedor, proveedor_usuario_id),
        INDEX idx_cliente_social_cliente (cliente_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    return $conexion->query($sql);
}

function cuenta_iniciar_sesion_cliente($id, $nombre) {
    session_regenerate_id(true);
    $_SESSION['cliente_id'] = (int)$id;
    $_SESSION['cliente_nombre'] = (string)$nombre;
    unset($_SESSION['cuenta_csrf'], $_SESSION['oauth_state']);
}
