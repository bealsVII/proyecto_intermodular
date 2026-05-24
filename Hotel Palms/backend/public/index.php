<?php
/**
 * Punto de entrada de la aplicación.
 * Inicializa la aplicación.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

// =============================================
// Manejo de errores
// =============================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// =============================================
// Cargar configuración
// =============================================
require_once __DIR__ . '/../config/constants.php';

// =============================================
// Iniciar sesión
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start([
        'cookie_lifetime'  => SESSION_LIFETIME,
        'cookie_secure'    => false, // Configurado como verdadero en producción con HTTPS
        'cookie_httponly'  => true,
        'cookie_samesite'  => 'Lax',
        'use_strict_mode'  => true,
    ]);
}

// =============================================
// Encabezados CORS (para API)
// =============================================
header('Access-Control-Allow-Origin: ' . APP_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// =============================================
// Enrutador de carga y controladores
// =============================================
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/RoomController.php';
require_once __DIR__ . '/../src/Controllers/BookingController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';

// =============================================
// Inicializar enrutador
// =============================================
$router = new Router();

// =============================================
// Rutas de autenticación
// =============================================
$router->post('/auth/login',    'AuthController@login');
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/logout',   'AuthController@logout');
$router->get('/auth/me',        'AuthController@me');

// =============================================
// Rutas de habitaciones
// =============================================
$router->get('/rooms',              'RoomController@index');
$router->get('/rooms/{id}',         'RoomController@show');
$router->post('/rooms',             'RoomController@store');
$router->put('/rooms/{id}',         'RoomController@update');
$router->delete('/rooms/{id}',      'RoomController@destroy');
$router->get('/rooms/availability', 'RoomController@availability');

// =============================================
// Reserva de rutas
// =============================================
$router->get('/bookings',            'BookingController@index');
$router->get('/bookings/{id}',       'BookingController@show');
$router->post('/bookings',           'BookingController@store');
$router->put('/bookings/{id}',       'BookingController@update');
$router->delete('/bookings/{id}',    'BookingController@destroy');
$router->post('/bookings/{id}/cancel', 'BookingController@cancel');
$router->get('/bookings/export/pdf', 'BookingController@exportPdf');
$router->get('/bookings/export/csv', 'BookingController@exportCsv');
$router->get('/bookings/statistics', 'BookingController@statistics');

// =============================================
// Rutas de usuario
// =============================================
$router->get('/users',      'UserController@index');
$router->get('/users/{id}', 'UserController@show');
$router->post('/users',     'UserController@store');
$router->put('/users/{id}', 'UserController@update');
$router->delete('/users/{id}', 'UserController@destroy');

// =============================================
// Solicitud de envío
// =============================================
try {
    $router->dispatch();
} catch (Exception $exception) {
    if (APP_DEBUG) {
        error_log("[Router] Exception: " . $exception->getMessage());
    }
    ApiResponse::serverError(
        APP_DEBUG ? $exception->getMessage() : 'Ocurrió un error inesperado'
    );
}