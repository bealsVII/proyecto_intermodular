<?php
/**
 * Constantes y configuración de la aplicación.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

// =============================================
// Configuración de base de datos
// =============================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'hotel_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// =============================================
// Configuración de la aplicación
// =============================================
define('APP_NAME', 'Hotel Reservation System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('API_URL', APP_URL . '/api');

// =============================================
// Configuración de seguridad
// =============================================
define('SESSION_LIFETIME', 3600);        // 1 hora
define('SESSION_NAME', 'hotel_session');
define('HASH_COST', 12);                  // factor de costo bcrypt
define('CSRF_TOKEN_NAME', '_csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);              // 15 minutos
define('RATE_LIMIT_MAX', 100);            // solicitudes por minuto
define('RATE_LIMIT_WINDOW', 60);          // segundos

// =============================================
// Configuración de paginación
// =============================================
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// =============================================
// Configuración de carga de archivos
// =============================================
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// =============================================
// Configuración de exportación
// =============================================
define('PDF_MARGIN_LEFT', 15);
define('PDF_MARGIN_RIGHT', 15);
define('PDF_MARGIN_TOP', 20);
define('PDF_MARGIN_BOTTOM', 20);

// =============================================
// Configuración de email (SMTP)
// =============================================
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@hotel.com');

// =============================================
// Configuración API
// =============================================
define('API_VERSION', 'v1');
define('API_KEY_LENGTH', 64);
define('API_RATE_LIMIT', 60);
define('API_RATE_WINDOW', 60);