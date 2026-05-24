<?php
/**
 * Middleware de roles.
 * Verificación detallada de permisos para acciones específicas.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/AuthMiddleware.php';

class RoleMiddleware
{
    /**
     * Defina la matriz de permisos de roles.
     *
     * @return array Permisos de rol.
     */
    public static function getPermissions(): array
    {
        return [
            'admin' => [
                'users'     => ['view', 'create', 'update', 'delete'],
                'rooms'     => ['view', 'create', 'update', 'delete'],
                'bookings'  => ['view', 'create', 'update', 'delete', 'export', 'manage'],
                'reports'   => ['view', 'export'],
                'settings'  => ['view', 'update'],
            ],
            'receptionist' => [
                'users'     => ['view'],
                'rooms'     => ['view', 'create', 'update'],
                'bookings'  => ['view', 'create', 'update', 'export'],
                'reports'   => ['view', 'export'],
                'settings'  => [],
            ],
            'guest' => [
                'users'     => ['view_own', 'update_own'],
                'rooms'     => ['view'],
                'bookings'  => ['view_own', 'create_own', 'cancel_own'],
                'reports'   => [],
                'settings'  => [],
            ],
        ];
    }

    /**
     * Compruebe si el usuario tiene un permiso específico.
     *
     * @param string $resource Nombre del recurso.
     * @param string $action Nombre de la acción.
     * @return bool
     */
    public static function hasPermission(string $resource, string $action): bool
    {
        if (!AuthMiddleware::isAuthenticated()) {
            return false;
        }

        $userRole    = $_SESSION['user']['role'] ?? '';
        $permissions = self::getPermissions();

        if (!isset($permissions[$userRole])) {
            return false;
        }

        if (!isset($permissions[$userRole][$resource])) {
            return false;
        }

        return in_array($action, $permissions[$userRole][$resource]);
    }

    /**
     * Requiere un permiso específico.
     *
     * @param string $resource Nombre del recurso.
     * @param string $action Action name.
     * @return bool
     */
    public static function requirePermission(string $resource, string $action): bool
    {
        if (!self::hasPermission($resource, $action)) {
            ApiResponse::forbidden('You do not have permission to ' . $action . ' this ' . $resource);
            return false;
        }
        return true;
    }
}