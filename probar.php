<?php
require_once "conexion.php";
echo "<h2>Prueba de conexión · Perfumería</h2>";
echo "<p>PHP versión: " . phpversion() . "</p>";
$conexion = conectar();
echo "<p style='color:green'>✔ Conexión correcta a la base de datos</p>";
foreach (array("perfumes", "clientes", "pedidos", "pedido_detalles", "pedido_entregas") as $t) {
    $r = $conexion->query("SELECT COUNT(*) AS n FROM $t");
    if ($r) { $f = $r->fetch_assoc(); echo "<p style='color:green'>✔ $t: " . (int)$f['n'] . " registros</p>"; }
    else { echo "<p style='color:red'>❌ Falta la tabla $t (" . e($conexion->error) . ")</p>"; }
}
echo "<p><a href='index.html'>Ir al inicio</a></p>";
