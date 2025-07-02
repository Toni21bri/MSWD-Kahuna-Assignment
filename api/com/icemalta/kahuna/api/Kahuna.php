<?php

require_once 'com/icemalta/kahuna/util/ApiUtil.php';
require 'com/icemalta/kahuna/model/product.php';  // Do this for every model class you add
require 'com/icemalta/kahuna/model/user.php';
require 'com/icemalta/kahuna/model/accessToken.php';
require_once __DIR__ . '/../model/Register.php';



use com\icemalta\kahuna\util\ApiUtil;
use com\icemalta\kahuna\model\Product; // Also do this for every model class you add
use com\icemalta\kahuna\model\User;
use com\icemalta\kahuna\model\AccessToken;
use com\icemalta\kahuna\model\Register;


cors();  // Enable Cross-Origin Requests 

$endPoints = [];  // This contains a list of functions which respond to the client request
$requestData = [];  // This contains any data passed to the API via GET, POST, or some other method
header("Content-Type: application/json; charset=UTF-8");

/* BASE URI */
$BASE_URI = '/kahuna/api/';

function sendResponse(mixed $data = null, int $code = 200, mixed $error = null): void
{
    if (!is_null($data)) {
        $response['data'] = $data;
    }
    if (!is_null($error)) {
        $response['error'] = $error;
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    http_response_code($code);
}

function checkToken(array $requestData): bool
{
    if (!isset($requestData['token']) || !isset($requestData['user'])) {
        return false;
    }
    $token = new AccessToken($requestData['user'], $requestData['token']);
    return AccessToken::verify($token);
}

/* Get Request Data */
$requestMethod = $_SERVER['REQUEST_METHOD'];
switch ($requestMethod) {
    case 'GET':
        $requestData = $_GET;
        break;
    case 'POST':
        $requestData = $_POST;
        break;
    case 'PATCH':
        parse_str(file_get_contents('php://input'), $requestData);
        ApiUtil::parse_raw_http_request($requestData);
        $requestData = is_array($requestData) ? $requestData : [];
        break;
    case 'DELETE':
        break;
    default:
        sendResponse(null, 405, 'Method not allowed.');
}

/* Extract EndPoint */
$parsedURI = parse_url($_SERVER["REQUEST_URI"]);
$path = explode('/', str_replace($BASE_URI, "", $parsedURI["path"]));
$endPoint = $path[0];
$requestData['dataId'] = isset($path[1]) ? $path[1] : null;
if (empty($endPoint)) {
    $endPoint = "/";
}

/* Extract Token */
if (isset($_SERVER["HTTP_X_API_KEY"])) {
    $requestData["user"] = $_SERVER["HTTP_X_API_USER"];
}
if (isset($_SERVER["HTTP_X_API_KEY"])) {
    $requestData["token"] = $_SERVER["HTTP_X_API_KEY"];
}

/* EndPoint Handlers */
$endpoints["/"] = function (string $requestMethod, array $requestData): void {
    sendResponse('Welcome to Kahuna API!');
};

$endpoints["404"] = function (string $requestMethod, array $requestData): void {
    sendResponse(null, 404, "Endpoint " . $requestData["endPoint"] . " not found.");
};

$endpoints["product"] = function (string $requestMethod, array $requestData): void {
    // The $requestMethod variable contains the HTTP verb used in this request 
    if ($requestMethod === 'GET') {
        $products = Product::load();
        sendResponse($products);
    } elseif ($requestMethod === 'POST') {
      // The $requestData variable contains any data sent with the request (such as the form body)
      $serial = $requestData['serial'];
      $name = $requestData['name'];
      $warrantyLength = $requestData['warrantyLength'];
      $product = new Product($serial, $name, $warrantyLength);
      $product = Product::save($product); // Save product to DB and get the updated product (with an ID)
      sendResponse($product, 201);
    } else {
        sendResponse(null, 405, 'Method not allowed');
    }
};

$endpoints["user"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'POST') {
        // Register a new user 
        $email = $requestData['email'];
        $password = $requestData['password'];
        $createdAt = $requestData['createdAt'];
        $role = $requestData['role'];
        $user = new User($email, $password, $createdAt, $role);
        $user = User::save($user);
        sendResponse($user, 201); // Ok Resource created
    } elseif ($requestMethod === 'PATCH') {
        // edit an existing user
        sendResponse(null, 500, 'Updating a user has not yet been implemented.');
    } elseif ($requestMethod === 'DELETE') {
        // Delete a user
        sendResponse(null, 501, 'User deleted Successfully.');
    } else {
        // Client made a mistake
        sendResponse(null, 405, 'Method not allowed');
    }
};

