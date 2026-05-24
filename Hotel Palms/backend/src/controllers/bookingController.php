<?php
/**
 * Controlador de reservas.
 * Gestiona las operaciones CRUD de reservas y la administración de reservas.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../Models/Booking.php';
require_once __DIR__ . '/../Models/Room.php';
require_once __DIR__ . '/../Services/ApiResponse.php';
require_once __DIR__ . '/../Services/PdfService.php';
require_once __DIR__ . '/../Services/CsvService.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class BookingController
{
    /** @var Booking */
    private Booking $bookingModel;

    /** @var Room */
    private Room $roomModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->bookingModel = new Booking();
        $this->roomModel    = new Room();
    }

    /**
     * Obtén todas las reservas con filtros y paginación.
     *
     * @param array $queryParams Query parameters
     * @return void JSON response
     */
    public function index(array $queryParams = []): void
    {
        AuthMiddleware::requireAuth();

        try {
            $currentUser = $_SESSION['user']['id'];
            $userRole    = $_SESSION['user']['role'];

            $filters = [
                'status'     => $queryParams['status'] ?? null,
                'check_in'   => $queryParams['check_in'] ?? null,
                'check_out'  => $queryParams['check_out'] ?? null,
                'room_id'    => $queryParams['room_id'] ?? null,
                'room_type'  => $queryParams['room_type'] ?? null,
                'search'     => $queryParams['search'] ?? null,
                'date_from'  => $queryParams['date_from'] ?? null,
                'date_to'    => $queryParams['date_to'] ?? null,
            ];

            $pagination = [
                'page'  => max(1, intval($queryParams['page'] ?? 1)),
                'limit' => min(MAX_PAGE_SIZE, max(1, intval($queryParams['limit'] ?? DEFAULT_PAGE_SIZE))),
            ];

            // Los huéspedes solo pueden ver sus propias reservas.
            $filters['user_id'] = ($userRole === 'guest') ? $currentUser : null;

            $result = $this->bookingModel->findAll($filters, $pagination);

            ApiResponse::success([
                'data'       => $result['bookings'],
                'pagination' => [
                    'current_page' => $pagination['page'],
                    'per_page'     => $pagination['limit'],
                    'total'        => $result['total'],
                    'last_page'    => ceil($result['total'] / $pagination['limit']),
                ],
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error fetching bookings'
            );
        }
    }

    /**
     * Realiza una única reserva.
     *
     * @param int $id ID de reserva.
     * @return void JSON response
     */
    public function show(int $id): void
    {
        AuthMiddleware::requireAuth();

        try {
            $booking = $this->bookingModel->findById($id);

            if ($booking === null) {
                ApiResponse::notFound('Booking not found');
                return;
            }

            // Verificación de autorización.
            if ($_SESSION['user']['role'] === 'guest' && $booking['user_id'] != $_SESSION['user']['id']) {
                ApiResponse::forbidden('You can only view your own bookings');
                return;
            }

            ApiResponse::success(['data' => $booking]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error fetching booking'
            );
        }
    }

    /**
     * Crear una nueva reserva.
     *
     * @param array $input POST data
     * @return void JSON response
     */
    public function store(array $input): void
    {
        AuthMiddleware::requireAuth();

        try {
            $validation = $this->validateBooking($input);
            if (!$validation['valid']) {
                ApiResponse::validationError($validation['errors']);
                return;
            }

            $userId = $_SESSION['user']['id'];

            // Consultar disponibilidad de habitaciones.
            $room = $this->roomModel->findById(intval($input['room_id']));
            if ($room === null) {
                ApiResponse::notFound('Room not found');
                return;
            }

            // Verifique que los huéspedes no excedan la capacidad.
            if (intval($input['guests']) > $room['capacity']) {
                ApiResponse::validationError([
                    'guests' => sprintf(
                        'La habitación solo admite %d huéspedes',
                        $room['capacity']
                    ),
                ]);
                return;
            }

            // Crear reserva con transacción.
            $booking = $this->bookingModel->create($userId, $input);

            ApiResponse::success([
                'message' => 'Booking created successfully',
                'data'    => $booking,
            ], 201);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error creating booking'
            );
        }
    }

    /**
     * Actualizar el estado de una reserva.
     *
     * @param int $id Booking ID
     * @param array $input POST data
     * @return void JSON response
     */
    public function update(int $id, array $input): void
    {
        AuthMiddleware::requireRole(['admin', 'receptionist']);

        try {
            $existingBooking = $this->bookingModel->findById($id);
            if ($existingBooking === null) {
                ApiResponse::notFound('Booking not found');
                return;
            }

            $validation = $this->validateBookingUpdate($input);
            if (!$validation['valid']) {
                ApiResponse::validationError($validation['errors']);
                return;
            }

            $booking = $this->bookingModel->update($id, $input);

            ApiResponse::success([
                'message' => 'Booking updated successfully',
                'data'    => $booking,
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error updating booking'
            );
        }
    }

    /**
     * Cancelar una reserva.
     *
     * @param int $id Booking ID
     * @return void JSON response
     */
    public function cancel(int $id): void
    {
        AuthMiddleware::requireAuth();

        try {
            $existingBooking = $this->bookingModel->findById($id);
            if ($existingBooking === null) {
                ApiResponse::notFound('Booking not found');
                return;
            }

            // Las huéspedes solo pueden cancelar sus propias reservas pendientes.
            if ($_SESSION['user']['role'] === 'guest') {
                if ($existingBooking['user_id'] != $_SESSION['user']['id']) {
                    ApiResponse::forbidden('You can only cancel your own bookings');
                    return;
                }
                if ($existingBooking['status'] !== 'pending') {
                    ApiResponse::error('Only pending bookings can be cancelled by guests', 400);
                    return;
                }
            }

            $booking = $this->bookingModel->cancel($id);

            ApiResponse::success([
                'message' => 'Booking cancelled successfully',
                'data'    => $booking,
            ]);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error cancelling booking'
            );
        }
    }

    /**
     * Eliminar una reserva.
     *
     * @param int $id Booking ID
     * @return void JSON response
     */
    public function destroy(int $id): void
    {
        AuthMiddleware::requireRole(['admin']);

        try {
            $existingBooking = $this->bookingModel->findById($id);
            if ($existingBooking === null) {
                ApiResponse::notFound('Booking not found');
                return;
            }

            $this->bookingModel->delete($id);

            ApiResponse::success(['message' => 'Booking deleted successfully']);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error deleting booking'
            );
        }
    }

    /**
     * Exportar reservas a PDF.
     *
     * @param array $queryParams Filtros para exportar.
     * @return void Archivo PDF enviado directamente.
     */
    public function exportPdf(array $queryParams = []): void
    {
        AuthMiddleware::requireRole(['admin', 'receptionist']);

        try {
            $filters = [
                'status'  => $queryParams['status'] ?? null,
                'date_from' => $queryParams['date_from'] ?? null,
                'date_to'   => $queryParams['date_to'] ?? null,
            ];

            $bookings = $this->bookingModel->findAll($filters, ['page' => 1, 'limit' => 500])['bookings'];

            $pdfService = new PdfService();
            $pdfService->generateBookingsPdf($bookings);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error generating PDF'
            );
        }
    }

    /**
     * Exportar reservas a CSV.
     *
     * @param array $queryParams Filtros para exportar.
     * @return void Archivo CSV enviado directamente.
     */
    public function exportCsv(array $queryParams = []): void
    {
        AuthMiddleware::requireRole(['admin', 'receptionist']);

        try {
            $filters = [
                'status'  => $queryParams['status'] ?? null,
                'date_from' => $queryParams['date_from'] ?? null,
                'date_to'   => $queryParams['date_to'] ?? null,
            ];

            $bookings = $this->bookingModel->findAll($filters, ['page' => 1, 'limit' => 500])['bookings'];

            $csvService = new CsvService();
            $csvService->generateBookingsCsv($bookings);

        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error generating CSV'
            );
        }
    }

    /**
     * Obtenga estadísticas de reservas.
     *
     * @return void JSON response
     */
    public function statistics(): void
    {
        AuthMiddleware::requireRole(['admin', 'receptionist']);

        try {
            $stats = $this->bookingModel->getStatistics();
            ApiResponse::success(['data' => $stats]);
        } catch (Exception $exception) {
            ApiResponse::serverError(
                APP_DEBUG ? $exception->getMessage() : 'Error fetching statistics'
            );
        }
    }

    /**
     * Validar los datos introducidos para la creación de la reserva.
     *
     * @param array $input Datos de entrada.
     * @return array Resultado de la validación.
     */
    private function validateBooking(array $input): array
    {
        $errors = [];

        if (empty($input['room_id']) || !is_numeric($input['room_id'])) {
            $errors['room_id'] = 'La habitación es obligatoria';
        }

        if (empty($input['check_in'])) {
            $errors['check_in'] = 'La fecha de entrada es obligatoria';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['check_in'])) {
            $errors['check_in'] = 'Formato de fecha inválido (YYYY-MM-DD)';
        }

        if (empty($input['check_out'])) {
            $errors['check_out'] = 'La fecha de salida es obligatoria';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['check_out'])) {
            $errors['check_out'] = 'Formato de fecha inválido (YYYY-MM-DD)';
        }

        // Validación de fecha.
        if (!empty($input['check_in']) && !empty($input['check_out'])) {
            $checkInDate  = date_create($input['check_in']);
            $checkOutDate = date_create($input['check_out']);

            if ($checkInDate >= $checkOutDate) {
                $errors['check_out'] = 'La fecha de salida debe ser posterior a la de entrada';
            }

            if ($checkInDate < date_create(date('Y-m-d'))) {
                $errors['check_in'] = 'La fecha de entrada no puede ser en el pasado';
            }
        }

        if (empty($input['guests']) || !is_numeric($input['guests'])) {
            $errors['guests'] = 'El número de huéspedes es obligatorio';
        } elseif (intval($input['guests']) < 1) {
            $errors['guests'] = 'Debe haber al menos 1 huésped';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validar la información de actualización de la reserva.
     *
     * @param array $input Datos de entrada.
     * @return array Resultado de la validación.
     */
    private function validateBookingUpdate(array $input): array
    {
        $errors = [];
        $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];

        if (!empty($input['status']) && !in_array($input['status'], $validStatuses)) {
            $errors['status'] = 'Estado no válido. Valores: ' . implode(', ', $validStatuses);
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}