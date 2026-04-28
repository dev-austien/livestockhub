<?php
// ─── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/jwt.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/middleware/auth.php';

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/FarmerController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/BreedController.php';
require_once __DIR__ . '/controllers/LocationController.php';
require_once __DIR__ . '/controllers/LivestockController.php';
require_once __DIR__ . '/controllers/OrderController.php';
require_once __DIR__ . '/controllers/TransactionController.php';

// ─── CORS Headers ─────────────────────────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Parse Request ────────────────────────────────────────────────────────────
$method   = $_SERVER['REQUEST_METHOD'];
$rawUri   = $_SERVER['REQUEST_URI'];
$basePath = '/api';          // Change this if your subfolder is different

// Strip query string and base path
$path = parse_url($rawUri, PHP_URL_PATH);
$path = '/' . trim(str_replace($basePath, '', $path), '/');
$segments = array_filter(explode('/', $path), fn($s) => $s !== '');
$segments = array_values($segments);

// ─── Route Matching ───────────────────────────────────────────────────────────
// Helper to match a pattern and extract named params
function matchRoute(string $pattern, array $segments): ?array {
    $patternParts = array_values(array_filter(explode('/', trim($pattern, '/')), fn($s) => $s !== ''));
    if (count($patternParts) !== count($segments)) return null;

    $params = [];
    foreach ($patternParts as $i => $part) {
        if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
            $params[trim($part, '{}')] = $segments[$i];
        } elseif ($part !== $segments[$i]) {
            return null;
        }
    }
    return $params;
}

$authCtrl        = new AuthController();
$userCtrl        = new UserController();
$farmerCtrl      = new FarmerController();
$categoryCtrl    = new CategoryController();
$breedCtrl       = new BreedController();
$locationCtrl    = new LocationController();
$livestockCtrl   = new LivestockController();
$orderCtrl       = new OrderController();
$transactionCtrl = new TransactionController();

// ─── Auth routes (no JWT required) ───────────────────────────────────────────

// POST /auth/login
if ($method === 'POST' && matchRoute('/auth/login', $segments) !== null) {
    $authCtrl->login(); exit;
}
// POST /auth/register
if ($method === 'POST' && matchRoute('/auth/register', $segments) !== null) {
    $authCtrl->register(); exit;
}
// GET /auth/me
if ($method === 'GET' && matchRoute('/auth/me', $segments) !== null) {
    $user = Auth::requireAuth();
    $authCtrl->me($user); exit;
}

// ─── User routes ─────────────────────────────────────────────────────────────

if (($p = matchRoute('/users', $segments)) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'  => $userCtrl->index($user),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/users/{id}', $segments)) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'    => $userCtrl->show($user, (int)$p['id']),
        'PUT'    => $userCtrl->update($user, (int)$p['id']),
        'DELETE' => $userCtrl->delete($user, (int)$p['id']),
        default  => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/users/{id}/status', $segments)) !== null) {
    $user = Auth::requireAuth();
    if ($method === 'PATCH') $userCtrl->updateStatus($user, (int)$p['id']);
    else Response::error('Method not allowed', 405);
    exit;
}

// ─── Farmer routes ────────────────────────────────────────────────────────────

if (matchRoute('/farmers', $segments) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'  => $farmerCtrl->index($user),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/farmers/{id}', $segments)) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET' => $farmerCtrl->show($user, (int)$p['id']),
        'PUT' => $farmerCtrl->update($user, (int)$p['id']),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/farmers/{id}/contacts', $segments)) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'  => $farmerCtrl->contacts($user, (int)$p['id']),
        'POST' => $farmerCtrl->addContact($user, (int)$p['id']),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/farmers/{id}/contacts/{contact_id}', $segments)) !== null) {
    $user = Auth::requireAuth();
    if ($method === 'DELETE') $farmerCtrl->deleteContact($user, (int)$p['id'], (int)$p['contact_id']);
    else Response::error('Method not allowed', 405);
    exit;
}

// ─── Category routes ──────────────────────────────────────────────────────────

