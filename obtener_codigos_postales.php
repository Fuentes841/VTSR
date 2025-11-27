<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['provincia']) || !isset($_GET['localidad'])) {
    echo json_encode([]);
    exit;
}

$provincia = $_GET['provincia'];
$localidad = $_GET['localidad'];

try {
    $conexion = getDBConnection();
    $query = "SELECT id_postal FROM codigo_postal WHERE provincia = ? AND localidad = ? ORDER BY id_postal";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "ss", $provincia, $localidad);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    $codigos_postales = [];
    while ($row = mysqli_fetch_assoc($resultado)) {
        $codigos_postales[] = [
            'id_postal' => $row['id_postal']
        ];
    }
    
    echo json_encode($codigos_postales);
} catch (Exception $e) {
    echo json_encode([]);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
} 