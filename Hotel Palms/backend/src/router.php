<?php
/**
 * Enrutador de aplicaciones.
 * Gestiona el enrutamiento de URL y el envío de solicitudes.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

class Router
{
    /** @var array Colección de rutas */
    private array $routes = [];

    /**
     * Registra una ruta GET.
     *
     * @param string $path URL path
     * @param callable|string $handler Route handler
     * @return self
     */
    public function get(string $path, $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * Registra una ruta POST.
     *
     * @param string $path URL path
     * @param callable|string $handler Route handler
     * @return self
     */
    public function post(string $path, $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    /**
     * Registra una ruta PUT.
     *
     * @param string $path URL path
     * @param callable|string $handler Route handler
     * @return self
     */
    public function put(string $path, $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }

    /**
     * Registra una ruta DELETE.
     *
     * @param string $path URL path
     * @param callable|string $handler Route handler
     * @return self
     */
    public function delete(string $path, $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }

    /**
     * Agrega una ruta a la colección.
     *
     * @param string $method HTTP method
     * @param string $path URL path
     * @param callable|string $handler Route handler
     */
    private function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => rtrim($path, '/'),
            'handler' => $handler,
        ];
    }

    /**
     * Envíe la solicitud actual al responsable correspondiente.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = rtrim($uri, '/');

        // Eliminar el prefijo /api
        $apiPrefix = '/api/' . API_VERSION;
        if (str_starts_with($uri, $apiPrefix)) {
            $uri = substr($uri, strlen($apiPrefix));
        } else {
            // Servir el frontend para solicitudes que no sean de API
            $this->serveFrontend();
            return;
        }

        $queryParams = $_GET;

        // Encuentra la ruta coincidente
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri)) {
                $params = $this->extractParams($route['path'], $uri);

                // Manejar la sintaxis de clase@método
                if (is_string($route['handler']) && str_contains($route['handler'], '@')) {
                    [$className, $methodName] = explode('@', $route['handler']);
                    $controller = new $className();

                    if (!method_exists($controller, $methodName)) {
                        ApiResponse::serverError("Method {$methodName} not found in {$className}");
                        return;
                    }

                    // Determinar la fuente de entrada en función del método HTTP.
                    if ($method === 'GET') {
                        $controller->$methodName(array_merge($queryParams, $params));
                    } else {
                        $input = $this->getInputData();
                        $controller->$methodName(array_merge($params, $input));
                    }
                } else {
                    $route['handler']($params, $queryParams);
                }

                return;
            }
        }

        ApiResponse::notFound('API endpoint not found');
    }

    /**
     * Asociar una ruta con la URI actual.
     *
     * @param string $routePath Route pattern
     * @param string $uri Current URI
     * @return bool
     */
    private function matchPath(string $routePath, string $uri): bool
    {
        // Convertir parámetros de ruta a expresiones regulares.
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#' . $pattern . '$#';

        return (bool) preg_match($pattern, $uri);
    }

    /**
     * Extraer parámetros de la URI.
     *
     * @param string $routePath Route pattern
     * @param string $uri Current URI
     * @return array Extracted parameters
     */
    private function extractParams(string $routePath, string $uri): array
    {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#' . $pattern . '$#';

        preg_match($pattern, $uri, $matches);

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Obtenga los datos de entrada de la solicitud POST/PUT.
     *
     * @return array Datos de entrada
     */
    private function getInputData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $json = file_get_contents('php://input');
            return json_decode($json, true) ?: [];
        }

        return $_POST;
    }

    /**
     * Sirva la aplicación de interfaz.
     */
    private function serveFrontend(): void
    {
        $frontendPath = __DIR__ . '/../../frontend/';

        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            $viewFile = $frontendPath . 'views/' . $page . '.html';
        } else {
            $viewFile = $frontendPath . 'index.html';
        }

        if (file_exists($viewFile)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($viewFile);
        } else {
            // Se recurre a index.html para el enrutamiento de SPA.
            header('Content-Type: text/html; charset=utf-8');
            readfile($frontendPath . 'index.html');
        }
    }
}