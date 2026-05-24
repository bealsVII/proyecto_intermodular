<?php
/**
 * Middleware de autenticación.
 * Gestiona la validación de sesiones y el control de acceso basado en roles.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../Services/ApiResponse.php';

class AuthMiddleware
{
    /**
     * Requiere autenticación de usuario.
     * Redirige al inicio de sesión si no está autenticado.
     *
     * @return bool Si el usuario está autenticado.
     */
    public static function requireAuth(): bool
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required',
            ]);
            exit;
        }

        // Comprobar el tiempo de espera de la sesión.
        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            session_unset();
            session_destroy();
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Session expired',
            ]);
            exit;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Se requiere rol(es) específico(s).
     *
     * @param array $allowedRoles Roles permitidos.
     * @return bool Si el usuario ha requerido un rol.
     */
    public static function requireRole(array $allowedRoles): bool
    {
        self::requireAuth();

        $userRole = $_SESSION['user']['role'] ?? '';

        if (!in_array($userRole, $allowedRoles)) {
            ApiResponse::forbidden(
                'You do not have permission to access this resource'
            );
            return false;
        }

        return true;
    }

    /**
     * Compruebe si el usuario está autenticado (sin bloqueo).
     *
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) &&
            (time() - ($_SESSION['last_activity'] ?? 0) <= SESSION_LIFETIME);
    }

    /**
     * Obtener el rol del usuario actual.
     *
     * @return string|null
     */
    public static function getUserRole(): ?string
    {
        return self::isAuthenticated() ? ($_SESSION['user']['role'] ?? null) : null;
    }
}