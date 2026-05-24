<?php
/**
 * Asistente de respuesta de API.
 * Respuestas JSON estandarizadas para la API.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

class ApiResponse
{
    /**
     * Enviar una respuesta de éxito.
     *
     * @param mixed $data Datos de respuesta.
     * @param int $statusCode Código de estado HTTP.
     * @return void
     */
    public static function success($data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => true,
            'data'    => $data,
            'timestamp' => date('c'),
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Enviar una respuesta de error.
     *
     * @param string $message Mensaje de error.
     * @param int $statusCode Código de estado HTTP.
     * @return void
     */
    public static function error(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Enviar una respuesta de error de validación.
     *
     * @param array $errors Errores de validación.
     * @return void
     */
    public static function validationError(array $errors): void
    {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $errors,
            'timestamp' => date('c'),
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Enviar una respuesta 401 No autorizado.
     *
     * @param string $message Mensaje de error.
     * @return void
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Enviar una respuesta 403 Prohibido.
     *
     * @param string $message Mensaje de error.
     * @return void
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    /**
     * Enviar una respuesta 404 Not Found.
     *
     * @param string $message Mensaje de error.
     * @return void
     */
    public static function notFound(string $message = 'Not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Enviar una respuesta de error del servidor 500.
     *
     * @param string $message Mensaje de error.
     * @return void
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, 500);
    }
}