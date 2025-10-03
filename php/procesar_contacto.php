<?php

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
$servicio = filter_input(INPUT_POST, 'servicio', FILTER_SANITIZE_STRING);
$mensaje = filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_STRING);

if (empty($nombre) || empty($email) || empty($servicio) || empty($mensaje)) {
    echo json_encode([
        'success' => false,
        'message' => 'Por favor completa todos los campos requeridos'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'El correo electrónico no es válido'
    ]);
    exit;
}

$conn = getConnection();

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión con la base de datos'
    ]);
    exit;
}

$sql = "INSERT INTO contactos (nombre, email, telefono, servicio, mensaje, fecha_registro, ip_address) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar la consulta'
    ]);
    closeConnection($conn);
    exit;
}

$ip_address = $_SERVER['REMOTE_ADDR'];

$stmt->bind_param("ssssss", $nombre, $email, $telefono, $servicio, $mensaje, $ip_address);

if ($stmt->execute()) {
    $to = "tu_email@nexafox.com";
    $subject = "Nuevo contacto desde Nexa-Fox: $nombre";
    $body = "Nombre: $nombre\n";
    $body .= "Email: $email\n";
    $body .= "Teléfono: $telefono\n";
    $body .= "Servicio: $servicio\n";
    $body .= "Mensaje: $mensaje\n";
    $body .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    
    $headers = "From: noreply@nexafox.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    
    echo json_encode([
        'success' => true,
        'message' => '¡Gracias por contactarnos! Te responderemos pronto.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar la información'
    ]);
}

$stmt->close();
closeConnection($conn);
?>