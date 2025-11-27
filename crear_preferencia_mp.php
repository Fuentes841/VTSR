<?php
// crear_preferencia_mp.php
header('Content-Type: application/json');

// MercadoPago SDK
require_once __DIR__ . '/vendor/autoload.php';

// Tus credenciales
$access_token = 'APP_USR-6974421447525913-062316-634bec8167464d6dec356c4db8e109de-49212733';

// Inicializar SDK
MercadoPago\SDK::setAccessToken($access_token);

// Obtener productos del carrito
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !is_array($data) || count($data) === 0) {
    echo json_encode(['error' => 'Carrito vacío o datos incorrectos']);
    exit;
}

$items = [];
foreach ($data as $prod) {
    $items[] = [
        'title' => $prod['nombre'] ?? 'Producto',
        'quantity' => (int)($prod['cantidad_total'] ?? 1),
        'currency_id' => 'ARS',
        'unit_price' => (float)($prod['precio'] ?? 0)
    ];
}

$preference = new MercadoPago\Preference();
$preference->items = array_map(function($item) {
    $mp_item = new MercadoPago\Item();
    $mp_item->title = $item['title'];
    $mp_item->quantity = $item['quantity'];
    $mp_item->currency_id = $item['currency_id'];
    $mp_item->unit_price = $item['unit_price'];
    return $mp_item;
}, $items);

// Opcional: URLs de retorno
$preference->back_urls = [
    'success' => 'http://localhost/dashboard/prueba2/carrito.php?mp=success',
    'failure' => 'http://localhost/dashboard/prueba2/carrito.php?mp=failure',
    'pending' => 'http://localhost/dashboard/prueba2/carrito.php?mp=pending'
];
$preference->auto_return = 'approved';

$preference->save();

if (isset($preference->id)) {
    echo json_encode(['preference_id' => $preference->id]);
} else {
    echo json_encode(['error' => 'No se pudo crear la preferencia']);
} 