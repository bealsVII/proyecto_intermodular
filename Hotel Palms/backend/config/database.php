<?php
/**
 * Controlador de conexión a la base de datos mediante PDO.
 * Implementa el patrón Singleton para la gestión de conexiones.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

require_once __DIR__ . '/constants.php';

class Database
{
    /** @var static Base de datos|nulo */
    private static ?Database $instance = null;

    /** @var PDO */
    private PDO $connection;

    /**
     * Constructor privado para garantizar el patrón singleton.
     *
     * @throws PDOException Si la conexión falla
     */
    private function __construct()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

            if (APP_DEBUG) {
                error_log("[Database] Connected successfully to " . DB_NAME);
            }
        } catch (PDOException $exception) {
            if (APP_DEBUG) {
                error_log("[Database] Connection error: " . $exception->getMessage());
            }
            throw new PDOException("Database connection failed. " . (APP_DEBUG ? $exception->getMessage() : ''));
        }
    }

    /**
     * Obtén una instancia única de la base de datos.
     *
     * @return static
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtenga el objeto de conexión PDO.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Ejecutar una sentencia preparada con parámetros.
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return PDOStatement
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    /**
     * Obtener una sola fila.
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->execute($sql, $params);
        $result = $statement->fetch();
        return $result ?: null;
    }

    /**
     * Obtener todas las filas.
     *
     * @param string $sql Consulta SQL.
     * @param array $params Parametros.
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->execute($sql, $params);
        return $statement->fetchAll();
    }

    /**
     * Iniciar una transacción.
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Realizar una transacción.
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Revertir una transacción.
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Evitar la clonación de un único elemento.
     */
    private function __clone() {}

    /**
     * Evitar la deserialización de un singleton.
     */
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }
}