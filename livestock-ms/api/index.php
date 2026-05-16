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
 * Dynamic Routing Logic with REST Method & JWT Authentication Mapping
 */
try {
    // Split the route into parts (e.g., "locations/12" becomes ["locations", "12"])
    $parts = explode('/', $route);
    
    // --- PLURAL-TO-SINGULAR ALIAS MAPPER ---
    $routeName = !empty($parts[0]) ? strtolower($parts[0]) : '';
    
    $routeAliases = [
        'users'        => 'User',
        'categories'   => 'Category',
        'locations'    => 'Location',
        'farmers'      => 'Farmer',
        'breeds'       => 'Breed',
        'transactions' => 'Transaction',
        'orders'       => 'Order',
        'livestock'    => 'Livestock'
    ];

    if (array_key_exists($routeName, $routeAliases)) {
        $classNamePrefix = $routeAliases[$routeName];
    } else {
        $classNamePrefix = ucfirst($routeName);
    }
    // ----------------------------------------

    $controllerName = !empty($classNamePrefix) ? $classNamePrefix . 'Controller' : null;
    $method = $_SERVER['REQUEST_METHOD'];

    // 1. Check if the Controller class actually exists
    if ($controllerName && class_exists($controllerName)) {
        $controller = new $controllerName();

        // 2. Determine Action Name & ID Parameter based on REST design rules
        $actionName = null;
        $idParam = null;

        // Check if the second URL segment is a numeric identifier (e.g., /locations/12)
        if (!empty($parts[1]) && is_numeric($parts[1])) {
            $idParam = (int)$parts[1];
        }

        // Map HTTP Methods directly to clean Controller methods
        if ($method === 'GET') {
            if ($idParam !== null) {
                // Special check for livestock sub-actions (e.g., GET /livestock/12/weights -> weights)
                $subAction = !empty($parts[2]) ? $parts[2] : '';
                if ($subAction === 'weights') {
                    $actionName = 'weights';
                } else {
                    $actionName = 'show';
                }
            } else {
                $actionName = 'index';
            }
        } elseif ($method === 'POST') {
            if ($idParam !== null) {
                // Special check for livestock sub-actions (e.g., POST /livestock/12/weights -> addWeight)
                $subAction = !empty($parts[2]) ? $parts[2] : '';
                if ($subAction === 'weights') {
                    $actionName = 'addWeight';
                } else {
                    $actionName = !empty($subAction) ? $subAction : 'store';
                }
            } else {
                // e.g., /auth/register -> register, /auth/login -> login
                $actionName = (!empty($parts[1])) ? $parts[1] : 'store';
            }
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            if ($idParam !== null) {
                // Special check for order/livestock sub-actions (e.g., /orders/12/status -> updateStatus)
                $subAction = !empty($parts[2]) ? $parts[2] : '';
                if ($subAction === 'status') {
                    $actionName = 'updateStatus';
                } else {
                    $actionName = !empty($subAction) ? $subAction : 'update';
                }
            } else {
                $actionName = (!empty($parts[1])) ? $parts[1] : 'update';
            }
        } elseif ($method === 'DELETE') {
            if ($idParam !== null) {
                // Special check for livestock sub-actions (e.g., DELETE /livestock/12/weights/5 -> deleteWeight)
                $subAction = !empty($parts[2]) ? $parts[2] : '';
                if ($subAction === 'weights') {
                    $actionName = 'deleteWeight';
                } else {
                    $actionName = 'delete';
                }
            } else {
                $actionName = 'delete';
            }
        }

        // 3. Fallback to secondary URL segment if no structural method matches yet
        if (!$actionName && !empty($parts[1])) {
            $actionName = $parts[1];
        }

        // 4. Verify the targeted method exists inside the controller instance
        if ($actionName && method_exists($controller, $actionName)) {
            
            // --- AUTHENTICATION INTERCEPT MIDDLEWARE LAYER ---
            // Public open routes that do not require JWT authorization tokens
            $publicRoutes = [
                'auth/login',
                'auth/register'
            ];
            $currentRouteCheck = strtolower("$routeName/" . (!empty($parts[1]) ? $parts[1] : ''));

            if (in_array($currentRouteCheck, $publicRoutes)) {
                // Execute open route without authentication mapping
                $controller->$actionName();
            } else {
                // Enforce token extraction and pass user claims down into controller
                $authUser = Auth::requireAuth();
                
                // Route execution matching method signatures (with or without id parameters)
                if ($idParam !== null) {
                    if ($actionName === 'weights') {
                        $controller->$actionName($idParam);
                    } elseif ($actionName === 'deleteWeight') {
                        // Capture the weight_id from $parts[3] (e.g., /livestock/12/weights/5)
                        $weightId = !empty($parts[3]) ? (int)$parts[3] : 0;
                        $controller->$actionName($authUser, $idParam, $weightId);
                    } else {
                        $controller->$actionName($authUser, $idParam);
                    }
                } else {
                    $controller->$actionName($authUser);
                }
            }
            
        } else {
            throw new Exception("Action '$actionName' not found in $controllerName", 404);
        }
    } else {
        throw new Exception("Route not found: " . $route, 404);
    }

} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    // Prevent code 0 or invalid strings from breaking response layouts
    if (!is_int($code) || $code < 100 || $code > 599) { $code = 500; }
    http_response_code($code);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}