$endpoints["login"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod == 'POST') {
        $email = $requestData['email'];
        $password = $requestData['password'];
        $createdAt = $requestData['createdAt'] ?? date('Y-m-d');
        $role = $requestData['role'] ?? 'user'; // Or some default role
        $user = new User($email, $password, $createdAt, $role);
        $user = User::authenticate($user);
        if ($user) {
            // Generate a token for this user 
            $token = new AccessToken($user->getId());
            $token = AccessToken::save($token);
            sendResponse(['user' => $user->getId(), 
            'token' => $token->getToken()
            ]);
        } else {
            sendResponse(null, 401, "Login Failed.");
        }
    } else {
        sendResponse(null, 405, "Method not allowed.");
    }
};

$endpoints["logout"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'POST') {
        if (checkToken($requestData)) {
            $userId = $requestData['user'];
            $token = new AccessToken($userId);
            $token = AccessToken::delete($token);
            sendResponse('You have been logged out.');
        } else {
            sendResponse(null, 403, "Missing, invalid or expired token.");
        }
    } else {
        sendResponse(null, 405, "Method not allowed.");
    }
};

$endpoints["token"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'GET') {
        if (checkToken($requestData)) {
            sendResponse(['valid' => true, 'token' => $requestData['token']]);
        } else {
            sendResponse(['valid' => false, 'token' => $requestData['token']]);
        }
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};

// Register new product

$endpoints["register"] = function (string $requestMethod, array $requestData): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    $userId = $_POST['userId'] ?? null;
    $serial = $_POST['serial'] ?? null;
    $purchaseDate = $_POST['purchaseDate'] ?? null;
    $retailer = $_POST['retailer'] ?? '';

    if (!$userId || !$serial || !$purchaseDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        $reg = new Register((int)$userId, $serial, $purchaseDate, $retailer);
        $saved = Register::register($reg);
        echo json_encode($saved);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
};

$endpoints["products"] = function (string $requestMethod, array $requestData) {
    if (!checkToken($requestData)) {
        sendResponse(null, 403, 'Missing, invalid or expired token.');
        return;
    }
    $userId = $requestData['user'];

    // Extract extra path segment if exists
    $parsedURI = parse_url($_SERVER["REQUEST_URI"]);
    $path = explode('/', str_replace($GLOBALS['BASE_URI'], "", $parsedURI["path"]));
    // $path[0] is 'products', $path[1] may be serial number or null
    $serialNumber = $path[1] ?? null;

    if ($requestMethod === 'GET') {
        if (!$serialNumber) {
            // GET /products - list all registered products for this user
            $products = Register::getByUser($userId);
            sendResponse($products);
        } else {
            // GET /products/{serial_number} - show product details + warranty
            $product = Register::getProductByUserAndSerial($userId, $serialNumber);
            if (!$product) {
                sendResponse(null, 404, 'Product not found');
                return;
            }
            $purchaseDate = new DateTime($product['purchase_date']);
            $warrantyPeriod = $product['warranty_period_months'];
            $expiryDate = clone $purchaseDate;
            $expiryDate->modify("+{$warrantyPeriod} months");
            $now = new DateTime();

            $warrantyLeft = max(0, $now->diff($expiryDate)->invert === 1 ? 0 : $now->diff($expiryDate)->m + ($now->diff($expiryDate)->y * 12));

            sendResponse([
                'serial_number' => $product['serial_number'],
                'product_name' => $product['product_name'],
                'purchase_date' => $product['purchase_date'],
                'warranty_left_months' => $warrantyLeft
            ]);
        }
    } else {
        sendResponse(null, 405, 'Method not allowed');
    }
};

$endpoints["products"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod !== 'GET') {
        sendResponse(null, 405, "Method not allowed.");
        return;
    }

    if (!checkToken($requestData)) {
        sendResponse(null, 403, "Missing, invalid or expired token.");
        return;
    }

    $userId = (int)$requestData['user'];
    $serial = $requestData['dataId'] ?? null;

    try {
        if ($serial) {
            // Return one product's details
            $allProducts = Register::getByUser($userId);
            foreach ($allProducts as $product) {
                if ($product['serial'] === $serial) {
                    sendResponse($product);
                    return;
                }
            }
            sendResponse(null, 404, "Product with serial $serial not found for this user.");
        } else {
            // Return all registered products for this user
            $products = Register::getByUser($userId);
            sendResponse($products);
        }
    } catch (Exception $e) {
        sendResponse(null, 500, $e->getMessage());
    }
};




   









// Create an account 
// Login 
// Logout
// Register a product they own 
// Get a list of products they have registered (showing remaining warranty on each)

// 1. Create a new table in db.sql 
// 2. Create a new model in the model folder 
// 3. Add endpoints as above 



function cors()
{
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH, DELETE");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}

try {
    if (isset($endpoints[$endPoint])) {
        $endpoints[$endPoint]($requestMethod, $requestData);
    } else {
        $endpoints["404"]($requestMethod, array("endPoint" => $endPoint));
    }
} catch (Exception $e) {
    sendResponse(null, 500, $e->getMessage());
} catch (Error $e) {
    sendResponse(null, 500, $e->getMessage());
}