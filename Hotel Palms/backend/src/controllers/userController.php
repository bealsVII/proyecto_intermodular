<?php
/**
 * Controlador de usuario.
 * Gestiona las operaciones CRUD de administración de usuarios.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Services/ApiResponse.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class UserController
{
    /** @var User */
    private User $userModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Obtenga todos los usuarios (solo administrador / recepcionista).
     *
     * @param array $queryParams Query parameters
     * @return void JSON response
     */
    public function index(array $queryParams = []): void
    {
        AuthMiddleware::requireRole(['admin', 'receptionist']);

        try {
            $filters = [
                'role'   => $queryParams['role'] ?? null,
                'search' => $queryParams['search'] ?? null,
            ];

            $pagination = [
                'page'  => max(1, intval($queryParams['page'] ?? 1)),
                'limit' => min(MAX_PAGE_SIZE, max(1, intval($queryParams['limit'] ?? DEFAULT_PAGE_SIZE))),
            ];

            $result = $this->userModel->findAll($filters, $pagination);

            ApiResponse::success([
                'data'       => $result['users'],
                'pagination' => [
                    'current_page' => $pagination['page'],
                    'per_page'     => $pagination['limit'],
                    'total'        => $result['total'],
                    'last_page'    => ceil($result['total'] / $pagination['limit']),
                ],
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error al recuperar usuarios'
            );
        }
    }

    /**
     * Consigue un solo usuario.
     *
     * @param int $id ID de usuarios.
     * @return void JSON response
     */
    public function show(int $id): void
    {
        AuthMiddleware::requireAuth();

        try {
            // Los usuarios solo pueden ver su propio perfil a menos que el administrador.
            if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['id'] != $id) {
                ApiResponse::forbidden('Solo puedes ver tu propio perfil.');
                return;
            }

            $user = $this->userModel->findById($id);

            if ($user === null) {
                ApiResponse::notFound('Usuario no encontrado');
                return;
            }

            ApiResponse::success(['data' => $user]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error al recuperar el usuario'
            );
        }
    }

    /**
     * Crear un nuevo usuario.
     *
     * @param array $input POST data
     * @return void JSON response
     */
    public function store(array $input): void
    {
        AuthMiddleware::requireRole(['admin']);

        try {
            $validation = $this->validateUser($input);
            if (!$validation['valid']) {
                ApiResponse::validationError($validation['errores']);
                return;
            }

            $user = $this->userModel->create($input);

            ApiResponse::success([
                'message' => 'Usuario creado con éxito',
                'data'    => $user,
            ], 201);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error al crear usuario'
            );
        }
    }

    /**
     * Actualizar un usuario.
     *
     * @param int $id ID de usuarios.
     * @param array $input POST data
     * @return void JSON response
     */
    public function update(int $id, array $input): void
    {
        AuthMiddleware::requireAuth();

        try {
            if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['id'] != $id) {
                ApiResponse::forbidden('Solo puedes actualizar tu propio perfil.');
                return;
            }

            $existingUser = $this->userModel->findById($id);
            if ($existingUser === null) {
                ApiResponse::notFound('Usuario no encontrado');
                return;
            }

            $validation = $this->validateUser($input, true);
            if (!$validation['valid']) {
                ApiResponse::validationError($validation['errores']);
                return;
            }

            $user = $this->userModel->update($id, $input);

            ApiResponse::success([
                'message' => 'Usuario actualizado con éxito',
                'data'    => $user,
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error al actualizar usuario'
            );
        }
    }

    /**
     * Eliminar un usuario.
     *
     * @param int $id User ID
     * @return void JSON response
     */
    public function destroy(int $id): void
    {
        AuthMiddleware::requireRole(['admin']);

        try {
            $existingUser = $this->userModel->findById($id);
            if ($existingUser === null) {
                ApiResponse::notFound('Usuario no encontrado');
                return;
            }

            // Prevenir la autodestrucción.
            if ($_SESSION['user']['id'] == $id) {
                ApiResponse::error('No puedes borrarte a ti mismo', 400);
                return;
            }

            $this->userModel->delete($id);

            ApiResponse::success(['message' => 'Usuario eliminado exitosamente']);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error al eliminar el usuario'
            );
        }
    }

    /**
     * Validar los datos introducidos por el usuario.
     *
     * @param array $input Input data
     * @param bool $isUpdate Whether this is an update
     * @return array Validation result
     */
    private function validateUser(array $input, bool $isUpdate = false): array
    {
        $errors = [];
        $validRoles = ['admin', 'receptionist', 'guest'];

        if (!$isUpdate && empty($input['first_name'])) {
            $errors['first_name'] = 'El nombre es obligatorio';
        }

        if (!$isUpdate && empty($input['last_name'])) {
            $errors['last_name'] = 'El apellido es obligatorio';
        }

        if (!$isUpdate && empty($input['email'])) {
            $errors['email'] = 'El correo es obligatorio';
        } elseif (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correo no es válido';
        }

        if (!$isUpdate && empty($input['password'])) {
            $errors['password'] = 'La contraseña es obligatoria';
        } elseif (!empty($input['password']) && strlen($input['password']) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if (!empty($input['role']) && !in_array($input['role'], $validRoles)) {
            $errors['role'] = 'Rol no válido. Valores: ' . implode(', ', $validRoles);
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}