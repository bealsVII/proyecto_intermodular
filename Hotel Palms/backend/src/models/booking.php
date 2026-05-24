<?php
/**
 * Modelo de Reserva.
 * Gestiona todas las operaciones de la base de datos para las reservas.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../../config/database.php';

class Booking
{
    /** @var Database */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Buscar una reserva por ID.
     *
     * @param int $id ID de reserva.
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT b.*,
                u.first_name, u.last_name, u.email, u.phone,
                r.room_number, r.room_type, r.price_per_night
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN rooms r ON b.room_id = r.id
                WHERE b.id = :id";

        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Encuentra todas las reservas con filtros y paginación.
     *
     * @param array $filters Filtros de búsqueda.
     * @param array $pagination Configuración de paginación.
     * @return array Resultados con paginación.
     */
    public function findAll(array $filters = [], array $pagination = []): array
    {
        $page  = $pagination['page'] ?? 1;
        $limit = $pagination['limit'] ?? DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $whereClauses = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereClauses[] = "b.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $whereClauses[] = "b.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['room_id'])) {
            $whereClauses[] = "b.room_id = :room_id";
            $params['room_id'] = $filters['room_id'];
        }

        if (!empty($filters['check_in'])) {
            $whereClauses[] = "b.check_in = :check_in";
            $params['check_in'] = $filters['check_in'];
        }

        if (!empty($filters['date_from'])) {
            $whereClauses[] = "b.check_in >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereClauses[] = "b.check_out <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $whereClauses[] = "(b.confirmation_code LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

        // Cuenta total
        $countSql = "SELECT COUNT(*) as total FROM bookings b
                      JOIN users u ON b.user_id = u.id {$whereSql}";
        $countResult = $this->db->fetchOne($countSql, $params);
        $total = intval($countResult['total']);

        // Obtener reservas.
        $sql = sprintf(
            "SELECT b.*,
                    u.first_name, u.last_name, u.email, u.phone,
                    r.room_number, r.room_type, r.capacity
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN rooms r ON b.room_id = r.id
             %s ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset",
            $whereSql
        );

        $params['limit']  = $limit;
        $params['offset'] = $offset;

        $bookings = $this->db->fetchAll($sql, $params);

        // Agregar campos calculados.
        foreach ($bookings as &$booking) {
            $booking['nights'] = $this->calculateNights($booking['check_in'], $booking['check_out']);
            $booking['customer_name'] = $booking['first_name'] . ' ' . $booking['last_name'];
        }

        return [
            'bookings' => $bookings,
            'total'    => $total,
        ];
    }

    /**
     * Crear una nueva reserva.
     *
     * @param int $userId ID de usuario.
     * @param array $data Datos de reserva.
     * @return array Reserva creada.
     * @throws Exception Si no hay habitaciones disponibles.
     */
    public function create(int $userId, array $data): array
    {
        // Consultar disponibilidad de habitaciones.
        $this->checkAvailability($data['room_id'], $data['check_in'], $data['check_out']);

        // Calcular precio total.
        $nights = $this->calculateNights($data['check_in'], $data['check_out']);
        $room = (new Room())->findById(intval($data['room_id']));
        $totalPrice = $room['price_per_night'] * $nights;

        // Generar código de confirmación.
        $confirmationCode = $this->generateConfirmationCode();

        // Usar transacción.
        $db = $this->db;
        $db->beginTransaction();

        try {
            $sql = "INSERT INTO bookings (user_id, room_id, check_in, check_out, guests,
                    total_price, status, special_requests, confirmation_code)
                    VALUES (:user_id, :room_id, :check_in, :check_out, :guests,
                    :total_price, 'confirmed', :special_requests, :confirmation_code)";

            $this->db->execute($sql, [
                'user_id'           => $userId,
                'room_id'           => $data['room_id'],
                'check_in'          => $data['check_in'],
                'check_out'         => $data['check_out'],
                'guests'            => $data['guests'] ?? 1,
                'total_price'       => $totalPrice,
                'special_requests'  => $data['special_requests'] ?? null,
                'confirmation_code' => $confirmationCode,
            ]);

            $db->commit();

            return $this->findById($db->getConnection()->lastInsertId());

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Actualizar una reserva.
     *
     * @param int $id ID de reserva.
     * @param array $data Actualizar datos.
     * @return array Reserva actualizada.
     */
    public function update(int $id, array $data): array
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['status', 'special_requests', 'payment_method'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            throw new Exception('No fields to update');
        }

        $sql = "UPDATE bookings SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);

        return $this->findById($id);
    }

    /**
     * Cancelar una reserva.
     *
     * @param int $id ID de reserva.
     * @return array Reserva actualizada.
     */
    public function cancel(int $id): array
    {
        $sql = "UPDATE bookings SET status = 'cancelled' WHERE id = :id AND status IN ('pending', 'confirmed')";
        $this->db->execute($sql, ['id' => $id]);

        return $this->findById($id);
    }

    /**
     * Eliminar una reserva.
     *
     * @param int $id ID de reserva.
     * @return bool Estado de éxito.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM bookings WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) !== false;
    }

    /**
     * Obtenga estadísticas de reservas.
     *
     * @return array Datos estadísticos.
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Reservas totales por estado.
        $statusSql = "SELECT status, COUNT(*) as count
                      FROM bookings
                      GROUP BY status";
        $statusResults = $this->db->fetchAll($statusSql);
        $stats['by_status'] = array_column($statusResults, 'count', 'status');

        // Ingresos por mes.
        $revenueSql = "SELECT DATE_FORMAT(check_in, '%Y-%m') as month,
                        SUM(total_price) as revenue,
                        COUNT(*) as bookings
                       FROM bookings
                       WHERE status IN ('confirmed', 'completed')
                       GROUP BY month
                       ORDER BY month DESC
                       LIMIT 12";
        $stats['monthly_revenue'] = $this->db->fetchAll($revenueSql);

        // Tasa de ocupación.
        $occupancySql = "SELECT COUNT(DISTINCT room_id) as occupied_rooms
                         FROM bookings
                         WHERE status IN ('confirmed', 'completed')
                         AND check_in <= CURDATE()
                         AND check_out > CURDATE()";
        $occupancyResult = $this->db->fetchOne($occupancySql);
        $stats['current_occupancy'] = intval($occupancyResult['occupied_rooms'] ?? 0);

        // Total revenue
        $totalRevenueSql = "SELECT COALESCE(SUM(total_price), 0) as total
                            FROM bookings
                            WHERE status IN ('confirmed', 'completed')";
        $totalRevenueResult = $this->db->fetchOne($totalRevenueSql);
        $stats['total_revenue'] = floatval($totalRevenueResult['total']);

        return $stats;
    }

    /**
     * Consultar disponibilidad de habitaciones para un rango de fechas determinado.
     *
     * @param int $roomId ID de habitación.
     * @param string $checkIn Fecha de entrada.
     * @param string $checkOut Fecha de salida.
     * @throws Exception Si no hay habitaciones disponibles.
     */
    private function checkAvailability(int $roomId, string $checkIn, string $checkOut): void
    {
        $sql = "SELECT COUNT(*) as conflicts
                FROM bookings
                WHERE room_id = :room_id
                AND status IN ('pending', 'confirmed', 'completed')
                AND (
                    (check_in <= :check_in AND check_out > :check_in)
                    OR
                    (check_in < :check_out AND check_out >= check_out)
                    OR
                    (check_in >= :check_in AND check_out <= :check_out)
                )";

        $result = $this->db->fetchOne($sql, [
            'room_id'   => $roomId,
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]);

        if (intval($result['conflicts']) > 0) {
            throw new Exception('Room is not available for the selected dates');
        }
    }

    /**
     * Calcula el número de noches entre dos fechas.
     *
     * @param string $checkIn Fecha de entrada.
     * @param string $checkOut Fecha de salida.
     * @return int Número de noches.
     */
    private function calculateNights(string $checkIn, string $checkOut): int
    {
        $date1 = new DateTime($checkIn);
        $date2 = new DateTime($checkOut);
        return $date1->diff($date2)->days;
    }

    /**
     * Genera un código de confirmación único.
     *
     * @return string Código de confirmación.
     */
    private function generateConfirmationCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Garantizar la singularidad.
        $sql = "SELECT id FROM bookings WHERE confirmation_code = :code";
        $existing = $this->db->fetchOne($sql, ['code' => $code]);

        if ($existing !== null) {
            return $this->generateConfirmationCode();
        }

        return $code;
    }
}