if (matchRoute('/categories', $segments) !== null) {
    match($method) {
        'GET'  => $categoryCtrl->index(),
        'POST' => $categoryCtrl->store(Auth::requireAuth()),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/categories/{id}', $segments)) !== null) {
    match($method) {
        'GET'    => $categoryCtrl->show((int)$p['id']),
        'PUT'    => $categoryCtrl->update(Auth::requireAuth(), (int)$p['id']),
        'DELETE' => $categoryCtrl->delete(Auth::requireAuth(), (int)$p['id']),
        default  => Response::error('Method not allowed', 405),
    }; exit;
}

// ─── Breed routes ─────────────────────────────────────────────────────────────

if (matchRoute('/breeds', $segments) !== null) {
    match($method) {
        'GET'  => $breedCtrl->index(),
        'POST' => $breedCtrl->store(Auth::requireAuth()),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/breeds/{id}', $segments)) !== null) {
    match($method) {
        'GET'    => $breedCtrl->show((int)$p['id']),
        'PUT'    => $breedCtrl->update(Auth::requireAuth(), (int)$p['id']),
        'DELETE' => $breedCtrl->delete(Auth::requireAuth(), (int)$p['id']),
        default  => Response::error('Method not allowed', 405),
    }; exit;
}

// ─── Location routes ──────────────────────────────────────────────────────────

if (matchRoute('/locations', $segments) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'  => $locationCtrl->index($user),
        'POST' => $locationCtrl->store($user),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/locations/{id}', $segments)) !== null) {
    match($method) {
        'GET'    => $locationCtrl->show((int)$p['id']),
        'PUT'    => $locationCtrl->update(Auth::requireAuth(), (int)$p['id']),
        'DELETE' => $locationCtrl->delete(Auth::requireAuth(), (int)$p['id']),
        default  => Response::error('Method not allowed', 405),
    }; exit;
}

// ─── Livestock routes ─────────────────────────────────────────────────────────

if (matchRoute('/livestock', $segments) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'  => $livestockCtrl->index($user),
        'POST' => $livestockCtrl->store($user),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/livestock/{id}', $segments)) !== null) {
    match($method) {
        'GET'    => $livestockCtrl->show((int)$p['id']),
        'PUT'    => $livestockCtrl->update(Auth::requireAuth(), (int)$p['id']),
        'DELETE' => $livestockCtrl->delete(Auth::requireAuth(), (int)$p['id']),
        default  => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/livestock/{id}/weights', $segments)) !== null) {
    match($method) {
        'GET'  => $livestockCtrl->weights((int)$p['id']),
        'POST' => $livestockCtrl->addWeight(Auth::requireAuth(), (int)$p['id']),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/livestock/{id}/weights/{weight_id}', $segments)) !== null) {
    if ($method === 'DELETE') $livestockCtrl->deleteWeight(Auth::requireAuth(), (int)$p['id'], (int)$p['weight_id']);
    else Response::error('Method not allowed', 405);
    exit;
}

// ─── Order routes ─────────────────────────────────────────────────────────────

if (matchRoute('/orders', $segments) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'  => $orderCtrl->index($user),
        'POST' => $orderCtrl->store($user),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/orders/{id}', $segments)) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'    => $orderCtrl->show($user, (int)$p['id']),
        'DELETE' => $orderCtrl->delete($user, (int)$p['id']),
        default  => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/orders/{id}/status', $segments)) !== null) {
    if ($method === 'PATCH') $orderCtrl->updateStatus(Auth::requireAuth(), (int)$p['id']);
    else Response::error('Method not allowed', 405);
    exit;
}

// ─── Transaction routes ───────────────────────────────────────────────────────

if (matchRoute('/transactions', $segments) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'  => $transactionCtrl->index($user),
        'POST' => $transactionCtrl->store($user),
        default => Response::error('Method not allowed', 405),
    }; exit;
}
if (($p = matchRoute('/transactions/{id}', $segments)) !== null) {
    $user = Auth::requireAuth();
    match($method) {
        'GET'    => $transactionCtrl->show($user, (int)$p['id']),
        'PUT'    => $transactionCtrl->update($user, (int)$p['id']),
        'DELETE' => $transactionCtrl->delete($user, (int)$p['id']),
        default  => Response::error('Method not allowed', 405),
    }; exit;
}

// ─── Fallback ─────────────────────────────────────────────────────────────────
Response::notFound('Endpoint not found');
