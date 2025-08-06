<?php
$host = 'IP_O_HOST_DEL_SERVIDOR_DB'; // Ej: 127.0.0.1 o tu IP pública
$dbname = 'nombre_de_tu_base_de_datos';
$username = 'usuario';
$password = 'contraseña';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Activar errores como excepciones
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión exitosa a la base de datos.";
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
?>
