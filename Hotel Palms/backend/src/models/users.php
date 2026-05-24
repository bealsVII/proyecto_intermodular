<?php
/**
 * Modelo de usuario.
 * Gestiona todas las operaciones de la base de datos para los usuarios.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/../../config/database.php';

class User
{
    /** @var Database */
    private Database $db;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Encuentra un usuario por ID.
     *
     * @param int $id ID de usuario.
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, first_name, last_name, email, role, phone, dni, is_active, last_login, created_at
                FROM users WHERE id = :id";

        $result = $this->db->fetchOne($sql, ['id' => $id]);

        if ($result !== null) {
            $result['full_name'] = $result['first_name'] . ' ' . $result['last_name'];
        }

        return $result;
    }

    /**
     * Encuentra un usuario por email.
     *
     * @param string $email Dirección de email.
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        return $this->db->fetchOne($sql, ['email' => $email]);
    }

    /**
     * Encontrar un usuario por DNI.
     *
     * @param string $dni DNI/NIF
     * @return array|null
     */
    public function findByDni(string $dni): ?array
    {
        $sql = "SELECT * FROM users WHERE dni = :dni";
        return $this->db->fetchOne($sql, ['dni' => strtoupper($dni)]);
    }

    /**
     * Encuentra a todos los usuarios con filtros y paginación opcionales.
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

        if (!empty($filters['role'])) {
            $whereClauses[] = "role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $whereClauses[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

        // Cuenta total.
        $countSql = "SELECT COUNT(*) as total FROM users {$whereSql}";
        $countResult = $this->db->fetchOne($countSql, $params);
        $total = intval($countResult['total']);

        // Obtener usuarios.
        $sql = sprintf(
            "SELECT id, first_name, last_name, email, role, phone, dni, is_active, last_login, created_at
             FROM users %s ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
            $whereSql
        );

        $params['limit']  = $limit;
        $params['offset'] = $offset;

        $users = $this->db->fetchAll($sql, $params);

        // Agregar nombre completo.
        foreach ($users as &$user) {
            $user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        }

        return [
            'users' => $users,
            'total' => $total,
        ];
    }

    /**
     * Crear un nuevo usuario.
     *
     * @param array $data Datos de usuario.
     * @return array Usuario creado.
     * @throws Exception Si el usuario ya existe.
     */
    public function create(array $data): array
    {
        // Comprobar la unicidad del email.
        $existing = $this->findByEmail($data['email']);
        if ($existing !== null) {
            throw new Exception('Email already registered');
        }

        // Contraseña hash.
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);

        $sql = "INSERT INTO users (first_name, last_name, email, password_hash, role, phone, dni, is_active)
                VALUES (:first_name, :last_name, :email, :password_hash, :role, :phone, :dni, :is_active)";

        $this->db->execute($sql, [
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'password_hash' => $data['password_hash'],
            'role'          => $data['role'] ?? 'guest',
            'phone'         => $data['phone'] ?? null,
            'dni'           => $data['dni'] ?? null,
            'is_active'     => true,
        ]);

        return $this->findById($this->db->getConnection()->lastInsertId());
    }

    /**
     * Actualizar un usuario existente.
     *
     * @param int $id ID de usuario.
     * @param array $data Actualizar datos.
     * @return array Usuario actualizado.
     * @throws Exception Si el usuario no se encuentra.
     */
    public function update(int $id, array $data): array
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['first_name', 'last_name', 'email', 'role', 'phone', 'dni', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        // Manejar la actualización de contraseña por separado.
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        }

        if (empty($fields)) {
            throw new Exception('No fields to update');
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);

        return $this->findById($id);
    }

    /**
     * Eliminar un usuario.
     *
     * @param int $id ID de usuario.
     * @return bool Estado de éxito.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) !== false;
    }

    /**
     * Actualizar la marca de tiempo del último inicio de sesión.
     *
     * @param int $id ID de usuario.
     * @return bool Estado de éxito.
     */
    public function updateLastLogin(int $id): bool
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) !== false;
    }
}