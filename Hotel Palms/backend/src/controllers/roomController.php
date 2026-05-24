<?php
/**
 * Controlador de habitación.
 * Gestiona las operaciones CRUD de las habitaciones del hotel.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../Models/Room.php';
require_once __DIR__ . '/../Services/ApiResponse.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class RoomController
{
    /** @var Room */
    private Room $roomModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->roomModel = new Room();
    }

    /**
     * Obtén todas las habitaciones con filtros y paginación opcionales.
     *
     * @param array $queryParams Query parameters
     * @return void JSON response
     */
    public function index(array $queryParams = []): void
    {
        try {
            $filters = [
                'room_type'   => $queryParams['room_type'] ?? null,
                'min_price'   => $queryParams['min_price'] ?? null,
                'max_price'   => $queryParams['max_price'] ?? null,
                'capacity'    => $queryParams['capacity'] ?? null,
                'is_available' => $queryParams['available'] ?? null,
                'floor'       => $queryParams['floor'] ?? null,
                'search'      => $queryParams['search'] ?? null,
            ];

            $pagination = [
                'page'  => max(1, intval($queryParams['page'] ?? 1)),
                'limit' => min(MAX_PAGE_SIZE, max(1, intval($queryParams['limit'] ?? DEFAULT_PAGE_SIZE))),
            ];

            $sort = [
                'field' => in_array($queryParams['sort_by'] ?? 'id', ['id', 'price_per_night', 'room_number', 'room_type'])
                    ? $queryParams['sort_by']
                    : 'id',
                'order' => in_array($queryParams['sort_order'] ?? 'asc', ['asc', 'desc'])
                    ? $queryParams['sort_order']
                    : 'asc',
            ];

            $result = $this->roomModel->findAll($filters, $pagination, $sort);

            ApiResponse::success([
                'data'       => $result['rooms'],
                'pagination' => [
                    'current_page' => $pagination['page'],
                    'per_page'     => $pagination['limit'],
                    'total'        => $result['total'],
                    'last_page'    => ceil($result['total'] / $pagination['limit']),
                ],
                'filters'    => array_filter($filters),
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error fetching rooms'
            );
        }
    }

    /**
     * Consigue una habitación individual presentando tu identificación.
     *
     * @param int $id Room ID
     * @return void JSON response
     */
    public function show(int $id): void
    {
        try {
            $room = $this->roomModel->findById($id);

            if ($room === null) {
                ApiResponse::notFound('Room not found');
                return;
            }

            ApiResponse::success(['data' => $room]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error fetching room'
            );
        }
    }

    /**
     * Crear una nueva habitación.
     *
     * @param array $input POST data
     * @return void JSON response
     */
    public function store(array $input): void
    {
        AuthMiddleware::requireRole(['admin', 'receptionist']);

        try {
            $validation = $this->validateRoom($input);
            if (!$validation['valid']) {
                ApiResponse::validationError($validation['errors']);
                return;
            }

            $room = $this->roomModel->create($input);

            ApiResponse::success([
                'message' => 'Room created successfully',
                'data'    => $room,
            ], 201);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error creating room'
            );
        }
    }

    /**
     * Actualizar una habitación existente.
     *
     * @param int $id Room ID
     * @param array $input POST data
     * @return void JSON response
     */
    public function update(int $id, array $input): void
    {
        AuthMiddleware::requireRole(['admin', 'receptionist']);

        try {
            // Comprobar si existe espacio.
            $existingRoom = $this->roomModel->findById($id);
            if ($existingRoom === null) {
                ApiResponse::notFound('Room not found');
                return;
            }

            $validation = $this->validateRoom($input, true);
            if (!$validation['valid']) {
                ApiResponse::validationError($validation['errors']);
                return;
            }

            $room = $this->roomModel->update($id, $input);

            ApiResponse::success([
                'message' => 'Room updated successfully',
                'data'    => $room,
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error updating room'
            );
        }
    }

    /**
     * Eliminar una habitación (eliminación lógica mediante la configuración de no disponible).
     *
     * @param int $id ID de habitaciones
     * @return void JSON response
     */
    public function destroy(int $id): void
    {
        AuthMiddleware::requireRole(['admin']);

        try {
            $existingRoom = $this->roomModel->findById($id);
            if ($existingRoom === null) {
                ApiResponse::notFound('Room not found');
                return;
            }

            $this->roomModel->delete($id);

            ApiResponse::success(['message' => 'Room deleted successfully']);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error deleting room'
            );
        }
    }

    /**
     * Consulta la disponibilidad de habitaciones para un rango de fechas.
     *
     * @param array $queryParams Fechas de entrada y salida.
     * @return void JSON response
     */
    public function availability(array $queryParams = []): void
    {
        try {
            $checkIn  = $queryParams['check_in'] ?? null;
            $checkOut = $queryParams['check_out'] ?? null;

            if (!$checkIn || !$checkOut) {
                ApiResponse::validationError([
                    'date' => 'Los fechas de entrada y salida son obligatorias',
                ]);
                return;
            }

            // Validar fechas.
            $checkInDate  = date_create($checkIn);
            $checkOutDate = date_create($checkOut);

            if (!$checkInDate || !$checkOutDate) {
                ApiResponse::validationError(['date' => 'Formato de fecha inválido (YYYY-MM-DD)']);
                return;
            }

            if ($checkInDate >= $checkOutDate) {
                ApiResponse::validationError(['date' => 'La fecha de salida debe ser posterior a la de entrada']);
                return;
            }

            $availableRooms = $this->roomModel->findAvailable($checkIn, $checkOut);

            ApiResponse::success([
                'data'  => $availableRooms,
                'count' => count($availableRooms),
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error checking availability'
            );
        }
    }

    /**
     * Validar los datos de entrada de la habitación.
     *
     * @param array $input Datos de entrada.
     * @param bool $isUpdate Si esto es una actualización.
     * @return array Resultado de la validación.
     */
    private function validateRoom(array $input, bool $isUpdate = false): array
    {
        $errors = [];
        $validTypes = ['single', 'double', 'triple', 'suite', 'family'];

        if (!$isUpdate && empty($input['room_number'])) {
            $errors['room_number'] = 'El número de habitación es obligatorio';
        } elseif (!empty($input['room_number']) && strlen($input['room_number']) > 10) {
            $errors['room_number'] = 'El número de habitación debe tener máximo 10 caracteres';
        }

        if (!empty($input['room_type']) && !in_array($input['room_type'], $validTypes)) {
            $errors['room_type'] = 'Tipo de habitación no válido. Valores: ' . implode(', ', $validTypes);
        }

        if (!empty($input['capacity']) && !is_numeric($input['capacity'])) {
            $errors['capacity'] = 'La capacidad debe ser un número';
        } elseif (!empty($input['capacity']) && intval($input['capacity']) < 1) {
            $errors['capacity'] = 'La capacidad debe ser al menos 1';
        }

        if (!empty($input['price_per_night'])) {
            if (!is_numeric($input['price_per_night'])) {
                $errors['price_per_night'] = 'El precio debe ser un número';
            } elseif (floatval($input['price_per_night']) < 0) {
                $errors['price_per_night'] = 'El precio no puede ser negativo';
            }
        }

        if (!empty($input['floor']) && !is_numeric($input['floor'])) {
            $errors['floor'] = 'La planta debe ser un número';
        }

        // Validar las comodidades si se proporcionan.
        if (!empty($input['amenities'])) {
            if (is_string($input['amenities'])) {
                $decoded = json_decode($input['amenities'], true);
                if (!is_array($decoded)) {
                    $errors['amenities'] = 'Los servicios deben ser un JSON válido';
                }
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}