<?php
/**
 * Controlador de autenticación.
 * Gestiona el inicio de sesión, el registro, el cierre de sesión y la administración de sesiones de los usuarios.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/ApiResponse.php';

class AuthController
{
    /** @var AuthService */
    private AuthService $authService;

    /**
     * Constructor: inicializa AuthService.
     */
    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Gestionar la solicitud de inicio de sesión del usuario.
     *
     * @param array $input POST data with email and password
     * @return void JSON response sent directly
     */
    public function login(array $input): void
    {
        try {
            // Validar entrada.
            $validation = $this->validateLogin($input);
            if (!$validation['valid']) {
                ApiResponse::validationError($validation['errors']);
                return;
            }

            // Intentar iniciar sesión.
            $result = $this->authService->login(
                $input['email'],
                $input['password']
            );

            if (!$result['success']) {
                ApiResponse::error($result['message'], $result['status']);
                return;
            }

            ApiResponse::success([
                'message' => 'Login successful',
                'user'    => $result['user'],
                'token'   => $result['token'],
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Login failed'
            );
        }
    }

    /**
     * Gestionar la solicitud de registro de usuario.
     *
     * @param array $input POST data for registration
     * @return void JSON response sent directly
     */
    public function register(array $input): void
    {
        try {
            // Validar entrada.
            $validation = $this->validateRegistration($input);
            if (!$validation['valid']) {
                ApiResponse::validationError($validation['errors']);
                return;
            }

            // Intento de registro.
            $result = $this->authService->register($input);

            if (!$result['success']) {
                ApiResponse::error($result['message'], $result['status']);
                return;
            }

            ApiResponse::success([
                'message' => 'Registration successful',
                'user'    => $result['user'],
            ], 201);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Registration failed'
            );
        }
    }

    /**
     * Gestionar la solicitud de cierre de sesión del usuario.
     *
     * @return void JSON response sent directly
     */
    public function logout(): void
    {
        $this->authService->logout();
        ApiResponse::success(['message' => 'Logged out successfully']);
    }

    /**
     * Obtener el usuario autenticado actual.
     *
     * @return void JSON response sent directly
     */
    public function me(): void
    {
        $user = $this->authService->getCurrentUser();
        if ($user === null) {
            ApiResponse::unauthorized('No active session');
            return;
        }
        ApiResponse::success(['user' => $user]);
    }

    /**
     * Validar los datos de inicio de sesión.
     *
     * @param array $input Input data
     * @return array Validation result
     */
    private function validateLogin(array $input): array
    {
        $errors = [];

        if (empty($input['email'])) {
            $errors['email'] = 'El correo electrónico es obligatorio';
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correo electrónico no es válido';
        }

        if (empty($input['password'])) {
            $errors['password'] = 'La contraseña es obligatoria';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validar los datos de registro introducidos.
     *
     * @param array $input Input data
     * @return array Validation result
     */
    private function validateRegistration(array $input): array
    {
        $errors = [];

        // Validación de nombre.
        if (empty($input['first_name'])) {
            $errors['first_name'] = 'El nombre es obligatorio';
        } elseif (strlen($input['first_name']) < 2 || strlen($input['first_name']) > 50) {
            $errors['first_name'] = 'El nombre debe tener entre 2 y 50 caracteres';
        }

        // Validación de apellidos.
        if (empty($input['last_name'])) {
            $errors['last_name'] = 'El apellido es obligatorio';
        } elseif (strlen($input['last_name']) < 2 || strlen($input['last_name']) > 50) {
            $errors['last_name'] = 'El apellido debe tener entre 2 y 50 caracteres';
        }

        // Validación de email.
        if (empty($input['email'])) {
            $errors['email'] = 'El correo electrónico es obligatorio';
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correo electrónico no es válido';
        }

        // Validación de contraseña.
        if (empty($input['password'])) {
            $errors['password'] = 'La contraseña es obligatoria';
        } elseif (strlen($input['password']) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
        } elseif (!preg_match('/[A-Z]/', $input['password'])) {
            $errors['password'] = 'La contraseña debe contener al menos una mayúscula';
        } elseif (!preg_match('/[0-9]/', $input['password'])) {
            $errors['password'] = 'La contraseña debe contener al menos un número';
        } elseif (!preg_match('/[@$!%*?&]/', $input['password'])) {
            $errors['password'] = 'La contraseña debe contener al menos un carácter especial';
        }

        // Confirmar Contraseña.
        if ($input['password'] !== ($input['password_confirmation'] ?? '')) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden';
        }

        // Validación del móvil (opcional).
        if (!empty($input['phone']) && !preg_match('/^[+]?[\d\s()-]{9,15}$/', $input['phone'])) {
            $errors['phone'] = 'El teléfono no es válido';
        }

        // Validación de DNI (opcional)
        if (!empty($input['dni']) && !preg_match('/^\d{8}[A-Za-z]$/', $input['dni'])) {
            $errors['dni'] = 'El DNI no es válido';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}