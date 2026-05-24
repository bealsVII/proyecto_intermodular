<?php
/**
 * Modelo de habitación.
 * Gestiona todas las operaciones de la base de datos de las habitaciones del hotel.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../../config/database.php';

class Room
{
    /** @var Database */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Encuentra una habitación por ID.
     *
     * @param int $id ID de habitación.
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT r.*,
                JSON_EXTRACT(r.amenities, '$') as amenities_array,
                JSON_EXTRACT(r.images, '$') as images_array
                FROM rooms r WHERE r.id = :id";

        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Encuentra todas las habitaciones con filtros y paginación.
     *
     * @param array $filters Filtros de búsqueda.
     * @param array $pagination Configuración de paginación.
     * @param array $sort Configuración de clasificación.
     * @return array Resultados con paginación.
     */
    public function findAll(array $filters = [], array $pagination = [], array $sort = []): array
    {
        $page  = $pagination['page'] ?? 1;
        $limit = $pagination['limit'] ?? DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $whereClauses = [];
        $params = [];

        if (!empty($filters['room_type'])) {
            $whereClauses[] = "room_type = :room_type";
            $params['room_type'] = $filters['room_type'];
        }

        if (!empty($filters['min_price'])) {
            $whereClauses[] = "price_per_night >= :min_price";
            $params['min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $whereClauses[] = "price_per_night <= :max_price";
            $params['max_price'] = $filters['max_price'];
        }

        if (!empty($filters['capacity'])) {
            $whereClauses[] = "capacity >= :capacity";
            $params['capacity'] = $filters['capacity'];
        }

        if (isset($filters['is_available']) && $filters['is_available'] !== '') {
            $whereClauses[] = "is_available = :is_available";
            $params['is_available'] = filter_var($filters['is_available'], FILTER_VALIDATE_BOOLEAN);
        }

        if (!empty($filters['floor'])) {
            $whereClauses[] = "floor = :floor";
            $params['floor'] = $filters['floor'];
        }

        if (!empty($filters['search'])) {
            $whereClauses[] = "(room_number LIKE :search OR description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

        $sortField = in_array($sort['field'] ?? 'id', ['id', 'price_per_night', 'room_number', 'room_type'])
            ? $sort['field'] : 'id';
        $sortOrder = $sort['order'] ?? 'asc';

        // Cuenta total.
        $countSql = "SELECT COUNT(*) as total FROM rooms {$whereSql}";
        $countResult = $this->db->fetchOne($countSql, $params);
        $total = intval($countResult['total']);

        // Buscar habitaciones.
        $sql = sprintf(
            "SELECT * FROM rooms %s ORDER BY %s %s LIMIT :limit OFFSET :offset",
            $whereSql,
            $sortField,
            $sortOrder
        );

        $params['limit']  = $limit;
        $params['offset'] = $offset;

        $rooms = $this->db->fetchAll($sql, $params);

        // Decodificar campos JSON.
        foreach ($rooms as &$room) {
            $room['amenities'] = json_decode($room['amenities'] ?? '[]', true);
            $room['images'] = json_decode($room['images'] ?? '[]', true);
        }

        return [
            'rooms' => $rooms,
            'total' => $total,
        ];
    }

    /**
     * Encuentra habitaciones disponibles para un rango de fechas.
     *
     * @param string $checkIn Fecha de entrada.
     * @param string $checkOut Fecha de salida.
     * @return array Available Habitaciones.
     */
    public function findAvailable(string $checkIn, string $checkOut): array
    {
        $sql = "SELECT r.*
                FROM rooms r
                WHERE r.is_available = TRUE
                AND r.id NOT IN (
                    SELECT b.room_id
                    FROM bookings b
                    WHERE b.status IN ('pending', 'confirmed', 'completed')
                    AND (
                        (b.check_in <= :check_in AND b.check_out > :check_in)
                        OR
                        (b.check_in < :check_out AND b.check_out >= b.check_out)
                        OR
                        (b.check_in >= :check_in AND b.check_out <= :check_out)
                    )
                )
                ORDER BY r.price_per_night ASC";

        return $this->db->fetchAll($sql, [
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]);
    }

    /**
     * Crear una nueva habitación.
     *
     * @param array $data Datos de la habitación.
     * @return array Habitación creada.
     */
    public function create(array $data): array
    {
        $sql = "INSERT INTO rooms (room_number, room_type, capacity, price_per_night,
                description, amenities, floor, is_available)
                VALUES (:room_number, :room_type, :capacity, :price_per_night,
                :description, :amenities, :floor, :is_available)";

        $this->db->execute($sql, [
            'room_number'   => $data['room_number'],
            'room_type'     => $data['room_type'],
            'capacity'      => $data['capacity'] ?? 1,
            'price_per_night' => $data['price_per_night'],
            'description'   => $data['description'] ?? null,
            'amenities'     => is_array($data['amenities'] ?? null)
                ? json_encode($data['amenities'])
                : '[]',
            'floor'         => $data['floor'] ?? null,
            'is_available'  => true,
        ]);

        return $this->findById($this->db->getConnection()->lastInsertId());
    }

    /**
     * Actualizar una habitación.
     *
     * @param int $id ID de habitación.
     * @param array $data Actualizar datos.
     * @return array Habitación actualizada.
     */
    public function update(int $id, array $data): array
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['room_number', 'room_type', 'capacity', 'price_per_night',
                          'description', 'amenities', 'floor', 'is_available'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                if ($field === 'amenities' && is_array($data[$field])) {
                    $params[$field] = json_encode($data[$field]);
                } else {
                    $params[$field] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            throw new Exception('No fields to update');
        }

        $sql = "UPDATE rooms SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);

        return $this->findById($id);
    }

    /**
     * Eliminar una habitación.
     *
     * @param int $id ID de habitación.
     * @return bool Estado de éxito.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM rooms WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) !== false;
    }
}