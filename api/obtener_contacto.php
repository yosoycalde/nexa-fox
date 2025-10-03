<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID inválido'
    ]);
    exit;
}

$id = (int)$_GET['id'];
$conn = getConnection();

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión'
    ]);
    exit;
}

$sql = "SELECT * FROM contactos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $contacto = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'contacto' => $contacto
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Contacto no encontrado'
    ]);
}

$stmt->close();
closeConnection($conn);
?>