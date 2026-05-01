<?php
/**
 * Project: LivestoChub API
 * Purpose: Main Router with Localhost Subfolder Support
 */

// 1. Load Configurations & Helpers
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/jwt.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/middleware/auth.php';

// 2. Load Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/FarmerController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/BreedController.php';
require_once __DIR__ . '/controllers/LocationController.php';
require_once __DIR__ . '/controllers/LivestockController.php';
require_once __DIR__ . '/controllers/OrderController.php';
require_once __DIR__ . '/controllers/TransactionController.php';

// 3. CORS Headers - This prevents "Network Error" in the browser
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Handle Pre-flight OPTIONS request (Browser safety check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- 4. PATH CLEANER (The Fix for Localhost 404) ---
// This strips the local folder names so the code only sees "auth/register"
$basePath = '/livestockhub/livestock-ms/api'; 
$requestUri = $_SERVER['REQUEST_URI'];

if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$path = parse_url($requestUri, PHP_URL_PATH);
$route = trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'];
// ---------------------------------------------------

/**
 * Dynamic Routing Logic
 */
try {
    // Split the route into parts (e.g., "livestock/view" becomes ["livestock", "view"])
    $parts = explode('/', $route);
    $controllerName = !empty($parts[0]) ? ucfirst($parts[0]) . 'Controller' : null;
    $actionName = !empty($parts[1]) ? $parts[1] : 'index';

    // 1. Check if the Controller class actually exists
    if ($controllerName && class_exists($controllerName)) {
        $controller = new $controllerName();

        // 2. Check if the specific function (action) exists in that controller
        if (method_exists($controller, $actionName)) {
            // Call the function (e.g., AuthController->register())
            $controller->$actionName();
        } else {
            throw new Exception("Action '$actionName' not found in $controllerName", 404);
        }
    } else {
        // If it's not a dynamic match, fallback to your manual routes or 404
        throw new Exception("Route not found: " . $route, 404);
    }

} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}