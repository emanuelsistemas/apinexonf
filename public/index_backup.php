<?php
require_once '../vendor/autoload.php';

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Responder OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregar configurações
if (file_exists('../.env')) {
    $dotenv = parse_ini_file('../.env');
    foreach ($dotenv as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Router simples
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Middleware de autenticação (opcional)
function verificarToken() {
    if (isset($_ENV['API_TOKEN']) && !empty($_ENV['API_TOKEN'])) {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        
        if ($token !== 'Bearer ' . $_ENV['API_TOKEN']) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido']);
            exit();
        }
    }
}

// Rotas da API
switch ($uri) {
    case '/api/gerar-nfe':
        if ($method === 'POST') {
            verificarToken();
            require_once '../src/Controllers/GerarNFeController.php';
            $controller = new \NexoNFe\Controllers\GerarNFeController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/enviar-sefaz':
        if ($method === 'POST') {
            verificarToken();
            require_once '../src/Controllers/EnviarSefazController.php';
            $controller = new \NexoNFe\Controllers\EnviarSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/consultar-nfe':
        if ($method === 'GET') {
            verificarToken();
            require_once '../src/Controllers/ConsultarNFeController.php';
            $controller = new \NexoNFe\Controllers\ConsultarNFeController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/status':
        echo json_encode([
            'status' => 'API NFe Online',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'domain' => 'apinfe.nexopdv.com'
        ]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint não encontrado']);
        break;
}
?>
