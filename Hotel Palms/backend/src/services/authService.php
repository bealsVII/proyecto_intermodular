<?php
/**
 * Servicio de autenticación.
 * Gestiona el inicio de sesión, el registro, el cierre de sesión y la administración de sesiones.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../Models/User.php';

class AuthService
{
    /** @var User */
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Autenticar a un usuario con email y contraseña.
     *
     * @param string $email Email del usuario.
     * @param string $password Contraseña de usuario.
     * @return array Resultado de la autenticación.
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if ($user === null) {
            return [
                'success' => false,
                'message' => 'Email o contraseña incorrectos',
                'status'  => 401,
            ];
        }

        // Comprueba si la cuenta está activa..
        if (!$user['is_active']) {
            return [
                'success' => false,
                'message' => 'La cuenta está desactivada. Póngase en contacto con la administración.',
                'status'  => 403,
            ];
        }

        // Verificar contraseña.
        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Email o contraseña incorrectos',
                'status'  => 401,
            ];
        }

        // Actualizar último inicio de sesión.
        $this->userModel->updateLastLogin($user['id']);

        // Iniciar sesión.
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'         => $user['id'],
            'first_name' => $user['nombre'],
            'last_name'  => $user['apellidos'],
            'email'      => $user['email'],
            'role'       => $user['rol'],
        ];
        $_SESSION['last_activity'] = time();

        // Generar un token simple (JWT sería mejor para producción).
        $token = bin2hex(random_bytes(32));

        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user'    => $_SESSION['usuario'],
            'token'   => $token,
        ];
    }

    /**
     * Registra un nuevo usuario.
     *
     * @param array $data Datos de registro.
     * @return array Resultado del registro.
     */
    public function register(array $data): array
    {
        try {
            $user = $this->userModel->create($data);

            return [
                'success' => true,
                'message' => 'Registro exitoso',
                'user'    => $user,
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'status'  => 400,
            ];
        }
    }

    /**
     * Cerrar sesión del usuario actual.
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Obtener el usuario autenticado actual.
     *
     * @return array|null
     */
    public function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Compruebe si el usuario está autenticado.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }
}